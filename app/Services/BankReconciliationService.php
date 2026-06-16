<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\LedgerEntry;
use Illuminate\Support\Collection;

/**
 * Bank reconciliation — compare each BankAccount's `current_balance`
 * (mutated incrementally by LedgerService on every entry create/update/
 * delete) against the balance derived from re-summing the ledger.
 *
 * `expected_balance = initial_balance + Σ income.net − Σ expense.net`
 * `diff = actual_balance − expected_balance`
 *
 * A non-zero diff means a manual DB write happened outside LedgerService
 * (e.g. someone set current_balance directly in tinker), or an entry was
 * force-deleted, or there's an actual bug. The detail view exposes the
 * running balance so the user can pinpoint which entry the divergence
 * started at.
 */
class BankReconciliationService
{
    /**
     * Per-account summary across the whole ledger lifetime.
     *
     * @return Collection<int, array{
     *   account: BankAccount,
     *   initial_balance: float,
     *   total_income: float,
     *   total_expense: float,
     *   expected_balance: float,
     *   actual_balance: float,
     *   diff: float,
     *   entry_count: int,
     * }>
     */
    public function overview(): Collection
    {
        $accounts = BankAccount::orderBy('account_type')->orderBy('bank_name')->get();

        return $accounts->map(function (BankAccount $account) {
            $entries = LedgerEntry::where('bank_account_id', $account->id)->get();
            $income = (float) $entries->where('type', 'income')->sum('net_amount');
            $expense = (float) $entries->where('type', 'expense')->sum('net_amount');
            $initial = (float) $account->initial_balance;
            $expected = $initial + $income - $expense;
            $actual = (float) $account->current_balance;
            $diff = round($actual - $expected, 2);

            return [
                'account' => $account,
                'initial_balance' => $initial,
                'total_income' => round($income, 2),
                'total_expense' => round($expense, 2),
                'expected_balance' => round($expected, 2),
                'actual_balance' => round($actual, 2),
                'diff' => $diff,
                'entry_count' => $entries->count(),
            ];
        });
    }

    /**
     * Per-account detail with running balance.
     *
     * Walks entries in chronological order and accumulates a
     * running balance — useful to find the row where actual and
     * expected first diverged.
     *
     * @return array{
     *   account: BankAccount,
     *   initial_balance: float,
     *   final_expected: float,
     *   final_actual: float,
     *   diff: float,
     *   rows: array<int, array{
     *     entry: LedgerEntry,
     *     delta: float,
     *     running: float,
     *   }>,
     * }
     */
    public function detail(BankAccount $account): array
    {
        $entries = LedgerEntry::where('bank_account_id', $account->id)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $running = (float) $account->initial_balance;
        $rows = [];
        foreach ($entries as $entry) {
            $delta = ($entry->type === 'income' ? 1 : -1) * (float) $entry->net_amount;
            $running += $delta;
            $rows[] = [
                'entry' => $entry,
                'delta' => round($delta, 2),
                'running' => round($running, 2),
            ];
        }

        $expected = round($running, 2);
        $actual = round((float) $account->current_balance, 2);

        return [
            'account' => $account,
            'initial_balance' => (float) $account->initial_balance,
            'final_expected' => $expected,
            'final_actual' => $actual,
            'diff' => round($actual - $expected, 2),
            'rows' => $rows,
        ];
    }

    /**
     * Reset `current_balance` of a single account to the expected value.
     * Use only when the diff has been investigated and the system
     * computation is the source of truth.
     */
    public function repair(BankAccount $account): float
    {
        $detail = $this->detail($account);
        $account->current_balance = $detail['final_expected'];
        $account->save();
        return $detail['final_expected'];
    }
}
