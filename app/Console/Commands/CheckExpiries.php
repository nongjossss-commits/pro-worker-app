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

        // EXPLANATION: Step 1 - Clean up very old notifications first.
        // This removes notifications for documents that expired more than a year ago.
        $this->info('Cleaning up old notifications...');
        Notification::where('due_date', '<', $today->copy()->subYear())->delete();

        // Define document types and their corresponding notification types
        $documentChecks = [
            'passportExpiryDate'   => 'passport_expiry',
            'workPermitExpiryDate' => 'work_permit_expiry',
            'visaExpiryDate'       => 'visa_expiry',
            'ninetyDayReportDate'  => 'ninety_day_report',
            // We can add more special checks later here
        ];

        foreach ($documentChecks as $dateField => $notificationType) {
            $this->info("Checking: {$notificationType}...");

            // EXPLANATION: Step 2 - Expand the search range.
            // We now look for documents that expired up to 30 days ago AND will expire in the next 90 days.
            $pastThreshold = $today->copy()->subDays(30);
            $futureThreshold = $today->copy()->addDays(90);

            $employees = Employee::whereNotNull($dateField)
                ->whereBetween($dateField, [$pastThreshold, $futureThreshold])
                ->get();

            foreach ($employees as $employee) {
                if ($employee->is_cancelled) {
                    continue; // Skip cancelled employees
                }

                $expiryDate = Carbon::parse($employee->{$dateField});

                // EXPLANATION: Step 3 - The calculation logic is now more robust.
                // diffInDays will be positive for future dates, negative for past dates.
                $daysRemaining = $today->diffInDays($expiryDate, false);

                // For special MOU renewal types, we create separate notifications
                $currentNotificationType = $notificationType;
                if ($notificationType === 'work_permit_expiry') {
                     if ($employee->workPermitMOUGroup === 'มติต่ออายุในประเทศ' || $employee->workPermitMOUGroup === 'มติขึ้นทะเบียน') {
                        $currentNotificationType = 'resolution_renewal';
                    }
                }
                 if ($notificationType === 'passport_expiry' && $employee->passportType === 'CI') {
                    $currentNotificationType = 'ci_renewal';
                }


                Notification::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'type' => $currentNotificationType,
                    ],
                    [
                        'due_date' => $expiryDate,
                        'days_remaining' => $daysRemaining,
                        'status' => 'unread', // Reset status in case it was cancelled before
                        'danger_threshold' => 15, // e.g., show as critical if less than 15 days
                    ]
                );
            }
        }

        $this->info('Finished checking for expiring documents.');
        return 0;
    }
}
