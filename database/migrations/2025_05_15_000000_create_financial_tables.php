<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., Proworker Recruitment Co., Ltd.
            $table->text('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('logo_path')->nullable(); // Storage path
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->onDelete('cascade');
            $table->string('type')->default('installment'); // installment, down_payment, full_payment
            $table->decimal('amount', 12, 2);
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0); // For partial payments
            $table->string('slip_path')->nullable(); // Evidence
            $table->string('status')->default('pending'); // pending, partial, paid, overdue
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('company_profiles');
    }
};
