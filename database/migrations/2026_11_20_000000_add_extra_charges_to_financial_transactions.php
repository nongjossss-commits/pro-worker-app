<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "ค่าใช้จ่ายเพิ่มเติม" on a bill become their own lines (2026-11-20).
 *
 * Before, adding one only raised the bill's amount and appended a line to its
 * Notes ("+ ค่ารถ: ฿500.00") — the printed bill had no row for it (its rows
 * did not add up to the total) and individual collection had nothing to
 * collect it against.
 *
 *  - financial_transactions.extra_charges: [{id, description, amount}] — part
 *    of `amount` (amount = employees / work + the extra charges).
 *  - financial_transaction_item_payments: an individual-collection entry can
 *    now be for an extra charge (extra_charge_id) instead of a person
 *    (production_item_id becomes nullable).
 *  - Existing bills: the "+ label: ฿amount" lines the old button wrote into
 *    Notes are moved into extra_charges (amount unchanged — it already
 *    included them). Only lines in exactly that form are touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('financial_transactions', 'extra_charges')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->json('extra_charges')->nullable()->after('line_items');
            });
        }

        Schema::table('financial_transaction_item_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('production_item_id')->nullable()->change();
        });
        if (! Schema::hasColumn('financial_transaction_item_payments', 'extra_charge_id')) {
            Schema::table('financial_transaction_item_payments', function (Blueprint $table) {
                $table->string('extra_charge_id', 40)->nullable()->after('production_item_id');
            });
        }

        // Move the old "+ label: ฿amount" note lines into extra_charges.
        DB::table('financial_transactions')
            ->where('notes', 'like', '%+ %฿%')
            ->orderBy('id')
            ->get(['id', 'notes', 'extra_charges'])
            ->each(function ($t) {
                $existing = json_decode($t->extra_charges ?? 'null', true) ?: [];
                $kept = [];
                $moved = [];
                foreach (preg_split('/\r\n|\n|\r/', (string) $t->notes) as $line) {
                    if (preg_match('/^\+ (.+): ฿\s?([\d,]+(?:\.\d+)?)\s*$/u', trim($line), $m)) {
                        $amount = (float) str_replace(',', '', $m[2]);
                        if ($amount > 0) {
                            $moved[] = ['id' => 'x' . $t->id . '_' . (count($existing) + count($moved) + 1), 'description' => trim($m[1]), 'amount' => round($amount, 2)];
                            continue;
                        }
                    }
                    $kept[] = $line;
                }
                if ($moved) {
                    $notes = trim(implode("\n", $kept));
                    DB::table('financial_transactions')->where('id', $t->id)->update([
                        'extra_charges' => json_encode(array_merge($existing, $moved), JSON_UNESCAPED_UNICODE),
                        'notes' => $notes === '' ? null : $notes,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Put the charges back into Notes the way the old button wrote them.
        DB::table('financial_transactions')->whereNotNull('extra_charges')->orderBy('id')->get(['id', 'notes', 'extra_charges'])
            ->each(function ($t) {
                $lines = collect(json_decode($t->extra_charges, true) ?: [])
                    ->map(fn ($c) => '+ ' . ($c['description'] ?? 'ค่าใช้จ่ายเพิ่มเติม') . ': ฿' . number_format((float) ($c['amount'] ?? 0), 2))
                    ->all();
                if ($lines) {
                    DB::table('financial_transactions')->where('id', $t->id)->update([
                        'notes' => trim(($t->notes ? $t->notes . "\n" : '') . implode("\n", $lines)),
                    ]);
                }
            });
        DB::table('financial_transaction_item_payments')->whereNotNull('extra_charge_id')->delete();
        Schema::table('financial_transaction_item_payments', function (Blueprint $table) {
            $table->dropColumn('extra_charge_id');
        });
        Schema::table('financial_transaction_item_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('production_item_id')->nullable(false)->change();
        });
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn('extra_charges');
        });
    }
};
