<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time data correction: the old Finance\ExpenseController flow
 * (`expenses` table) has always mutated `bank_accounts.current_balance`
 * directly, completely separately from LedgerEntry/LedgerService — so
 * every historical `expenses` row is invisible to "บันทึกรายรับรายจ่าย"/
 * the daily report, even though it already affected the account's real
 * balance. This inserts a matching LedgerEntry per existing `expenses` row
 * (source_type=Expense::class) so those figures show up correctly —
 * matched by the unique source_type+source_id pair already indexed on
 * ledger_entries, so this is safe to re-run. `expenses` rows themselves
 * are left completely untouched — nothing is deleted or migrated away
 * from that table, this only adds a corresponding copy into the newer one.
 *
 * expense_category_id and expense_categories.id are the SAME table
 * LedgerEntry uses for category_type='expense', so category_id needs no
 * remapping.
 */
return new class extends Migration
{
    public function up(): void
    {
        $expenses = DB::table('expenses')
            ->whereNotIn('id', function ($query) {
                $query->select('source_id')
                    ->from('ledger_entries')
                    ->where('source_type', 'App\\Models\\Expense');
            })
            ->get();

        $seq = 1;
        foreach ($expenses as $expense) {
            $amount = (float) $expense->amount;
            $vat = (float) $expense->vat_amount;
            $net = $amount + $vat;

            DB::table('ledger_entries')->insert([
                'entry_no' => 'LE-BF-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
                'entry_date' => $expense->expense_date,
                'type' => 'expense',
                'bank_account_id' => $expense->bank_account_id,
                'category_id' => $expense->expense_category_id,
                'category_type' => 'expense',
                'gross_amount' => $net,
                'vat_treatment' => 'none',
                'vat_rate' => 0,
                'vat_amount' => 0,
                'subtotal' => $net,
                'wht_type' => 'none',
                'wht_rate' => 0,
                'wht_amount' => 0,
                'net_amount' => $net,
                'description' => $expense->description ?: 'รายจ่าย (ย้ายมาจากระบบเดิม)',
                'source_type' => 'App\\Models\\Expense',
                'source_id' => $expense->id,
                'ai_source' => 'manual',
                'ai_status' => 'confirmed',
                'created_by' => $expense->created_by,
                'created_at' => $expense->created_at,
                'updated_at' => now(),
            ]);
            $seq++;
        }
    }

    public function down(): void
    {
        DB::table('ledger_entries')->where('source_type', 'App\\Models\\Expense')->delete();
    }
};
