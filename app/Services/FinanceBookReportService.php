<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Models\LedgerEntry;
use Carbon\Carbon;

/**
 * Period-scoped account balances + itemized category breakdown for the
 * main Finance "บันทึกรายรับรายจ่าย" report — the same two-section shape as
 * LaborReportService::accountBalancesForRange()/categoryTransactions(), but
 * against the main app's own BankAccount/LedgerEntry (see FinanceBookReportService
 * vs. LaborReportService: same formulas, `initial_balance`/`net_amount`
 * instead of `opening_balance`/`amount`). Deliberately has no "Per Team"/
 * "Team Summary" equivalent — those are Labor's team-billing concept, which
 * has no analog here.
 *
 * All historical `expenses` table rows have been backfilled into
 * LedgerEntry (source_type=Expense::class — see the
 * 2026_08_22_100002_backfill_ledger_entries_for_existing_expenses migration),
 * and bill payments + the "record expense" flow now post through
 * LedgerService too, so LedgerEntry is the single source of truth here.
 */
class FinanceBookReportService
{
    public function accountBalancesForRange(Carbon $from, Carbon $to)
    {
        $accounts = BankAccount::orderBy('account_type')->orderBy('bank_name')->get();

        $rows = $accounts->map(function (BankAccount $account) use ($from, $to) {
            $openingIncome = (float) $account->ledgerEntries()
                ->where('type', 'income')->where('entry_date', '<', $from->toDateString())->sum('net_amount');
            $openingExpense = (float) $account->ledgerEntries()
                ->where('type', 'expense')->where('entry_date', '<', $from->toDateString())->sum('net_amount');
            $opening = (float) $account->initial_balance + $openingIncome - $openingExpense;

            $income = (float) $account->ledgerEntries()
                ->where('type', 'income')->whereBetween('entry_date', [$from, $to])->sum('net_amount');
            $expense = (float) $account->ledgerEntries()
                ->where('type', 'expense')->whereBetween('entry_date', [$from, $to])->sum('net_amount');

            return (object) [
                'account' => $account,
                'opening_balance' => $opening,
                'income' => $income,
                'expense' => $expense,
                'closing_balance' => $opening + $income - $expense,
            ];
        });

        return (object) [
            'rows' => $rows,
            'totals' => (object) [
                'opening_balance' => (float) $rows->sum('opening_balance'),
                'income' => (float) $rows->sum('income'),
                'expense' => (float) $rows->sum('expense'),
                'closing_balance' => (float) $rows->sum('closing_balance'),
            ],
        ];
    }

    /**
     * Aggregated (SUM only, no itemization) type+category breakdown for
     * the Books index page's quick-glance table — the itemized version
     * (every individual entry) is categoryTransactions() below, used by
     * the full Reports page. Mirrors LaborReportService::categorySummary().
     */
    public function categorySummary(Carbon $from, Carbon $to)
    {
        $rows = LedgerEntry::whereBetween('entry_date', [$from, $to])
            ->selectRaw('type, category_id, category_type, SUM(net_amount) as total')
            ->groupBy('type', 'category_id', 'category_type')
            ->get();

        $incomeCategoryNames = IncomeCategory::pluck('name', 'id');
        $expenseCategoryNames = ExpenseCategory::pluck('name', 'id');
        $rows->each(function ($row) use ($incomeCategoryNames, $expenseCategoryNames) {
            $row->total = (float) $row->total;
            $row->label = $row->type === 'income'
                ? ($incomeCategoryNames[$row->category_id] ?? '-')
                : ($expenseCategoryNames[$row->category_id] ?? '-');
        });

        return $rows->sortBy('type')->sortByDesc('total')->values();
    }

    /**
     * Itemized breakdown of every LedgerEntry in the range, grouped by
     * type+category — used by the full Reports page.
     */
    public function categoryTransactions(Carbon $from, Carbon $to)
    {
        $items = LedgerEntry::whereBetween('entry_date', [$from, $to])
            ->with(['category', 'bankAccount'])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get()
            ->map(fn (LedgerEntry $e) => (object) [
                'entry_date' => $e->entry_date,
                'description' => $e->description,
                'net_amount' => (float) $e->net_amount,
                'bankAccount' => $e->bankAccount,
                'type' => $e->type,
                'category_label' => $e->category->name ?? '-',
            ]);

        $groups = $items->groupBy(fn ($item) => $item->type . '|' . $item->category_label)
            ->map(function ($groupItems, $key) {
                [$type, $label] = explode('|', $key, 2);

                return (object) [
                    'type' => $type,
                    'label' => $label,
                    'items' => $groupItems->values(),
                    'subtotal' => (float) $groupItems->sum('net_amount'),
                ];
            });

        $income = $groups->where('type', 'income')->sortByDesc('subtotal')->values();
        $expense = $groups->where('type', 'expense')->sortByDesc('subtotal')->values();

        $incomeTotal = (float) $income->sum('subtotal');
        $expenseTotal = (float) $expense->sum('subtotal');

        return (object) [
            'groups' => $income->concat($expense)->values(),
            'income_total' => $incomeTotal,
            'expense_total' => $expenseTotal,
            'net' => $incomeTotal - $expenseTotal,
        ];
    }
}
