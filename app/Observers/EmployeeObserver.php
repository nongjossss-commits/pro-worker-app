<?php

namespace App\Observers;

use App\Models\Employee;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class EmployeeObserver
{
    /**
     * Handle the Employee "updated" event.
     *
     * @param  \App\Models\Employee  $employee
     * @return void
     */
    public function updated(Employee $employee)
    {
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

        // isDirty() checks if any of the given attributes have changed.
        if ($employee->isDirty($fieldsToMonitor)) {
            // By passing the employee's ID, we can run a much faster, targeted check
            // instead of re-checking every single employee in the system.
            // Wrap in try-catch to prevent observer failure from blocking the update
            try {
                Artisan::queue('app:check-expiries', ['employee_id' => $employee->id]);
            } catch (\Throwable $e) {
                Log::error("Failed to queue check-expiries for employee {$employee->id}: " . $e->getMessage());
            }
        }
    }
}
