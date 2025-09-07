<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Notification;
use Carbon\Carbon;

class CheckExpiries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-expiries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expiring employee documents and create notifications.';

    /**
     * Execute the console command.
     */
public function handle()
{
    $this->info('Checking for expiring documents...');
    $employees = Employee::whereNull('terminated_at')->get();
    $today = Carbon::today();

    foreach ($employees as $employee) {
        // --- Standard 45-day checks ---
        $standardChecks = [
            'passport_expiry' => $employee->passport_expiry_date,
            'visa_expiry' => $employee->visa_expiry_date,
            'work_permit_expiry' => $employee->work_permit_expiry_date,
            'ninety_day_report' => $employee->ninety_day_report_date,
        ];

        foreach ($standardChecks as $type => $expiryDateString) {
            if ($expiryDateString) {
                $expiryDate = Carbon::parse($expiryDateString)->startOfDay();
                $thresholdDate = $today->copy()->addDays(45);
                // CORRECT LOGIC: Check if the date is on or after today AND on or before the threshold
                if ($expiryDate->gte($today) && $expiryDate->lte($thresholdDate)) {
                    Notification::updateOrCreate(
                        ['employee_id' => $employee->id, 'type' => $type],
                        ['due_date' => $expiryDate, 'message' => 'Standard expiry check.']
                    );
                }
            }
        }

        // --- Special check for CI Renewal (1.5 years / 548 days threshold) ---
        if ($employee->passportType === 'CI' && $employee->passport_expiry_date) {
            $expiryDate = Carbon::parse($employee->passport_expiry_date)->startOfDay();
            $thresholdDate = $today->copy()->addDays(548);
            if ($expiryDate->gte($today) && $expiryDate->lte($thresholdDate)) {
                Notification::updateOrCreate(
                    ['employee_id' => $employee->id, 'type' => 'ci_renewal'],
                    ['due_date' => $expiryDate, 'message' => 'CI Renewal check.']
                );
            }
        }

        // --- Special check for Resolution Renewal (1.5 years / 548 days threshold) ---
        $resolutionTypes = ['มติต่ออายุในประเทศ', 'มติขึ้นทะเบียน'];
        if (in_array($employee->workPermitMOUGroup, $resolutionTypes) && $employee->work_permit_expiry_date) {
            $expiryDate = Carbon::parse($employee->work_permit_expiry_date)->startOfDay();
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
