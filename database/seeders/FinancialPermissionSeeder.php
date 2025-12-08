<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FinancialPermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        Permission::firstOrCreate(['name' => 'manage-finance']);

        // update roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo('manage-finance');

        // Staff might need it too? User implied 'professional', usually implies Admin/Accountant.
        // We leave it to Admin only for now as requested.
    }
}
