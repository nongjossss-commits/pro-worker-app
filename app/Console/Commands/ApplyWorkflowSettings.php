<?php

namespace App\Console\Commands;

use App\Models\ProductionItem;
use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Per-WorkType MOU auto-apply for the Workflow menu.
 *
 * After a user clicks "Finish" on a workflow item, we wait 24 hours
 * (the safety window — users can `restoreItem` to undo within that
 * period) and then push the admin-configured MOU group (and, if set,
 * the work-permit expiry) onto the linked employee. The 24-hour delay
 * is the whole point of this command — it's the difference between
 * the Workflow menu (kept) and the Registration/Renewal Resolution
 * menus (dropped in favour of the immediate 4-colour progression).
 *
 * Settings live in SystemSetting under group='workflow' with a key
 * suffixed by the work_type_id, so each Workflow tab can have its
 * own auto-MOU value (e.g. "MOU Renewal" → "MOU 2 ปีหลัง"):
 *   workflow_auto_mou_group_{workTypeId}
 *   workflow_auto_work_permit_expiry_{workTypeId}    (optional)
 *
 * Scheduled hourly from routes/console.php.
 */
class ApplyWorkflowSettings extends Command
{
    protected $signature = 'app:apply-workflow-settings';

    protected $description = 'Apply admin-configured MOU group (and optional expiry) to employees whose Workflow item was finalized > 24 hours ago.';

    public function handle(): int
    {
        $this->info('ApplyWorkflowSettings: starting…');

        $items = ProductionItem::with(['employee', 'order'])
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->where('completed_at', '<=', now()->subHours(24))
            ->where(function ($q) {
                $q->whereNull('workflow_settings_applied')
                  ->orWhere('workflow_settings_applied', false);
            })
            ->get();

        if ($items->isEmpty()) {
            $this->info('No workflow items pending settings application.');
            return self::SUCCESS;
        }

        // Pre-load all workflow settings in one shot — keyed by work_type_id
        // for O(1) lookup inside the loop.
        $rows = SystemSetting::where('group', 'workflow')->get();
        $settingsByWorkType = [];
        foreach ($rows as $row) {
            if (preg_match('/^workflow_auto_(mou_group|work_permit_expiry)_(\d+)$/', $row->key, $m)) {
                $field = $m[1] === 'mou_group' ? 'mou_group' : 'wp_expiry';
                $settingsByWorkType[(int) $m[2]][$field] = $row->value;
            }
        }

        $applied = 0;
        $skippedNoSetting = 0;
        $skippedNoEmployee = 0;

        foreach ($items as $item) {
            $workTypeId = optional($item->order)->work_type_id;
            if (!$workTypeId || empty($settingsByWorkType[$workTypeId])) {
                $skippedNoSetting++;
                continue;
            }
            $employee = $item->employee;
            if (!$employee) {
                $skippedNoEmployee++;
                continue;
            }

            $cfg = $settingsByWorkType[$workTypeId];
            $changed = false;

            if (!empty($cfg['mou_group'])) {
                $employee->workPermitMOUGroup = $cfg['mou_group'];
                $changed = true;
            }
            // Expiry is optional — MOU border-crossing dates vary per worker
            // so admins usually leave it blank. Apply only when explicitly set.
            if (!empty($cfg['wp_expiry'])) {
                $employee->workPermitExpiryDate = $cfg['wp_expiry'];
                $changed = true;
            }

            if ($changed) {
                $employee->save();
            }

            // Mark applied regardless of `$changed` so we don't re-check this
            // item every hour for an employee that genuinely has no MOU group
            // to set (otherwise the query keeps re-fetching it forever).
            $item->workflow_settings_applied = true;
            $item->saveQuietly();
            $applied++;
        }

        $msg = sprintf(
            'ApplyWorkflowSettings: applied=%d, skipped_no_setting=%d, skipped_no_employee=%d',
            $applied, $skippedNoSetting, $skippedNoEmployee
        );
        $this->info($msg);
        Log::info($msg);

        return self::SUCCESS;
    }
}
