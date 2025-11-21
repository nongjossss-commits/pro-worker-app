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

        // Use a clean slate approach: remove all active notifications and recreate them based on current settings
        $this->info('Resetting active notifications...');
        Notification::where('status', '!=', 'cancelled')->delete();

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
                $specificThreshold = $settings->get($currentNotificationType)->days_before_expiry ?? null;

                // Only create a notification if the expiry is within the specific threshold
                if ($specificThreshold !== null && $daysRemaining <= $specificThreshold) {
                    Notification::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'type'        => $currentNotificationType,
                        ],
                        [
                            'due_date'       => $expiryDate,
                            'days_remaining' => $daysRemaining,
                            'status'         => 'unread',
                            'message'        => "เอกสารจะหมดอายุใน {$daysRemaining} วัน (ตั้งค่าแจ้งเตือน: {$specificThreshold} วัน)",
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
        if (!$setting) {
            $this->warn("Setting for {$notificationType} not found. Skipping.");
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
        if (!$setting) {
            $this->warn("Setting for {$notificationType} not found. Skipping.");
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
            $this->info("Skipping {$notificationType} (disabled or settings missing).");
            return;
        }

        // Pink Card is missing if pinkCardNo is null or empty string
        // And employee is not terminated
        $employees = Employee::where(function($query) {
                $query->whereNull('pinkCardNo')
                      ->orWhere('pinkCardNo', '=', '');
            })
            ->whereNull('terminated_at')
            ->get();

        $this->info("Found {$employees->count()} employees with missing Pink Card.");

        foreach ($employees as $employee) {
            Notification::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'type' => $notificationType,
                ],
                [
                    'due_date' => $today,
                    'days_remaining' => 0,
                    'status' => 'unread',
                    'message' => "ยังไม่มีข้อมูลบัตรชมพู",
                ]
            );
        }
    }

    protected function checkResidencePermitMissing($today, $settings)
    {
        $notificationType = 'residence_permit_missing';
        $setting = $settings->get($notificationType);

        if (!$setting || !$setting->is_enabled) {
            $this->info("Skipping {$notificationType} (disabled or settings missing).");
            return;
        }

        // Residence Notification is missing if employee_doc_7 is null
        // And employee is not terminated
        $employees = Employee::whereNull('employee_doc_7')
            ->whereNull('terminated_at')
            ->get();

        $this->info("Found {$employees->count()} employees with missing Residence Notification.");

        foreach ($employees as $employee) {
            Notification::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'type' => $notificationType,
                ],
                [
                    'due_date' => $today,
                    'days_remaining' => 0,
                    'status' => 'unread',
                    'message' => "ยังไม่มีเอกสารแจ้งที่พักอาศัย",
                ]
            );
        }
    }
}
