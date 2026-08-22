<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\FinancialProfile;
use App\Models\LaborBill;
use App\Models\LaborBillingSetting;
use App\Models\LaborTeam;
use App\Services\LaborBillService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        [$periodStart, $periodEnd] = $this->resolvePeriodRange($period);

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

        return view('labor.bills.index', compact(
            'bills', 'teams', 'profiles', 'currentProfileId',
            'totalBilled', 'totalPaid', 'totalOutstanding', 'billCount',
            'teamId', 'paymentStatus', 'period',
        ));
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
