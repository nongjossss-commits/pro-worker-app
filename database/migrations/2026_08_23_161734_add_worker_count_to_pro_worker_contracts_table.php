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
        // The table-creation migration for pro_worker_contracts is timestamped after this
        // one, so on a fresh install it may not exist yet.
        if (!Schema::hasTable('pro_worker_contracts')) {
            Schema::create('pro_worker_contracts', function (Blueprint $table) {
                $table->id();
                $table->string('contract_no')->unique();
                $table->foreignId('pro_worker_contract_template_id')->constrained('pro_worker_contract_templates')->restrictOnDelete();
                $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('labor_team_id')->nullable()->constrained('labor_teams')->nullOnDelete();
                $table->json('field_values')->nullable();
                $table->string('file_path');
                $table->dateTime('issued_at');
                $table->timestamps();

                $table->index(['labor_team_id', 'issued_at']);
                $table->index(['issued_by', 'issued_at']);
            });
        }

        if (!Schema::hasColumn('pro_worker_contracts', 'worker_count')) {
            Schema::table('pro_worker_contracts', function (Blueprint $table) {
                $table->unsignedInteger('worker_count')->nullable()->after('field_values');
            });
        }
    }

    public function down(): void
    {
        Schema::table('pro_worker_contracts', function (Blueprint $table) {
            $table->dropColumn('worker_count');
        });
    }
};
