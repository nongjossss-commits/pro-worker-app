<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional link from a "ลูกทีม" (LaborTeamMember, a name-only roster entry)
 * to the actual User login account that person uses, if they have one.
 * Nullable and unique: a team member with no login stays exactly as before
 * (a pure name record — every existing billing/ledger display already reads
 * straight off LaborTeamMember.name and is unaffected either way); a team
 * member who does have a login can be matched to exactly one User, and a
 * User can be matched to at most one team member (enforced by the unique
 * index — MySQL allows any number of NULLs in a unique column).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('labor_team_members', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('labor_team_id')
                ->constrained('users')->nullOnDelete();
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('labor_team_members', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
