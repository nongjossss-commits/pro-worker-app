<?php

namespace App\Observers;

use App\Models\Employee;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class EmployeeObserver
{
    /**
     * Handle the Employee "updated" event.
     */
    public function updated(Employee $employee): void
    {
        $dateFields = [
            'passportExpiryDate',
            'workPermitExpiryDate',
            'visaExpiryDate',
            'ninetyDayReportDate',
        ];

        $wasChanged = false;
        foreach ($dateFields as $field) {
            if ($employee->isDirty($field)) {
                $wasChanged = true;
                break;
            }
        }

        if ($wasChanged) {
            Log::info("Expiry date changed for employee ID: {$employee->id}. Triggering notification re-check.");
            // We can call the command directly.
            // For a single employee, this is still very fast and ensures all logic is re-evaluated.
            Artisan::call('app:check-expiries');
        }
    }
}
