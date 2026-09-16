<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborChargeType;
use App\Models\LaborLedgerEntry;
use App\Models\LaborTeam;
use App\Models\LaborTeamMember;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Central billing tab — Accounting Staff records a charge here (not inside
 * a specific team's ledger). They pick the team member who actually filed
 * the job, and the entry is attributed to that member's team automatically.
 * Still writes into labor_ledger_entries, so it shows up on the team's own
 * ledger/dashboard totals without any change to those pages.
 */
class LaborChargeEntryController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        // Unfiltered on purpose (not ->where('is_active', true)): a filter list
        // needs to include teams/types that were later deactivated, so entries
        // recorded against them can still be found. It also means this list is
        // always live — adding/removing a charge type updates the filter with
        // no extra wiring needed.
        $chargeTypes = LaborChargeType::orderBy('name')->get();
        $teams = LaborTeam::orderBy('name')->get();

        $search = trim((string) $request->query('search'));

        $entriesQuery = LaborLedgerEntry::whereNotNull('labor_charge_type_id')
            ->with(['team', 'member', 'chargeType', 'creator'])
            ->when($search !== '', fn ($q) => $q->where('request_number', 'like', '%' . $search . '%'))
            ->when($request->query('team_id'), fn ($q, $v) => $q->where('labor_team_id', $v))
            ->when($request->query('member_id'), fn ($q, $v) => $q->where('labor_team_member_id', $v))
            ->when($request->query('charge_type_id'), fn ($q, $v) => $q->where('labor_charge_type_id', $v));

        $entries = $entriesQuery
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        // Every active member, grouped by their (fixed-at-registration) team —
        // rendered as a plain <select> with <optgroup> per team. Small roster,
        // so no need for an AJAX search: everyone's just listed, and the
        // browser's own type-ahead (press a letter, jumps to it) covers search.
        // Also include anyone deactivated since who's still the filer on one of
        // this page's entries, so their edit form doesn't lose that selection.
        $referencedMemberIds = $entries->pluck('labor_team_member_id')->filter()->unique();

        $membersByTeam = LaborTeamMember::where(function ($q) use ($referencedMemberIds) {
                $q->where('is_active', true)->orWhereIn('id', $referencedMemberIds);
            })
            ->with('team')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($m) => $m->team->name ?? __('No Team'));

        // The "Filed By" filter needs every member regardless of team/active
        // status — a historical entry can reference someone since deactivated
        // or moved teams, and the filter must still be able to find it.
        $allMembersByTeam = LaborTeamMember::with('team')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($m) => $m->team->name ?? __('No Team'));

        $filters = $request->only(['search', 'team_id', 'member_id', 'charge_type_id']);

        // Always the all-time grand total across every entry, independent of
        // the filters/search above — this is a standing overview, not a
        // reflection of whatever the user happens to be looking at right now.
        $chargeTypeStats = LaborChargeType::nationalityStats();

        return view('labor.charges.index', compact('chargeTypes', 'entries', 'membersByTeam', 'teams', 'allMembersByTeam', 'filters', 'chargeTypeStats'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        if ($duplicate = $this->findDuplicateRequestNumber($request->input('request_number'))) {
            return $this->duplicateResponse($request, $duplicate);
        }

        $validated = $request->validate([
            'labor_charge_type_id' => ['required', Rule::exists('labor_charge_types', 'id')->where('is_active', true)],
            'labor_team_member_id' => ['required', 'exists:labor_team_members,id'],
            'entry_date' => ['required', 'date'],
            'request_number' => ['required', 'string', 'max:255'],
            'qty_laos' => ['nullable', 'integer', 'min:0'],
            'qty_myanmar' => ['nullable', 'integer', 'min:0'],
            'qty_cambodia' => ['nullable', 'integer', 'min:0'],
            'qty_vietnam' => ['nullable', 'integer', 'min:0'],
        ]);

        [$quantity, $breakdown] = $this->resolveQuantity($request, $validated);
        if ($quantity < 1) {
            return $this->quantityErrorResponse($request);
        }

        $chargeType = LaborChargeType::findOrFail($validated['labor_charge_type_id']);
        $member = LaborTeamMember::findOrFail($validated['labor_team_member_id']);

        $data = [
            'labor_team_id' => $member->labor_team_id,
            'labor_team_member_id' => $member->id,
            'labor_charge_type_id' => $chargeType->id,
            'entry_date' => $validated['entry_date'],
            'description' => "{$chargeType->name} — เลขคำขอ {$validated['request_number']}",
            'amount' => $chargeType->rate * $quantity,
            'request_number' => $validated['request_number'],
            'quantity' => $quantity,
            'unit_rate' => $chargeType->rate,
            'created_by' => $request->user()->id,
        ];
        if ($breakdown !== null) {
            $data += $breakdown;
        }

        LaborLedgerEntry::create($data);

        return $this->successResponse($request, 'บันทึกรายการเรียกเก็บเรียบร้อยแล้ว');
    }

    public function update(Request $request, LaborLedgerEntry $entry)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);
        abort_unless($entry->labor_charge_type_id !== null, 404);

        if ($duplicate = $this->findDuplicateRequestNumber($request->input('request_number'), $entry->id)) {
            return $this->duplicateResponse($request, $duplicate);
        }

        $validated = $request->validate([
            'labor_charge_type_id' => ['required', Rule::exists('labor_charge_types', 'id')->where('is_active', true)],
            'labor_team_member_id' => ['required', 'exists:labor_team_members,id'],
            'entry_date' => ['required', 'date'],
            'request_number' => ['required', 'string', 'max:255'],
            'qty_laos' => ['nullable', 'integer', 'min:0'],
            'qty_myanmar' => ['nullable', 'integer', 'min:0'],
            'qty_cambodia' => ['nullable', 'integer', 'min:0'],
            'qty_vietnam' => ['nullable', 'integer', 'min:0'],
        ]);

        [$quantity, $breakdown] = $this->resolveQuantity($request, $validated, $entry);
        if ($quantity < 1) {
            return $this->quantityErrorResponse($request);
        }

        $chargeType = LaborChargeType::findOrFail($validated['labor_charge_type_id']);
        $member = LaborTeamMember::findOrFail($validated['labor_team_member_id']);

        $data = [
            'labor_team_id' => $member->labor_team_id,
            'labor_team_member_id' => $member->id,
            'labor_charge_type_id' => $chargeType->id,
            'entry_date' => $validated['entry_date'],
            'description' => "{$chargeType->name} — เลขคำขอ {$validated['request_number']}",
            'amount' => $chargeType->rate * $quantity,
            'request_number' => $validated['request_number'],
            'quantity' => $quantity,
            'unit_rate' => $chargeType->rate,
            'updated_by' => $request->user()->id,
        ];
        // Breakdown columns are left untouched when the user didn't fill in any
        // nationality box — required so opening an old entry (recorded before
        // this feature existed) and editing something unrelated (e.g. just the
        // date) never overwrites its still-correct total with a blank/zero
        // breakdown. See resolveQuantity() for the full rule.
        if ($breakdown !== null) {
            $data += $breakdown;
        }

        $entry->update($data);

        return $this->successResponse($request, 'แก้ไขรายการเรียกเก็บเรียบร้อยแล้ว');
    }

    /**
     * Turns the 4 per-nationality inputs into a total quantity + (optionally)
     * the breakdown to persist. If the user left all 4 blank:
     *   - editing an existing entry -> keep its current quantity, and signal
     *     "don't touch the breakdown columns" (null) so old data recorded
     *     before this feature existed is never clobbered.
     *   - creating a new entry -> there's nothing to fall back to, so this
     *     returns a quantity of 0, which the caller rejects.
     *
     * @return array{0: int, 1: ?array<string,int>}
     */
    protected function resolveQuantity(Request $request, array $validated, ?LaborLedgerEntry $existing = null): array
    {
        $natFields = ['qty_laos', 'qty_myanmar', 'qty_cambodia', 'qty_vietnam'];
        $anyProvided = collect($natFields)->contains(fn ($f) => $request->filled($f));

        if ($anyProvided) {
            $breakdown = collect($natFields)->mapWithKeys(fn ($f) => [$f => (int) ($validated[$f] ?? 0)])->all();

            return [array_sum($breakdown), $breakdown];
        }

        if ($existing) {
            return [(int) $existing->quantity, null];
        }

        return [0, null];
    }

    protected function quantityErrorResponse(Request $request)
    {
        $message = 'กรุณาระบุจำนวนอย่างน้อย 1 สัญชาติ';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'errors' => ['qty_laos' => [$message]]], 422);
        }

        return back()->withErrors(['qty_laos' => $message])->withInput();
    }

    /**
     * A plain `unique` validation rule can only say "taken" — it can't tell the
     * caller WHICH record it collides with, and the frontend needs that (team,
     * filed by, charge type, quantity, amount, date) to let the user optionally
     * see who/what it was recorded as before going back to fix the number,
     * without ever closing the form they were filling in.
     */
    protected function findDuplicateRequestNumber(?string $requestNumber, ?int $excludeEntryId = null): ?LaborLedgerEntry
    {
        if (!$requestNumber) {
            return null;
        }

        return LaborLedgerEntry::where('request_number', $requestNumber)
            ->whereNotNull('labor_charge_type_id')
            ->when($excludeEntryId, fn ($q) => $q->where('id', '!=', $excludeEntryId))
            ->with(['team', 'member', 'chargeType'])
            ->first();
    }

    protected function duplicateResponse(Request $request, LaborLedgerEntry $duplicate)
    {
        $existing = [
            'id' => $duplicate->id,
            'team' => $duplicate->team->name ?? '-',
            'filed_by' => $duplicate->member->name ?? '-',
            'charge_type' => $duplicate->chargeType->name ?? '-',
            'quantity' => $duplicate->quantity,
            'amount' => number_format((float) $duplicate->amount, 2),
            'date' => $duplicate->entry_date->format('d/m/Y'),
        ];

        if ($request->wantsJson()) {
            return response()->json(['duplicate' => true, 'existing' => $existing], 422);
        }

        return back()->withErrors(['request_number' => 'เลขคำขอนี้ถูกใช้ไปแล้ว'])->withInput();
    }

    protected function successResponse(Request $request, string $message)
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    public function destroy(Request $request, LaborLedgerEntry $entry)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);
        abort_unless($entry->labor_charge_type_id !== null, 404);

        $entry->delete();

        return back()->with('success', 'ลบรายการเรียบร้อยแล้ว (กู้คืนได้)');
    }

    public function restore(Request $request, int $entryId)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $entry = LaborLedgerEntry::onlyTrashed()
            ->whereNotNull('labor_charge_type_id')
            ->findOrFail($entryId);

        $entry->restore();

        return back()->with('success', 'กู้คืนรายการเรียบร้อยแล้ว');
    }
}
