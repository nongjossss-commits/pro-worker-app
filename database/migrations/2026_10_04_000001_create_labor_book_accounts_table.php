<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "สมุดบัญชี" — Pro Walker Labor's OWN bank accounts/cashbooks, tracking the
 * company's income/expenses. Deliberately a separate table from the main
 * app's `bank_accounts` (which belongs to a `FinancialProfile` for the main
 * business) — confirmed with the user: Labor's books must never share
 * balances with the main program's accounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labor_book_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_book_accounts');
    }
};
