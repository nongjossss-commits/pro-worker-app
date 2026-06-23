<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\Artisan;
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
        // New employee: try renewal first (existing behavior), then registration.
        // If renewal matches, syncRegistrationStatus sees the new status and skips.
        $this->syncRenewalStatus($employee);
        $this->syncRegistrationStatus($employee);
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

        // 2. Auto-pull into renewal / registration menu when expiry dates change.
        // RULE: add-only — never auto-eject, never auto-move between tabs.
        // Once an employee is in a resolution menu, only manual completion/cancellation removes them.
        // Progress within the menu is tracked by getRenewalProgressAttribute (color coding).
        if ($employee->isDirty(['workPermitExpiryDate', 'visaExpiryDate', 'workPermitMOUGroup'])) {
            $this->syncRenewalStatus($employee);
            $this->syncRegistrationStatus($employee);
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
     * Find the default registration tab if employee's WP/Visa matches the registration auto-target.
     * Registration uses a single SystemSetting target (group=registration) — no per-tab config.
     */
    protected function findMatchingRegistrationTab(Employee $employee): ?int
    {
        // Skip MOU types
        if ($employee->workPermitMOUGroup && stripos($employee->workPermitMOUGroup, 'MOU') !== false) {
            return null;
        }

        $settings = \App\Models\SystemSetting::where('group', 'registration')
            ->whereIn('key', ['registration_auto_visa_expiry', 'registration_auto_work_permit_expiry'])
            ->pluck('value', 'key');

        $visaTarget = $settings['registration_auto_visa_expiry'] ?? null;
        $wpTarget   = $settings['registration_auto_work_permit_expiry'] ?? null;

        if (!$visaTarget && !$wpTarget) return null;

        $wpMatch = $wpTarget && $employee->workPermitExpiryDate
            && $employee->workPermitExpiryDate->format('Y-m-d') === $wpTarget;
        $visaMatch = $visaTarget && $employee->visaExpiryDate
            && $employee->visaExpiryDate->format('Y-m-d') === $visaTarget;

        if (!$wpMatch && !$visaMatch) return null;

        // Pick the default tab if present, else the oldest registration tab
        $defaultTab = \App\Models\ResolutionTab::where('type', 'registration')
            ->where('is_default', true)
            ->value('id');
        if ($defaultTab) return $defaultTab;

        return \App\Models\ResolutionTab::where('type', 'registration')
            ->orderBy('id')
            ->value('id');
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

    /**
     * Mirror of syncRenewalStatus for the registration menu.
     * Same add-only rule — only pulls employees who aren't yet in any resolution menu.
     */
    protected function syncRegistrationStatus(Employee $employee)
    {
        try {
            if (in_array($employee->status, self::RESOLUTION_STATUSES, true)) {
                return; // already in some resolution menu — leave alone
            }

            $matchedTabId = $this->findMatchingRegistrationTab($employee);
            if ($matchedTabId) {
                $employee->updateQuietly([
                    'status' => 'registration_pending',
                    'resolution_tab_id' => $matchedTabId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Failed registration sync for employee {$employee->id}: " . $e->getMessage());
        }
    }
}
