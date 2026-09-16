<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ใบลดหนี้ (Credit Note) — reduces a specific billed FinancialTransaction,
 * mirroring TaxInvoice's shape (running number per fiscal year, draft →
 * issued → void, immutable once issued) rather than mutating the original
 * bill. financial_transaction_id is the required link ("which bill this
 * reduces" — every FinancialTransaction is a real billed line regardless of
 * whether a formal Tax Invoice was ever issued for it); related_tax_invoice_id
 * is an optional secondary link for when that bill's payment already had a
 * Tax Invoice issued and its declared VAT needs correcting too.
 *
 * Deliberately has NO bank_account_id / ledger_entry_id — this credit note
 * does not represent cash movement (see CreditNoteService's docblock), so it
 * must never feed BankReconciliationService's balance math. Output-VAT
 * correction is handled separately by TaxReportService reading this table
 * directly, not through LedgerEntry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();

            // เลข running ต่อปีภาษี เช่น CN-2026-0001
            $table->string('credit_note_no', 30)->unique();
            $table->date('credit_note_date');
            $table->unsignedSmallInteger('fiscal_year');

            $table->foreignId('financial_transaction_id')->constrained('financial_transactions')->restrictOnDelete();
            $table->foreignId('related_tax_invoice_id')->nullable()->constrained('tax_invoices')->nullOnDelete();
            $table->foreignId('issuer_profile_id')->nullable()->constrained('financial_profiles')->restrictOnDelete();

            // ข้อมูลลูกค้า — snapshot ตอนออก เหมือน tax_invoices
            $table->string('customer_name');
            $table->string('customer_tax_id', 15)->nullable();
            $table->string('customer_branch', 50)->nullable();
            $table->text('customer_address')->nullable();

            $table->decimal('subtotal', 15, 2);
            $table->decimal('vat_rate', 5, 2)->default(7);
            $table->decimal('vat_amount', 15, 2);
            $table->decimal('total_credit', 15, 2);

            // เหตุผลการลดหนี้ — required ตามที่กฎหมายภาษีกำหนดว่าใบลดหนี้ต้องระบุเหตุผล
            $table->text('reason');

            $table->enum('status', ['draft', 'issued', 'void'])->default('draft');

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['fiscal_year', 'credit_note_date']);
            $table->index(['status', 'credit_note_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
