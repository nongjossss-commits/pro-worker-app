<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Providers\RouteServiceProvider;

class RolesPermissionsPageTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Manually register routes for this test to ensure they are discovered
        $this->artisan('config:clear');
        $this->artisan('route:clear');

        // Create roles and permissions
        $adminRole = Role::create(['name' => 'admin']);
        $staffRole = Role::create(['name' => 'staff']);

        // Create a dummy user and assign roles
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

    public function test_admin_user_can_access_admin_page()
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/roles-permissions');

        $response->assertStatus(200);
        $response->assertSeeText('Manage Roles and Permissions');
    }
}