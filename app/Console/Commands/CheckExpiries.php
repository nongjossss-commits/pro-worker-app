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

            $pastThreshold = $today->copy()->subDays(365); // Check for items expired up to a year ago
            $futureThreshold = $today->copy()->addDays(45); // Check for items expiring in the next 45 days

            // --- FIX: This query now uses the correct camelCase column name ---
            $employees = Employee::whereNotNull($dateField)
                ->whereBetween($dateField, [$pastThreshold, $futureThreshold])
                ->get();

            foreach ($employees as $employee) {
                if ($employee->is_cancelled ?? false) { // Use null coalescing for safety
                    continue;
                }

                // Ensure we have a Carbon instance for calculation
                $expiryDate = Carbon::parse($employee->{$dateField});
                $daysRemaining = $today->diffInDays($expiryDate, false);
                $currentNotificationType = $notificationType;

                if ($notificationType === 'work_permit_expiry' && in_array($employee->workPermitMOUGroup, ['มติต่ออายุในประเทศ', 'มติขึ้นทะเบียน'])) {
                    $currentNotificationType = 'resolution_renewal';
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
