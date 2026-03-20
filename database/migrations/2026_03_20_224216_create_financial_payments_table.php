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
        Schema::create('financial_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_transaction_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->datetime('paid_at');
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slip_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_payments');
    }
};
