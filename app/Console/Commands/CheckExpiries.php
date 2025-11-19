<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
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
        return 0;
    }
}
