<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sum of this transaction's ISSUED credit notes — kept in sync by
 * CreditNoteService (recomputed via sum(), same pattern as paid_amount),
 * never written to directly elsewhere. Deliberately separate from
 * discount_amount (a pre-billing adjustment) so a credit note's own
 * numbering/reason/audit trail stays intact and distinguishable from a
 * plain discount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->decimal('credit_amount', 15, 2)->default(0)->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn('credit_amount');
        });
    }
};
