<?php

use App\Services\ProWorkerContractEmployerFields;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Pro Walker Labour → Contract History: search by the employer's ID number
 * too. employer_ref_snapshot holds it (copied from the template's own
 * field — see ProWorkerContractEmployerFields), indexed for the search box.
 *
 * Also fills the employer name / ID for contracts issued so far (they were
 * "-": the issuance form stopped setting employer_name_snapshot), using the
 * labels guessed from each template — only these two search columns are
 * written; field_values, the PDFs and the edit history are untouched.
 * A Super Admin can change the chosen fields later (Contract Templates →
 * Form Order), which re-syncs that template's contracts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pro_worker_contracts', function (Blueprint $table) {
            $table->string('employer_ref_snapshot')->nullable()->after('employer_name_snapshot');
            $table->index('employer_ref_snapshot');
        });

        try {
            app(ProWorkerContractEmployerFields::class)->syncAll();
        } catch (\Throwable $e) {
            // Search data only — never block the deploy; saving the template setting re-syncs.
            Log::warning('Contract employer fields sync skipped: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        Schema::table('pro_worker_contracts', function (Blueprint $table) {
            $table->dropIndex(['employer_ref_snapshot']);
            $table->dropColumn('employer_ref_snapshot');
        });
    }
};
