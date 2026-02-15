<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SuperAdminSetting;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Super Admin Role
        $role = Role::firstOrCreate(['name' => 'super-admin']);

        // Give all permissions to super-admin (optional, but good practice if using Spatie)
        // Usually Super Admin bypasses checks via Gate::before, but explicit assignment helps in some setups.
        // For now, I'll assume Spatie's Gate::before is configured or I'll give all existing permissions.
        $permissions = Permission::all();
        $role->syncPermissions($permissions);

        // 2. Create Super Admin User
        $user = User::firstOrCreate(
            ['email' => 'superadmin@proworker.com'], // Using a distinct email
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('SuperAdmin@2026'), // Default password
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole($role);

        // 3. Seed Default Settings
        $keys = [
            'dashboard',
            'activity_logs',
            'notifications',
            'incomplete_data',
            'ticket_inbox',
            'employer_ticket',
            'employers',
            'employees',
            'production',
            'workflow',
            'registration_resolution',
            'renewal_resolution',
            'importers',
            'agents',
            'delegates',
            'user_management',
            'roles_permissions',
            'pdf_templates',
            'central_trash',
        ];

        foreach ($keys as $key) {
            SuperAdminSetting::firstOrCreate(
                ['key' => $key],
                ['is_visible' => true, 'access_password' => null]
            );
        }
    }
}
