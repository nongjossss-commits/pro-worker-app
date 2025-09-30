<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Database\Seeders\RoleAndPermissionSeeder; // เพิ่มบรรทัดนี้

class RolesPermissionsPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Run the seeder that creates roles and permissions
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_cannot_access_admin_page()
    {
        $response = $this->get(route('admin.roles_permissions.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_access_admin_page()
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
        $response->assertSeeText('Manage Roles and Permissions');
        $response->assertSeeText('admin');
        $response->assertSeeText('staff');
    }
}