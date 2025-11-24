<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\EmployeeTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupTeamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create admin user
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_manage_independent_loads_only_active_group()
    {
        // Create 2 groups
        $group1 = EmployeeGroup::create(['name' => 'Group 1', 'type' => 'independent']);
        $group2 = EmployeeGroup::create(['name' => 'Group 2', 'type' => 'independent']);

        // Create teams for both
        $team1 = $group1->teams()->create(['name' => 'Team 1']);
        $team2 = $group2->teams()->create(['name' => 'Team 2']);

        // Visit with active_group = group2
        $response = $this->get(route('groups.independent.manage', ['active_group' => $group2->id]));

        $response->assertStatus(200);

        // Check that 'activeGroup' variable in view is group2
        $response->assertViewHas('activeGroup', function($viewGroup) use ($group2) {
            return $viewGroup->id === $group2->id;
        });

        // Check that 'allGroups' are present
        $response->assertViewHas('allGroups', function($all) {
            return $all->count() === 2;
        });

        // Check that we are NOT loading teams for group1 in the 'activeGroup' variable
        // The activeGroup should have teams loaded
        $activeGroup = $response->viewData('activeGroup');
        $this->assertTrue($activeGroup->relationLoaded('teams'));
        $this->assertCount(1, $activeGroup->teams);
        $this->assertEquals('Team 2', $activeGroup->teams->first()->name);
    }

    public function test_create_group_redirects_to_new_group_as_active()
    {
        $response = $this->post(route('groups.store'), [
            'name' => 'New Group',
            'type' => 'independent'
        ]);

        $group = EmployeeGroup::where('name', 'New Group')->first();
        $this->assertNotNull($group);

        $response->assertRedirect(route('groups.independent.manage', ['active_group' => $group->id]));
    }
}
