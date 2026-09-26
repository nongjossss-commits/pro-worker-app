<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a WHT certificate link to a standalone (bill-less) LaborTaxInvoice —
 * an external-customer invoice — instead of only ever a LaborBill. Both
 * stay nullable/optional, same looseness as the existing labor_bill_id.
 *
 * labor_team_id is denormalized (auto-set from whichever of labor_bill_id/
 * labor_tax_invoice_id is picked) so the WHT list can be filtered/tracked
 * by team, same pattern as labor_tax_invoices.labor_team_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labor_wht_certificates', function (Blueprint $table) {
            $table->foreignId('labor_tax_invoice_id')->nullable()->after('labor_bill_id')
                ->constrained('labor_tax_invoices')->nullOnDelete();
            $table->foreignId('labor_team_id')->nullable()->after('labor_tax_invoice_id')
                ->constrained('labor_teams')->nullOnDelete();

            $table->index(['labor_team_id', 'tax_period_year', 'tax_period_month']);
        });
    }

    public function down(): void
    {
        Schema::table('labor_wht_certificates', function (Blueprint $table) {
            $table->dropIndex(['labor_team_id', 'tax_period_year', 'tax_period_month']);
            $table->dropConstrainedForeignId('labor_tax_invoice_id');
            $table->dropConstrainedForeignId('labor_team_id');
        });
    }
};
