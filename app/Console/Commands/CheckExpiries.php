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
    Notification::truncate(); // Start with a clean slate
    $thresholdDate = Carbon::now()->addDays(45);
    $today = Carbon::now()->startOfDay();
    $employees = Employee::all();
    foreach ($employees as $employee) {
        $documentTypes = [
            'passport_expiry' => $employee->passportExpiryDate,
            'visa_expiry' => $employee->visaExpiryDate,
            'work_permit_expiry' => $employee->workPermitExpiryDate,
            'ninety_day_report' => $employee->ninetyDayReportDate,
        ];
        foreach ($documentTypes as $type => $expiryDateString) {
            if ($expiryDateString) {
                $expiryDate = Carbon::parse($expiryDateString)->startOfDay();
                if ($expiryDate->isBetween($today, $thresholdDate)) {
                    Notification::create([
                        'employee_id' => $employee->id,
                        'type' => $type,
                        'message' => "The employee's " . str_replace('_', ' ', $type) . " is expiring on " . $expiryDate->format('Y-m-d') . ".",
                        'due_date' => $expiryDate,
                    ]);
                }
            }
        }
    }
    $this->info('Done.');
}
}
