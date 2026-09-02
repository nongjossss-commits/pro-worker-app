<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborBill;
use App\Models\LaborBookAccount;
use App\Models\LaborLedgerEntry;
use App\Models\LaborTeam;
use App\Models\LaborTeamMember;
use Illuminate\Http\Request;

class LaborDashboardController extends Controller
{
    /**
     * Role-based dashboard visibility:
     *  - labor-team: sees ONLY their own team's chart + member list — never
     *    redirected away anymore, this IS their dashboard now.
     *  - labor-shareholder: always sees the all-teams overview; if they also
     *    lead their own team (labor_team_id set — optional, see
     *    LaborUserController), they additionally see that team's section,
     *    same as labor-team would.
     *  - labor-accounting / admin / super-admin: all-teams overview (unchanged
     *    level of access, just presented as charts now instead of a table).
     *  - labor-member: sees ONLY their own personal totals (via their
     *    User::laborTeamMember() match) — never a team's data, never
     *    another member's data, and no other screen in the module at all
     *    (see App\Http\Middleware\RestrictLaborMemberAccess).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('labor-member')) {
            $member = $user->laborTeamMember;
            abort_unless($member, 403, 'บัญชีนี้ยังไม่ได้ถูกจับคู่กับข้อมูลลูกทีมใด กรุณาติดต่อ Super Admin');

            return view('labor.dashboard', [
                'mode' => 'own-member-only',
                'ownMember' => array_merge(
                    $this->memberSummary($member->id),
                    ['recentActivity' => $this->memberRecentActivity($member->id)]
                ),
            ]);
        }

        if ($user->hasRole('labor-team')) {
            abort_unless($user->labor_team_id, 403, 'บัญชีนี้ยังไม่ได้ผูกกับทีมงานใด กรุณาติดต่อ Super Admin');

            return view('labor.dashboard', [
                'mode' => 'own-team-only',
                'ownTeam' => array_merge(
                    $this->teamSummary($user->labor_team_id),
                    ['recentActivity' => $this->recentActivity($user->labor_team_id)]
                ),
            ]);
        }

        $summaries = LaborTeam::orderBy('name')->get()->map(fn ($team) => $this->teamSummary($team->id));

        $data = [
            'mode' => 'overview',
            'teams' => $summaries,
            'canManage' => $user->can('manage-labor-ledger'),
            'recentActivity' => $this->recentActivity(null),
            'booksBalance' => $this->totalBooksBalance(),
        ];

        if ($user->hasRole('labor-shareholder') && $user->labor_team_id) {
            $data['mode'] = 'overview-plus-own-team';
            $data['ownTeam'] = array_merge(
                $this->teamSummary($user->labor_team_id),
                ['recentActivity' => $this->recentActivity($user->labor_team_id)]
            );
        }

        return view('labor.dashboard', $data);
    }

    /**
     * Billed/paid/outstanding for one team, from labor_ledger_entries.amount
     * (positive = charge, negative = payment/credit — same convention used
     * throughout the module, see LaborBillPdfService).
     */
    protected function teamSummary(int $teamId): array
    {
        $team = LaborTeam::withSum(
            ['ledgerEntries as billed' => fn ($q) => $q->where('amount', '>', 0)],
            'amount'
        )->withSum(
            ['ledgerEntries as paid_raw' => fn ($q) => $q->where('amount', '<', 0)],
            'amount'
        )->findOrFail($teamId);

        $billed = (float) ($team->billed ?? 0);
        $paid = abs((float) ($team->paid_raw ?? 0));

        return [
            'team' => $team,
            'billed' => $billed,
            'paid' => $paid,
            'outstanding' => $billed - $paid,
            'members' => $team->members()->orderBy('name')->get(),
        ];
    }

    /**
     * Billed/paid/outstanding for one PERSON (labor-member's own dashboard)
     * — same shape/convention as teamSummary() above, just scoped by
     * labor_team_member_id instead of labor_team_id. No 'members' key here
     * (there's nothing to break down further than one person).
     */
    protected function memberSummary(int $memberId): array
    {
        $member = LaborTeamMember::with('team')->withSum(
            ['ledgerEntries as billed' => fn ($q) => $q->where('amount', '>', 0)],
            'amount'
        )->withSum(
            ['ledgerEntries as paid_raw' => fn ($q) => $q->where('amount', '<', 0)],
            'amount'
        )->findOrFail($memberId);

        $billed = (float) ($member->billed ?? 0);
        $paid = abs((float) ($member->paid_raw ?? 0));

        return [
            'member' => $member,
            'billed' => $billed,
            'paid' => $paid,
            'outstanding' => $billed - $paid,
        ];
    }

    /**
     * Same idea as recentActivity() but scoped to one person's own ledger
     * entries only — bills stay out of this feed entirely since a bill is
     * issued at the team level, never attributable to one member. No link
     * is built (unlike recentActivity()) since a labor-member login cannot
     * reach labor.teams.show anyway — see RestrictLaborMemberAccess.
     */
    protected function memberRecentActivity(int $memberId, int $limit = 15)
    {
        return LaborLedgerEntry::where('labor_team_member_id', $memberId)
            ->latest('entry_date')
            ->limit($limit)
            ->get()
            ->map(fn ($e) => [
                'date' => $e->entry_date,
                'type' => $e->amount > 0 ? 'charge' : 'payment',
                'label' => $e->amount > 0 ? __('Charge') : __('Payment'),
                'description' => $e->description,
                'amount' => (float) $e->amount,
            ]);
    }

    /**
     * Merged, read-only feed of recent bills issued + ledger entries
     * (charges/payments), across all teams (team_id = null, for the overview
     * section) or scoped to one team. Lets labor-shareholder (and anyone else
     * who can see this dashboard) check what's been happening without
     * needing access to the Finance menu, which stays edit-only for
     * labor-accounting/admin/super-admin.
     */
    protected function recentActivity(?int $teamId, int $limit = 15)
    {
        $billsQuery = LaborBill::with('team')->where('status', '!=', 'void');
        $entriesQuery = LaborLedgerEntry::with('team');

        if ($teamId) {
            $billsQuery->where('labor_team_id', $teamId);
            $entriesQuery->where('labor_team_id', $teamId);
        }

        $bills = $billsQuery->latest('issued_at')->limit($limit)->get()->map(fn ($b) => [
            'date' => $b->issued_at,
            'team' => $b->team->name ?? '-',
            'type' => 'bill',
            'label' => __('Bill Issued'),
            'description' => $b->bill_no,
            'amount' => (float) $b->period_charges,
            'link' => route('labor.teams.show', $b->labor_team_id),
        ]);

        $entries = $entriesQuery->latest('entry_date')->limit($limit)->get()->map(fn ($e) => [
            'date' => $e->entry_date,
            'team' => $e->team->name ?? '-',
            'type' => $e->amount > 0 ? 'charge' : 'payment',
            'label' => $e->amount > 0 ? __('Charge') : __('Payment'),
            'description' => $e->description,
            'amount' => (float) $e->amount,
            'link' => route('labor.teams.show', $e->labor_team_id),
        ]);

        return $bills->concat($entries)->sortByDesc('date')->take($limit)->values();
    }

    /**
     * Sum of every active book account's live balance — see
     * LaborBookAccount::getCurrentBalanceAttribute().
     */
    protected function totalBooksBalance(): float
    {
        $accounts = LaborBookAccount::withSum(
            ['transactions as income_total' => fn ($q) => $q->where('type', 'income')],
            'amount'
        )->withSum(
            ['transactions as expense_total' => fn ($q) => $q->where('type', 'expense')],
            'amount'
        )->where('is_active', true)->get();

        return $accounts->sum(fn ($a) => (float) $a->opening_balance + (float) ($a->income_total ?? 0) - (float) ($a->expense_total ?? 0));
    }
}
