<?php

use App\Models\FinancialTransaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-head bills keep the price each employee was billed at.
 *
 * Until now the per-person price was always read live from the group's
 * pricing tiers, so editing or deleting a tier silently changed the price
 * of employees already on a bill (even fully paid ones). `unit_price` is a
 * snapshot taken when the employee is put on the bill; NULL = no snapshot
 * (non-per-head bills, or no tier) → the old live lookup is used.
 *
 * Backfill: existing per-head bills get today's tier price — exactly what
 * the screens show right now, so nothing visible changes on deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_transaction_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->nullable()->after('production_item_id');
        });

        $rows = DB::table('financial_transaction_items as fti')
            ->join('financial_transactions as ft', 'ft.id', '=', 'fti.financial_transaction_id')
            ->join('production_financial_groups as g', 'g.id', '=', 'ft.production_financial_group_id')
            ->join('production_items as pi', 'pi.id', '=', 'fti.production_item_id')
            ->select('fti.id', 'fti.production_item_id', 'pi.employee_id', 'g.financial_data')
            ->orderBy('fti.id')
            ->get();

        foreach ($rows as $row) {
            $data = is_string($row->financial_data) ? json_decode($row->financial_data, true) : (array) $row->financial_data;
            if (($data['pricing_mode'] ?? 'per_head') !== 'per_head') {
                continue;
            }
            $price = FinancialTransaction::tierPriceFor($data['pricing_tiers'] ?? [], $row->production_item_id, $row->employee_id);
            if ($price !== null && $price > 0) {
                DB::table('financial_transaction_items')->where('id', $row->id)->update(['unit_price' => $price]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('financial_transaction_items', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });
    }
};
