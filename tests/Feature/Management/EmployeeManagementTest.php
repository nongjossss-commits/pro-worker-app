<?php

namespace Tests\Feature\Management;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Employer;
use App\Models\Employee;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $staffUser;
    protected $regularUser;
    protected $employer;
    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $permissions = [
            'view-employers', 'create-employers', 'edit-employers', 'delete-employers',
            'view-employees', 'create-employees', 'edit-employees', 'delete-employees'
        ];
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $staffRole = Role::create(['name' => 'staff']);
        $staffRole->givePermissionTo(['view-employers', 'view-employees', 'edit-employees']);

        // Create users and assign roles
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);

        $this->staffUser = User::factory()->create();
        $this->staffUser->assignRole($staffRole);

        $this->regularUser = User::factory()->create();

        // Create a sample employer and employee
        $this->employer = Employer::factory()->create();
        $this->employee = Employee::factory()->create(['employer_id' => $this->employer->id]);
    }

    // Test for viewing all employees
    public function test_admin_and_staff_can_view_employees_index()
    {
        $this->actingAs($this->adminUser)->get(route('employees.index'))
            ->assertStatus(200)
            ->assertSee($this->employee->employeeNameTh);

        $this->actingAs($this->staffUser)->get(route('employees.index'))
            ->assertStatus(200)
            ->assertSee($this->employee->employeeNameTh);
    }

    public function test_unauthorized_user_cannot_view_employees_index()
    {
        $this->actingAs($this->regularUser)->get(route('employees.index'))->assertStatus(403);
    }

    public function test_guest_is_redirected_from_employees_index()
    {
        $this->get(route('employees.index'))->assertRedirect('/login');
    }

    // Tests for creating an employee
    public function test_admin_can_access_create_employee_page()
    {
        $response = $this->actingAs($this->adminUser)->get(route('employees.create'));
        $response->assertStatus(200);
    }

    public function test_unauthorized_users_cannot_access_create_employee_page()
    {
        $this->actingAs($this->staffUser)->get(route('employees.create'))->assertStatus(403);
        $this->actingAs($this->regularUser)->get(route('employees.create'))->assertStatus(403);
    }

    public function test_admin_can_store_a_new_employee()
    {
        $employeeData = Employee::factory()->make(['employer_id' => $this->employer->id])->toArray();
        $response = $this->actingAs($this->adminUser)->post(route('employees.store'), $employeeData);
        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', ['employeePassport' => $employeeData['employeePassport']]);
    }

    // Tests for editing an employee
    public function test_admin_and_staff_can_access_edit_employee_page()
    {
        $this->actingAs($this->adminUser)->get(route('employees.edit', $this->employee))
            ->assertStatus(200)
            ->assertSee($this->employee->employeeNameTh);

        $this->actingAs($this->staffUser)->get(route('employees.edit', $this->employee))
            ->assertStatus(200)
            ->assertSee($this->employee->employeeNameTh);
    }

    public function test_unauthorized_user_cannot_access_edit_employee_page()
    {
        $this->actingAs($this->regularUser)->get(route('employees.edit', $this->employee))->assertStatus(403);
    }

    public function test_admin_and_staff_can_update_employee()
    {
        $updatedData = ['employer_id' => $this->employer->id, 'employeeNameEn' => 'Updated Name En', 'employeeNameTh' => 'Updated Name'];
        $this->actingAs($this->adminUser)->put(route('employees.update', $this->employee), $updatedData)
             ->assertRedirect(route('employees.index') . '#employee-card-' . $this->employee->id);
        $this->assertDatabaseHas('employees', ['id' => $this->employee->id, 'employeeNameTh' => 'Updated Name']);

        $updatedData2 = ['employer_id' => $this->employer->id, 'employeeNameEn' => 'Updated Name En', 'employeeNameTh' => 'Updated By Staff'];
        $this->actingAs($this->staffUser)->put(route('employees.update', $this->employee), $updatedData2)
             ->assertRedirect(route('employees.index') . '#employee-card-' . $this->employee->id);
        $this->assertDatabaseHas('employees', ['id' => $this->employee->id, 'employeeNameTh' => 'Updated By Staff']);
    }

    // Tests for deleting an employee
    public function test_admin_can_delete_employee()
    {
        $response = $this->actingAs($this->adminUser)->delete(route('employees.destroy', $this->employee));
        $response->assertRedirect();
        $this->assertSoftDeleted('employees', ['id' => $this->employee->id]);
    }

    public function test_unauthorized_users_cannot_delete_employee()
    {
        $this->actingAs($this->staffUser)->delete(route('employees.destroy', $this->employee))->assertStatus(403);
        $this->actingAs($this->regularUser)->delete(route('employees.destroy', $this->employee))->assertStatus(403);
    }
}