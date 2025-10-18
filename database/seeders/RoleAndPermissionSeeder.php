<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Seeding Roles and Permissions...');

        // Create Permissions
        $permissions = [
            'view-dashboard',
            'manage-users', 'manage-roles', 'manage-settings',
            'view-employers', 'create-employers', 'edit-employers', 'delete-employers',
            'view-employees', 'create-employees', 'edit-employees', 'delete-employees',
            'terminate-employees',
            'restore-employees',
            'force-delete-employees',

            // START: Add new permissions for other models
            'restore-employers',
            'force-delete-employers',

            'view-agents', 'create-agents', 'edit-agents', 'delete-agents',
            'restore-agents', 'force-delete-agents',

            'view-importers', 'create-importers', 'edit-importers', 'delete-importers',
            'restore-importers', 'force-delete-importers',

            'view-delegates', 'create-delegates', 'edit-delegates', 'delete-delegates',
            'restore-delegates', 'force-delete-delegates',

            'delete-addresses', 'restore-addresses', 'force-delete-addresses'
            // END: Add new permissions
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this.command->info('All base permissions created or verified successfully.');

        // Create Staff Role and assign permissions
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        $staffPermissions = [
            'view-employers', 'create-employers', 'edit-employers',
            'view-employees', 'edit-employees', 'create-employees',
            'terminate-employees',
            'restore-employees'
        ];

        $staffRole->syncPermissions($staffPermissions);
        $this.command->info('Staff role created/verified and permissions synced.');

        // Create Admin Role and assign all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());
        $this.command->info('Admin role created/verified and assigned all permissions.');

        // --- THIS IS THE FIX (User) ---
        // Create a demo staff user *only if* they don't exist
        $staffUser = User::firstOrCreate(
            ['email' => 'staff@example.com'], // Find by email
            [ // Data to use if creating
                'name' => 'Staff User',
                'password' => Hash::make('staff_password_1234'),
            ]
        );
        $staffUser->assignRole($staffRole);
        $this.command->info('Staff User (staff@example.com) created/verified and assigned to staff role.');
        // --- END OF FIX (User) ---

        // Assign admin role to the existing test user
        $adminUser = User::where('email', 'test@example.com')->first();
        if ($adminUser) {
            $adminUser->assignRole($adminRole);
            $this.command->info('Admin role assigned to existing Test User (test@example.com).');
        }

        $this.command->info('Role and Permission Seeding COMPLETED.');
    }
}