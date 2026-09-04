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

        // Assign to Admin and Staff — this is the basic, expected ability
        // for operational staff. Deliberately NOT Caretaker: this seeder
        // originally also granted it to Caretaker (to preserve everyone's
        // pre-existing de facto ability, back when the check was just
        // 'edit-employees') but that was a temporary regression-avoidance
        // default, not the intended long-term policy — Caretaker is a more
        // limited, customer-care-scoped role and should not tick workflow
        // steps by default. Explicitly revoke it below too, so re-running
        // this seeder on an install that already granted it (e.g. this
        // exact seeder having run once before) actually corrects it instead
        // of just skipping the (already-done) grant. An admin can still
        // grant it to one specific Caretaker user at admin.users.edit.
        foreach (['admin', 'staff'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permission);
            }
        }

        $caretakerRole = Role::where('name', 'caretaker')->first();
        if ($caretakerRole && $caretakerRole->hasPermissionTo($permission)) {
            $caretakerRole->revokePermissionTo($permission);
        }
    }
}
