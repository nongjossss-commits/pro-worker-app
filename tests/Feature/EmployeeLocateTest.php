<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class EmployeeLocateTest extends TestCase
{
    use RefreshDatabase;

    public function test_locate_redirects_to_correct_page_on_employer_edit()
    {
        // 1. Create User (Admin) to access routes
        Role::create(['name' => 'admin']);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        // 2. Create Employer
        $employer = Employer::factory()->create();

        // 3. Create 15 Employees.
        // We want strict ordering.
        // Page size is 10.
        // We want target to be on Page 2 (items 11-20).
        // Sorting is created_at DESC, id DESC.

        // Let's create them in loop with distinct created_at
        $employees = [];
        for ($i = 0; $i < 15; $i++) {
            $employees[] = Employee::factory()->create([
                'employer_id' => $employer->id,
                'created_at' => now()->subMinutes($i), // Newest first (index 0 is newest)
            ]);
        }

        // Index 0 is newest (Row 1, Page 1)
        // Index 9 is 10th newest (Row 10, Page 1)
        // Index 10 is 11th newest (Row 11, Page 2)

        $targetEmployee = $employees[10];

        // 4. Call locate
        $response = $this->get(route('employees.locate', $targetEmployee->id));

        // 5. Assert Redirect
        // Expected URL: employers/{id}/edit?page=2
        $expectedUrl = route('employers.edit', [
            'employer' => $employer->id,
            'page' => 2
        ]);

        $response->assertRedirect($expectedUrl);

        // 6. Assert Session
        $response->assertSessionHas('highlight_employee', $targetEmployee->id);
    }

    public function test_locate_redirects_to_page_1_for_newest_employee()
    {
        Role::firstOrCreate(['name' => 'admin']);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $employer = Employer::factory()->create();

        // Create 5 employees
        $employees = [];
        for ($i = 0; $i < 5; $i++) {
            $employees[] = Employee::factory()->create([
                'employer_id' => $employer->id,
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $targetEmployee = $employees[0]; // Newest

        $response = $this->get(route('employees.locate', $targetEmployee->id));

        $expectedUrl = route('employers.edit', [
            'employer' => $employer->id,
            'page' => 1
        ]);

        $response->assertRedirect($expectedUrl);
        $response->assertSessionHas('highlight_employee', $targetEmployee->id);
    }
}
