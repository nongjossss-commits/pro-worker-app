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

        // Define permissions (A comprehensive list implied by source code structure)
        $permissions = [
            // General Management
            'manage-roles', 'manage-settings',
            // Trash
            'view-trash',
            // Employers
            'view-employers', 'create-employers', 'edit-employers',
'delete-employers', 'restore-employers', 'force-delete-employers',
            // Employees
            'view-employees', 'create-employees', 'edit-employees', 'delete-employees', 'restore-employees', 'force-delete-employees', 'terminate-employees',
            // Agents
            'view-agents', 'create-agents', 'edit-agents', 'delete-agents', 'restore-agents', 'force-delete-agents',
            // Importers
            'view-importers', 'create-importers', 'edit-importers', 'delete-importers', 'restore-importers', 'force-delete-importers',

            // Delegates
            'view-delegates', 'create-delegates', 'edit-delegates', 'delete-delegates', 'restore-delegates', 'force-delete-delegates',
        ];

        DB::transaction(function () use ($permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }

            // Create Roles
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            $staffRole = Role::firstOrCreate(['name' => 'staff']);


            // 1. Admin gets all permissions
            $adminRole->syncPermissions(Permission::all());

            // 2. Staff Permissions: Exclude management, trash view, and permanent deletion/restoration.
            // Staff can terminate (soft delete) employees.
            $staffPermissions = [
                'view-employers', 'create-employers', 'edit-employers',

                'view-employees', 'create-employees', 'edit-employees', 'terminate-employees', // Staff can terminate (soft delete) employees
                'view-agents', 'create-agents', 'edit-agents',
                'view-importers', 'create-importers', 'edit-importers',
                'view-delegates', 'create-delegates', 'edit-delegates',
            ];

            // FIX: Use syncPermissions to apply the restricted set, fixing the bug where Staff might inherit unintended permissions.
            $staffRole->syncPermissions($staffPermissions);

            // Create or find Admin User
            User::firstOrCreate(
                ['email' => 'admin@example.com'],
                ['name' => 'Admin User', 'password' => Hash::make('admin_password_1234')]
            )->assignRole($adminRole);

            // Create or find Staff User
            $staffUser = User::firstOrCreate(
                ['email' => 'staff@example.com'],
                ['name' => 'Staff User', 'password' => Hash::make('staff_password_1234')]
            );

            $staffUser->assignRole($staffRole);
        });
    }
}