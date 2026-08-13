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
