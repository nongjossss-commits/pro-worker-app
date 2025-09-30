<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Database\Seeders\RoleAndPermissionSeeder;

class RolesPermissionsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_cannot_access_admin_page()
    {
        $response = $this->get(route('admin.roles_permissions.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_users_are_forbidden()
    {
        $user = User::factory()->create();
        $user->assignRole('staff');

        $response = $this->actingAs($user)->get(route('admin.roles_permissions.index'));
        $response->assertStatus(403);
    }

    public function test_admin_user_can_access_admin_page()
    {
        $adminUser = User::factory()->create();
        $adminUser->assignRole('admin');

        $response = $this->actingAs($adminUser)->get(route('admin.roles_permissions.index'));

        $response->assertStatus(200);
        $response->assertSee('Manage Roles and Permissions');
    }
}