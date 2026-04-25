<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class EmployeeObserver
{
    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee)
    {
        $this->checkRenewalAutoImport($employee);
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

        // 2. Renewal Auto-Sync Logic
        // When expiry dates or MOU change: add/reassign/remove based on matching tabs
        if ($employee->isDirty(['workPermitExpiryDate', 'visaExpiryDate', 'workPermitMOUGroup'])) {
            $this->syncRenewalStatus($employee);
        }
    }

    /**
     * Find the renewal tab (if any) whose target expiry date matches the employee's WP or Visa.
     * Returns tab ID or null.
     */
    protected function findMatchingRenewalTab(Employee $employee): ?int
    {
        // Skip MOU types — they don't qualify for renewal
        if ($employee->workPermitMOUGroup && stripos($employee->workPermitMOUGroup, 'MOU') !== false) {
            return null;
        }

        // 1. Per-tab configs
        $tabConfigs = SystemConfig::where('key', 'like', 'renewal_target_expiry_date_%')->get();

        foreach ($tabConfigs as $config) {
            $tabId = (int) str_replace('renewal_target_expiry_date_', '', $config->key);
            $targetDate = $config->value;
            if (!$tabId || !$targetDate) continue;

            // Verify tab still exists (not soft-deleted)
            $tabExists = \App\Models\ResolutionTab::where('id', $tabId)->where('type', 'renewal')->exists();
            if (!$tabExists) continue;

            $wpMatch = $employee->workPermitExpiryDate && $employee->workPermitExpiryDate->format('Y-m-d') === $targetDate;
            $visaMatch = $employee->visaExpiryDate && $employee->visaExpiryDate->format('Y-m-d') === $targetDate;
            if ($wpMatch || $visaMatch) {
                return $tabId;
            }
        }

        // 2. Legacy fallback — global key (old data, pre-tab era)
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
     * Sync employee's renewal status based on current expiry dates.
     * - Match found: add to tab / reassign to different tab
     * - No match: remove from renewal (back to 'active')
     * - Finalized (completed/cancelled): never touched
     */
    protected function syncRenewalStatus(Employee $employee)
    {
        try {
            // Preserve finalized states
            if (in_array($employee->status, ['renewal_completed', 'renewal_cancelled'])) {
                return;
            }

            $matchedTabId = $this->findMatchingRenewalTab($employee);

            // --- Case 1: Match found ---
            if ($matchedTabId) {
                $needsUpdate = ($employee->resolution_tab_id !== $matchedTabId)
                            || ($employee->status !== 'renewal_pending');

                if ($needsUpdate) {
                    $updateData = ['resolution_tab_id' => $matchedTabId];
                    if ($employee->status !== 'renewal_pending') {
                        $updateData['status'] = 'renewal_pending';
                    }
                    $employee->updateQuietly($updateData);
                }
                return;
            }

            // --- Case 2: No match, and employee is currently renewal_pending → remove ---
            if ($employee->status === 'renewal_pending') {
                $employee->updateQuietly([
                    'status' => 'active',
                    'resolution_tab_id' => null,
                ]);
            }

        } catch (\Throwable $e) {
            Log::error("Failed renewal sync for employee {$employee->id}: " . $e->getMessage());
        }
    }

    /**
     * Legacy alias — kept for backward compatibility with `created` event.
     */
    protected function checkRenewalAutoImport(Employee $employee)
    {
        $this->syncRenewalStatus($employee);
    }
}
