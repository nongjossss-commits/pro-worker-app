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
        // Per-employee payment ledger for a bill collected individually
        // (FinancialTransaction.payment_collection_mode = 'per_employee') —
        // each row is one person's one payment. A group of these gets rolled
        // up into a single FinancialPayment (financial_payment_id) when the
        // user finishes a collection round, so receipts/ledger/paid_amount
        // keep working exactly as they do for a lump-sum bill. Rows with
        // financial_payment_id still null haven't been rolled up yet.
        Schema::create('financial_transaction_item_payments', function (Blueprint $table) {
            $table->id();
            // Explicit short constraint names — the default auto-generated
            // name (table + column + "_foreign") exceeds MySQL's 64-char
            // identifier limit on this table's long name.
            $table->foreignId('financial_transaction_id')
                ->constrained('financial_transactions', 'id', 'ftip_transaction_fk')
                ->cascadeOnDelete();
            $table->foreignId('production_item_id')
                ->constrained('production_items', 'id', 'ftip_item_fk')
                ->cascadeOnDelete();
            $table->foreignId('financial_payment_id')->nullable()
                ->constrained('financial_payments', 'id', 'ftip_payment_fk')
                ->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('paid_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()
                ->constrained('users', 'id', 'ftip_created_by_fk')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['financial_transaction_id', 'production_item_id'], 'ftip_txn_item_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transaction_item_payments');
    }
};
