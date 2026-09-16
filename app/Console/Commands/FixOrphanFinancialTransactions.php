<?php

namespace App\Console\Commands;

use App\Models\FinancialPayment;
use App\Models\FinancialTransaction;
use App\Models\ProductionFinancialGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Recovery tool for finance tabs that were deleted while still holding
 * transactions. Before we added the delete-guard to destroyFinancialGroup,
 * the UI let operators soft-delete a tab (ProductionFinancialGroup) even
 * when installments/down-payments existed. The rows stayed in
 * `financial_transactions` but the owning tab was gone → no UI path back
 * to edit or clear them.
 *
 * Three orphan classes are detected:
 *   1. Soft-deleted group (deleted_at != NULL). Recoverable: restore the
 *      tab and the operator can clear its transactions normally. Prefer
 *      this path — it does not destroy any billing history.
 *   2. Missing group (row gone entirely — hard-delete or DB manipulation).
 *      Not recoverable. Only cleanup is to hard-delete the orphan
 *      transactions and their payments. Requires --confirm.
 *   3. Ghost transactions from SalesLeadController::transition()'s old
 *      quotation-conversion bug (fixed — see that method's docblock): every
 *      row has BOTH production_order_id AND production_financial_group_id
 *      NULL, which normal creation paths never produce (a transaction is
 *      always created via a group's ->transactions() relation). These were
 *      never attached to any real bill, so restoring a tab makes no sense
 *      for them — only cleanup (hard-delete) applies. Requires --confirm,
 *      same as Class 2.
 *
 * Default run is a dry-run report — no writes.
 */
class FixOrphanFinancialTransactions extends Command
{
    protected $signature = 'finance:fix-orphans
                            {--restore-tabs : Restore all soft-deleted finance tabs that still have transactions attached}
                            {--purge-transactions : Hard-delete orphan transactions whose group no longer exists at all (irreversible)}
                            {--confirm : Required together with --purge-transactions}';

    protected $description = 'Diagnose and repair orphan finance transactions left behind by deleted tabs. Dry-run by default.';

    public function handle(): int
    {
        $restoreTabs = (bool) $this->option('restore-tabs');
        $purgeTx     = (bool) $this->option('purge-transactions');
        $confirmed   = (bool) $this->option('confirm');

        if ($restoreTabs && $purgeTx) {
            $this->error('Cannot combine --restore-tabs with --purge-transactions in a single run. Do them one at a time.');
            return self::INVALID;
        }
        if ($purgeTx && !$confirmed) {
            $this->error('--purge-transactions is destructive. Re-run with --confirm to proceed.');
            return self::INVALID;
        }

        $this->line('');
        $this->info('===== Orphan Finance Transactions =====');
        $this->line('');

        // --- Class 1: transactions whose group is soft-deleted (recoverable)
        $softDeletedGroupIds = ProductionFinancialGroup::onlyTrashed()
            ->whereHas('transactions')
            ->pluck('id');

        $softDeletedGroups = ProductionFinancialGroup::onlyTrashed()
            ->whereIn('id', $softDeletedGroupIds)
            ->withCount([
                'transactions',
                'transactions as paid_transactions_count' => function ($q) {
                    $q->whereIn('status', ['paid', 'partial']);
                },
            ])
            ->get();

        // --- Class 2: transactions whose group row is completely gone
        $existingGroupIds = ProductionFinancialGroup::withTrashed()->pluck('id')->all();
        $missingGroupTxIds = FinancialTransaction::whereNotNull('production_financial_group_id')
            ->whereNotIn('production_financial_group_id', $existingGroupIds)
            ->pluck('id');

        $missingGroupTx = FinancialTransaction::whereIn('id', $missingGroupTxIds)->get();

        // --- Class 3: ghost rows from the old sales-lead transition bug
        $ghostSalesLeadTx = FinancialTransaction::whereNull('production_order_id')
            ->whereNull('production_financial_group_id')
            ->get();

        // --- Report
        $this->line('Class 1 — soft-deleted tabs still owning transactions (recoverable):');
        if ($softDeletedGroups->isEmpty()) {
            $this->line('  (none)');
        } else {
            $rows = $softDeletedGroups->map(fn ($g) => [
                'group_id'   => $g->id,
                'name'       => $g->name ?: '(unnamed)',
                'deleted_at' => optional($g->deleted_at)->format('Y-m-d H:i'),
                'total_tx'   => $g->transactions_count,
                'paid/partial' => $g->paid_transactions_count,
                'pending'    => $g->transactions_count - $g->paid_transactions_count,
            ])->all();
            $this->table(array_keys($rows[0]), $rows);
        }

        $this->line('');
        $this->line('Class 2 — transactions whose group row is missing (only cleanup possible):');
        if ($missingGroupTx->isEmpty()) {
            $this->line('  (none)');
        } else {
            $rows = $missingGroupTx->map(fn ($t) => [
                'tx_id'        => $t->id,
                'group_id_ref' => $t->production_financial_group_id,
                'type'         => $t->type,
                'amount'       => $t->amount,
                'status'       => $t->status,
                'due_date'     => optional($t->due_date)->format('Y-m-d'),
            ])->all();
            $this->table(array_keys($rows[0]), $rows);
        }

        $this->line('');
        $this->line('Class 3 — ghost transactions from the old sales-lead quotation-conversion bug (only cleanup possible):');
        if ($ghostSalesLeadTx->isEmpty()) {
            $this->line('  (none)');
        } else {
            $rows = $ghostSalesLeadTx->map(fn ($t) => [
                'tx_id'    => $t->id,
                'amount'   => $t->amount,
                'status'   => $t->status,
                'notes'    => \Illuminate\Support\Str::limit($t->notes, 40),
                'created_at' => optional($t->created_at)->format('Y-m-d H:i'),
            ])->all();
            $this->table(array_keys($rows[0]), $rows);
        }

        // --- Action mode
        if (!$restoreTabs && !$purgeTx) {
            $this->line('');
            $this->comment('Dry-run only — no changes written.');
            $this->line('Next steps:');
            $this->line('  • Re-run with  --restore-tabs           to bring Class 1 tabs back so their transactions become editable.');
            $this->line('  • Re-run with  --purge-transactions --confirm   to hard-delete Class 2 AND Class 3 transactions (irreversible).');
            return self::SUCCESS;
        }

        if ($restoreTabs) {
            if ($softDeletedGroups->isEmpty()) {
                $this->info('Nothing to restore.');
                return self::SUCCESS;
            }
            $count = 0;
            DB::transaction(function () use ($softDeletedGroups, &$count) {
                foreach ($softDeletedGroups as $g) {
                    $g->restore();
                    $count++;
                }
            });
            $msg = "Restored {$count} finance tab(s). Their transactions are now reachable via the normal UI — you can clear each transaction and then delete the tab through the (now guarded) UI path.";
            $this->info($msg);
            Log::info('finance:fix-orphans restore-tabs — ' . $msg);
            return self::SUCCESS;
        }

        if ($purgeTx) {
            if ($missingGroupTx->isEmpty() && $ghostSalesLeadTx->isEmpty()) {
                $this->info('No Class 2 or Class 3 orphans to purge.');
                return self::SUCCESS;
            }
            $txIds = $missingGroupTx->pluck('id')->merge($ghostSalesLeadTx->pluck('id'))->unique()->values()->all();
            $paymentCount = FinancialPayment::whereIn('financial_transaction_id', $txIds)->count();

            $this->warn('About to hard-delete ' . count($txIds) . " transaction(s) (Class 2 + Class 3) and {$paymentCount} related payment row(s).");
            $this->warn('This is IRREVERSIBLE. Take a database backup first if you have not.');
            if (!$this->confirm('Proceed?', false)) {
                $this->line('Aborted by user.');
                return self::SUCCESS;
            }

            DB::transaction(function () use ($txIds, &$paymentCount) {
                FinancialPayment::whereIn('financial_transaction_id', $txIds)->delete();
                FinancialTransaction::whereIn('id', $txIds)->delete();
            });
            $msg = "Purged " . count($txIds) . " orphan transaction(s) and {$paymentCount} payment row(s).";
            $this->info($msg);
            Log::warning('finance:fix-orphans purge-transactions — ' . $msg . ' ids=' . implode(',', $txIds));
            return self::SUCCESS;
        }

        return self::SUCCESS;
    }
}
