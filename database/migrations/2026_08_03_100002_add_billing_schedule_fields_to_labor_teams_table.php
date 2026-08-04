<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-team auto-billing schedule — each team's cadence is independent, set
 * by Accounting Staff/Super Admin on the team's own page. `last_auto_billed_on`
 * stops the daily scheduled command from generating two bills for the same
 * team on the same day.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('labor_teams', function (Blueprint $table) {
            $table->boolean('auto_billing_enabled')->default(false)->after('is_active');
            $table->string('billing_cadence')->nullable()->after('auto_billing_enabled'); // daily | weekly | monthly
            $table->unsignedTinyInteger('billing_day_of_week')->nullable()->after('billing_cadence'); // 0=Sun..6=Sat, for weekly
            $table->unsignedTinyInteger('billing_day_of_month')->nullable()->after('billing_day_of_week'); // 1-31, for monthly
            $table->date('last_auto_billed_on')->nullable()->after('billing_day_of_month');
        });
    }

    public function down(): void
    {
        Schema::table('labor_teams', function (Blueprint $table) {
            $table->dropColumn([
                'auto_billing_enabled',
                'billing_cadence',
                'billing_day_of_week',
                'billing_day_of_month',
                'last_auto_billed_on',
            ]);
        });
    }
};
