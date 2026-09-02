<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * "ลูกทีม" — the 4th Pro Walker Labour role, alongside labor-accounting/
 * labor-shareholder/labor-team. Sees only their own personal totals (see
 * LaborDashboardController's 'own-member-only' mode) — access to every
 * other screen in the module is blocked outright by
 * App\Http\Middleware\RestrictLaborMemberAccess, not by this permission
 * (view-labor-ledger is granted only for consistency with the other two
 * read-only roles; no controller actually checks it, same as them).
 */
class LaborMemberRolePermissionSeeder extends Seeder
{
    public function run()
    {
        $permission = Permission::firstOrCreate(['name' => 'view-labor-ledger']);

        $role = Role::firstOrCreate(['name' => 'labor-member']);
        $role->givePermissionTo($permission);
    }
}
