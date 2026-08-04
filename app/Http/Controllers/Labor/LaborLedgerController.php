<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborLedgerEntry;
use App\Models\LaborTeam;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LaborLedgerController extends Controller
{
    public function store(Request $request, LaborTeam $team)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric'],
            'labor_team_member_id' => ['nullable', Rule::exists('labor_team_members', 'id')->where('labor_team_id', $team->id)],
        ]);

        $team->ledgerEntries()->create($validated + [
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'บันทึกรายการเรียบร้อยแล้ว');
    }

    public function update(Request $request, LaborTeam $team, LaborLedgerEntry $entry)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);
        abort_unless($entry->labor_team_id === $team->id, 404);

        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric'],
            'labor_team_member_id' => ['nullable', Rule::exists('labor_team_members', 'id')->where('labor_team_id', $team->id)],
        ]);

        $entry->update($validated + [
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'แก้ไขรายการเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, LaborTeam $team, LaborLedgerEntry $entry)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);
        abort_unless($entry->labor_team_id === $team->id, 404);

        // Who deleted it is captured on the audit log entry itself (user_id),
        // not on updated_by — no need for an extra "updated" event first.
        $entry->delete();

        return back()->with('success', 'ลบรายการเรียบร้อยแล้ว (กู้คืนได้)');
    }

    public function restore(Request $request, LaborTeam $team, int $entryId)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $entry = LaborLedgerEntry::onlyTrashed()
            ->where('labor_team_id', $team->id)
            ->findOrFail($entryId);

        $entry->restore();

        return back()->with('success', 'กู้คืนรายการเรียบร้อยแล้ว');
    }
}
