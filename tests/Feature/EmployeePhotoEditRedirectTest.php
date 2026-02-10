<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EmployeePhotoEditRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_employee_redirects_to_previous_url_if_provided()
    {
        // 0. Create Permission
        Permission::create(['name' => 'edit-employees']);

        // 1. Create User and Employee
        $user = User::factory()->create();
        $user->givePermissionTo('edit-employees');

        $employer = Employer::factory()->create();
        $employee = Employee::factory()->create([
            'employer_id' => $employer->id,
            // 'employeeNameEn' => 'Test Employee', // If factory handles it, good.
        ]);

        // 2. Define valid update data
        $updateData = [
            // Required fields
            'employer_id' => $employer->id,
            'employeeNameEn' => 'Updated Name',
            // Add _previous parameter to simulate the form hidden input
            '_previous' => route('employees.edit', $employee->id),
        ];

        // 3. Act: Send PUT request
        $response = $this->actingAs($user)
                         ->put(route('employees.update', $employee->id), $updateData);

        // 4. Assert: Check redirection to Edit Page
        $response->assertRedirect(route('employees.edit', $employee->id));

        // Verify data update
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'employeeNameEn' => 'Updated Name',
        ]);
    }
}
