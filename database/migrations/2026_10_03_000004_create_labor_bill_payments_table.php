<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A recorded payment against a LaborBill. LaborBill itself never gets
 * "settled" (see its migration docblock) — payments are tracked separately
 * here, one row per payment, supporting partial payments. A receipt (PDF) is
 * issued per payment via LaborBillPaymentService::issueReceipt(), which
 * assigns `receipt_no` at issue time (not at record time) — same draft→issue
 * numbering discipline as LaborTaxInvoice/LaborWhtCertificate, so an
 * abandoned/corrected payment entry never burns a receipt number.
 *
 * No existing main-app equivalent to copy verbatim: `financial_payments` has
 * a `receipt_generated_at` column (so a receipt-per-payment PDF was clearly
 * intended there too) but no receipt PDF service was ever built for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labor_bill_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('labor_bill_id')->constrained('labor_bills')->cascadeOnDelete();

            $table->decimal('amount', 12, 2);
            $table->date('paid_at');
            $table->enum('payment_method', ['cash', 'transfer', 'promptpay', 'other'])->default('transfer');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('slip_path')->nullable();

            // Set if the payer withheld tax on this specific payment.
            $table->foreignId('wht_certificate_id')->nullable()
                ->constrained('labor_wht_certificates')->nullOnDelete();

            $table->string('receipt_no', 30)->nullable()->unique();
            $table->timestamp('receipt_generated_at')->nullable();
            $table->string('receipt_pdf_path')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['labor_bill_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_bill_payments');
    }
};
