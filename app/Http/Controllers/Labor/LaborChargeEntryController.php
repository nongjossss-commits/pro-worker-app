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

        return view('labor.charges.index', compact('chargeTypes', 'entries'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'labor_charge_type_id' => ['required', Rule::exists('labor_charge_types', 'id')->where('is_active', true)],
            'labor_team_member_id' => ['required', 'exists:labor_team_members,id'],
            'entry_date' => ['required', 'date'],
            'request_number' => ['required', 'string', 'max:255', 'unique:labor_ledger_entries,request_number'],
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

        return back()->with('success', 'บันทึกรายการเรียกเก็บเรียบร้อยแล้ว');
    }

    public function update(Request $request, LaborLedgerEntry $entry)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);
        abort_unless($entry->labor_charge_type_id !== null, 404);

        $validated = $request->validate([
            'labor_charge_type_id' => ['required', Rule::exists('labor_charge_types', 'id')->where('is_active', true)],
            'labor_team_member_id' => ['required', 'exists:labor_team_members,id'],
            'entry_date' => ['required', 'date'],
            'request_number' => ['required', 'string', 'max:255', Rule::unique('labor_ledger_entries', 'request_number')->ignore($entry->id)],
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

        return back()->with('success', 'แก้ไขรายการเรียกเก็บเรียบร้อยแล้ว');
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
