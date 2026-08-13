<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the binary labor_access_granted flag with a 3-tier
 * labor_access_level enum (none/view/edit) — Super Admin can now grant an
 * `admin` role user either read-only or full edit access to Pro Walker
 * Labor, instead of only all-or-nothing. No production rows had
 * labor_access_granted = true at the time of this migration, so there is
 * nothing to backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('labor_access_granted');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('labor_access_level', ['none', 'view', 'edit'])->default('none')->after('labor_team_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('labor_access_level');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('labor_access_granted')->default(false)->after('labor_team_id');
        });
    }
};
