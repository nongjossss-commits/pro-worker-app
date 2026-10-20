<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\FinancialProfile;
use App\Models\LaborBill;
use App\Models\LaborBillingSetting;
use App\Models\LaborChargeType;
use App\Models\LaborLedgerEntry;
use App\Models\LaborTeam;
use App\Services\LaborBillService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Central Billing statements — periodic snapshots sent to each team so they
 * can review their outstanding balance. Same manage-labor-ledger gate as the
 * rest of Central Billing; the issuer profile picker is intentionally open
 * to both Accounting Staff and Super Admin (not locked to Super Admin like
 * the charge-type catalog).
 */
class LaborBillController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $teamId = $request->input('team_id');
        $paymentStatus = $request->input('payment_status'); // '', 'outstanding', 'paid'
        $period = $request->input('period', 'all'); // today|month|quarter|year|all
        // A specific month (YYYY-MM) or day (YYYY-MM-DD) overrides the quick-filter preset.
        $filterMonth = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month')) ? $request->input('month') : null;
        $filterDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->input('date')) ? $request->input('date') : null;

        [$periodStart, $periodEnd] = $this->resolvePeriodRange($period);
        if ($filterDate) {
            [$periodStart, $periodEnd] = [Carbon::parse($filterDate)->startOfDay(), Carbon::parse($filterDate)->endOfDay()];
        } elseif ($filterMonth) {
            $m = Carbon::createFromFormat('Y-m-d', $filterMonth . '-01');
            [$periodStart, $periodEnd] = [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()];
        }

        $applyFilters = function ($query) use ($teamId, $periodStart, $periodEnd) {
            if ($teamId) {
                $query->where('labor_team_id', $teamId);
            }
            if ($periodStart) {
                $query->whereBetween('issued_at', [$periodStart, $periodEnd]);
            }
            return $query;
        };

        $listQuery = $applyFilters(LaborBill::query()->withSum('payments as paid_sum', 'amount'));

        // whereRaw with an embedded correlated subquery (rather than
        // havingRaw against the withSum alias) — MySQL's strict mode
        // rejects HAVING that mixes a plain column (total_due) with an
        // aggregate alias when the query has no GROUP BY.
        $paidSubquery = 'COALESCE((select sum(amount) from labor_bill_payments
            where labor_bill_payments.labor_bill_id = labor_bills.id
            and labor_bill_payments.deleted_at is null), 0)';

        if ($paymentStatus === 'outstanding') {
            $listQuery->where('status', '!=', 'void')->whereRaw("total_due - {$paidSubquery} > 0");
        } elseif ($paymentStatus === 'paid') {
            $listQuery->where('status', '!=', 'void')->whereRaw("total_due - {$paidSubquery} <= 0");
        }

        $bills = $listQuery->with(['team', 'financialProfile', 'creator'])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        // Summary cards always reflect the full team+period scope (regardless
        // of the payment_status slice being listed), so switching between
        // "Outstanding"/"Fully Paid" doesn't make the totals jump around.
        $summaryBase = $applyFilters(LaborBill::query()->where('status', '!=', 'void'));
        $totalBilled = (float) (clone $summaryBase)->sum('total_due');
        $billCount = (clone $summaryBase)->count();

        $totalPaid = (float) \App\Models\LaborBillPayment::whereHas('bill', function ($q) use ($teamId, $periodStart, $periodEnd) {
            $q->where('status', '!=', 'void');
            if ($teamId) {
                $q->where('labor_team_id', $teamId);
            }
            if ($periodStart) {
                $q->whereBetween('issued_at', [$periodStart, $periodEnd]);
            }
        })->sum('amount');

        $totalOutstanding = $totalBilled - $totalPaid;

        $teams = LaborTeam::where('is_active', true)->orderBy('name')->get();
        $profiles = FinancialProfile::where('type', 'biller')->orderBy('name')->get();
        $currentProfileId = LaborBillingSetting::current()->financial_profile_id;
        $chargeTypes = LaborChargeType::where('is_active', true)->orderBy('name')->get();
        $tracking = $this->teamTracking($teamId);

        // Monthly charge bill picker: active members grouped by team.
        $memberTeams = LaborTeam::where('is_active', true)->orderBy('name')
            ->with(['members' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->get()
            ->filter(fn ($t) => $t->members->isNotEmpty())
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'members' => $t->members->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values(),
            ])->values();

        // The monthly charge window opens on the latest month that has a monthly
        // bill (e.g. already billed ahead for next month), else this month.
        $latestMonthly = LaborBill::where('bill_type', 'monthly')->where('status', '!=', 'void')->max('period_start');
        $monthlyDefaultMonth = $latestMonthly ? Carbon::parse($latestMonthly)->format('Y-m') : now()->format('Y-m');

        $createdBills = LaborBill::with('team')->whereIn('id', (array) session('created_bill_ids', []))->orderBy('id')->get();

        return view('labor.bills.index', compact(
            'bills', 'teams', 'profiles', 'currentProfileId',
            'totalBilled', 'totalPaid', 'totalOutstanding', 'billCount',
            'teamId', 'paymentStatus', 'period', 'filterMonth', 'filterDate',
            'chargeTypes', 'tracking', 'memberTeams', 'createdBills', 'monthlyDefaultMonth',
        ));
    }

    /**
     * Per-team follow-up figures, all-time as of now: charges already billed,
     * paid (payments and other credits — negative ledger entries), billed
     * minus paid, and charges not billed yet. Billed + unbilled - paid equals
     * the team's ledger balance.
     */
    private function teamTracking($teamId)
    {
        $scope = fn ($q) => $teamId ? $q->where('labor_team_id', $teamId) : $q;

        $charges = $scope(LaborLedgerEntry::query()->charges())
            ->selectRaw('labor_team_id, SUM(amount) as total')
            ->groupBy('labor_team_id')->pluck('total', 'labor_team_id');
        $unbilled = $scope(LaborLedgerEntry::query()->charges()->unbilled())
            ->selectRaw('labor_team_id, SUM(amount) as total, COUNT(*) as cnt')
            ->groupBy('labor_team_id')->get()->keyBy('labor_team_id');
        $credits = $scope(LaborLedgerEntry::query()->where(fn ($q) => $q->where('amount', '<', 0)->orWhereNotNull('labor_bill_payment_id')))
            ->selectRaw('labor_team_id, SUM(amount) as total')
            ->groupBy('labor_team_id')->pluck('total', 'labor_team_id');

        $teamIds = $charges->keys()->merge($credits->keys())->unique();

        return LaborTeam::whereIn('id', $teamIds)->orderBy('name')->get()->map(function ($team) use ($charges, $unbilled, $credits) {
            $unbilledAmount = (float) ($unbilled[$team->id]->total ?? 0);
            $billed = (float) ($charges[$team->id] ?? 0) - $unbilledAmount;
            $paid = -(float) ($credits[$team->id] ?? 0);

            return (object) [
                'team' => $team,
                'billed' => $billed,
                'paid' => $paid,
                'remaining' => $billed - $paid,
                'unbilled' => $unbilledAmount,
                'unbilled_count' => (int) ($unbilled[$team->id]->cnt ?? 0),
            ];
        });
    }

    /**
     * Charge entries for the itemized bill picker (JSON), filtered by the
     * date they were recorded: optional team, charge types, billing status
     * (unbilled / billed / all) and a month, a day or a from–to range.
     * Billed rows carry the bill they're on, for display only.
     */
    public function unbilled(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'team_id' => ['nullable', 'integer'],
            'charge_type_ids' => ['nullable', 'array'],
            'charge_type_ids.*' => ['integer'],
            'status' => ['nullable', 'in:unbilled,billed,all'],
            'mode' => ['nullable', 'in:all,month,day,range'],
            'month' => ['nullable', 'date_format:Y-m'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $status = $validated['status'] ?? 'unbilled';
        $query = LaborLedgerEntry::query()->charges()
            ->with(['team:id,name', 'member:id,name', 'chargeType:id,name', 'bill:id,bill_no,status']);
        if ($status === 'unbilled') {
            $query->unbilled();
        } elseif ($status === 'billed') {
            $query->billed();
        }

        if (!empty($validated['team_id'])) {
            $query->where('labor_team_id', $validated['team_id']);
        }
        if (!empty($validated['charge_type_ids'])) {
            $query->whereIn('labor_charge_type_id', $validated['charge_type_ids']);
        }
        $mode = $validated['mode'] ?? 'all';
        if ($mode === 'month' && !empty($validated['month'])) {
            $m = Carbon::createFromFormat('Y-m-d', $validated['month'] . '-01');
            $query->whereRaw('DATE(entry_date) BETWEEN ? AND ?', [$m->copy()->startOfMonth()->toDateString(), $m->copy()->endOfMonth()->toDateString()]);
        } elseif ($mode === 'day' && !empty($validated['date'])) {
            $query->whereRaw('DATE(entry_date) = ?', [$validated['date']]);
        } elseif ($mode === 'range') {
            if (!empty($validated['from'])) {
                $query->whereRaw('DATE(entry_date) >= ?', [$validated['from']]);
            }
            if (!empty($validated['to'])) {
                $query->whereRaw('DATE(entry_date) <= ?', [$validated['to']]);
            }
        }

        $limit = 1000;
        $entries = $query->orderBy('labor_team_id')->orderBy('entry_date')->orderBy('id')->limit($limit + 1)->get();
        $truncated = $entries->count() > $limit;
        $entries = $entries->take($limit);

        // Entries covered by a statement bill aren't tagged — find that bill by date.
        $statements = LaborBill::query()->where('bill_type', 'statement')->where('status', '!=', 'void')
            ->whereIn('labor_team_id', $entries->whereNull('labor_bill_id')->pluck('labor_team_id')->unique())
            ->orderBy('issued_at')->get(['id', 'labor_team_id', 'bill_no', 'period_start', 'period_end'])
            ->groupBy('labor_team_id');

        return response()->json([
            'truncated' => $truncated,
            'entries' => $entries->map(function ($e) use ($statements) {
                $bill = $e->labor_bill_id ? $e->bill : ($statements[$e->labor_team_id] ?? collect())->first(
                    fn ($b) => $e->entry_date->toDateString() >= $b->period_start->toDateString()
                        && $e->entry_date->toDateString() <= $b->period_end->toDateString()
                );

                return [
                    'id' => $e->id,
                    'team_id' => $e->labor_team_id,
                    'team' => $e->team->name ?? '-',
                    'date' => $e->entry_date->format('d/m/Y'),
                    'description' => $e->description,
                    'member' => $e->member->name ?? null,
                    'charge_type' => $e->chargeType->name ?? null,
                    'amount' => (float) $e->amount,
                    'bill_no' => $bill?->bill_no,
                    'bill_url' => $bill ? route('labor.bills.show', $bill->id) : null,
                ];
            })->values(),
        ]);
    }

    /** Itemized bill(s) from the ticked entries — one bill per team. */
    public function storeItemized(Request $request, LaborBillService $service)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'entry_ids' => ['required', 'array', 'min:1'],
            'entry_ids.*' => ['integer'],
            'charge_type_ids' => ['nullable', 'array'],
            'charge_type_ids.*' => ['integer'],
            'financial_profile_id' => ['nullable', 'exists:financial_profiles,id'],
        ]);

        try {
            $bills = $service->generateItemized(
                array_map('intval', $validated['entry_ids']),
                $validated['financial_profile_id'] ?? null,
                $validated['charge_type_ids'] ?? [],
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['entry_ids' => $e->getMessage()]);
        }

        return back()->with('success', __('Created :count bill(s).', ['count' => $bills->count()]))
            ->with('created_bill_ids', $bills->pluck('id')->all());
    }

    /**
     * Per-member defaults for the monthly charge bill (JSON) — loaded as soon
     * as the window opens (no charge type to pick any more):
     *  - amount / description / source month from the member's latest
     *    (non-void) monthly bill, whatever month or heading — so what was
     *    entered last time stays until someone changes it;
     *  - billed: the member's (non-void) monthly bills in the chosen month,
     *    with heading, current amount / description and whether the bill is
     *    locked (paid / tax invoice / WHT) — the page flags "already billed"
     *    for the heading being typed and lets it be edited on the same bill;
     *  - last_title / titles: the latest heading and recent headings to pick from.
     */
    public function monthlyDefaults(Request $request, LaborBillService $service)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);
        $monthStart = Carbon::createFromFormat('Y-m-d', $validated['month'] . '-01')->toDateString();

        $entries = LaborLedgerEntry::query()
            ->whereNotNull('labor_team_member_id')
            ->whereHas('bill', fn ($q) => $q->where('bill_type', 'monthly')->where('status', '!=', 'void'))
            ->with('bill:id,period_start,bill_no,title,charge_type_ids,created_at')
            ->orderByDesc('labor_bill_id')->orderByDesc('id')
            ->get(['id', 'labor_team_member_id', 'labor_bill_id', 'amount', 'description', 'entry_date']);

        $defaults = [];
        $locked = [];
        foreach ($entries as $e) {
            $row = $defaults[$e->labor_team_member_id] ?? ['amount' => null, 'description' => null, 'source_month' => null, 'billed' => []];
            if ($row['amount'] === null) {
                $row['amount'] = (float) $e->amount;
                $row['description'] = $e->description;
                $row['source_month'] = $e->bill->period_start->format('Y-m');
            }
            if ($e->bill->period_start->toDateString() === $monthStart) {
                // On that month's bill already: its current figures, so they can be edited in place.
                $locked[$e->labor_bill_id] ??= $service->monthlyBillLocked($e->bill);
                $row['billed'][] = [
                    'bill_no' => $e->bill->bill_no, 'title' => $e->bill->heading,
                    'amount' => (float) $e->amount, 'description' => $e->description,
                    'locked' => $locked[$e->labor_bill_id],
                ];
            }
            $defaults[$e->labor_team_member_id] = $row;
        }

        $recentBills = LaborBill::where('bill_type', 'monthly')->where('status', '!=', 'void')
            ->orderByDesc('id')->limit(60)->get(['id', 'title', 'charge_type_ids']);
        $titles = $recentBills->map->heading->filter()->unique()->values();

        return response()->json([
            'defaults' => (object) $defaults,
            'last_title' => $titles->first(),
            'titles' => $titles->take(20)->values(),
        ]);
    }

    /** "ใบวางบิลเรียกเก็บค่าใช้จ่ายรายเดือน" — one bill (and PDF) per team. */
    public function storeMonthly(Request $request, LaborBillService $service)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'title' => ['required', 'string', 'max:150'],
            'entry_date' => ['required', 'date'],
            'financial_profile_id' => ['nullable', 'exists:financial_profiles,id'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.member_id' => ['required', 'integer', 'distinct', 'exists:labor_team_members,id'],
            'rows.*.amount' => ['required', 'numeric', 'gt:0'],
            'rows.*.description' => ['required', 'string', 'max:255'],
        ]);

        $month = Carbon::createFromFormat('Y-m-d', $validated['month'] . '-01');

        // A team already billed for this month and heading gets that same bill
        // updated (amounts edited / people added) — see saveMonthly().
        try {
            $result = $service->saveMonthly(
                $month,
                $validated['title'],
                Carbon::parse($validated['entry_date']),
                $validated['rows'],
                $validated['financial_profile_id'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['rows' => $e->getMessage()]);
        }

        $message = collect([
            $result['created']->isNotEmpty() ? __('Created :count bill(s).', ['count' => $result['created']->count()]) : null,
            $result['updated']->isNotEmpty() ? __('Updated :count bill(s): :nos', ['count' => $result['updated']->count(), 'nos' => $result['updated']->pluck('bill_no')->implode(', ')]) : null,
        ])->filter()->implode(' ') ?: __('Nothing changed.');

        return back()->with('success', $message)
            ->with('created_bill_ids', $result['created']->concat($result['updated'])->pluck('id')->all());
    }

    /**
     * Quick-filter presets for the Bills index (Today/This Month/This
     * Quarter/This Year/All Time) — returns [start, end] Carbon instances,
     * or [null, null] for 'all' (no date filtering).
     */
    private function resolvePeriodRange(string $period): array
    {
        return match ($period) {
            'today' => [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'quarter' => [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()],
            'year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            default => [null, null],
        };
    }

    public function store(Request $request, LaborBillService $service)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'labor_team_id' => ['required', 'exists:labor_teams,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'financial_profile_id' => ['nullable', 'exists:financial_profiles,id'],
        ]);

        $team = LaborTeam::findOrFail($validated['labor_team_id']);

        $service->generate(
            $team,
            Carbon::parse($validated['period_start']),
            Carbon::parse($validated['period_end']),
            $validated['financial_profile_id'] ?? null,
        );

        return back()->with('success', 'วางบิลเรียบร้อยแล้ว');
    }

    public function show(Request $request, LaborBill $bill)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $bill->load(['team', 'financialProfile', 'creator', 'payments' => function ($q) {
            $q->orderByDesc('paid_at')->orderByDesc('id');
        }, 'payments.bankAccount', 'payments.whtCertificate', 'payments.bookTransaction.account', 'taxInvoices', 'whtCertificates']);

        $bankAccounts = $bill->financial_profile_id
            ? \App\Models\BankAccount::where('financial_profile_id', $bill->financial_profile_id)->where('is_active', true)->get()
            : collect();

        $bookAccounts = \App\Models\LaborBookAccount::where('is_active', true)->orderBy('name')->get();

        return view('labor.bills.show', compact('bill', 'bankAccounts', 'bookAccounts'));
    }

    /**
     * Stream the bill PDF inline (preview in a new tab) — same pattern as
     * Finance\TaxInvoiceController::pdf(), so the browser's own PDF viewer
     * lets the user save it if they want, rather than forcing an immediate
     * download before they've seen it.
     */
    public function download(Request $request, LaborBill $bill)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        if (!$bill->pdf_path || !Storage::disk('public')->exists($bill->pdf_path)) {
            abort(404, 'ไม่พบไฟล์ PDF ของบิลนี้');
        }

        $binary = Storage::disk('public')->get($bill->pdf_path);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $bill->bill_no . '.pdf"',
        ]);
    }

    public function void(Request $request, LaborBill $bill, LaborBillService $service)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:255'],
        ]);

        $service->void($bill, $validated['void_reason']);

        return back()->with('success', 'ยกเลิกบิลเรียบร้อยแล้ว');
    }

    public function updateSettings(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'financial_profile_id' => ['nullable', 'exists:financial_profiles,id'],
        ]);

        $setting = LaborBillingSetting::current();
        $setting->update($validated);

        return back()->with('success', 'อัปเดตหัวบิลเรียบร้อยแล้ว');
    }
}
