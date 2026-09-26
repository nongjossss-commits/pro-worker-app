<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Line items for a LaborTaxInvoice — "this service, at this price, for this
 * many people, comes to this much", summed to the invoice's subtotal.
 * Applies uniformly whether the invoice was built from a LaborBill (one
 * auto-filled row, still editable/extendable — see
 * LaborTaxInvoiceService::previewFromBill()) or manually for an external
 * customer (LaborCustomer, added freely).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labor_tax_invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('labor_tax_invoice_id')->constrained('labor_tax_invoices')->cascadeOnDelete();

            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_tax_invoice_items');
    }
};
