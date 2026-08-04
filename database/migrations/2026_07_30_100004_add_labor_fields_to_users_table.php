<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - labor_team_id: which team a `labor-team` role user belongs to (own-team scoping).
 * - labor_access_granted: Super Admin opt-in flag for `admin` role users to see/use
 *   the Pro Walker Labor module. Deliberately NOT a Spatie permission — the `admin`
 *   role bypasses all permission checks via Gate::before (AuthServiceProvider), so a
 *   permission-based gate would let every admin in by default. This flag is checked
 *   explicitly instead (see EnsureLaborAccess middleware).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('labor_team_id')->nullable()->after('is_ticket_hidden')->constrained('labor_teams')->nullOnDelete();
            $table->boolean('labor_access_granted')->default(false)->after('labor_team_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('labor_team_id');
            $table->dropColumn('labor_access_granted');
        });
    }
};
