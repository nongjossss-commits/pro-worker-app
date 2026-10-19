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
 * "ประวัติการเบิกสัญญา": the per-team summary cards are filters — click one or
 * several (team_ids[]), click again to remove; the dropdown (team_id) still
 * picks a single team. Works for any team, including ones added later.
 */
class LaborContractTeamCardFilterTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function viewer(): User
    {
        Role::findOrCreate('labor-accounting');
        $user = User::factory()->create();
        $user->assignRole('labor-accounting');
        return $user->fresh();
    }

    private function contract(User $issuer, LaborTeam $team): void
    {
        $template = ProWorkerContractTemplate::firstOrCreate(['name' => 'แม่แบบทดสอบ'], ['file_path' => 'x.pdf']);
        ProWorkerContract::create([
            'contract_no' => 'PW-CARD-' . str_pad((string) ++$this->seq, 4, '0', STR_PAD_LEFT),
            'pro_worker_contract_template_id' => $template->id,
            'issued_by' => $issuer->id,
            'labor_team_id' => $team->id,
            'employer_name_snapshot' => 'นายจ้าง ' . $this->seq,
            'file_path' => 'contracts/none.pdf',
            'issued_at' => now(),
        ]);
    }

    public function test_team_cards_filter_one_or_several_teams_and_toggle_off(): void
    {
        $viewer = $this->viewer();
        $a = LaborTeam::create(['name' => 'ทีม A', 'is_active' => true]);
        $b = LaborTeam::create(['name' => 'ทีม B', 'is_active' => true]);
        $c = LaborTeam::create(['name' => 'ทีมใหม่ C', 'is_active' => true]);
        $this->contract($viewer, $a);  // 0001
        $this->contract($viewer, $b);  // 0002
        $this->contract($viewer, $c);  // 0003

        // no filter: every team's card is a toggle link, all contracts listed
        $html = $this->actingAs($viewer)->get(route('labor.contracts.index'))->assertOk()->getContent();
        $this->assertSame(3, substr_count($html, 'data-team-card="'));
        foreach (['0001', '0002', '0003'] as $n) {
            $this->assertTrue(str_contains($html, 'PW-CARD-' . $n), "lists $n");
        }

        // two teams picked on the cards
        $html = $this->actingAs($viewer)->get(route('labor.contracts.index', ['team_ids' => [$a->id, $c->id]]))->assertOk()->getContent();
        $this->assertTrue(str_contains($html, 'PW-CARD-0001') && str_contains($html, 'PW-CARD-0003'), 'shows picked teams');
        $this->assertFalse(str_contains($html, 'PW-CARD-0002'), 'hides the other team');
        $this->assertSame(2, substr_count($html, 'aria-pressed="true"'));
        $this->assertTrue(str_contains($html, 'value="__multi" selected'), 'dropdown shows "several teams"');
        $this->assertSame(2, substr_count($html, 'data-team-ids>'), 'search form keeps the pick');
        // clicking A again leaves only C
        $toggleA = route('labor.contracts.index', ['team_ids' => [$c->id]]);
        $this->assertTrue(str_contains($html, 'href="' . e($toggleA) . '"'), 'card A toggles itself off');

        // the dropdown's single team wins over the cards' pick
        $html = $this->actingAs($viewer)->get(route('labor.contracts.index', ['team_id' => $b->id, 'team_ids' => [$a->id]]))->assertOk()->getContent();
        $this->assertTrue(str_contains($html, 'PW-CARD-0002'), 'dropdown team listed');
        $this->assertFalse(str_contains($html, 'PW-CARD-0001'), 'card pick replaced');
    }
}
