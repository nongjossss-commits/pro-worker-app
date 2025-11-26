<?php

namespace App\Observers;

use App\Models\Employee;
use Illuminate\Support\Facades\Artisan;

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
            // Queue the command to run asynchronously in the background.
            // This prevents blocking the user's request and causing timeouts.
            Artisan::queue('app:check-expiries');
        }
    }
}
