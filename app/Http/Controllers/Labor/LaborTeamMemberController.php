<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborTeam;
use App\Models\LaborTeamMember;
use Illuminate\Http\Request;

/**
 * Central registry for "ลูกทีม" (team members) — an ID is registered here
 * with its team chosen at the same moment, on purpose: there is no route
 * into creating one from inside a team's own page, and no way to move an
 * existing ID to a different team afterwards. Both are deliberate — the
 * team pairing happens exactly once, at registration, to keep attribution
 * unambiguous for Central Billing's member search.
 */
class LaborTeamMemberController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $members = LaborTeamMember::with('team')
            ->withCount('ledgerEntries')
            ->orderByDesc('id')
            ->paginate(30);

        $teams = LaborTeam::where('is_active', true)->orderBy('name')->get();

        return view('labor.team-members.index', compact('members', 'teams'));
    }

    /**
     * Cross-team lookup for the Central Billing "who filed this" Select2 field.
     */
    public function search(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $term = (string) $request->query('q', '');

        $members = LaborTeamMember::query()
            ->with('team')
            ->where('is_active', true)
            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (LaborTeamMember $m) => [
                'id' => $m->id,
                'text' => $m->name . ($m->team ? " ({$m->team->name})" : ''),
            ]);

        return response()->json(['results' => $members]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'labor_team_id' => ['required', 'exists:labor_teams,id'],
        ]);

        LaborTeamMember::create($validated);

        return back()->with('success', 'ลงทะเบียนลูกทีมเรียบร้อยแล้ว');
    }

    public function update(Request $request, LaborTeamMember $member)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        // Name and active status only — the team pairing is fixed at registration.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $member->update($validated);

        return back()->with('success', 'แก้ไขลูกทีมเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, LaborTeamMember $member)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        // Ledger entries already recorded under this member keep their history —
        // labor_team_member_id just goes null (see migration's nullOnDelete).
        $member->delete();

        return back()->with('success', 'ลบลูกทีมเรียบร้อยแล้ว');
    }
}
