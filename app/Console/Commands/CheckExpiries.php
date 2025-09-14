<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Notification;
use Carbon\Carbon;

class CheckExpiries extends Command
{
    protected $signature = 'app:check-expiries';
    protected $description = 'Check for expiring employee documents and create notifications.';

public function handle()
{
    $this->info('Checking for expiring and overdue documents...');
    $today = now()->startOfDay();

    $documentChecks = [
        'passportExpiryDate'   => 'passport_expiry',
        'workPermitExpiryDate' => 'work_permit_expiry',
        'visaExpiryDate'       => 'visa_expiry',
        'ninetyDayReportDate'  => 'ninety_day_report',
    ];

    foreach ($documentChecks as $dateField => $baseNotificationType) {
        // --- START: UPGRADED QUERY LOGIC ---
        // Set the boundaries: from 30 days ago to 90 days in the future
        $pastThreshold = $today->copy()->subDays(30);
        $futureThreshold = $today->copy()->addDays(90);

        // Find employees with documents expiring or expired within our window
        $employees = \App\Models\Employee::whereNotNull($dateField)
            ->whereBetween($dateField, [$pastThreshold, $futureThreshold])
            ->get();
        // --- END: UPGRADED QUERY LOGIC ---

        foreach ($employees as $employee) {
            $expiryDate = \Carbon\Carbon::parse($employee->{$dateField});

            // This calculation will now correctly produce negative numbers for past dates
            $daysRemaining = $today->diffInDays($expiryDate, false);

            $notificationType = $baseNotificationType;

            if ($baseNotificationType === 'work_permit_expiry') {
                if ($employee->workPermitMOUGroup === 'มติขึ้นทะเบียน') {
                    $notificationType = 'registration_renewal';
                }
            }

            \App\Models\Notification::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'type' => $notificationType,
                ],
                [
                    'due_date' => $expiryDate,
                    'days_remaining' => $daysRemaining,
                    'message' => 'Standard expiry check.',
                    'danger_threshold' => 30,
                    'status' => 'unread',
                ]
            );
        }
    }

    $this->info('Finished checking for documents.');
    return 0;
}
}
