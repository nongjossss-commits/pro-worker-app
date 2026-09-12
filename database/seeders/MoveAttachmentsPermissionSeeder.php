<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MoveAttachmentsPermissionSeeder extends Seeder
{
    public function run()
    {
        // Create the permission — controls EmployeeController::bulkMoveAttachments()
        // ("Move Attachment Files"), previously hard-restricted to
        // hasRole('super-admin'). Deliberately NOT granted to any role here
        // (not even Admin) — Super Admin always passes regardless, via the
        // dedicated 'move-attachments' branch in AppServiceProvider's
        // Gate::before (which explicitly excludes Admin from its usual
        // blanket bypass for this one ability). Everyone else, Admin
        // included, only gets it if a Super Admin ticks it for that
        // specific user via the existing admin.users.edit "Delegate
        // Permissions" checkbox list — this permission exists purely so
        // that checkbox has something to grant.
        Permission::firstOrCreate(['name' => 'move-attachments']);
    }
}
