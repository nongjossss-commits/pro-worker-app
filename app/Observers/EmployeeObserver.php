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
     * Find the renewal tab matching this employee.
     *
     * Single source of truth for INBOUND auto-pull: per-tab SystemConfig
     * `renewal_target_expiry_date_{tabId}`, written by the "Configuration /
     * Import by Expiry" button (RenewalController::configureExpiry()).
     * That button both (a) immediately pulls in every currently-matching
     * employee, and (b) leaves this value in place so any employee whose
     * dates later change to match gets pulled in automatically too.
     *
     * IMPORTANT — this is deliberately NOT the same setting as "Auto
     * Setting" (SystemSetting `renewal_auto_visa_expiry__tab_{id}` /
     * `renewal_auto_work_permit_expiry__tab_{id}`). That one is OUTBOUND
     * only: `App\Console\Commands\UpdateResolutionData` reads it to push a
     * target date onto an employee 24h after they're marked finished in
     * this tab. It must never drive inbound matching — confirmed with the
     * user 2026-08-11 after finding the code had conflated the two (a
     * previous fix mistakenly made Auto Setting the inbound source, since
     * its own code comment called it "the new source of truth" — it never
     * was; the two settings serve opposite directions and must stay separate).
     *
     * Tab-existence check (below) prevents the original bug from recurring:
     * a `renewal_target_expiry_date_{tabId}` row surviving after its tab is
     * deleted must never match anyone. On top of that, tab deletion now
     * also deletes this row directly (see ResolutionTabController::forceDelete()
     * and PurgeDeletedResolutionTabs) so it doesn't linger as orphaned data
     * at all once the 7-day cooldown passes.
     */
    protected function findMatchingRenewalTab(Employee $employee): ?int
    {
        // Skip MOU types — they don't qualify for renewal
        if ($employee->workPermitMOUGroup && stripos($employee->workPermitMOUGroup, 'MOU') !== false) {
            return null;
        }

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
