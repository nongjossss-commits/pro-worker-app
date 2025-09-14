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
    $this->info('Checking for expiring documents...');
    $today = now()->startOfDay();

    // Define document types and their corresponding notification types
    $documentChecks = [
        'passportExpiryDate'   => 'passport_expiry',
        'workPermitExpiryDate' => 'work_permit_expiry', // This will be handled specially
        'visaExpiryDate'       => 'visa_expiry',
        'ninetyDayReportDate'  => 'ninety_day_report',
    ];

    foreach ($documentChecks as $dateField => $baseNotificationType) {
        // Get all employees with documents expiring within the threshold
        // Note: Threshold logic can be enhanced later from a settings table
        $thresholdDate = $today->copy()->addDays(90);

        $employees = \App\Models\Employee::whereNotNull($dateField)
            ->whereBetween($dateField, [$today, $thresholdDate])
            ->get();

        foreach ($employees as $employee) {
            $expiryDate = \Carbon\Carbon::parse($employee->{$dateField});

            // CORRECTED DAY CALCULATION
            $daysRemaining = $today->diffInDays($expiryDate, false);

            $notificationType = $baseNotificationType;

            // SPECIAL HANDLING FOR WORK PERMITS BASED ON MOU GROUP
            if ($baseNotificationType === 'work_permit_expiry') {
                if ($employee->workPermitMOUGroup === 'มติขึ้นทะเบียน') {
                    $notificationType = 'registration_renewal'; // Assign new type
                }
                // Add more else if for other groups like 'มติต่ออายุในประเทศ' if needed
            }

            // Create or update the notification
            \App\Models\Notification::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'type' => $notificationType,
                ],
                [
                    'due_date' => $expiryDate,
                    'days_remaining' => $daysRemaining,
                    'danger_threshold' => 30, // Can be made dynamic later
                    'status' => 'unread',
                ]
            );
        }
    }

    $this->info('Finished checking for expiring documents.');
    return 0;
}
}
