<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();

            // Running number — generate ตอน create ใน Service (LE-YYYY-MM-####)
            $table->string('entry_no', 30)->nullable()->unique();
            $table->date('entry_date');
            $table->enum('type', ['income', 'expense']);

            $table->foreignId('bank_account_id')->constrained()->restrictOnDelete();

            // Polymorphic ไป income_categories / expense_categories
            $table->unsignedBigInteger('category_id')->nullable();
            $table->enum('category_type', ['income', 'expense'])->nullable();
            $table->index(['category_type', 'category_id']);

            // คู่สัญญา (ลูกค้าจ่ายให้เรา / เราจ่ายให้ supplier)
            $table->string('counterparty_name')->nullable();
            $table->string('counterparty_tax_id', 15)->nullable();

            // -------- Amount breakdown --------
            // gross_amount   = ยอดที่ตกลงกันก่อนคำนวณภาษี (ใส่เข้าระบบ)
            // vat_treatment  = none/taxable/exempt/zero_rate
            // vat_rate       = อัตรา VAT (ปกติ 7)
            // vat_amount     = ภาษีมูลค่าเพิ่ม
            // subtotal       = gross - (vat ถ้า included) — ฐานคำนวณ WHT
            // wht_type       = none/pnd3/pnd53
            // wht_rate       = อัตรา WHT
            // wht_amount     = ยอดหัก ณ ที่จ่าย
            // net_amount     = เงินจริงที่เข้า/ออกบัญชี
            $table->decimal('gross_amount', 15, 2);
            $table->enum('vat_treatment', ['none', 'taxable', 'exempt', 'zero_rate'])->default('none');
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->enum('wht_type', ['none', 'pnd3', 'pnd53'])->default('none');
            $table->decimal('wht_rate', 5, 2)->default(0);
            $table->decimal('wht_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);

            // -------- Documents --------
            $table->string('tax_invoice_no', 50)->nullable();
            $table->date('tax_invoice_date')->nullable();
            $table->string('receipt_path')->nullable();
            $table->json('attached_files')->nullable();

            // -------- AI metadata (Phase 3 — ตอนนี้ default manual) --------
            $table->enum('ai_source', ['manual', 'ocr', 'text'])->default('manual');
            $table->json('ai_extracted_data')->nullable();
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->enum('ai_status', ['confirmed', 'pending_review', 'rejected'])->default('confirmed');

            // -------- Polymorphic link ไป FinancialPayment/Expense เดิม --------
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->index(['source_type', 'source_id']);

            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            // -------- Audit --------
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for reporting
            $table->index(['entry_date', 'type']);
            $table->index(['type', 'ai_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
