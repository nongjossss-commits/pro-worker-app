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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date');

            $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();

            // ผูกกับหัวบิล (Financial Profile) ว่าใครเป็นคนจ่าย
            $table->foreignId('financial_profile_id')->nullable()->constrained()->nullOnDelete();

            // ผูกกับงาน (ถ้าเป็นรายจ่ายตรงของงาน)
            $table->foreignId('production_order_id')->nullable()->constrained('production_orders')->nullOnDelete();
            $table->foreignId('financial_transaction_id')->nullable()->constrained('financial_transactions')->nullOnDelete();

            $table->decimal('amount', 15, 2);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->boolean('is_tax_deductible')->default(false); // เอาค่ามาจาก category ตอนสร้าง แต่อาจจะให้ user override ได้

            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable(); // สลิป/ใบเสร็จ
            $table->string('wht_document_path')->nullable(); // ใบหัก ณ ที่จ่ายถ้ามี

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
