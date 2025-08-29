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

        $thresholdDate = Carbon::now()->addDays(45);
        $today = Carbon::now();

        $employees = Employee::all();

        foreach ($employees as $employee) {
            $documentTypes = [
                'passport_expiry' => $employee->passportExpiryDate,
                'visa_expiry' => $employee->visaExpiryDate,
                'work_permit_expiry' => $employee->workPermitExpiryDate,
                'ninety_day_report' => $employee->ninetyDayReportDate,
            ];

            foreach ($documentTypes as $type => $expiryDate) {
                if ($expiryDate) {
                    $expiryDate = Carbon::parse($expiryDate);

                    if ($expiryDate->between($today, $thresholdDate)) {
                        $existingNotification = Notification::where('employee_id', $employee->id)
                            ->where('type', $type)
                            ->first();

                        if (!$existingNotification) {
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
        }

        $this->info('Done.');
    }
}
