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
        $employees = Employee::whereNull('terminated_at')->get();
        $today = Carbon::today();

        // Clear only old, non-cancelled notifications to prevent stale data
        Notification::where('status', '!=', 'cancelled')->delete();

        foreach ($employees as $employee) {
            // Standard 45-day checks
            $standardChecks = [
                'passport_expiry' => $employee->passportExpiryDate,
                'visa_expiry' => $employee->visaExpiryDate,
                'work_permit_expiry' => $employee->workPermitExpiryDate,
                'ninety_day_report' => $employee->ninetyDayReportDate,
            ];

            foreach ($standardChecks as $type => $expiryDateString) {
                if ($type === 'passport_expiry' && $employee->passportType === 'CI') {
                    continue; // Skip standard passport check for CI employees
                }
                if ($expiryDateString) {
                    $expiryDate = Carbon::parse($expiryDateString)->startOfDay();
                    $thresholdDate = $today->copy()->addDays(45);
                    if ($expiryDate->gte($today) && $expiryDate->lte($thresholdDate)) {
                        Notification::updateOrCreate(
                            ['employee_id' => $employee->id, 'type' => $type],
                            ['due_date' => $expiryDate, 'message' => 'Standard expiry check.']
                        );
                    }
                }
            }

            // Special check for CI Renewal (1.5 years / 548 days threshold)
            if ($employee->passportType === 'CI' && $employee->passportExpiryDate) {
                $expiryDate = Carbon::parse($employee->passportExpiryDate)->startOfDay();
                $thresholdDate = $today->copy()->addDays(548);
                if ($expiryDate->gte($today) && $expiryDate->lte($thresholdDate)) {
                    Notification::updateOrCreate(
                        ['employee_id' => $employee->id, 'type' => 'ci_renewal'],
                        ['due_date' => $expiryDate, 'message' => 'CI Renewal check.']
                    );
                }
            }

            // Special check for Resolution Renewal (1.5 years / 548 days threshold)
            $resolutionTypes = ['มติต่ออายุในประเทศ', 'มติขึ้นทะเบียน'];
            if (in_array($employee->workPermitMOUGroup, $resolutionTypes) && $employee->workPermitExpiryDate) {
                $expiryDate = Carbon::parse($employee->workPermitExpiryDate)->startOfDay();
                $thresholdDate = $today->copy()->addDays(548);
                 if ($expiryDate->gte($today) && $expiryDate->lte($thresholdDate)) {
                    Notification::updateOrCreate(
                        ['employee_id' => $employee->id, 'type' => 'resolution_renewal'],
                        ['due_date' => $expiryDate, 'message' => 'Resolution Renewal check.']
                    );
                }
            }
        }
        $this->info('Notification check complete.');
    }
}
