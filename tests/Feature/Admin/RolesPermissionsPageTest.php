<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RolesPermissionsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_admin_page()
    {
        $response = $this->get(route('admin.roles_permissions.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_access_admin_page()
    {
        $this->seed(); // Run seeders to create roles

        $user = User::factory()->create();
        $user->assignRole('staff'); // Assign a non-admin role

        $response = $this->actingAs($user)->get(route('admin.roles_permissions.index'));
        $response->assertStatus(403); // Forbidden
    }

    public function test_admin_user_can_access_admin_page()
    {
        $this->seed(); // Run seeders to create roles

        $adminUser = User::whereEmail('test@example.com')->first(); // Our seeder makes this user an admin

        $response = $this->actingAs($adminUser)->get(route('admin.roles_permissions.index'));

        $response->assertStatus(200);
        $response->assertSeeText('Manage Roles and Permissions');
        $response->assertSeeText('admin');
        $response->assertSeeText('staff');
        $response->assertSeeText('manage-users');
    }
}