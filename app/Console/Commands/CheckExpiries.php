<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Notification;
use App\Models\NotificationSetting;
use Carbon\Carbon;

class CheckExpiries extends Command
{
    protected $signature = 'app:check-expiries';
    protected $description = 'Check for expiring/expired employee documents and create/update notifications.';

    public function handle()
    {
        $this->info('Starting expiry check process...');
        $today = now()->startOfDay();

        $this->info('Loading notification settings...');
        $settings = NotificationSetting::all()->keyBy('notification_type');

        // Clean up very old notifications (older than 1 year)
        $this->info('Cleaning up old notifications...');
        Notification::where('due_date', '<', $today->copy()->subYear())->delete();

        // This clean slate approach is inefficient for large datasets.
        // It's being replaced by more targeted checks.
        // Notification::where('status', '!=', 'cancelled')->delete();

        $documentChecks = [
            'passportExpiryDate'   => 'passport_expiry',
            'workPermitExpiryDate' => 'work_permit_expiry',
            'visaExpiryDate'       => 'visa_expiry',
            'ninetyDayReportDate'  => 'ninety_day_report',
        ];

        foreach ($documentChecks as $dateField => $notificationType) {
            $this->info("Checking: {$notificationType}...");

            // Determine the maximum look-ahead period for the initial database query
            $maxDays = 0;
            if ($notificationType === 'passport_expiry') {
                $maxDays = max(
                    $settings->get('passport_expiry')->days_before_expiry ?? 60,
                    $settings->get('ci_renewal')->days_before_expiry ?? 60
                );
            } elseif ($notificationType === 'work_permit_expiry') {
                $maxDays = max(
                    $settings->get('work_permit_mou')->days_before_expiry ?? 60,
                    $settings->get('resolution_renewal')->days_before_expiry ?? 60,
                    $settings->get('new_registration_renewal')->days_before_expiry ?? 60
                );
            } else {
                $maxDays = $settings->get($notificationType)->days_before_expiry ?? 60;
            }

            $pastThreshold = $today->copy()->subDays(365);
            $futureThreshold = $today->copy()->addDays($maxDays);

            $employees = Employee::whereNotNull($dateField)
                ->whereBetween($dateField, [$pastThreshold, $futureThreshold])
                ->get();

            $this->info("Found {$employees->count()} employees for field [{$dateField}] within a {$maxDays}-day threshold.");

            foreach ($employees as $employee) {
                if ($employee->is_cancelled ?? false) {
                    continue;
                }

                $expiryDate = Carbon::parse($employee->{$dateField});
                $daysRemaining = $today->diffInDays($expiryDate, false);
                $currentNotificationType = $notificationType;

                // Determine the specific notification type based on employee data
                if ($notificationType === 'work_permit_expiry') {
                     if ($employee->workPermitMOUGroup === 'MOU') {
                        $currentNotificationType = 'work_permit_mou';
                    } elseif ($employee->workPermitMOUGroup === 'มติต่ออายุในประเทศ') {
                        $currentNotificationType = 'resolution_renewal';
                    } elseif ($employee->workPermitMOUGroup === 'มติขึ้นทะเบียน') {
                        $currentNotificationType = 'new_registration_renewal';
                    }
                }

                if ($notificationType === 'passport_expiry' && $employee->passportType === 'CI') {
                    $currentNotificationType = 'ci_renewal';
                }

                // Get the specific threshold for the determined notification type
                $setting = $settings->get($currentNotificationType);

                // Only create a notification if the setting is enabled and the expiry is within the specific threshold
                if ($setting && $setting->is_enabled && $setting->days_before_expiry !== null && $daysRemaining <= $setting->days_before_expiry) {
                    Notification::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'type'        => $currentNotificationType,
                        ],
                        [
                            'due_date'       => $expiryDate,
                            'days_remaining' => $daysRemaining,
                            'status'         => 'unread',
                            'message'        => "เอกสารจะหมดอายุใน {$daysRemaining} วัน (ตั้งค่าแจ้งเตือน: {$setting->days_before_expiry} วัน)",
                        ]
                    );
                }
            }
        }

        $this->info('Finished checking for expiring documents.');

        $this->info('Checking for expiring employer documents...');
        $this->checkEmployerDocumentExpiries($today, $settings);

        $this->info('Checking for expiring employee insurances...');
        $this->checkEmployeeInsuranceExpiries($today, $settings);

        $this->info('Checking for missing Pink Cards...');
        $this->checkPinkCardMissing($today, $settings);

        $this->info('Checking for missing Residence Notifications...');
        $this->checkResidencePermitMissing($today, $settings);

        $this->info('Expiry check process finished.');
        return 0;
    }

    protected function checkEmployerDocumentExpiries($today, $settings)
    {
        $notificationType = 'employer_document_expiry';
        $setting = $settings->get($notificationType);

        if (!$setting || !$setting->is_enabled) {
            $this->info("Skipping {$notificationType} (disabled or settings missing).");
            return;
        }

        $threshold = $setting->days_before_expiry;
        $futureThreshold = $today->copy()->addDays($threshold);

        $employers = Employer::whereNotNull('employer_doc_company_expiry')
            ->whereBetween('employer_doc_company_expiry', [$today, $futureThreshold])
            ->get();

        $this->info("Found {$employers->count()} employers with expiring documents within a {$threshold}-day threshold.");

        foreach ($employers as $employer) {
            $expiryDate = Carbon::parse($employer->employer_doc_company_expiry);
            $daysRemaining = $today->diffInDays($expiryDate, false);

            if ($daysRemaining <= $threshold) {
                Notification::updateOrCreate(
                    [
                        'employer_id' => $employer->id,
                        'type' => $notificationType,
                    ],
                    [
                        'employee_id' => null, // Explicitly set employee_id to null
                        'due_date' => $expiryDate,
                        'days_remaining' => $daysRemaining,
                        'status' => 'unread',
                        'message' => "เอกสารบริษัทของนายจ้างจะหมดอายุใน {$daysRemaining} วัน",
                    ]
                );
            }
        }
    }

    protected function checkEmployeeInsuranceExpiries($today, $settings)
    {
        $notificationType = 'employee_insurance_expiry';
        $setting = $settings->get($notificationType);

        if (!$setting || !$setting->is_enabled) {
            $this->info("Skipping {$notificationType} (disabled or settings missing).");
            return;
        }

        $threshold = $setting->days_before_expiry;
        $futureThreshold = $today->copy()->addDays($threshold);
        $insuranceFields = ['insurance_expiry_date', 'insurance_expiry_date_hospital', 'insurance_expiry_date_private'];

        foreach ($insuranceFields as $field) {
            $employees = Employee::whereNotNull($field)
                ->where('insurance_type', '!=', 'ประกันสังคม')
                ->whereBetween($field, [$today, $futureThreshold])
                ->get();

            $this->info("Found {$employees->count()} employees with expiring insurance (field: {$field}) within a {$threshold}-day threshold.");

            foreach ($employees as $employee) {
                $expiryDate = Carbon::parse($employee->{$field});
                $daysRemaining = $today->diffInDays($expiryDate, false);

                if ($daysRemaining <= $threshold) {
                    Notification::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'type' => $notificationType,
                        ],
                        [
                            'due_date' => $expiryDate,
                            'days_remaining' => $daysRemaining,
                            'status' => 'unread',
                            'message' => "ประกันของลูกจ้างจะหมดอายุใน {$daysRemaining} วัน",
                        ]
                    );
                }
            }
        }
    }

    protected function checkPinkCardMissing($today, $settings)
    {
        $notificationType = 'pink_card_missing';
        $setting = $settings->get($notificationType);

        if (!$setting || !$setting->is_enabled) {
            $this->info("Deleting disabled notification type: {$notificationType}...");
            Notification::where('type', $notificationType)->delete();
            return;
        }

        $this->info("Processing: {$notificationType}");

        // Get IDs of employees who are missing the pink card and are not terminated
        $employeeIdsWithMissingDoc = Employee::where(function ($query) {
            $query->whereNull('pinkCardNo')->orWhere('pinkCardNo', '=', '');
        })
        ->whereNull('terminated_at')
        ->pluck('id');

        // Get IDs of employees who already have a notification for this type
        $employeeIdsWithNotification = Notification::where('type', $notificationType)->pluck('employee_id');

        // Determine who needs a notification (in the first list, but not the second)
        $idsToCreate = $employeeIdsWithMissingDoc->diff($employeeIdsWithNotification);

        // Determine which notifications are outdated and should be removed
        $idsToDelete = $employeeIdsWithNotification->diff($employeeIdsWithMissingDoc);

        if ($idsToDelete->isNotEmpty()) {
            $this->info("Deleting {$idsToDelete->count()} outdated '{$notificationType}' notifications.");
            Notification::where('type', $notificationType)->whereIn('employee_id', $idsToDelete)->delete();
        }

        if ($idsToCreate->isNotEmpty()) {
            $this->info("Creating {$idsToCreate->count()} new '{$notificationType}' notifications.");
            $newNotifications = [];
            $now = now();
            foreach ($idsToCreate as $employeeId) {
                $newNotifications[] = [
                    'employee_id' => $employeeId,
                    'employer_id' => null,
                    'type' => $notificationType,
                    'due_date' => $today,
                    'days_remaining' => 0,
                    'status' => 'unread',
                    'message' => "ยังไม่มีข้อมูลบัตรชมพู",
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            // Use insert for bulk creation
            Notification::insert($newNotifications);
        }

        $this->info("Finished processing for {$notificationType}.");
    }

    protected function checkResidencePermitMissing($today, $settings)
    {
        $notificationType = 'residence_permit_missing';
        $setting = $settings->get($notificationType);

        if (!$setting || !$setting->is_enabled) {
            $this->info("Deleting disabled notification type: {$notificationType}...");
            Notification::where('type', $notificationType)->delete();
            return;
        }

        $this->info("Processing: {$notificationType}");

        // Get IDs of employees who are missing the residence permit and are not terminated
        $employeeIdsWithMissingDoc = Employee::whereNull('employee_doc_7')
            ->whereNull('terminated_at')
            ->pluck('id');

        // Get IDs of employees who already have a notification for this type
        $employeeIdsWithNotification = Notification::where('type', $notificationType)->pluck('employee_id');

        // Determine who needs a notification
        $idsToCreate = $employeeIdsWithMissingDoc->diff($employeeIdsWithNotification);

        // Determine which notifications to remove
        $idsToDelete = $employeeIdsWithNotification->diff($employeeIdsWithMissingDoc);

        if ($idsToDelete->isNotEmpty()) {
            $this->info("Deleting {$idsToDelete->count()} outdated '{$notificationType}' notifications.");
            Notification::where('type', $notificationType)->whereIn('employee_id', $idsToDelete)->delete();
        }

        if ($idsToCreate->isNotEmpty()) {
            $this->info("Creating {$idsToCreate->count()} new '{$notificationType}' notifications.");
            $newNotifications = [];
            $now = now();
            foreach ($idsToCreate as $employeeId) {
                $newNotifications[] = [
                    'employee_id' => $employeeId,
                    'employer_id' => null,
                    'type' => $notificationType,
                    'due_date' => $today,
                    'days_remaining' => 0,
                    'status' => 'unread',
                    'message' => "ยังไม่มีเอกสารแจ้งที่พักอาศัย",
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            Notification::insert($newNotifications);
        }

        $this->info("Finished processing for {$notificationType}.");
    }
}
