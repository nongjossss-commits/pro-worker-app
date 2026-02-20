<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CaretakerEmployeeAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist
        Role::firstOrCreate(['name' => 'caretaker']);
        Role::firstOrCreate(['name' => 'employer']);
    }

    /** @test */
    public function caretaker_can_view_employees_of_assigned_employer()
    {
        // Create a caretaker user
        $caretaker = User::factory()->create();
        $caretaker->assignRole('caretaker');

        // Create an employer
        $employer = Employer::factory()->create();

        // Assign the caretaker to the employer via the pivot table
        $employer->caretakers()->attach($caretaker->id);

        // Create an employee belonging to the employer
        $employee = Employee::factory()->create([
            'employer_id' => $employer->id,
        ]);

        // Authenticate as the caretaker
        $this->actingAs($caretaker);

        // Try to fetch the employee
        $retrievedEmployee = Employee::find($employee->id);

        // Assert that the employee is visible
        $this->assertNotNull($retrievedEmployee, 'Caretaker should be able to see the employee of their assigned employer.');
        $this->assertEquals($employee->id, $retrievedEmployee->id);
    }

    /** @test */
    public function caretaker_cannot_view_employees_of_unassigned_employer()
    {
        // Create a caretaker user
        $caretaker = User::factory()->create();
        $caretaker->assignRole('caretaker');

        // Create an employer
        $employer = Employer::factory()->create();

        // Do NOT assign the caretaker to this employer

        // Create an employee belonging to the employer
        $employee = Employee::factory()->create([
            'employer_id' => $employer->id,
        ]);

        // Authenticate as the caretaker
        $this->actingAs($caretaker);

        // Try to fetch the employee
        $retrievedEmployee = Employee::find($employee->id);

        // Assert that the employee is NOT visible
        $this->assertNull($retrievedEmployee, 'Caretaker should NOT be able to see the employee of an unassigned employer.');
    }
}
