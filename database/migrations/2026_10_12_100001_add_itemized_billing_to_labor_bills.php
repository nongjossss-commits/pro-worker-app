<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item-by-item billing for Pro Walker Labour bills.
 *
 * - labor_bills.bill_type: 'statement' (the original running-balance bill —
 *   every existing row), 'itemized' (only the ledger entries ticked when it
 *   was issued) or 'monthly' (the monthly per-member charge bill).
 * - labor_bills.charge_type_ids: the charge-type filter used when issuing an
 *   itemized/monthly bill (informational, shown on the bill).
 * - labor_ledger_entries.labor_bill_id: the itemized/monthly bill an entry
 *   was billed on, so it is never billed twice. Statement bills never set it;
 *   their entries count as billed by date range instead
 *   (LaborLedgerEntry::scopeUnbilled). Cleared again when the bill is voided.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('labor_bills', function (Blueprint $table) {
            $table->string('bill_type', 20)->default('statement')->after('bill_no');
            $table->json('charge_type_ids')->nullable()->after('bill_type');
        });

        Schema::table('labor_ledger_entries', function (Blueprint $table) {
            $table->foreignId('labor_bill_id')->nullable()->after('labor_bill_payment_id')
                ->constrained('labor_bills')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('labor_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('labor_bill_id');
        });

        Schema::table('labor_bills', function (Blueprint $table) {
            $table->dropColumn(['bill_type', 'charge_type_ids']);
        });
    }
};
