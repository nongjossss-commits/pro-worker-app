<?php

namespace Tests\Feature\Seeder;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleAndPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_and_permission_seeder_runs_correctly()
    {
        // 1. Run all the seeders
        $this->seed();

        // 2. Assert that roles were created
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'staff']);

        // 3. Assert that permissions were created
        $this->assertDatabaseHas('permissions', ['name' => 'manage-users']);
        $this->assertDatabaseHas('permissions', ['name' => 'view-employees']);

        // 4. Assert that the admin role has all permissions
        $adminRole = Role::whereName('admin')->first();
        $allPermissionsCount = Permission::count();
        $this->assertCount($allPermissionsCount, $adminRole->permissions);

        // 5. Assert that the test user was assigned the admin role
        $user = User::whereEmail('test@example.com')->first();
        $this->assertTrue($user->hasRole('admin'));
    }
}