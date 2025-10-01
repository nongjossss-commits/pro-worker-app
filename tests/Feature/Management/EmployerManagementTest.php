<?php

namespace Tests\Feature\Management;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Employer;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class EmployerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $staffUser;
    protected $regularUser;
    protected $employer;

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

        // Create a sample employer
        $this->employer = Employer::factory()->create();
    }

    // Test for viewing all employers
    public function test_admin_can_view_employers_index()
    {
        $response = $this->actingAs($this->adminUser)->get(route('employers.index'));
        $response->assertStatus(200);
        $response->assertSee($this->employer->employerNameTh);
    }

    public function test_staff_can_view_employers_index()
    {
        $response = $this->actingAs($this->staffUser)->get(route('employers.index'));
        $response->assertStatus(200);
        $response->assertSee($this->employer->employerNameTh);
    }

    public function test_unauthorized_user_cannot_view_employers_index()
    {
        $response = $this->actingAs($this->regularUser)->get(route('employers.index'));
        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_from_employers_index()
    {
        $response = $this->get(route('employers.index'));
        $response->assertRedirect('/login');
    }

    // Tests for creating an employer
    public function test_admin_can_access_create_employer_page()
    {
        $response = $this->actingAs($this->adminUser)->get(route('employers.create'));
        $response->assertStatus(200);
    }

    public function test_unauthorized_users_cannot_access_create_employer_page()
    {
        $this->actingAs($this->staffUser)->get(route('employers.create'))->assertStatus(403);
        $this->actingAs($this->regularUser)->get(route('employers.create'))->assertStatus(403);
    }

    public function test_admin_can_store_a_new_employer()
    {
        $employerData = Employer::factory()->make()->toArray();
        $response = $this->actingAs($this->adminUser)->post(route('employers.store'), $employerData);
        $response->assertRedirect(route('employers.index'));
        $this->assertDatabaseHas('employers', ['employerId' => $employerData['employerId']]);
    }

    public function test_unauthorized_users_cannot_store_a_new_employer()
    {
        $employerData = Employer::factory()->make()->toArray();
        $this->actingAs($this->staffUser)->post(route('employers.store'), $employerData)->assertStatus(403);
        $this->actingAs($this->regularUser)->post(route('employers.store'), $employerData)->assertStatus(403);
    }

    // Tests for editing an employer
    public function test_admin_can_access_edit_employer_page()
    {
        $response = $this->actingAs($this->adminUser)->get(route('employers.edit', $this->employer));
        $response->assertStatus(200);
        $response->assertSee($this->employer->employerNameTh);
    }

    public function test_unauthorized_users_cannot_access_edit_employer_page()
    {
        $this->actingAs($this->staffUser)->get(route('employers.edit', $this->employer))->assertStatus(403);
        $this->actingAs($this->regularUser)->get(route('employers.edit', $this->employer))->assertStatus(403);
    }

    public function test_admin_can_update_employer()
    {
        $updatedData = ['employerNameTh' => 'Updated Name'];
        $response = $this->actingAs($this->adminUser)->put(route('employers.update', $this->employer), $updatedData);
        $response->assertRedirect(route('employers.index'));
        $this->assertDatabaseHas('employers', ['id' => $this->employer->id, 'employerNameTh' => 'Updated Name']);
    }

    public function test_unauthorized_users_cannot_update_employer()
    {
        $updatedData = ['employerNameTh' => 'Updated Name'];
        $this->actingAs($this->staffUser)->put(route('employers.update', $this->employer), $updatedData)->assertStatus(403);
        $this->actingAs($this->regularUser)->put(route('employers.update', $this->employer), $updatedData)->assertStatus(403);
    }

    // Tests for deleting an employer
    public function test_admin_can_delete_employer()
    {
        $response = $this->actingAs($this->adminUser)->delete(route('employers.destroy', $this->employer));
        $response->assertRedirect(route('employers.index'));
        $this->assertDatabaseMissing('employers', ['id' => $this->employer->id]);
    }

    public function test_unauthorized_users_cannot_delete_employer()
    {
        $this->actingAs($this->staffUser)->delete(route('employers.destroy', $this->employer))->assertStatus(403);
        $this->actingAs($this->regularUser)->delete(route('employers.destroy', $this->employer))->assertStatus(403);
    }
}