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
            'workPermitExpiryDate' => 'work_permit_expiry', // This will be our trigger
            'visaExpiryDate'       => 'visa_expiry',
            'ninetyDayReportDate'  => 'ninety_day_report',
        ];

        foreach ($documentChecks as $dateField => $notificationType) {
            $this->info("Checking: {$notificationType}...");

            // Widen the search range for work permits to handle both future and past dates
            $pastThreshold = $today->copy()->subDays(365);
            $futureThreshold = $today->copy()->addDays(50); // Set to 50 for the new 'ขอต่อ' requirement

            $employees = Employee::whereNotNull($dateField)
                ->whereBetween($dateField, [$pastThreshold, $futureThreshold])
                ->get();

            $this->info("Found {$employees->count()} employees for field [{$dateField}].");

            foreach ($employees as $employee) {
                if ($employee->is_cancelled ?? false) {
                    continue;
                }

                $expiryDate = Carbon::parse($employee->{$dateField});
                $daysRemaining = $today->diffInDays($expiryDate, false);
                $currentNotificationType = $notificationType;

                // --- NEW LOGIC: Determine notification type based on workPermitMOUGroup ---
                if ($notificationType === 'work_permit_expiry') {
                    if ($employee->workPermitMOUGroup === 'MOU') {
                        $currentNotificationType = 'work_permit_mou';
                    } elseif ($employee->workPermitMOUGroup === 'มติต่ออายุในประเทศ') {
                        $currentNotificationType = 'resolution_renewal';
                    } elseif ($employee->workPermitMOUGroup === 'มติขึ้นทะเบียน') {
                        $currentNotificationType = 'new_registration_renewal'; // New, specific type
                    }
                }

                if ($notificationType === 'passport_expiry' && $employee->passportType === 'CI') {
                    $currentNotificationType = 'ci_renewal';
                }

                Notification::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'type'        => $currentNotificationType,
                    ],
                    [
                        'due_date'       => $expiryDate,
                        'days_remaining' => $daysRemaining,
                        'status'         => 'unread',
                        'message'        => 'Automated expiry check.',
                    ]
                );
            }
        }

        $this->info('Finished checking for expiring documents.');
        return 0;
    }
}
