<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborTeam;
use Illuminate\Http\Request;

class LaborTeamController extends Controller
{
    /**
     * Team management list — create teams, toggle active/inactive.
     * Manage-only; shareholder/team-role never reach this (no link rendered,
     * and enforced here too in case of a direct URL hit).
     */
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $teams = LaborTeam::withSum('ledgerEntries as total_owed', 'amount')
            ->orderBy('name')
            ->get();

        return view('labor.teams.index', compact('teams'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        LaborTeam::create($validated);

        return back()->with('success', 'สร้างทีมงานเรียบร้อยแล้ว');
    }

    public function update(Request $request, LaborTeam $team)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $team->update($validated);

        return back()->with('success', 'อัปเดตทีมงานเรียบร้อยแล้ว');
    }

    /**
     * Per-team auto-billing cadence — independent of every other team's,
     * consumed by app:generate-labor-bills (see that command + LaborBillService).
     */
    public function updateBillingSchedule(Request $request, LaborTeam $team)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'auto_billing_enabled' => ['nullable', 'boolean'],
            'billing_cadence' => ['required_if:auto_billing_enabled,1', 'nullable', 'in:daily,weekly,monthly'],
            'billing_day_of_week' => ['required_if:billing_cadence,weekly', 'nullable', 'integer', 'between:0,6'],
            'billing_day_of_month' => ['required_if:billing_cadence,monthly', 'nullable', 'integer', 'between:1,31'],
        ]);
        $validated['auto_billing_enabled'] = $request->boolean('auto_billing_enabled');

        $team->update($validated);

        return back()->with('success', 'อัปเดตตารางวางบิลอัตโนมัติเรียบร้อยแล้ว');
    }

    /**
     * Ledger detail for one team. `labor-team` users may only view their own
     * team; everyone else who cleared the module-entry gate may view any team.
     */
    public function show(Request $request, LaborTeam $team)
    {
        $user = $request->user();

        if ($user->hasRole('labor-team') && $user->labor_team_id !== $team->id) {
            abort(403, 'คุณดูได้เฉพาะยอดของทีมตัวเองเท่านั้น');
        }

        $entries = $team->ledgerEntries()
            ->with(['creator', 'updater', 'member'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(30);

        $totalOwed = $team->ledgerEntries()->sum('amount');
        $canManage = $user->can('manage-labor-ledger');

        // Per-member breakdown: how many entries each ลูกทีม has filed, and for how much.
        $members = $team->members()
            ->withCount('ledgerEntries')
            ->withSum('ledgerEntries as total_amount', 'amount')
            ->orderBy('name')
            ->get();

        // Bills are read-only here (even for `labor-team` users viewing their own
        // team) — this is how each team reviews its outstanding balance history.
        $bills = $team->bills()->orderByDesc('issued_at')->orderByDesc('id')->limit(20)->get();

        return view('labor.teams.show', compact('team', 'entries', 'totalOwed', 'canManage', 'members', 'bills'));
    }
}
