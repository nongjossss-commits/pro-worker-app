<?php

namespace Tests\Feature;

use App\Models\LaborTeam;
use App\Models\ProWorkerContract;
use App\Models\ProWorkerContractTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * "เบิกสัญญา Pro Worker" list: a team lead (labor-team position) sees every
 * contract issued within their team — not only the ones they issued
 * themselves — and nothing from other teams. Team members still see only
 * their own. Editing stays issuer-only.
 */
class LaborContractTeamLeadVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function user(string $role, ?LaborTeam $team, ?string $position = null): User
    {
        Role::findOrCreate($role);
        $user = User::factory()->create();
        $user->forceFill([
            'labor_team_id' => $team?->id,
            'labor_position' => $position,
            // An admin account needs Labour access granted to enter the module at all.
            'labor_access_level' => $role === 'admin' ? 'edit' : 'none',
        ])->save();
        $user->assignRole($role);
        return $user->fresh();
    }

    private function contract(User $issuer, LaborTeam $team): ProWorkerContract
    {
        $template = ProWorkerContractTemplate::firstOrCreate(['name' => 'แม่แบบทดสอบ'], ['file_path' => 'x.pdf']);

        return ProWorkerContract::create([
            'contract_no' => 'PW-TEST-' . str_pad((string) ++$this->seq, 4, '0', STR_PAD_LEFT),
            'pro_worker_contract_template_id' => $template->id,
            'issued_by' => $issuer->id,
            'labor_team_id' => $team->id,
            'employer_name_snapshot' => 'นายจ้าง ' . $this->seq,
            'file_path' => 'contracts/none.pdf',
            'issued_at' => now(),
        ]);
    }

    public function test_team_lead_sees_every_contract_of_their_team_only(): void
    {
        $teamA = LaborTeam::create(['name' => 'ทีม A', 'is_active' => true]);
        $teamB = LaborTeam::create(['name' => 'ทีม B', 'is_active' => true]);
        $lead = $this->user('labor-team', $teamA);
        $memberA = $this->user('labor-member', $teamA);
        $memberB = $this->user('labor-member', $teamB);

        $ownByLead = $this->contract($lead, $teamA);
        $byMemberA = $this->contract($memberA, $teamA);
        $byMemberB = $this->contract($memberB, $teamB);

        $this->actingAs($lead)->get(route('labor.contracts.index'))->assertOk()
            ->assertSee($ownByLead->contract_no)
            ->assertSee($byMemberA->contract_no)
            ->assertDontSee($byMemberB->contract_no);

        // Opening a team member's contract works; another team's doesn't.
        $this->actingAs($lead)->get(route('labor.contracts.show', $byMemberA))->assertOk();
        $this->actingAs($lead)->get(route('labor.contracts.show', $byMemberB))->assertForbidden();
        // Still can't edit someone else's contract.
        $this->actingAs($lead)->get(route('labor.contracts.edit', $byMemberA))->assertForbidden();

        // Team members are unchanged: own issuances only.
        $this->actingAs($memberA)->get(route('labor.contracts.index'))->assertOk()
            ->assertSee($byMemberA->contract_no)
            ->assertDontSee($ownByLead->contract_no);
        $this->actingAs($memberA)->get(route('labor.contracts.show', $ownByLead))->assertForbidden();
    }

    public function test_admin_account_given_the_team_lead_position_sees_the_whole_team(): void
    {
        $teamA = LaborTeam::create(['name' => 'ทีม A', 'is_active' => true]);
        $teamB = LaborTeam::create(['name' => 'ทีม B', 'is_active' => true]);
        $lead = $this->user('admin', $teamA, 'labor-team');
        $byMemberA = $this->contract($this->user('labor-member', $teamA), $teamA);
        $byMemberB = $this->contract($this->user('labor-member', $teamB), $teamB);

        $this->actingAs($lead)->get(route('labor.contracts.index'))->assertOk()
            ->assertSee($byMemberA->contract_no)
            ->assertDontSee($byMemberB->contract_no);
    }
}
