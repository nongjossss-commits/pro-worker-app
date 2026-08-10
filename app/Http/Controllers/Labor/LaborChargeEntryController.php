<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborChargeType;
use App\Models\LaborLedgerEntry;
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

        $chargeTypes = LaborChargeType::orderBy('name')->get();

        $entries = LaborLedgerEntry::whereNotNull('labor_charge_type_id')
            ->with(['team', 'member', 'chargeType', 'creator'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(30);

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

        return view('labor.charges.index', compact('chargeTypes', 'entries', 'membersByTeam'));
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
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $chargeType = LaborChargeType::findOrFail($validated['labor_charge_type_id']);
        $member = LaborTeamMember::findOrFail($validated['labor_team_member_id']);

        LaborLedgerEntry::create([
            'labor_team_id' => $member->labor_team_id,
            'labor_team_member_id' => $member->id,
            'labor_charge_type_id' => $chargeType->id,
            'entry_date' => $validated['entry_date'],
            'description' => "{$chargeType->name} — เลขคำขอ {$validated['request_number']}",
            'amount' => $chargeType->rate * $validated['quantity'],
            'request_number' => $validated['request_number'],
            'quantity' => $validated['quantity'],
            'unit_rate' => $chargeType->rate,
            'created_by' => $request->user()->id,
        ]);

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
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $chargeType = LaborChargeType::findOrFail($validated['labor_charge_type_id']);
        $member = LaborTeamMember::findOrFail($validated['labor_team_member_id']);

        $entry->update([
            'labor_team_id' => $member->labor_team_id,
            'labor_team_member_id' => $member->id,
            'labor_charge_type_id' => $chargeType->id,
            'entry_date' => $validated['entry_date'],
            'description' => "{$chargeType->name} — เลขคำขอ {$validated['request_number']}",
            'amount' => $chargeType->rate * $validated['quantity'],
            'request_number' => $validated['request_number'],
            'quantity' => $validated['quantity'],
            'unit_rate' => $chargeType->rate,
            'updated_by' => $request->user()->id,
        ]);

        return $this->successResponse($request, 'แก้ไขรายการเรียกเก็บเรียบร้อยแล้ว');
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
