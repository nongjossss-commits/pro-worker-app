<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\Notification;
use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class EmployeeObserver
{
    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee): void
    {
        $this->checkMissingData($employee);
    }

    /**
     * Handle the Employee "updated" event.
     */
    public function updated(Employee $employee): void
    {
        $this->checkMissingData($employee);

        // Existing date expiry logic
        $dateFields = [
            'passportExpiryDate',
            'workPermitExpiryDate',
            'visaExpiryDate',
            'ninetyDayReportDate',
            'insurance_expiry_date',
            'insurance_expiry_date_hospital',
            'insurance_expiry_date_private',
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
            Artisan::call('app:check-expiries');
        }
    }

    /**
     * Check for missing Pink Card or Residence Data and create/delete notifications.
     */
    private function checkMissingData(Employee $employee): void
    {
        $this->checkPinkCardMissing($employee);
        $this->checkResidencePermitMissing($employee);
    }

    private function checkPinkCardMissing(Employee $employee): void
    {
        $type = 'pink_card_missing';

        // Check if setting is enabled
        $setting = NotificationSetting::where('notification_type', $type)->first();
        if (!$setting || !$setting->is_enabled) {
            // If disabled, ensure no notification exists
            Notification::where('employee_id', $employee->id)->where('type', $type)->delete();
            return;
        }

        // Logic: Missing if PinkCardNo is missing OR Pink Card File (employee_doc_4) is missing
        // Note: employee_doc_4 is stored as path string
        $isMissing = empty($employee->pinkCardNo) || empty($employee->employee_doc_4);

        if ($isMissing) {
            Notification::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'type' => $type,
                ],
                [
                    'employer_id' => $employee->employer_id,
                    'due_date' => null, // Not applicable
                    'status' => 'unread',
                    'days_remaining' => 0, // Not applicable, but useful for sorting if needed
                    'message' => 'ข้อมูลบัตรชมพูไม่ครบถ้วน', // Default message
                ]
            );
        } else {
            Notification::where('employee_id', $employee->id)->where('type', $type)->delete();
        }
    }

    private function checkResidencePermitMissing(Employee $employee): void
    {
        $type = 'residence_permit_missing';

        // Check if setting is enabled
        $setting = NotificationSetting::where('notification_type', $type)->first();
        if (!$setting || !$setting->is_enabled) {
            // If disabled, ensure no notification exists
            Notification::where('employee_id', $employee->id)->where('type', $type)->delete();
            return;
        }

        // Logic: Missing if Residence File (employee_doc_7) is missing
        $isMissing = empty($employee->employee_doc_7);

        if ($isMissing) {
             Notification::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'type' => $type,
                ],
                [
                    'employer_id' => $employee->employer_id,
                    'due_date' => null,
                    'status' => 'unread',
                    'days_remaining' => 0,
                    'message' => 'เอกสารแจ้งที่พักอาศัยไม่ครบถ้วน', // Default message
                ]
            );
        } else {
            Notification::where('employee_id', $employee->id)->where('type', $type)->delete();
        }
    }
}
