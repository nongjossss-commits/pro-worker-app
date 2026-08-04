<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the existing Labor audit trail to also cover LaborBill events
 * (created/voided) — bills are financial documents and get the same
 * traceability as ledger entries.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('labor_audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('labor_bill_id')->nullable()->after('labor_ledger_entry_id');
        });
    }

    public function down(): void
    {
        Schema::table('labor_audit_logs', function (Blueprint $table) {
            $table->dropColumn('labor_bill_id');
        });
    }
};
