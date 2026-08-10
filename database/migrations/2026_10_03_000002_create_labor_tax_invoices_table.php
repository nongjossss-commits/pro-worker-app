<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ใบกำกับภาษี (VAT) for the Pro Walker Labor module — mirrors the main app's
 * `tax_invoices` schema (see 2026_06_05_100001_create_tax_invoices_table.php)
 * so LaborTaxInvoicePdfService/LaborTaxInvoiceService can follow the exact
 * same conventions. Kept as its own table (not reusing `tax_invoices`)
 * because Labor is billed via LaborBill/LaborTeam, not the main app's
 * Employer/LedgerEntry — and the two numbering sequences must stay
 * independent (LTI-YYYY-#### vs INV-YYYY-####).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labor_tax_invoices', function (Blueprint $table) {
            $table->id();

            $table->string('invoice_no', 30)->unique();
            $table->date('invoice_date');
            $table->unsignedSmallInteger('fiscal_year');

            $table->foreignId('labor_bill_id')->nullable()->constrained('labor_bills')->nullOnDelete();
            $table->foreignId('issuer_profile_id')->constrained('financial_profiles')->restrictOnDelete();

            // Customer snapshot — pre-filled from LaborTeam's customer_* fields, editable per invoice.
            $table->string('customer_name');
            $table->string('customer_tax_id', 15)->nullable();
            $table->string('customer_branch', 50)->nullable();
            $table->text('customer_address')->nullable();

            $table->decimal('subtotal', 15, 2);
            $table->decimal('vat_rate', 5, 2)->default(7);
            $table->decimal('vat_amount', 15, 2);
            $table->decimal('total', 15, 2);

            $table->enum('status', ['draft', 'issued', 'void'])->default('draft');

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();

            $table->text('notes')->nullable();
            $table->json('payment_methods')->nullable();

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
        Schema::dropIfExists('labor_tax_invoices');
    }
};
