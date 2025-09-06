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
    Notification::truncate(); // Start fresh each time
    $employees = Employee::whereNull('terminated_at')->get();
    $today = Carbon::today();

    foreach ($employees as $employee) {
        // Standard 45-day checks
        $standardChecks = [
            'passport_expiry' => $employee->passport_expiry_date,
            'visa_expiry' => $employee->visa_expiry_date,
            'work_permit_expiry' => $employee->work_permit_expiry_date,
            'ninety_day_report' => $employee->ninety_day_report_date,
        ];

        foreach ($standardChecks as $type => $expiryDateString) {
            if ($expiryDateString) {
                $expiryDate = Carbon::parse($expiryDateString)->startOfDay();
                if ($expiryDate->isBetween($today, $today->copy()->addDays(45))) {
                    Notification::create([
                        'employee_id' => $employee->id, 'type' => $type,
                        'due_date' => $expiryDate, 'status' => 'unread',
                    ]);
                }
            }
        }

        // Special check for CI Renewal (1 year threshold)
        if ($employee->passportType === 'CI' && $employee->passport_expiry_date) {
            $expiryDate = Carbon::parse($employee->passport_expiry_date)->startOfDay();
            if ($expiryDate->isBetween($today, $today->copy()->addYear())) {
                Notification::create([
                    'employee_id' => $employee->id, 'type' => 'ci_renewal',
                    'due_date' => $expiryDate, 'status' => 'unread',
                ]);
            }
        }

        // Special check for Resolution Renewal (1 year threshold)
        $resolutionTypes = ['มติต่ออายุในประเทศ', 'มติขึ้นทะเบียน'];
        if (in_array($employee->workPermitMOUGroup, $resolutionTypes) && $employee->work_permit_expiry_date) {
            $expiryDate = Carbon::parse($employee->work_permit_expiry_date)->startOfDay();
            if ($expiryDate->isBetween($today, $today->copy()->addYear())) {
                Notification::create([
                    'employee_id' => $employee->id, 'type' => 'resolution_renewal',
                    'due_date' => $expiryDate, 'status' => 'unread',
                ]);
            }
        }
    }
    $this->info('Notification check complete.');
}
}
