<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * 'view-attachment-archive' — see replaced / removed attachments kept for 30
 * days (App\Support\AttachmentArchive). Super Admin always has it; Admin and
 * other users only when a Super Admin ticks it for them on the user edit page
 * (same opt-in rule as 'move-attachments' — AppServiceProvider's Gate::before).
 * Not given to any role here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'view-attachment-archive', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'view-attachment-archive')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
