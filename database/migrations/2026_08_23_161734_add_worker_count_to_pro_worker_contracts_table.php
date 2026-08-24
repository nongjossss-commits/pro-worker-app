<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional numeric "worker/labor count" captured from a template's
 * `worker_count`-type field (see LaborContractTemplateController's
 * builder) — denormalized onto the contract row itself so
 * LaborContractReportController can `sum('worker_count')` directly
 * instead of parsing each contract's JSON field_values per-report.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pro_worker_contracts', function (Blueprint $table) {
            $table->unsignedInteger('worker_count')->nullable()->after('field_values');
        });
    }

    public function down(): void
    {
        Schema::table('pro_worker_contracts', function (Blueprint $table) {
            $table->dropColumn('worker_count');
        });
    }
};
