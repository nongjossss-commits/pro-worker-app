<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_invoices', function (Blueprint $table) {
            $table->id();

            // เลข running ต่อปีภาษี เช่น INV-2026-0001 — generated โดย service
            $table->string('invoice_no', 30)->unique();
            $table->date('invoice_date');
            $table->unsignedSmallInteger('fiscal_year'); // ใช้ reset sequence

            // ผู้ออกใบ (บริษัทเรา) — ใช้ FinancialProfile เดิมที่ตั้งค่าไว้
            $table->foreignId('issuer_profile_id')->constrained('financial_profiles')->restrictOnDelete();

            // ข้อมูลลูกค้า — snapshot (ไม่ FK เพราะลูกค้าอาจเป็น manual entry)
            $table->string('customer_name');
            $table->string('customer_tax_id', 15)->nullable();
            $table->string('customer_branch', 50)->nullable();
            $table->text('customer_address')->nullable();

            // ยอด
            $table->decimal('subtotal', 15, 2);
            $table->decimal('vat_rate', 5, 2)->default(7);
            $table->decimal('vat_amount', 15, 2);
            $table->decimal('total', 15, 2);

            $table->enum('status', ['draft', 'issued', 'void', 'cancelled'])->default('draft');

            // Link กลับไป LedgerEntry (nullable — ถ้า create from ledger)
            $table->foreignId('related_ledger_entry_id')->nullable()
                ->constrained('ledger_entries')
                ->nullOnDelete();

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['fiscal_year', 'invoice_date']);
            $table->index(['status', 'invoice_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_invoices');
    }
};
