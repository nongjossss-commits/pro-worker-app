<?php

namespace Tests\Feature;

use App\Models\LaborLedgerEntry;
use App\Models\LaborTeam;
use App\Models\LaborTeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pro Walker Labour dashboard redesign (2026-10-29): the new look keeps the
 * same per-position visibility — overview for accounting, own team only for
 * a team lead, own totals only for a member.
 */
class LaborDashboardDesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['super-admin', 'labor-accounting', 'labor-team', 'labor-member'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function user(string $role, array $attrs = []): User
    {
        $user = User::factory()->create(array_merge(['status' => 'active'], $attrs));
        $user->assignRole($role);

        return $user;
    }

    private function teamWithLedger(string $name, float $charge, float $payment): array
    {
        $team = LaborTeam::create(['name' => $name, 'is_active' => true]);
        $member = LaborTeamMember::create(['labor_team_id' => $team->id, 'name' => "ลูกทีม $name", 'is_active' => true]);
        LaborLedgerEntry::create(['labor_team_id' => $team->id, 'labor_team_member_id' => $member->id,
            'entry_date' => '2026-09-01', 'description' => "ค่าบริการ $name", 'amount' => $charge, 'quantity' => 1, 'unit_rate' => $charge]);
        LaborLedgerEntry::create(['labor_team_id' => $team->id, 'labor_team_member_id' => $member->id,
            'entry_date' => '2026-09-05', 'description' => "รับชำระ $name", 'amount' => -$payment]);

        return [$team, $member];
    }

    public function test_overview_shows_every_team_in_the_new_layout(): void
    {
        $this->teamWithLedger('ทีมอัลฟา', 1000, 400);
        $this->teamWithLedger('ทีมเบต้า', 3000, 0);

        $html = $this->actingAs($this->user('super-admin'))->get(route('labor.dashboard'))->assertOk()->getContent();

        foreach (['ld-hero', 'ld-kpi', 'id="overviewChart"', 'id="overviewDonut"', 'ld-timeline'] as $part) {
            $this->assertStringContainsString($part, $html);
        }
        $this->assertStringContainsString('ทีมอัลฟา', $html);
        $this->assertStringContainsString('ทีมเบต้า', $html);
        $this->assertStringContainsString('4,000.00', $html); // total billed
        $this->assertStringContainsString('10%', $html);      // 400 / 4000 collected
    }

    public function test_team_lead_sees_only_own_team(): void
    {
        [$teamA] = $this->teamWithLedger('ทีมอัลฟา', 1000, 400);
        $this->teamWithLedger('ทีมเบต้า', 3000, 0);

        $html = $this->actingAs($this->user('labor-team', ['labor_team_id' => $teamA->id]))
            ->get(route('labor.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('class="ld-hero ld-hero-team', $html);
        $this->assertStringContainsString('ทีมอัลฟา', $html);
        $this->assertStringContainsString('600.00', $html);
        $this->assertStringNotContainsString('ทีมเบต้า', $html);
        $this->assertStringNotContainsString('id="overviewChart"', $html);
    }

    public function test_member_sees_only_own_totals(): void
    {
        [$team, $member] = $this->teamWithLedger('ทีมอัลฟา', 1000, 250);
        $this->teamWithLedger('ทีมเบต้า', 3000, 0);
        $memberUser = $this->user('labor-member', ['labor_team_id' => $team->id]);
        $member->update(['user_id' => $memberUser->id]);

        $html = $this->actingAs($memberUser)->get(route('labor.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('750.00', $html);
        $this->assertStringContainsString('ลูกทีม ทีมอัลฟา', $html);
        $this->assertStringNotContainsString('ทีมเบต้า', $html);
        $this->assertStringNotContainsString('class="ld-hero ld-hero-team', $html); // no team section
        $this->assertStringNotContainsString('id="overviewChart"', $html);
    }
}
