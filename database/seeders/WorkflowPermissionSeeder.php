<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WorkflowPermissionSeeder extends Seeder
{
    public function run()
    {
        // Create the permission
        $permission = Permission::firstOrCreate(['name' => 'manage-own-workflow']);

        // Assign to Admin and Staff
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permission);
        }

        $staff = Role::where('name', 'staff')->first();
        if ($staff) {
            $staff->givePermissionTo($permission);
        }

        // Ensure Employer does NOT have it by default
        // (No action needed as we are not assigning it)
    }
}
