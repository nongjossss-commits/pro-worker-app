<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

class RolesPermissionsPageTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the database with roles and permissions
        $this->seed(RoleAndPermissionSeeder::class);

        // Create users and assign roles
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->staffUser = User::factory()->create();
        $this->staffUser->assignRole('staff');
    }

    public function test_guests_cannot_access_admin_page()
    {
        $response = $this->get(route('admin.roles_permissions.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_users_are_forbidden()
    {
        $response = $this->actingAs($this->staffUser)->get(route('admin.roles_permissions.index'));
        $response->assertStatus(403);
    }

    public function test_admin_user_can_access_admin_page()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.roles_permissions.index'));

        $response->assertStatus(200);
        $response->assertSeeText('Manage Roles and Permissions');
        $response->assertSee('admin');
        $response->assertSee('staff');
    }
}