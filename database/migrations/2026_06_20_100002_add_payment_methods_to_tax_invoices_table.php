<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `payment_methods` to tax_invoices — a JSON array describing
 * the payment channels the issuer accepts on this specific invoice.
 *
 * Each entry has the shape:
 *   { type: 'cash' | 'transfer' | 'promptpay' | 'other',
 *     bank_account_id?: int,
 *     bank_code?: string,
 *     bank_name?: string,
 *     account_name?: string,
 *     account_number?: string,
 *     promptpay_id?: string,
 *     note?: string }
 *
 * Stored as JSON (not separate rows) because the data is rendered as
 * a frozen footer on the PDF — it shouldn't change after issuance,
 * and a relational table would make the immutability harder to
 * guarantee than just snapshotting the values into the invoice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_invoices', function (Blueprint $table) {
            $table->json('payment_methods')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('tax_invoices', function (Blueprint $table) {
            $table->dropColumn('payment_methods');
        });
    }
};
