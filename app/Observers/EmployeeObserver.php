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

        // 2. Renewal Auto-Import Logic
        // Check if expiry dates changed OR if status changed (to avoid loops, we check specific fields)
        if ($employee->isDirty(['workPermitExpiryDate', 'visaExpiryDate', 'workPermitMOUGroup'])) {
            $this->checkRenewalAutoImport($employee);
        }
    }

    /**
     * Check if employee matches the Renewal Target Date and auto-import.
     */
    protected function checkRenewalAutoImport(Employee $employee)
    {
        try {
            // 1. Check if configured
            $targetDate = SystemConfig::where('key', 'renewal_target_expiry_date')->value('value');
            if (!$targetDate) return;

            // 2. Check Expiry Match
            $matches = false;
            // Check Work Permit
            if ($employee->workPermitExpiryDate && $employee->workPermitExpiryDate->format('Y-m-d') === $targetDate) {
                $matches = true;
            }
            // Check Visa
            if (!$matches && $employee->visaExpiryDate && $employee->visaExpiryDate->format('Y-m-d') === $targetDate) {
                $matches = true;
            }

            if (!$matches) return;

            // 3. Check Exclusion (MOU)
            if ($employee->workPermitMOUGroup && stripos($employee->workPermitMOUGroup, 'MOU') !== false) {
                return;
            }

            // 4. Check Status safety (Don't overwrite existing resolution status unless it's null or active?)
            // Or just force it? The requirement says "system will check 100% sync".
            // Let's protect completed/cancelled/pending of OTHER types if possible, but
            // for now, if it's not already in Renewal process, add it.
            if (in_array($employee->status, ['renewal_pending', 'renewal_completed', 'renewal_cancelled'])) {
                return; // Already tracked
            }

            // Update status
            $employee->updateQuietly(['status' => 'renewal_pending']);

        } catch (\Throwable $e) {
            Log::error("Failed auto-import renewal for employee {$employee->id}: " . $e->getMessage());
        }
    }
}
