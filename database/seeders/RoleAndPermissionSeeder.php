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
            'view-notifications',
            'cancel-notifications', 'renew-notifications', 'restore-notifications', 'force-delete-notifications', // NEW
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

            'delete-addresses', 'restore-addresses', 'force-delete-addresses',
            'manage-tickets',
            'use-chat',
            'view-finance',
            'manage-finance',

            // PDF Templates
            'view-pdf-templates', 'create-pdf-templates', 'edit-pdf-templates', 'delete-pdf-templates',
            // END: Add new permissions

            // Pro Walker Labor module — used ONLY for in-module capability (view vs manage),
            // never for gating entry to the module itself. See EnsureLaborAccess middleware:
            // the `admin` role bypasses every permission check via Gate::before, so entry is
            // controlled by an explicit users.labor_access_level flag instead.
            'view-labor-ledger', 'manage-labor-ledger',
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
            'restore-employees',
            'view-dashboard',
            'view-notifications',
            'view-importers', 'edit-importers',
            'view-agents', 'edit-agents',
            'view-delegates', 'edit-delegates',
            'manage-tickets',
            'use-chat',
            'view-finance',
            'manage-finance',
            'view-pdf-templates', 'create-pdf-templates', 'edit-pdf-templates' // Staff can manage templates
        ];

        $staffRole->syncPermissions($staffPermissions);

        // FIX: Corrected all $this.command typos to $this->command
        $this->command->info('Staff role created/verified and permissions synced.');

        // --- START: Create Caretaker Role (NEW) ---
        $caretakerRole = Role::firstOrCreate(['name' => 'caretaker']);
        $caretakerPermissions = [
            // Base permissions
            'view-dashboard',
            'view-notifications',
            // Employer permissions (Scoped by Controller/Model)
            'view-employers',
            'edit-employers',
            'create-employers', // Allows adding but they might not see it unless assigned
            // Employee permissions (Scoped by Controller/Model)
            'view-employees',
            'create-employees',
            'edit-employees',
            'terminate-employees',
            'restore-employees',
            'use-chat'
            // Explicitly EXCLUDED:
            // view-importers, view-agents, view-delegates, view-trash, manage-users, etc.
        ];
        $caretakerRole->syncPermissions($caretakerPermissions);
        $this->command->info('Caretaker role created/verified and permissions synced.');
        // --- END: Create Caretaker Role (NEW) ---

        // Create Admin Role and assign all permissions — EXCEPT the two Labor
        // ledger abilities. Those are granted per-user via users.labor_access_level
        // instead (see AppServiceProvider's Gate::before carve-out), so admin
        // must NOT hold them at the role level or that carve-out never runs
        // (Gate::before stops at the first non-null result, and Spatie's own
        // check would grant them directly from the role before ours is reached).
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(
            Permission::whereNotIn('name', ['view-labor-ledger', 'manage-labor-ledger'])->get()
        );

        // FIX: Corrected all $this.command typos to $this->command
        $this->command->info('Admin role created/verified and assigned all permissions.');

        // --- START: Create Employer Role (NEW) ---
        $this->command->info('Creating Employer role...');
        $employerRole = Role::firstOrCreate(['name' => 'employer']);
        // [PATCH 3.2] Force Ground Truth Permissions (IDs 1, 5, 9)
        $employerPermissions = [
            'view-employers', // ID 5 (Confirmed)
            'view-employees' // ID 9 (Confirmed)
        ];
        // The syncPermissions function will handle overwriting the incorrect permissions
        $employerRole->syncPermissions($employerPermissions);
        $this->command->info('Employer role created/verified and permissions synced.');
        // --- END: Create Employer Role (NEW) ---

        // --- START: Pro Walker Labor roles (NEW) ---
        // Isolated module — these 3 roles have NO access to any other part of the
        // app (enforced by ConfineToLaborModule middleware) and log straight into
        // the Labor dashboard on login.
        $laborAccountingRole = Role::firstOrCreate(['name' => 'labor-accounting']);
        $laborAccountingRole->syncPermissions(['manage-labor-ledger']); // view + edit every team

        $laborShareholderRole = Role::firstOrCreate(['name' => 'labor-shareholder']);
        $laborShareholderRole->syncPermissions(['view-labor-ledger']); // view every team, read-only

        $laborTeamRole = Role::firstOrCreate(['name' => 'labor-team']);
        $laborTeamRole->syncPermissions(['view-labor-ledger']); // view own team only, read-only

        $this->command->info('Pro Walker Labor roles (labor-accounting, labor-shareholder, labor-team) created/verified.');
        // --- END: Pro Walker Labor roles (NEW) ---

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
