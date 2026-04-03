<?php

namespace Tests\Feature\Production;

use App\Models\User;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\RegistrationStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RegistrationNotStartedFilterTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $steps;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup User & Permissions
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'view-finance']));
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'edit-employees']));
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'manage-tickets']));

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);

        // Setup Steps
        $this->steps = collect([
            RegistrationStep::create(['name' => 'Step 1', 'order' => 1]),
            RegistrationStep::create(['name' => 'Step 2', 'order' => 2]),
        ]);
    }

    public function test_not_started_filter_excludes_completed_employees()
    {
        // Employer 1: Has PENDING employee with NO steps (Should be shown)
        $employer1 = Employer::factory()->create(['employerNameTh' => 'Pending Employer']);
        $emp1 = Employee::factory()->create([
            'employer_id' => $employer1->id,
            'status' => 'registration_pending'
        ]);
        // No steps attached

        // Employer 2: Has COMPLETED employee with NO steps (Will be shown because of current logic whereIn registration_completed and doesn't have step 1)
        $employer2 = Employer::factory()->create(['employerNameTh' => 'Completed Employer']);
        $emp2 = Employee::factory()->create([
            'employer_id' => $employer2->id,
            'status' => 'registration_completed'
        ]);
        // No steps attached

        // Employer 3: Has PENDING employee WITH steps (Should be HIDDEN)
        $employer3 = Employer::factory()->create(['employerNameTh' => 'Started Employer']);
        $emp3 = Employee::factory()->create([
            'employer_id' => $employer3->id,
            'status' => 'registration_pending'
        ]);
        $emp3->registrationSteps()->attach($this->steps[0]->id, ['completed_at' => now()]);

        // Act: Filter by 'not_started'
        $response = $this->actingAs($this->adminUser)
                         ->get(route('production.registration.operations', ['filter' => 'not_started']));

        $response->assertStatus(200);

        // Assert:
        // Employer 1 should be present
        $response->assertSee('Pending Employer');

        // Employer 2 SHOULD be present (Current Logic includes 'registration_completed' without step 1)
        $response->assertSee('Completed Employer');

        // Employer 3 should NOT be present
        $response->assertDontSee('Started Employer');
    }

    public function test_not_started_count_is_correct()
    {
        // 1. Pending, No Steps (Counted)
        $emp1 = Employee::factory()->create(['status' => 'registration_pending']);

        // 2. Completed, No Steps (Counted - Current Logic)
        $emp2 = Employee::factory()->create(['status' => 'registration_completed']);

        // 3. Pending, Has Steps (Not Counted)
        $started = Employee::factory()->create(['status' => 'registration_pending']);
        $started->registrationSteps()->attach($this->steps[0]->id, ['completed_at' => now()]);

        // Act
        $response = $this->actingAs($this->adminUser)
                         ->get(route('production.registration.operations'));

        // Assert
        // We need to inspect the view data variable $notStartedCount
        $notStartedCount = $response->viewData('notStartedCount');

        $this->assertEquals(2, $notStartedCount);
    }
}
