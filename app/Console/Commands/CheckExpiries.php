<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Notification;
use App\Models\NotificationSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CheckExpiries extends Command
{
    protected $signature = 'app:check-expiries {employee_id? : The ID of a specific employee to check}';
    protected $description = 'Check for expiring/expired documents and create notifications. Can optionally check for a single employee.';

    public function handle()
    {
        $employeeId = $this->argument('employee_id');

        if ($employeeId) {
            $this->info("Running targeted expiry check for employee ID: {$employeeId}");
            // Clear out all previous notifications for this employee to get a clean slate.
            Notification::where('employee_id', $employeeId)->delete();
        } else {
            $this->info('Starting full expiry check process for all entities...');
        }

        $today = now()->startOfDay();

        $this->info('Loading notification settings...');
        $settings = NotificationSetting::all()->keyBy('notification_type');

        if (!$employeeId) {
            // Clean up very old notifications (older than 1 year) only on full runs
            $this->info('Cleaning up old notifications...');
            Notification::where('due_date', '<', $today->copy()->subYear())->delete();

            // Strict cleanup: Remove notifications that no longer match the "days_before_expiry" setting
            $this->cleanupOutdatedNotifications($settings, $today);
        }

        // Use withoutGlobalScopes to ensure we check ALL employees in the system,
        // regardless of who triggered the command (e.g. an admin via web interface).
        $baseEmployeeQuery = Employee::withoutGlobalScopes();
        if ($employeeId) {
            $baseEmployeeQuery->where('id', $employeeId);
        }

        $documentChecks = [
            'passportExpiryDate'   => 'passport_expiry',
            'workPermitExpiryDate' => 'work_permit_expiry',
            'visaExpiryDate'       => 'visa_expiry',
            'ninetyDayReportDate'  => 'ninety_day_report',
        ];

        // Mapping to know which actual notification types belong to which check group
        $checkTypeToNotificationTypes = [
            'passport_expiry' => ['passport_expiry', 'ci_renewal'],
            'work_permit_expiry' => ['work_permit_mou', 'resolution_renewal', 'new_registration_renewal'],
            'visa_expiry' => ['visa_expiry'],
            'ninety_day_report' => ['ninety_day_report'],
        ];

        foreach ($documentChecks as $dateField => $notificationType) {
            $this->info("Checking: {$notificationType}...");

            $maxDays = 0;
            // Use optional() to safely access settings, defaulting to 60 days if setting or value is missing
            if ($notificationType === 'passport_expiry') {
                $maxDays = max(
                    optional($settings->get('passport_expiry'))->days_before_expiry ?? 60,
                    optional($settings->get('ci_renewal'))->days_before_expiry ?? 60
                );
            } elseif ($notificationType === 'work_permit_expiry') {
                $maxDays = max(
                    optional($settings->get('work_permit_mou'))->days_before_expiry ?? 60,
                    optional($settings->get('resolution_renewal'))->days_before_expiry ?? 60,
                    optional($settings->get('new_registration_renewal'))->days_before_expiry ?? 60
                );
            } else {
                $maxDays = optional($settings->get($notificationType))->days_before_expiry ?? 60;
            }

            $pastThreshold = $today->copy()->subDays(365);
            $futureThreshold = $today->copy()->addDays($maxDays);

            $employees = (clone $baseEmployeeQuery)
                ->whereNotNull($dateField)
                ->whereBetween($dateField, [$pastThreshold, $futureThreshold])
                ->get();

            $this->info("Found {$employees->count()} employees for field [{$dateField}] within a {$maxDays}-day threshold.");

            $validEmployeeIdsForThisCheck = [];

            foreach ($employees as $employee) {
                if ($employee->is_cancelled ?? false) {
                    continue;
                }

                $expiryDate = Carbon::parse($employee->{$dateField});
                $daysRemaining = $today->diffInDays($expiryDate, false);
                $currentNotificationType = $notificationType;

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

                $setting = $settings->get($currentNotificationType);
                $shouldHaveNotification = $setting &&
                    $setting->is_enabled &&
                    $setting->days_before_expiry !== null &&
                    $daysRemaining <= $setting->days_before_expiry &&
                    $daysRemaining >= -365;

                if ($shouldHaveNotification) {
                    Notification::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'type' => $currentNotificationType,
                        ],
                        [
                            'due_date' => $expiryDate,
                            'days_remaining' => $daysRemaining,
                            'status' => 'unread',
                            'message' => "เอกสารจะหมดอายุใน {$daysRemaining} วัน (ตั้งค่าแจ้งเตือน: {$setting->days_before_expiry} วัน)",
                        ]
                    );

                    // Mark this employee as having a valid notification for this group
                    $validEmployeeIdsForThisCheck[] = $employee->id;

                    // Remove sibling types if they exist (e.g. switched from MOU to Resolution)
                    $siblingTypes = array_diff($checkTypeToNotificationTypes[$notificationType], [$currentNotificationType]);
                    if (!empty($siblingTypes)) {
                        Notification::where('employee_id', $employee->id)
                                    ->whereIn('type', $siblingTypes)
                                    ->delete();
                    }

                } else {
                    // Explicitly remove if settings imply no notification
                    Notification::where('employee_id', $employee->id)
                                  ->where('type', $currentNotificationType)
                                  ->delete();
                }
            }

            // Sync Cleanup:
            // Delete notifications for any employee that is NOT in the valid list.
            // This handles cases where dates were updated to be safe (outside threshold),
            // or the employee was deleted/hidden, or settings changed.
            if (!$employeeId) {
                $typesToClean = $checkTypeToNotificationTypes[$notificationType] ?? [$notificationType];
                if (!empty($typesToClean)) {
                     $deletedCount = Notification::whereIn('type', $typesToClean)
                         ->whereNotIn('employee_id', $validEmployeeIdsForThisCheck)
                         ->delete();
                     if ($deletedCount > 0) {
                         $this->info("Cleaned up {$deletedCount} stale notifications for type group: {$notificationType}.");
                     }
                }
            }
        }

        $this->info('Finished checking for expiring documents.');

        if (!$employeeId) {
            $this->info('Checking for expiring employer documents...');
            $this->checkEmployerDocumentExpiries($today, $settings);
        }

        $this->info('Checking for expiring employee insurances...');
        $this->checkEmployeeInsuranceExpiries($today, $settings, $baseEmployeeQuery);

        $this->info('Checking for missing Pink Cards...');
        $this->checkPinkCardMissing($today, $settings, $baseEmployeeQuery);

        $this->info('Checking for missing Residence Notifications...');
        $this->checkResidencePermitMissing($today, $settings, $baseEmployeeQuery);

        // If this is a full run (not targeted at a specific employee), update the cache
        if (!$employeeId) {
            Cache::put('last_expiry_check_date', now()->toDateString(), now()->addDay());
            $this->info('Updated last_expiry_check_date cache.');
        }

        $this->info('Expiry check process finished.');
        return 0;
    }

    protected function cleanupOutdatedNotifications($settings, $today)
    {
        $this->info('Performing strict cleanup of outdated notifications...');

        $typesToCheck = [
            'passport_expiry',
            'ci_renewal',
            // 'work_permit_expiry', // Generic type if MOU group is missing
            'work_permit_mou',
            'resolution_renewal',
            'new_registration_renewal',
            'visa_expiry',
            'ninety_day_report',
            'pink_card_missing',      // Added to ensure disabled settings are cleaned up
            'residence_permit_missing' // Added to ensure disabled settings are cleaned up
        ];

        foreach ($typesToCheck as $type) {
            $setting = $settings->get($type);

            // Safety: If setting doesn't exist in DB, skip.
            // We should NOT assume missing = disabled for these core types, as it risks deleting valid data
            // if the settings table is incomplete.
            if (!$setting) {
                continue;
            }

            if (!$setting->is_enabled) {
                Notification::where('type', $type)->delete();
                continue;
            }

            // For missing document types, date-based cleanup is irrelevant as due_date is always today.
            if (in_array($type, ['pink_card_missing', 'residence_permit_missing'])) {
                continue;
            }

            $daysBefore = $setting->days_before_expiry ?? 60;
            $maxDate = $today->copy()->addDays($daysBefore);
            $minDate = $today->copy()->subDays(365);

            // Delete notifications that are outside the allowed window
            // i.e. Due date is further in the future than allowed OR older than 1 year
            $deleted = Notification::where('type', $type)
                ->where(function ($query) use ($maxDate, $minDate) {
                    $query->where('due_date', '>', $maxDate)
                          ->orWhere('due_date', '<', $minDate);
                })
                ->delete();

            if ($deleted > 0) {
                $this->info("Cleaned up {$deleted} outdated notifications for type: {$type} (Max Days: {$daysBefore})");
            }
        }
    }

    protected function checkEmployerDocumentExpiries($today, $settings)
    {
        $notificationType = 'employer_document_expiry';
        $setting = $settings->get($notificationType);

        if (!$setting || !$setting->is_enabled) {
            $this->info("Skipping {$notificationType} (disabled or settings missing).");
            // Ensure strictly cleaned up if disabled
            Notification::where('type', $notificationType)->delete();
            return;
        }

        $threshold = $setting->days_before_expiry;
        $futureThreshold = $today->copy()->addDays($threshold);
        // Look back 365 days to ensure we catch expired documents
        $pastThreshold = $today->copy()->subDays(365);

        $employers = Employer::whereNotNull('employer_doc_company_expiry')
            ->whereBetween('employer_doc_company_expiry', [$pastThreshold, $futureThreshold])
            ->get();

        $this->info("Found {$employers->count()} employers with expiring documents within a {$threshold}-day threshold.");

        $employerIdsInScope = $employers->pluck('id');

        // Strict cleanup: Remove any that are NOT in the current scope
        Notification::where('type', $notificationType)
            ->whereNotIn('employer_id', $employerIdsInScope)
            ->delete();

        foreach ($employers as $employer) {
            $expiryDate = Carbon::parse($employer->employer_doc_company_expiry);
            $daysRemaining = $today->diffInDays($expiryDate, false);

            Notification::updateOrCreate(
                [
                    'employer_id' => $employer->id,
                    'type' => $notificationType,
                ],
                [
                    'employee_id' => null,
                    'due_date' => $expiryDate,
                    'days_remaining' => $daysRemaining,
                    'status' => 'unread',
                    'message' => "เอกสารบริษัทของนายจ้างจะหมดอายุใน {$daysRemaining} วัน",
                ]
            );
        }
    }

    protected function checkEmployeeInsuranceExpiries($today, $settings, $baseEmployeeQuery)
    {
        $notificationType = 'employee_insurance_expiry';
        $setting = $settings->get($notificationType);

        if (!$setting || !$setting->is_enabled) {
            $this->info("Skipping {$notificationType} (disabled or settings missing).");
            // Ensure strictly cleaned up if disabled
            Notification::where('type', $notificationType)->delete();
            return;
        }

        $threshold = $setting->days_before_expiry;
        $futureThreshold = $today->copy()->addDays($threshold);
        // Look back 365 days to ensure we catch expired documents
        $pastThreshold = $today->copy()->subDays(365);
        $insuranceFields = ['insurance_expiry_date', 'insurance_expiry_date_hospital', 'insurance_expiry_date_private'];

        $allEmployeeIdsInScope = collect();

        foreach ($insuranceFields as $field) {
            $employees = (clone $baseEmployeeQuery)
                ->whereNotNull($field)
                ->where('insurance_type', '!=', 'ประกันสังคม')
                ->whereBetween($field, [$pastThreshold, $futureThreshold])
                ->get();

            $allEmployeeIdsInScope = $allEmployeeIdsInScope->merge($employees->pluck('id'));

            $this->info("Found {$employees->count()} employees with expiring insurance (field: {$field}) within a {$threshold}-day threshold.");

            foreach ($employees as $employee) {
                $expiryDate = Carbon::parse($employee->{$field});
                $daysRemaining = $today->diffInDays($expiryDate, false);

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

        // Strict cleanup
        Notification::where('type', $notificationType)
            ->whereNotIn('employee_id', $allEmployeeIdsInScope->unique())
            ->delete();
    }

    protected function checkPinkCardMissing($today, $settings, $baseEmployeeQuery)
    {
        $notificationType = 'pink_card_missing';
        $setting = $settings->get($notificationType);

        if (!$setting || !$setting->is_enabled) {
            $this->info("Deleting disabled notification type: {$notificationType}...");
            Notification::where('type', $notificationType)->delete();
            return;
        }

        $this->info("Processing: {$notificationType}");

        $employeeIdsWithMissingDoc = (clone $baseEmployeeQuery)->where(function ($query) {
            $query->whereNull('pinkCardNo')->orWhere('pinkCardNo', '=', '');
        })
        ->whereNull('terminated_at')
        ->pluck('id');

        $employeeIdsWithNotification = Notification::where('type', $notificationType)->pluck('employee_id');

        $idsToCreate = $employeeIdsWithMissingDoc->diff($employeeIdsWithNotification);

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
            Notification::insert($newNotifications);
        }

        $this->info("Finished processing for {$notificationType}.");
    }

    protected function checkResidencePermitMissing($today, $settings, $baseEmployeeQuery)
    {
        $notificationType = 'residence_permit_missing';
        $setting = $settings->get($notificationType);

        if (!$setting || !$setting->is_enabled) {
            $this->info("Deleting disabled notification type: {$notificationType}...");
            Notification::where('type', $notificationType)->delete();
            return;
        }

        $this->info("Processing: {$notificationType}");

        $employeeIdsWithMissingDoc = (clone $baseEmployeeQuery)
            ->whereNull('employee_doc_7')
            ->whereNull('terminated_at')
            ->pluck('id');

        $employeeIdsWithNotification = Notification::where('type', $notificationType)->pluck('employee_id');

        $idsToCreate = $employeeIdsWithMissingDoc->diff($employeeIdsWithNotification);

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
