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
            'restore-employees', // <-- เพิ่ม Permission ใหม่
            'force-delete-employees' // <-- เพิ่ม Permission ใหม่
        ];
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
        $this->command->info('All base permissions created successfully.');

        // Create Staff Role and assign permissions
        $staffRole = Role::create(['name' => 'staff']);
        $staffPermissions = [
            'view-employers', 'create-employers', 'edit-employers',
            'view-employees', 'edit-employees', 'create-employees',
            'terminate-employees',
            'restore-employees'
        ];
        $staffRole->givePermissionTo($staffPermissions);
        $this->command->info('Staff role created and assigned permissions.');

        // Create Admin Role and assign all permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());
        $this->command->info('Admin role created and assigned all permissions.');

        // Create a demo staff user
        $staffUser = User::factory()->create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
    'password' => Hash::make('staff_password_1234'),
        ]);
        $staffUser->assignRole($staffRole);
        $this->command->info('Staff User (staff@example.com) created and assigned to staff role.');

        // Assign admin role to the existing test user
        $adminUser = User::where('email', 'test@example.com')->first();
        if ($adminUser) {
            $adminUser->assignRole($adminRole);
            $this->command->info('Admin role assigned to existing Test User (test@example.com).');
        }

        $this->command->info('Role and Permission Seeding COMPLETED.');
    }
}