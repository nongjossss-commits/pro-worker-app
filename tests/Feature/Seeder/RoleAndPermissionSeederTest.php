<?php

namespace Tests\Feature\Seeder;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RoleAndPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_and_permission_seeder_runs_correctly()
    {
        // 1. Create the initial user that the seeder expects to find
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 2. Run the specific seeder
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        // 3. Assert that roles were created
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'staff']);

        // 4. Assert that permissions were created
        $this->assertDatabaseHas('permissions', ['name' => 'manage-users']);
        $this->assertDatabaseHas('permissions', ['name' => 'view-employees']);

        // 5. Assert that the admin role has all permissions
        $adminRole = Role::whereName('admin')->first();
        $allPermissionsCount = Permission::count();
        $this->assertCount($allPermissionsCount, $adminRole->permissions);

        // 6. Assert that the test user was assigned the admin role
        $this->assertTrue($user->hasRole('admin'));
    }
}