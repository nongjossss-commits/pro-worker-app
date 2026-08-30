<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resolution_tabs', function (Blueprint $table) {
            // Super Admin toggle — when off, this tab's employees still flow
            // in normally (clickable cards, appointments/calendar unaffected)
            // but the purple/pink "อยู่ในกลุ่มมติ..." card badge is suppressed,
            // so a not-yet-live tab (e.g. a future renewal round prepared in
            // advance) doesn't show the same urgent color as the tab that's
            // actually being worked right now. Defaults to true so existing
            // tabs keep behaving exactly as before.
            $table->boolean('badge_enabled')->default(true)->after('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('resolution_tabs', function (Blueprint $table) {
            $table->dropColumn('badge_enabled');
        });
    }
};
