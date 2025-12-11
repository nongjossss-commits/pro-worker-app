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

class RegistrationResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $employer;
    protected $steps;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Role & Permission
        $role = Role::firstOrCreate(['name' => 'admin']);
        // If there are specific permissions for this page, assign them.
        // The controller uses just 'auth' middleware for now, or maybe permission middleware?
        // RegistrationController::construct uses $this->middleware('auth');

        // 2. Create Admin User
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);

        // 3. Create Employer
        $this->employer = Employer::factory()->create([
            'employerNameTh' => 'Test Employer TH',
            'employerNameEn' => 'Test Employer EN',
        ]);

        // 4. Create Steps
        $this->steps = collect([
            RegistrationStep::create(['name' => 'Step A', 'order' => 1]),
            RegistrationStep::create(['name' => 'Step B', 'order' => 2]),
            RegistrationStep::create(['name' => 'Step C', 'order' => 3]),
        ]);
    }

    public function test_can_view_index_page()
    {
        $response = $this->actingAs($this->adminUser)
                         ->get(route('production.registration.index'));

        $response->assertStatus(200);
        $response->assertSee('Workflow Progress (Global)');
    }

    public function test_search_functionality()
    {
        // Create matching employee
        Employee::factory()->create([
            'employer_id' => $this->employer->id,
            'employeeNameTh' => 'Somchai Test',
            'status' => 'registration_pending'
        ]);

        // Create non-matching employee
        Employee::factory()->create([
            'employer_id' => $this->employer->id,
            'employeeNameTh' => 'John Doe',
            'status' => 'registration_pending'
        ]);

        // Search for 'Somchai'
        $response = $this->actingAs($this->adminUser)
                         ->get(route('production.registration.index', ['search' => 'Somchai']));

        $response->assertStatus(200);
        $response->assertSee('Somchai Test');
        $response->assertDontSee('John Doe');
    }

    public function test_highest_step_counting_logic()
    {
        $employee = Employee::factory()->create([
            'employer_id' => $this->employer->id,
            'status' => 'registration_pending'
        ]);

        // Attach steps 1 and 3 (skip 2)
        $employee->registrationSteps()->attach([
            $this->steps[0]->id => ['completed_at' => now()], // Order 1
            $this->steps[2]->id => ['completed_at' => now()], // Order 3
        ]);

        $response = $this->actingAs($this->adminUser)
                         ->get(route('production.registration.index'));

        // View should have 'stepStats' passed to it.
        // We can inspect the view data or response content.
        // Let's inspect view data for 'stepStats'.
        $stepStats = $response->viewData('stepStats');

        // Logic: Should only count for the highest step (Step C, ID 3).
        // Step A (ID 1) should be 0.
        // Step B (ID 2) should be 0.
        // Step C (ID 3) should be 1.

        $this->assertEquals(0, $stepStats[$this->steps[0]->id], 'Step A count should be 0');
        $this->assertEquals(0, $stepStats[$this->steps[1]->id], 'Step B count should be 0');
        $this->assertEquals(1, $stepStats[$this->steps[2]->id], 'Step C count should be 1');
    }

    public function test_update_progress_returns_correct_stats()
    {
        $employee = Employee::factory()->create([
            'employer_id' => $this->employer->id,
            'status' => 'registration_pending'
        ]);

        // Initial: No steps.

        // 1. Toggle Step 2 (Order 2)
        $response = $this->actingAs($this->adminUser)
             ->postJson(route('production.registration.progress.update', $employee->id), [
                 'step_id' => $this->steps[1]->id,
                 'completed' => true
             ]);

        $response->assertJson(['success' => true]);
        $json = $response->json();

        // Check returned stats
        // Global Stats: Step 2 should be 1. Step 1 and 3 should be 0.
        $this->assertEquals(1, $json['globalStats'][$this->steps[1]->id]);
        $this->assertEquals(0, $json['globalStats'][$this->steps[0]->id]);

        // 2. Toggle Step 3 (Order 3) -> Highest becomes Step 3
        $response = $this->actingAs($this->adminUser)
             ->postJson(route('production.registration.progress.update', $employee->id), [
                 'step_id' => $this->steps[2]->id,
                 'completed' => true
             ]);

        $json = $response->json();

        // Global Stats: Step 3 should be 1. Step 2 should be 0 (since it's no longer highest).
        $this->assertEquals(1, $json['globalStats'][$this->steps[2]->id]);
        $this->assertEquals(0, $json['globalStats'][$this->steps[1]->id]);
    }
}
