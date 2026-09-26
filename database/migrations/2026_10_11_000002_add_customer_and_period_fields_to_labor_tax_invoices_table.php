<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a LaborTaxInvoice be issued directly to an external customer instead
 * of always deriving from a LaborBill (labor_bill_id was already nullable —
 * this just adds the structured link + tracking that path was missing).
 *
 * labor_team_id is denormalized on purpose (also derivable via
 * bill.labor_team_id or customer.labor_team_id) so accounting can filter/
 * tally the invoice list by team with a single indexed column, same
 * pattern as labor_ledger_entries.labor_team_id.
 *
 * period_start/period_end track which billing period this invoice covers —
 * without it there is no way to catch a customer accidentally billed twice
 * for the same month, or skipped a month.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labor_tax_invoices', function (Blueprint $table) {
            $table->foreignId('labor_customer_id')->nullable()->after('labor_bill_id')
                ->constrained('labor_customers')->nullOnDelete();
            $table->foreignId('labor_team_id')->nullable()->after('labor_customer_id')
                ->constrained('labor_teams')->nullOnDelete();
            $table->date('period_start')->nullable()->after('fiscal_year');
            $table->date('period_end')->nullable()->after('period_start');

            $table->index(['labor_team_id', 'invoice_date']);
        });
    }

    public function down(): void
    {
        Schema::table('labor_tax_invoices', function (Blueprint $table) {
            $table->dropIndex(['labor_team_id', 'invoice_date']);
            $table->dropConstrainedForeignId('labor_customer_id');
            $table->dropConstrainedForeignId('labor_team_id');
            $table->dropColumn(['period_start', 'period_end']);
        });
    }
};
