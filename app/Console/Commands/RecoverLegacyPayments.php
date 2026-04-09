<?php

namespace App\Console\Commands;

use App\Models\FinancialTransaction;
use App\Models\FinancialPayment;
use Illuminate\Console\Command;

class RecoverLegacyPayments extends Command
{
    protected $signature = 'finance:recover-legacy-payments {--dry-run : Show what would be created without saving}';
    protected $description = 'Recover legacy payment data from transactions that have paid_amount but no payment records';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        // Find transactions with paid_amount > 0 but no payment records
        $orphans = FinancialTransaction::where('paid_amount', '>', 0)
            ->whereDoesntHave('payments')
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('No legacy payments to recover. All transactions have proper payment records.');
            return 0;
        }

        $this->info("Found {$orphans->count()} legacy transactions to recover:");
        $this->newLine();

        $headers = ['Txn ID', 'Amount', 'Paid', 'Paid At', 'Notes'];
        $rows = [];

        foreach ($orphans as $txn) {
            $rows[] = [
                $txn->id,
                number_format($txn->amount, 2),
                number_format($txn->paid_amount, 2),
                $txn->paid_at ?? $txn->updated_at,
                substr($txn->notes ?? '-', 0, 40),
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN - No changes made. Remove --dry-run to execute.');
            return 0;
        }

        if (!$this->confirm('Create payment records for these legacy transactions?')) {
            $this->info('Cancelled.');
            return 0;
        }

        $created = 0;
        foreach ($orphans as $txn) {
            FinancialPayment::create([
                'financial_transaction_id' => $txn->id,
                'amount' => $txn->paid_amount,
                'paid_at' => $txn->paid_at ?? $txn->updated_at,
                'bank_account_id' => $txn->bank_account_id,
                'slip_path' => $txn->slip_path,
                'notes' => 'Legacy: ยอดชำระจากระบบเก่า (ก่อนอัพเดตระบบบันทึกยอดจ่าย)',
                'created_by' => null,
            ]);
            $created++;
            $this->line("  Created payment for Txn#{$txn->id}: {$txn->paid_amount}");
        }

        $this->newLine();
        $this->info("Successfully recovered {$created} legacy payments.");

        return 0;
    }
}
