<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UpdateProgressStepsPermissionSeeder extends Seeder
{
    public function run()
    {
        // Create the permission — controls ticking/updating step progress in
        // Pre-Production, Workflow, Registration Resolution and Renewal
        // Resolution, independently of 'edit-employees' (full employee data
        // editing). Lets an admin grant/revoke step-ticking per person via
        // the existing admin.users.edit checkbox list without also touching
        // that person's ability to edit employee data.
        $permission = Permission::firstOrCreate(['name' => 'update-progress-steps']);

        // Assign to Admin, Staff and Caretaker — preserves everyone's current
        // de facto ability to tick steps (previously covered only by
        // 'edit-employees') so nothing regresses; an admin can then revoke
        // it per individual caretaker at admin.users.edit as needed.
        foreach (['admin', 'staff', 'caretaker'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
