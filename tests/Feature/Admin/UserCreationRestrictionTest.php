<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserCreationRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles
        Role::create(['name' => 'super-admin']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'staff']);

        // Assign permissions to admin so they can access users page
        $adminRole = Role::findByName('admin');
        // Admin needs permissions to view and manage users. Assuming these exist from seeder logic.
        Permission::create(['name' => 'manage-users']);
        Permission::create(['name' => 'manage-roles']);
        $adminRole->givePermissionTo(['manage-users', 'manage-roles']);
    }

    /** @test */
    public function admin_cannot_create_super_admin_user()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Super Admin',
            'email' => 'newsuper@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_name' => 'super-admin',
        ]);

        // Assert failure (currently might fail asserting 403 because it's not implemented, so I expect 302/200 if it passes validation)
        // If the restriction IS implemented, it should return 403 or redirect back with error.
        // Since I haven't implemented it yet, this test will FAIL if I assert 403.
        // I will assert that the user was NOT created with super-admin role.

        // However, standard Laravel authorization failure is 403.
        // Or validation error 422.
        // I'll check if the user exists.

        $this->assertDatabaseMissing('users', [
            'email' => 'newsuper@test.com',
        ]);

        // If the user was created despite the restriction, this test fails, confirming the bug/missing feature.
    }

    /** @test */
    public function super_admin_can_create_super_admin_user()
    {
        $superAdmin = User::create([
            'name' => 'Super Admin User',
            'email' => 'super@test.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $superAdmin->assignRole('super-admin');

        $response = $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'Another Super Admin',
            'email' => 'another@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_name' => 'super-admin',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'email' => 'another@test.com',
        ]);

        $user = User::where('email', 'another@test.com')->first();
        $this->assertTrue($user->hasRole('super-admin'));
    }

    /** @test */
    public function admin_cannot_update_user_to_super_admin()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $targetUser = User::create([
            'name' => 'Target User',
            'email' => 'target@test.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $targetUser->assignRole('staff');

        $response = $this->actingAs($admin)->put(route('admin.users.update', $targetUser), [
            'name' => 'Target User Updated',
            'email' => 'target@test.com',
            'role_name' => 'super-admin', // Attempt to escalate privilege
        ]);

        // Expect failure
        $targetUser->refresh();
        $this->assertFalse($targetUser->hasRole('super-admin'));
    }

    /** @test */
    public function admin_cannot_update_existing_super_admin()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $superAdminTarget = User::create([
            'name' => 'Super Admin Target',
            'email' => 'targetsuper@test.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $superAdminTarget->assignRole('super-admin');

        $response = $this->actingAs($admin)->put(route('admin.users.update', $superAdminTarget), [
            'name' => 'Hacked Name',
            'email' => 'targetsuper@test.com',
            'role_name' => 'staff', // Attempt to downgrade
        ]);

        // Expect failure
        $superAdminTarget->refresh();
        $this->assertEquals('Super Admin Target', $superAdminTarget->name);
        $this->assertTrue($superAdminTarget->hasRole('super-admin'));
    }
}
