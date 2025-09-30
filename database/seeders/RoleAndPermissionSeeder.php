<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'view-dashboard',
            'manage-users',
            'manage-roles',
            'view-employers',
            'create-employers',
            'edit-employers',
            'delete-employers',
            'view-employees',
            'create-employees',
            'edit-employees',
            'delete-employees',
            'manage-settings'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create Roles and assign existing permissions
        $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrator with all permissions']);
        $adminRole->givePermissionTo(Permission::all());

        $staffRole = Role::create(['name' => 'staff', 'description' => 'Staff member with limited permissions']);
        $staffRole->givePermissionTo(['view-employers', 'view-employees', 'edit-employees']);

        // Assign Admin role to the test user
        $user = User::where('email', 'test@example.com')->first();
        if ($user) {
            $user->assignRole($adminRole);
        }
    }
}