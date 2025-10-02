<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionsPageTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Manually create all necessary roles and permissions for this test file
        $adminRole = Role::create(['name' => 'admin']);
        $staffRole = Role::create(['name' => 'staff']);
        Permission::create(['name' => 'view-dashboard']); // Example permission

        // 2. Create specific users for testing and assign roles directly
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);

        $this->staffUser = User::factory()->create();
        $this->staffUser->assignRole($staffRole);
    }

    public function test_guests_cannot_access_admin_page()
    {
        $response = $this->get('/admin/roles-permissions');
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_users_are_forbidden()
    {
        $response = $this->actingAs($this->staffUser)->get('/admin/roles-permissions');
        $response->assertStatus(403);
    }

    public function test_admin_user_can_access_admin_page_and_it_returns_the_correct_view()
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/roles-permissions');

        // 3. Assert the most important things:
        $response->assertStatus(200); // It was successful
        $response->assertViewIs('admin.roles_permissions.index'); // It returned the correct view file
        $response->assertSee('Manage Roles and Permissions'); // It contains the correct title text
    }
}