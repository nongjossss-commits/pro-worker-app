<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Actual income/expense entries against a labor_book_accounts row. Balance
 * is never stored — always computed live as
 * opening_balance + SUM(income) - SUM(expense), same pattern as
 * LaborTeam::withSum() used throughout this module, to avoid a stored
 * balance drifting out of sync with the underlying rows.
 *
 * `source_type`/`source_id` link back to the record that generated this
 * entry automatically (currently only LaborBillPayment, when a team's
 * payment is posted into a book account) — auto-generated rows are not
 * editable/deletable from this table directly, only from their source.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labor_book_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labor_book_account_id')->constrained('labor_book_accounts')->restrictOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->string('category', 50)->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->string('description');
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['labor_book_account_id', 'transaction_date'], 'labor_book_txns_account_date_idx');
            $table->index(['source_type', 'source_id'], 'labor_book_txns_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_book_transactions');
    }
};
