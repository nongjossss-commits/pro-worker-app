<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Notification;
use Carbon\Carbon;

class CheckExpiries extends Command
{
    protected $signature = 'app:check-expiries';
    protected $description = 'Check for expiring/expired employee documents and create/update notifications.';

    public function handle()
    {
        $this->info('Starting expiry check process...');
        $today = now()->startOfDay();

        $this->info('Cleaning up old notifications...');
        Notification::where('due_date', '<', $today->copy()->subYear())->delete();

        $documentChecks = [
            'passportExpiryDate'   => 'passport_expiry',
            'workPermitExpiryDate' => 'work_permit_expiry',
            'visaExpiryDate'       => 'visa_expiry',
            'ninetyDayReportDate'  => 'ninety_day_report',
        ];

        foreach ($documentChecks as $dateField => $notificationType) {
            $this->info("Checking: {$notificationType}...");

            $pastThreshold = $today->copy()->subDays(365);
            $futureThreshold = $today->copy()->addDays(45);

            $employees = Employee::whereNotNull($dateField)
                ->whereBetween($dateField, [$pastThreshold, $futureThreshold])
                ->get();

            $this->info("Found {$employees->count()} employees for notification type [{$notificationType}].");

            foreach ($employees as $employee) {
                if ($employee->is_cancelled ?? false) {
                    continue;
                }

                $expiryDate = Carbon::parse($employee->{$dateField});
                $daysRemaining = $today->diffInDays($expiryDate, false);

                // --- SIMPLIFIED LOGIC: No longer re-routing notifications ---
                // All passport expiries will create a 'passport_expiry' notification.
                // All work permit expiries will create a 'work_permit_expiry' notification.
                $currentNotificationType = $notificationType;

                Notification::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'type' => $currentNotificationType,
                    ],
                    [
                        'due_date' => $expiryDate,
                        'days_remaining' => $daysRemaining,
                        'status' => 'unread',
                        'message' => 'Automated expiry check.',
                    ]
                );
            }
        }

        $this->info('Finished checking for expiring documents.');
        return 0;
    }
}
