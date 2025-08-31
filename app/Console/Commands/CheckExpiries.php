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
        Notification::truncate();

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
                        $notificationType = $type;
                        $message = "The employee's " . str_replace('_', ' ', $type) . " is expiring on " . $expiryDate->format('Y-m-d') . ".";

                        if ($type === 'passport_expiry' && $employee->passportType === 'CI') {
                            $notificationType = 'ci_renewal';
                            $message = "The employee's CI (passport) is expiring on " . $expiryDate->format('Y-m-d') . ".";
                        } elseif ($type === 'work_permit_expiry' && $employee->workPermitMOUGroup === 'มติต่ออายุในประเทศ') {
                            $notificationType = 'resolution_renewal';
                             $message = "The employee's work permit (resolution renewal) is expiring on " . $expiryDate->format('Y-m-d') . ".";
                        }

                        $existingNotification = Notification::where('employee_id', $employee->id)
                            ->where('type', $notificationType)
                            ->first();

                        if (!$existingNotification) {
                            Notification::create([
                                'employee_id' => $employee->id,
                                'type' => $notificationType,
                                'message' => $message,
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
