<?php

namespace App\Console\Commands;

use App\Models\ResolutionTab;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurgeDeletedResolutionTabs extends Command
{
    protected $signature = 'resolution-tabs:purge';
    protected $description = 'Permanently delete resolution tabs that have passed the 7-day cooldown period';

    public function handle()
    {
        $purgeableTabs = ResolutionTab::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays(7))
            ->get();

        if ($purgeableTabs->isEmpty()) {
            $this->info('No tabs to purge.');
            return 0;
        }

        foreach ($purgeableTabs as $tab) {
            $this->info("Purging tab: {$tab->name} (ID: {$tab->id}, type: {$tab->type})");

            DB::transaction(function () use ($tab) {
                // Nullify employee references
                $employeeCount = $tab->employees()->count();
                $tab->employees()->update(['resolution_tab_id' => null]);

                // Delete related data
                $tab->steps()->delete();
                $tab->systemSettings()->delete();
                $tab->notificationSettings()->delete();

                // Legacy "Import by Expiry" SystemConfig row (no FK relation) —
                // see EmployeeObserver::findMatchingRenewalTab() and
                // ResolutionTabController::forceDelete() for the same cleanup.
                \App\Models\SystemConfig::where('key', $tab->type . '_target_expiry_date_' . $tab->id)->delete();

                // Orders that already carry bills are kept (see
                // ResolutionTab::productionOrderIdsWithBills()) so billing /
                // payment history survives and Finance can still collect or
                // credit-note the remaining balance. Only unbilled orders go.
                $billedOrderIds = $tab->productionOrderIdsWithBills();
                $tab->productionOrders()->whereNotIn('id', $billedOrderIds)->forceDelete();
                DB::table('employer_resolution_tab')->where('resolution_tab_id', $tab->id)->delete();

                // Keep the (soft-deleted) tab row itself while any billed order
                // still points at it — it's the only record of which tab those
                // bills came from (production_orders.resolution_tab_id is
                // nullOnDelete). Everything operational above is already gone.
                if (!empty($billedOrderIds)) {
                    Log::info("Resolution tab {$tab->name} (ID: {$tab->id}) kept as archived: " . count($billedOrderIds) . " order(s) have financial records");
                    return;
                }

                // Force delete the tab
                $tab->forceDelete();

                Log::info("Purged resolution tab: {$tab->name} (ID: {$tab->id}), affected {$employeeCount} employees");
            });

            $this->info("  -> Done. Related data cleaned up.");
        }

        $this->info("Purged {$purgeableTabs->count()} tab(s).");
        return 0;
    }
}
