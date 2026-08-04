<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional attribution of a ledger entry to a specific team member — some
 * entries are a team-wide charge with no single person behind them.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('labor_ledger_entries', function (Blueprint $table) {
            $table->foreignId('labor_team_member_id')->nullable()->after('labor_team_id')
                ->constrained('labor_team_members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('labor_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('labor_team_member_id');
        });
    }
};
