<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employer;
use App\Models\Agent;
use App\Models\Importer;
use App\Models\Delegate;
use App\Models\Address;

class PruneSoftDeletes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-soft-deletes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete soft-deleted records older than 30 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Pruning soft-deleted records older than 30 days...');

        $cutoffDate = now()->subDays(30);

        // Prune Employers
        $employerCount = Employer::onlyTrashed()->where('deleted_at', '<=', $cutoffDate)->forceDelete();
        $this->info("Pruned $employerCount Employers.");

        // Prune Agents
        $agentCount = Agent::onlyTrashed()->where('deleted_at', '<=', $cutoffDate)->forceDelete();
        $this->info("Pruned $agentCount Agents.");

        // Prune Importers
        $importerCount = Importer::onlyTrashed()->where('deleted_at', '<=', $cutoffDate)->forceDelete();
        $this->info("Pruned $importerCount Importers.");

        // Prune Delegates
        $delegateCount = Delegate::onlyTrashed()->where('deleted_at', '<=', $cutoffDate)->forceDelete();
        $this->info("Pruned $delegateCount Delegates.");

        // Prune Addresses
        $addressCount = Address::onlyTrashed()->where('deleted_at', '<=', $cutoffDate)->forceDelete();
        $this->info("Pruned $addressCount Addresses.");

        $this->info('Soft delete pruning complete.');
        return 0;
    }
}