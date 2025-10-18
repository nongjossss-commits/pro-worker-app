<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Using a transaction to ensure atomicity
        DB::transaction(function () {
            // Define permissions
            $permissions = [
                // Trash
                'view-trash',

                // Employers
                'view-employers', 'create-employers', 'edit-employers', 'delete-employers', 'restore-employers', 'force-delete-employers',
                // Employees
                'view-employees', 'create-employees', 'edit-employees', 'terminate-employees', 'restore-employees', 'force-delete-employees',
                // Agents
                'view-agents', 'create-agents', 'edit-agents', 'delete-agents', 'restore-agents', 'force-delete-agents',
                // Importers
                'view-importers', 'create-importers', 'edit-importers', 'delete-importers', 'restore-importers', 'force-delete-importers',
                // Delegates
                'view-delegates', 'create-delegates', 'edit-delegates', 'delete-delegates', 'restore-delegates', 'force-delete-delegates',
                // Addresses
                'view-addresses', 'create-addresses', 'edit-addresses', 'delete-addresses', 'restore-addresses', 'force-delete-addresses',
                // Notifications
                'view-notifications', 'cancel-notifications', 'renew-notifications', 'restore-notifications', 'force-delete-notifications',
                // Roles & Settings
                'manage-roles', 'manage-settings',
            ];

            // Create permissions
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }

            // Create Admin Role and assign all permissions
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            $adminRole->givePermissionTo(Permission::all());

            // Create Staff Role and assign specific permissions
            $staffRole = Role::firstOrCreate(['name' => 'staff']);
            $staffPermissions = [
                'view-employers', 'create-employers', 'edit-employers',
                'view-employees', 'create-employees', 'edit-employees', 'terminate-employees',
                'view-agents', 'create-agents', 'edit-agents',
                'view-importers', 'create-importers', 'edit-importers',
                'view-delegates', 'create-delegates', 'edit-delegates',
                'view-addresses', 'create-addresses', 'edit-addresses',
                'view-notifications', 'cancel-notifications', 'renew-notifications',
            ];
            $staffRole->syncPermissions($staffPermissions);

            // Create or find Admin User
            $adminUser = User::firstOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'name' => 'Admin User',
                    'password' => Hash::make('admin_password_1234'),
                ]
            );
            $adminUser->assignRole($adminRole);

            // Create or find Staff User
            $staffUser = User::firstOrCreate(
                ['email' => 'staff@example.com'],
                [
                    'name' => 'Staff User',
                    'password' => Hash::make('staff_password_1234'),
                ]
            );
            $staffUser->assignRole($staffRole);
        });
    }
}