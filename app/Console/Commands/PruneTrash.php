<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionItem;
use App\Models\SystemSetting;
use Carbon\Carbon;

class PruneTrash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workflow:prune-trash';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune soft-deleted items older than the retention period.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $setting = SystemSetting::where('key', 'trash_retention_days')->first();
        $days = $setting ? (int) $setting->value : 30; // Default 30 days

        if ($days <= 0) {
            $this->info('Trash retention is set to Forever (0). No items pruned.');
            return;
        }

        $cutoff = Carbon::now()->subDays($days);

        $count = ProductionItem::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->count();

        if ($count > 0) {
            $this->info("Pruning {$count} items deleted before {$cutoff->toDateTimeString()}...");
            ProductionItem::onlyTrashed()
                ->where('deleted_at', '<', $cutoff)
                ->forceDelete();
            $this->info('Done.');
        } else {
            $this->info('No items to prune.');
        }
    }
}
