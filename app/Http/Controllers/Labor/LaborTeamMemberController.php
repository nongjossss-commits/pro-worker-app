<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborTeam;
use App\Models\LaborTeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        $members = LaborTeamMember::with(['team', 'user'])
            ->withCount('ledgerEntries')
            ->orderByDesc('id')
            ->paginate(30);

        $teams = LaborTeam::where('is_active', true)->orderBy('name')->get();

        // Every login that has Pro Walker Labour module access at all —
        // matching isn't restricted to the same team as the member (a
        // shareholder with no team of their own can still personally be a
        // team's member), so this list is intentionally unfiltered by team.
        $laborUsers = User::role(['labor-accounting', 'labor-shareholder', 'labor-team', 'labor-member'])
            ->orWhere(fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', 'admin'))->where('labor_access_level', '!=', 'none'))
            ->with('laborTeamMember')
            ->orderBy('name')
            ->get();

        return view('labor.team-members.index', compact('members', 'teams', 'laborUsers'));
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

        // Name, active status, and the optional login match — the team
        // pairing itself is fixed at registration (see class docblock).
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'user_id' => [
                'nullable',
                Rule::exists('users', 'id'),
                Rule::unique('labor_team_members', 'user_id')->ignore($member->id),
                function ($attribute, $value, $fail) {
                    $hasLaborAccess = User::whereKey($value)
                        ->where(function ($q) {
                            $q->role(['labor-accounting', 'labor-shareholder', 'labor-team', 'labor-member'])
                              ->orWhere(fn ($q2) => $q2->whereHas('roles', fn ($r) => $r->where('name', 'admin'))->where('labor_access_level', '!=', 'none'));
                        })
                        ->exists();
                    if (!$hasLaborAccess) {
                        $fail('รหัสผู้ใช้นี้ยังไม่มีสิทธิ์เข้าถึง Pro Walker Labour จับคู่ไม่ได้');
                    }
                },
            ],
        ], [
            'user_id.unique' => 'รหัสผู้ใช้นี้ถูกจับคู่กับลูกทีมคนอื่นไปแล้ว เลือกรหัสอื่น หรือยกเลิกการจับคู่เดิมก่อน',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        // Empty-string "-- ไม่จับคู่ --" selection must clear the link, not
        // save a blank string into a nullable FK column.
        $validated['user_id'] = $validated['user_id'] ?: null;

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
