<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per issued Pro Worker <-> Employer contract — the anti-forgery
 * record: contract_no is unique and never reused (see
 * ProWorkerContractService::generateContractNo(), same lockForUpdate()
 * pattern as LaborBillService::generateBillNo()/TaxInvoiceService).
 * labor_team_id is captured at issuance time (not a live join off the
 * issuer's current team) so a later team reassignment never rewrites
 * history/stats for contracts already issued — reuses the existing
 * LaborTeam table (see users.labor_team_id) since this feature lives
 * inside the Pro Walker Labor module, not a separate team concept.
 */
return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('pro_worker_contracts');
    }
};
