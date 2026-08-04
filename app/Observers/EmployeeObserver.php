<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeObserver
{
    /**
     * Statuses that mean "employee is already in some resolution menu".
     * The Observer never touches resolution_tab_id/status when employee is in any of these
     * — auto-pull is one-way (add only). Manual completion/cancellation is the only way out.
     */
    protected const RESOLUTION_STATUSES = [
        'registration_pending', 'registration_completed', 'registration_cancelled',
        'renewal_pending',      'renewal_completed',      'renewal_cancelled',
    ];

    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee)
    {
        // Registration Resolution is manual-add only (via the menu's "เพิ่มลูกจ้าง" form) —
        // new employees must never be auto-pulled in just because their expiry dates
        // happen to match the Auto Setting. Renewal keeps its existing auto-pull behavior.
        $this->syncRenewalStatus($employee);
    }

    /**
     * Handle the Employee "updated" event.
     *
     * @param  \App\Models\Employee  $employee
     * @return void
     */
    public function updated(Employee $employee)
    {
        // 1. Existing Expiry Check Logic
        $fieldsToMonitor = [
            'passportExpiryDate',
            'workPermitExpiryDate',
            'visaExpiryDate',
            'ninetyDayReportDate',
            'insurance_expiry_date',
            'insurance_expiry_date_hospital',
            'insurance_expiry_date_private',
            'pinkCardNo',
            'employee_doc_7', // Residence Permit
            'workPermitMOUGroup',
            'passportType',
        ];

        if ($employee->isDirty($fieldsToMonitor)) {
            try {
                Artisan::queue('app:check-expiries', ['employee_id' => $employee->id]);
            } catch (\Throwable $e) {
                Log::error("Failed to queue check-expiries for employee {$employee->id}: " . $e->getMessage());
            }
        }

        // 2. Auto-pull into renewal menu when expiry dates change.
        // RULE: add-only — never auto-eject, never auto-move between tabs.
        // Once an employee is in a resolution menu, only manual completion/cancellation removes them.
        // Progress within the menu is tracked by getRenewalProgressAttribute (color coding).
        // Registration Resolution is intentionally excluded — it is manual-add only.
        if ($employee->isDirty(['workPermitExpiryDate', 'visaExpiryDate', 'workPermitMOUGroup'])) {
            $this->syncRenewalStatus($employee);
        }

        // 3. Auto-cancel pending notify_out items when the employee moves to a different employer.
        // Rationale: notify_out from old employer is no longer relevant if the employee has been
        // transferred to a new employer (via change-employer workflow, sales transition, manual edit,
        // or any other path). The notify_out card represents leaving the OLD employer — once the
        // employee is under a NEW employer, that card should disappear from the notify_out menu.
        if ($employee->isDirty('employer_id')) {
            $this->autoCancelStaleNotifyOuts($employee);
        }

        // 4. Bump activity timestamp on all non-finalized production_items for this employee.
        // ProductionItem::$touches = ['order'] cascades to bump the parent order's updated_at,
        // which surfaces the employer card to the top of Pre-Prod / Workflow / Registration /
        // Renewal menus on the next page load (no realtime push — purely on next refresh).
        // NOTE: in the `updated` event, isDirty() returns false because attributes are already
        // synced. We use getChanges() (the post-save diff) and skip pure-timestamp changes to
        // avoid self-feedback loops.
        $changedKeys = array_keys($employee->getChanges());
        $meaningfulChanges = array_diff($changedKeys, ['updated_at', 'created_at']);
        if (!empty($meaningfulChanges)) {
            $this->bumpRelatedProductionItems($employee);
        }
    }

    /**
     * Touch (update updated_at on) every non-finalized ProductionItem belonging to this employee,
     * plus their parent ProductionOrder. Mass updates bypass $touches, so we update both tables
     * explicitly in one shot — fast even when an employee belongs to many orders.
     */
    protected function bumpRelatedProductionItems(Employee $employee): void
    {
        try {
            $now = now();
            $orderIds = \App\Models\ProductionItem::where('employee_id', $employee->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->pluck('production_order_id')
                ->unique()
                ->values();

            if ($orderIds->isEmpty()) return;

            \App\Models\ProductionItem::where('employee_id', $employee->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->update(['updated_at' => $now]);

            \App\Models\ProductionOrder::whereIn('id', $orderIds)
                ->update(['updated_at' => $now]);
        } catch (\Throwable $e) {
            Log::warning("bumpRelatedProductionItems failed for employee {$employee->id}: " . $e->getMessage());
        }
    }

    /**
     * Cancel any pending (not completed / not cancelled) notify_out items for this employee.
     * Triggered when employee.employer_id changes — the old notify_out card is stale.
     */
    protected function autoCancelStaleNotifyOuts(Employee $employee): void
    {
        try {
            // Find pending notify_out items for this employee where the order belongs to a DIFFERENT employer
            // than the one the employee now belongs to (i.e. the "from" employer of the notify_out).
            \App\Models\ProductionItem::where('employee_id', $employee->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereHas('order', function ($q) use ($employee) {
                    $q->where('status', '!=', 'cancelled')
                      ->whereHas('workType', fn($wt) => $wt->where('slug', 'notify_out'));
                    // Only cancel items whose order employer != employee's new employer
                    // (otherwise the notify_out is still happening from the same employer)
                    if ($employee->employer_id) {
                        $q->where('employer_id', '!=', $employee->employer_id);
                    }
                })
                ->update([
                    'status' => 'cancelled',
                    'remarks' => DB::raw("CONCAT(COALESCE(remarks, ''), ' [Auto-cancelled: employee moved to another employer on " . now()->format('Y-m-d H:i') . "]')"),
                ]);
        } catch (\Throwable $e) {
            Log::warning("autoCancelStaleNotifyOuts failed for employee {$employee->id}: " . $e->getMessage());
        }
    }

    /**
     * Iterate per-tab Auto Settings of a resolution group and return the first tab
     * whose visa OR work-permit target date matches the employee.
     *
     * Per-tab keys: {group}_auto_visa_expiry__tab_{id} / {group}_auto_work_permit_expiry__tab_{id}
     * Each tab matched independently — the first tab with a matching field wins.
     */
    protected function findMatchingTabByAutoSettings(Employee $employee, string $group): ?int
    {
        $rows = \App\Models\SystemSetting::where('group', $group)
            ->where(function ($q) use ($group) {
                $q->where('key', 'like', "{$group}_auto_visa_expiry__tab_%")
                  ->orWhere('key', 'like', "{$group}_auto_work_permit_expiry__tab_%");
            })
            ->get();

        // Group settings by tab ID: ['2' => ['visa' => '...', 'wp' => '...'], ...]
        $byTab = [];
        $visaPrefix = "{$group}_auto_visa_expiry__tab_";
        $wpPrefix   = "{$group}_auto_work_permit_expiry__tab_";

        foreach ($rows as $row) {
            $key = $row->key;
            if (str_starts_with($key, $visaPrefix)) {
                $tabId = (int) substr($key, strlen($visaPrefix));
                $byTab[$tabId]['visa'] = $row->value;
            } elseif (str_starts_with($key, $wpPrefix)) {
                $tabId = (int) substr($key, strlen($wpPrefix));
                $byTab[$tabId]['wp'] = $row->value;
            }
        }

        if (empty($byTab)) return null;

        // Validate which tabs still exist (not soft-deleted) — single query
        $existingTabIds = \App\Models\ResolutionTab::where('type', $group)
            ->whereIn('id', array_keys($byTab))
            ->pluck('id')
            ->all();

        $empWp = $employee->workPermitExpiryDate ? $employee->workPermitExpiryDate->format('Y-m-d') : null;
        $empVisa = $employee->visaExpiryDate ? $employee->visaExpiryDate->format('Y-m-d') : null;

        // Iterate in stable order so behavior is deterministic when multiple tabs match
        ksort($byTab);
        foreach ($byTab as $tabId => $targets) {
            if (!in_array($tabId, $existingTabIds, true)) continue;

            $wpMatch = !empty($targets['wp']) && $empWp === $targets['wp'];
            $visaMatch = !empty($targets['visa']) && $empVisa === $targets['visa'];
            if ($wpMatch || $visaMatch) {
                return $tabId;
            }
        }

        return null;
    }

    /**
     * Find the renewal tab matching this employee.
     * Priority:
     *  1. Per-tab Auto Settings (SystemSetting renewal_auto_*__tab_{id}) — single source of truth
     *  2. Legacy SystemConfig per-tab (renewal_target_expiry_date_{id}) — old code-path
     *  3. Legacy global SystemConfig (renewal_target_expiry_date) → default tab
     */
    protected function findMatchingRenewalTab(Employee $employee): ?int
    {
        // Skip MOU types — they don't qualify for renewal
        if ($employee->workPermitMOUGroup && stripos($employee->workPermitMOUGroup, 'MOU') !== false) {
            return null;
        }

        // 1. New source of truth — per-tab SystemSetting
        $matchedTabId = $this->findMatchingTabByAutoSettings($employee, 'renewal');
        if ($matchedTabId) return $matchedTabId;

        // 2. Legacy per-tab SystemConfig (single date used for both visa & wp)
        $tabConfigs = SystemConfig::where('key', 'like', 'renewal_target_expiry_date_%')->get();
        foreach ($tabConfigs as $config) {
            $tabId = (int) str_replace('renewal_target_expiry_date_', '', $config->key);
            $targetDate = $config->value;
            if (!$tabId || !$targetDate) continue;

            $tabExists = \App\Models\ResolutionTab::where('id', $tabId)->where('type', 'renewal')->exists();
            if (!$tabExists) continue;

            $wpMatch = $employee->workPermitExpiryDate && $employee->workPermitExpiryDate->format('Y-m-d') === $targetDate;
            $visaMatch = $employee->visaExpiryDate && $employee->visaExpiryDate->format('Y-m-d') === $targetDate;
            if ($wpMatch || $visaMatch) {
                return $tabId;
            }
        }

        // 3. Legacy global SystemConfig
        $legacyDate = SystemConfig::where('key', 'renewal_target_expiry_date')->value('value');
        if ($legacyDate) {
            $wpMatch = $employee->workPermitExpiryDate && $employee->workPermitExpiryDate->format('Y-m-d') === $legacyDate;
            $visaMatch = $employee->visaExpiryDate && $employee->visaExpiryDate->format('Y-m-d') === $legacyDate;
            if ($wpMatch || $visaMatch) {
                return \App\Models\ResolutionTab::where('type', 'renewal')
                    ->where('is_default', true)
                    ->value('id');
            }
        }

        return null;
    }

    /**
     * Sync employee into the renewal menu when their dates newly match a renewal target.
     *
     * RULE (add-only):
     *  - If the employee is already in ANY resolution menu (registration/renewal — pending or finalized)
     *    → do nothing. Progress/color is handled by getRenewalProgressAttribute.
     *  - Otherwise, if dates match a renewal tab target → pull into that tab.
     *
     * Never auto-eject, never auto-move between tabs.
     */
    protected function syncRenewalStatus(Employee $employee)
    {
        try {
            if (in_array($employee->status, self::RESOLUTION_STATUSES, true)) {
                return; // already in some resolution menu — leave alone
            }

            $matchedTabId = $this->findMatchingRenewalTab($employee);
            if ($matchedTabId) {
                $employee->updateQuietly([
                    'status' => 'renewal_pending',
                    'resolution_tab_id' => $matchedTabId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Failed renewal sync for employee {$employee->id}: " . $e->getMessage());
        }
    }
}
