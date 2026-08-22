<?php

namespace App\Services;

use App\Models\LaborBill;
use App\Models\LaborBillPayment;
use App\Models\LaborBookAccount;
use App\Models\LaborBookTransaction;
use App\Models\LaborChargeType;
use App\Models\LaborExpenseCategory;
use App\Models\LaborTeam;
use Carbon\Carbon;

/**
 * Cross-team billing summary for a date range — the "daily/weekly/monthly"
 * report is really just this same aggregation with different from/to
 * bounds, so there's one method rather than three near-duplicate ones.
 */
class LaborReportService
{
    public function summarize(Carbon $from, Carbon $to): array
    {
        $teams = LaborTeam::where('is_active', true)->orderBy('name')->get();

        $rows = [];
        $totals = [
            'opening_balance' => 0.0,
            'charges' => 0.0,
            'payments' => 0.0,
            'closing_balance' => 0.0,
            'bills_issued' => 0,
        ];

        foreach ($teams as $team) {
            $opening = (float) $team->ledgerEntries()
                ->where('entry_date', '<', $from->toDateString())
                ->sum('amount');

            $charges = (float) $team->ledgerEntries()
                ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
                ->where('amount', '>', 0)
                ->sum('amount');

            $payments = (float) $team->ledgerEntries()
                ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
                ->where('amount', '<', 0)
                ->sum('amount');

            $closing = $opening + $charges + $payments;

            $billsIssued = $team->bills()
                ->where('status', '!=', 'void')
                ->whereBetween('issued_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->count();

            $rows[] = [
                'team' => $team,
                'opening_balance' => $opening,
                'charges' => $charges,
                'payments' => $payments,
                'closing_balance' => $closing,
                'bills_issued' => $billsIssued,
            ];

            $totals['opening_balance'] += $opening;
            $totals['charges'] += $charges;
            $totals['payments'] += $payments;
            $totals['closing_balance'] += $closing;
            $totals['bills_issued'] += $billsIssued;
        }

        return ['rows' => $rows, 'totals' => $totals];
    }

    /**
     * Per-team breakdown: how much has landed in the company books (via the
     * auto-link from LaborBillPayment) in the selected range, alongside
     * each team's overall billed/paid/outstanding from LaborBill — the
     * latter is a running snapshot (not range-scoped), since bills carry
     * their own period definitions. Shared by LaborBookController's index
     * and LaborReportController — same numbers, two places to see them.
     */
    public function teamPaymentSummary(Carbon $from, Carbon $to)
    {
        $receivedByTeam = LaborBookTransaction::query()
            ->join('labor_bill_payments', function ($join) {
                $join->on('labor_book_transactions.source_id', '=', 'labor_bill_payments.id')
                    ->where('labor_book_transactions.source_type', LaborBillPayment::class);
            })
            ->join('labor_bills', 'labor_bill_payments.labor_bill_id', '=', 'labor_bills.id')
            ->whereBetween('labor_book_transactions.transaction_date', [$from, $to])
            ->selectRaw('labor_bills.labor_team_id as team_id, SUM(labor_book_transactions.amount) as received')
            ->groupBy('labor_bills.labor_team_id')
            ->pluck('received', 'team_id');

        return LaborTeam::orderBy('name')->get()->map(function ($team) use ($receivedByTeam) {
            $bills = LaborBill::active()->where('labor_team_id', $team->id)->get();

            return (object) [
                'team' => $team,
                'received_in_range' => (float) ($receivedByTeam[$team->id] ?? 0),
                'total_due' => (float) $bills->sum('total_due'),
                'total_paid' => (float) $bills->sum(fn ($bill) => $bill->total_paid),
                'balance_due' => (float) $bills->sum(fn ($bill) => $bill->balance_due),
            ];
        })->filter(fn ($row) => $row->received_in_range > 0 || $row->total_due > 0)->values();
    }

    /**
     * Company Books income/expense grouped by category for a date range —
     * shared by LaborBookController's index and LaborReportController.
     */
    public function categorySummary(Carbon $from, Carbon $to)
    {
        $rows = LaborBookTransaction::whereBetween('transaction_date', [$from, $to])
            ->selectRaw('type, category, labor_charge_type_id, labor_expense_category_id, SUM(amount) as total')
            ->groupBy('type', 'category', 'labor_charge_type_id', 'labor_expense_category_id')
            ->orderBy('type')
            ->orderByDesc('total')
            ->get();

        // Include inactive categories too, so labels for historical (since
        // deactivated) categories still resolve instead of showing blank.
        $chargeTypeNames = LaborChargeType::pluck('name', 'id');
        $expenseCategoryNames = LaborExpenseCategory::pluck('name', 'id');
        $rows->each(function ($row) use ($chargeTypeNames, $expenseCategoryNames) {
            $row->label = match (true) {
                $row->category === 'team_payment' => 'ชำระค่าบริการจากทีม (อัตโนมัติ)',
                $row->type === 'income' => $chargeTypeNames[$row->labor_charge_type_id] ?? '-',
                default => $expenseCategoryNames[$row->labor_expense_category_id] ?? '-',
            };
        });

        return $rows;
    }

    /**
     * Itemized version of categorySummary() — every individual transaction
     * grouped under its category (not just the SUM), so accounting staff
     * can see exactly which line items made up a category's total in a
     * daily/weekly/monthly report. Used by the Reports page + its Excel/PDF
     * exports; categorySummary() itself is untouched since
     * LaborBookController::index() still relies on the aggregated form.
     */
    public function categoryTransactions(Carbon $from, Carbon $to)
    {
        $transactions = LaborBookTransaction::whereBetween('transaction_date', [$from, $to])
            ->with(['chargeType', 'expenseCategory', 'account'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $groups = $transactions->groupBy(fn ($t) => $t->type . '|' . $t->category_label)
            ->map(function ($items, $key) {
                [$type, $label] = explode('|', $key, 2);

                return (object) [
                    'type' => $type,
                    'label' => $label,
                    'items' => $items->values(),
                    'subtotal' => (float) $items->sum('amount'),
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

    /**
     * Period-scoped reconciliation per Company Books account — unlike
     * accountBalances() (always "as of now", used by the Company Books
     * dashboard), this answers "what was the balance at the start of the
     * selected period, what moved during it, and what's the balance at the
     * end of it" — the same opening/movement/closing shape as summarize()
     * already gives for the Central Billing ledger, but for the office's
     * own cash accounts. This is what the Reports page's "Account
     * Balances" section and its PDF/Excel exports use, so the figures
     * actually respond to the Day/Week/Month/Quarter/Year filter instead
     * of always showing today's live balance regardless of period chosen.
     */
    public function accountBalancesForRange(Carbon $from, Carbon $to)
    {
        $accounts = LaborBookAccount::orderBy('name')->get();

        $rows = $accounts->map(function ($account) use ($from, $to) {
            $openingIncome = (float) $account->transactions()
                ->where('type', 'income')->where('transaction_date', '<', $from->toDateString())->sum('amount');
            $openingExpense = (float) $account->transactions()
                ->where('type', 'expense')->where('transaction_date', '<', $from->toDateString())->sum('amount');
            $opening = (float) $account->opening_balance + $openingIncome - $openingExpense;

            $income = (float) $account->transactions()
                ->where('type', 'income')->whereBetween('transaction_date', [$from, $to])->sum('amount');
            $expense = (float) $account->transactions()
                ->where('type', 'expense')->whereBetween('transaction_date', [$from, $to])->sum('amount');

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
     * Live (not date-ranged) balance per Company Books account — a balance
     * is always "as of now", computed the same way as
     * LaborBookAccount::getCurrentBalanceAttribute() to avoid a stored
     * value drifting out of sync.
     */
    public function accountBalances()
    {
        $accounts = LaborBookAccount::withSum(
            ['transactions as income_total' => fn ($q) => $q->where('type', 'income')],
            'amount'
        )->withSum(
            ['transactions as expense_total' => fn ($q) => $q->where('type', 'expense')],
            'amount'
        )->orderBy('name')->get();

        $accounts->each(function ($account) {
            $account->computed_balance = (float) $account->opening_balance
                + (float) ($account->income_total ?? 0)
                - (float) ($account->expense_total ?? 0);
        });

        return $accounts;
    }
}
