<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets Super Admin additionally assign one of the 4 native Pro Walker Labor
 * "position" roles (labor-team/labor-member/labor-shareholder/labor-accounting)
 * to an `admin` account already granted labor_access_level, so that account
 * picks up the SAME data-scope restrictions a native holder of that role
 * gets (see App\Models\User::hasLaborPosition()) — orthogonal to
 * labor_access_level, which keeps controlling view-vs-edit only. Null means
 * "no position assigned": an admin+labor_access_level account with no
 * position behaves exactly as before this feature (sees all teams, scoped
 * only by access-level) — opt-in, backward-compatible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('labor_position', ['labor-team', 'labor-member', 'labor-shareholder', 'labor-accounting'])
                ->nullable()
                ->default(null)
                ->after('labor_access_level');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('labor_position');
        });
    }
};
