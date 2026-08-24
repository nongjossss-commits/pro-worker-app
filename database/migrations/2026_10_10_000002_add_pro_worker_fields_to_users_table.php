<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff identifier shown on Pro Worker contract issuance history/reports
 * (see App\Http\Controllers\Labor\LaborContractController) — access to
 * that feature and team attribution both reuse the existing
 * labor_access_level/labor_team_id columns (2026_10_06/2026_07_30
 * migrations), not a new grant system.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('staff_code')->nullable()->after('revoked_permissions');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('staff_code');
        });
    }
};
