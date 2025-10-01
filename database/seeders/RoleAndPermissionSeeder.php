<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'view-dashboard',
            'manage-users', 'manage-roles', 'manage-settings',
            'view-employers', 'create-employers', 'edit-employers', 'delete-employers',
            'view-employees', 'create-employees', 'edit-employees', 'delete-employees'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create Roles and assign existing permissions
        $staffRole = Role::create(['name' => 'staff']);
        $staffRole->givePermissionTo([
            'view-employers', //
            'view-employees', //
            'edit-employees'  //
        ]);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all()); // Admin gets all permissions [cite: 198]

        // Create a demo staff user
        $staffUser = User::factory()->create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
        ]);
        $staffUser->assignRole($staffRole);

        // Assign admin role to the existing test user
        $adminUser = User::where('email', 'test@example.com')->first();
        if ($adminUser) {
            $adminUser->assignRole($adminRole);
        } else {
             // Or create a new admin user if not exists
            $adminUser = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'test@example.com',
            ]);
            $adminUser->assignRole($adminRole);
        }
    }
}