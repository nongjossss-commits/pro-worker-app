<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_check_sessions', function (Blueprint $table) {
            // Set only when a session finishes — the "business day" (05:00
            // cutoff, see AccountingPeriodService::businessDate()) it belongs
            // to, and its 1-based order among sessions finished that same
            // business day (a day can have more than one check pass).
            $table->date('business_date')->nullable()->after('ended_at');
            $table->unsignedTinyInteger('sequence_in_day')->nullable()->after('business_date');

            $table->index(['business_date', 'sequence_in_day']);
        });
    }

    public function down(): void
    {
        Schema::table('job_check_sessions', function (Blueprint $table) {
            $table->dropIndex(['business_date', 'sequence_in_day']);
            $table->dropColumn(['business_date', 'sequence_in_day']);
        });
    }
};
