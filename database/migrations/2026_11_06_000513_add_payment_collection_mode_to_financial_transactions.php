<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            // Default 'lump_sum' preserves the existing single-amount payment
            // flow for every current and future row unless explicitly
            // switched — see FinancialTransactionItemPayment for the new
            // per-employee collection mode this unlocks.
            $table->string('payment_collection_mode')->default('lump_sum')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn('payment_collection_mode');
        });
    }
};
