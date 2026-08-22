<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a payment-derived ledger entry back to the LaborBillPayment that
 * produced it (nullable — manual/charge entries never set this). Unique so
 * a payment can never generate more than one offsetting ledger entry, which
 * also makes the backfill in the next migration safe to depend on.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('labor_ledger_entries', function (Blueprint $table) {
            $table->foreignId('labor_bill_payment_id')->nullable()->unique()->after('labor_charge_type_id')
                ->constrained('labor_bill_payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('labor_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('labor_bill_payment_id');
        });
    }
};
