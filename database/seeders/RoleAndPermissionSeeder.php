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

        // FIX: Corrected all $this.command typos to $this->command
        $this->command->info('Seeding Roles and Permissions...');

        // Create Permissions
        $permissions = [
            'view-dashboard',
            'manage-users', 'manage-roles', 'manage-settings',
            'view-trash', //
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

        // FIX: Corrected all $this.command typos to $this->command
        $this->command->info('All base permissions created or verified successfully.');

        // Create Staff Role and assign permissions
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        $staffPermissions = [
            'view-employers', 'create-employers', 'edit-employers',
            'view-employees', 'edit-employees', 'create-employees',
            'terminate-employees',
            'restore-employees'
        ];

        $staffRole->syncPermissions($staffPermissions);

        // FIX: Corrected all $this.command typos to $this->command
        $this->command->info('Staff role created/verified and permissions synced.');

        // Create Admin Role and assign all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // FIX: Corrected all $this.command typos to $this->command
        $this->command->info('Admin role created/verified and assigned all permissions.');

        // --- START: Create Employer Role (NEW) ---
        $this->command->info('Creating Employer role...');
        $employerRole = Role::firstOrCreate(['name' => 'employer']);
        // [PATCH] Explicitly define the correct permissions
        $employerPermissions = [
            'view-dashboard', // ID 1 (Source 7)
            'view-employers', // ID 6 (Source 9)
            'view-employees' // ID 10 (Source 10)
        ];
        // The syncPermissions function will handle overwriting the incorrect permissions
        $employerRole->syncPermissions($employerPermissions);
        $this->command->info('Employer role created/verified and permissions synced.');
        // --- END: Create Employer Role (NEW) ---

        // Create a demo staff user *only if* they don't exist
        $staffUser = User::firstOrCreate(
            ['email' => 'staff@example.com'], // Find by email
            [ // Data to use if creating
                'name' => 'Staff User',
                'password' => Hash::make('staff_password_1234'),
            ]
        );
        $staffUser->assignRole($staffRole);

        // FIX: Corrected all $this.command typos to $this->command
        $this->command->info('Staff User (staff@example.com) created/verified and assigned to staff role.');

        // Assign admin role to the existing test user
        $adminUser = User::where('email', 'test@example.com')->first();
        if ($adminUser) {
            $adminUser->assignRole($adminRole);

            // FIX: Corrected all $this.command typos to $this->command
            $this->command->info('Admin role assigned to existing Test User (test@example.com).');
        }

        // FIX: Corrected all $this.command typos to $this->command
        $this->command->info('Role and Permission Seeding COMPLETED.');
    }
}