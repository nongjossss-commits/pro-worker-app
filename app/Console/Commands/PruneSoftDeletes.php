<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employer;
use App\Models\Agent;
use App\Models\Importer;
use App\Models\Delegate;
use App\Models\Address;
use App\Models\Employee;
use App\Models\SystemConfig;

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
    protected $description = 'Permanently delete soft-deleted records based on configured retention days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting soft delete pruning...');

        $models = [
            'employees' => Employee::class,
            'employers' => Employer::class,
            'agents' => Agent::class,
            'importers' => Importer::class,
            'delegates' => Delegate::class,
            'addresses' => Address::class,
        ];

        foreach ($models as $key => $modelClass) {
            // Fetch retention setting from SystemConfig
            // Key format: trash_retention_days_{model_key}
            // Value: integer (days) or null/'forever' (keep forever)
            $configKey = "trash_retention_days_{$key}";
            $retentionDays = SystemConfig::where('key', $configKey)->value('value');

            // If setting is null, 'forever', or <= 0, we skip pruning for this model.
            // This effectively means "Keep Forever" is the default if not configured, preventing accidental data loss.
            if ($retentionDays === null || strtolower($retentionDays) === 'forever' || (int)$retentionDays <= 0) {
                $this->info("Skipping pruning for {$key}. Retention setting: " . ($retentionDays ?? 'Not Set (Keep Forever)'));
                continue;
            }

            $days = (int)$retentionDays;
            $cutoffDate = now()->subDays($days);

            $count = $modelClass::onlyTrashed()->where('deleted_at', '<=', $cutoffDate)->forceDelete();
            $this->info("Pruned {$count} {$key} older than {$days} days.");
        }

        $this->info('Soft delete pruning complete.');
        return 0;
    }
}
