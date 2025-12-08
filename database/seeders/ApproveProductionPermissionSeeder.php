<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ApproveProductionPermissionSeeder extends Seeder
{
    public function run()
    {
        // Create the permission
        $permission = Permission::firstOrCreate(['name' => 'approve-production']);

        // Assign to Admin role
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo($permission);

        // Optionally assign to Staff if requested, but user said "Admin or authorized"
        // We will stick to Admin for now, user can assign manually if needed.
    }
}
