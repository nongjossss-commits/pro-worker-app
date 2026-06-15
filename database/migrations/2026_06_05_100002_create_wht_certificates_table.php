<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wht_certificates', function (Blueprint $table) {
            $table->id();

            // เลข running per type+period เช่น WHT-PND53-202606-0001
            $table->string('cert_no', 40)->unique();

            // ทิศของใบหัก: issued = เราออกให้ supplier, received = ลูกค้าออกให้เรา
            $table->enum('type', ['issued', 'received']);

            // ประเภทแบบยื่นภาษี
            $table->enum('wht_type', ['pnd3', 'pnd53']);

            // ช่วงเวลา (สำหรับยื่นรายเดือน)
            $table->unsignedSmallInteger('tax_period_year');
            $table->unsignedTinyInteger('tax_period_month');

            // ผู้จ่ายเงิน + ผู้รับเงิน (snapshot)
            $table->string('payer_name');
            $table->string('payer_tax_id', 15)->nullable();
            $table->string('payee_name');
            $table->string('payee_tax_id', 15)->nullable();

            // ประเภทรายได้ที่จ่าย (ใช้กับ ภ.ง.ด. — เช่น service, rent, advertising)
            $table->string('income_type', 50)->nullable();

            // ยอดเงิน
            $table->decimal('amount_paid', 15, 2);  // ยอดที่จ่าย (ก่อนหัก)
            $table->decimal('wht_rate', 5, 2);
            $table->decimal('wht_amount', 15, 2);
            $table->date('paid_at');

            // Polymorphic link ไป LedgerEntry หรือ FinancialTransaction เดิม
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            // ไฟล์ใบหัก (PDF) — upload จาก supplier หรือ generated ใน Phase 2.2
            $table->string('certificate_path')->nullable();

            $table->enum('status', ['draft', 'issued', 'submitted'])->default('draft');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'tax_period_year', 'tax_period_month']);
            $table->index(['source_type', 'source_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wht_certificates');
    }
};
