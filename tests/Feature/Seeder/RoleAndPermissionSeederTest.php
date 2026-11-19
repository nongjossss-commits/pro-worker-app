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

        // 4. The admin role has every permission EXCEPT the opt-in ones that a
        //    Super Admin grants per user (AppServiceProvider's Gate::before):
        //    the two Labor ledger tiers (users.labor_access_level), and
        //    move-attachments / view-attachment-archive (ticked per user).
        //    Holding any of these at role level would bypass that opt-in.
        $optIn = ['manage-labor-ledger', 'view-labor-ledger', 'move-attachments', 'view-attachment-archive'];
        $adminRole = Role::whereName('admin')->first();
        $this->assertEqualsCanonicalizing(
            Permission::whereNotIn('name', $optIn)->pluck('name')->all(),
            $adminRole->permissions->pluck('name')->all()
        );

        // 5. Assert that the test user was assigned the admin role
        $user = User::whereEmail('test@example.com')->first();
        $this->assertTrue($user->hasRole('admin'));
    }
}