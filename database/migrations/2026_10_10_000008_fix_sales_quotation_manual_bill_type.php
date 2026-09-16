<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time data fix: migration 2026_09_01_121241 backfilled EVERY existing
 * `production_orders` row with `work_type_id IS NULL` to
 * `manual_bill_type = 'invoice'`, without knowing about the separate
 * "sales_quotation"-status order SalesLeadController::loadFinancialTab()
 * creates for a still-quoting Sales Lead (also work_type_id = null). That
 * backfill wrongly tagged those as 'invoice', so FinancialHubController's
 * "Manual Bills" tab (which now filters IN 'invoice') would show them, and
 * the "Quotations" tab (which filters IN 'quotation') would not — the
 * opposite of correct. This corrects the tag for every such row, using the
 * same 'quotation' value now set going forward by loadFinancialTab() itself.
 *
 * Purely a relabel (no rows added/removed, no amounts touched) — safe to
 * run against production data.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('production_orders')
            ->where('status', 'sales_quotation')
            ->where('manual_bill_type', '!=', 'quotation')
            ->update(['manual_bill_type' => 'quotation']);
    }

    public function down(): void
    {
        DB::table('production_orders')
            ->where('status', 'sales_quotation')
            ->update(['manual_bill_type' => 'invoice']);
    }
};
