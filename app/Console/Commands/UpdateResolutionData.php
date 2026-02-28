<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class UpdateResolutionData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-resolution-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically apply resolution settings (work permit expiry, visa expiry, MOU group) to employees whose resolution is complete and older than 24 hours.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting UpdateResolutionData job...');

        // 1. Process Registration
        $this->processGroup('registration_completed', 'registration');

        // 2. Process Renewal
        $this->processGroup('renewal_completed', 'renewal');

        $this->info('UpdateResolutionData job completed.');
    }

    private function processGroup($status, $settingGroup)
    {
        $this->info("Processing group: $status ($settingGroup)");

        // Fetch settings
        $settingsRaw = SystemSetting::where('group', $settingGroup)->get();
        $settings = $settingsRaw->pluck('value', 'key')->toArray();

        // Check if any settings are configured
        $autoWorkPermit = $settings["{$settingGroup}_auto_work_permit_expiry"] ?? null;
        $autoVisa = $settings["{$settingGroup}_auto_visa_expiry"] ?? null;
        $autoMou = $settings["{$settingGroup}_auto_mou_group"] ?? null;

        if (!$autoWorkPermit && !$autoVisa && !$autoMou) {
            $this->info("No settings configured for $settingGroup. Skipping.");
            return;
        }

        // Query employees
        $employees = Employee::where('status', $status)
            ->whereNotNull('resolution_completed_at')
            ->where('resolution_completed_at', '<=', now()->subHours(24))
            ->where(function ($query) {
                $query->whereNull('resolution_settings_applied')
                      ->orWhere('resolution_settings_applied', false);
            })
            ->get();

        if ($employees->isEmpty()) {
            $this->info("No employees found needing update for $status.");
            return;
        }

        $count = 0;
        foreach ($employees as $employee) {
            $updated = false;

            if ($autoWorkPermit) {
                $employee->workPermitExpiryDate = $autoWorkPermit;
                $updated = true;
            }
            if ($autoVisa) {
                $employee->visaExpiryDate = $autoVisa;
                $updated = true;
            }
            if ($autoMou) {
                $employee->workPermitMOUGroup = $autoMou;
                $updated = true;
            }

            if ($updated) {
                $employee->resolution_settings_applied = true;
                $employee->save();
                $count++;
            }
        }

        $this->info("Updated $count employees for $status.");
        Log::info("UpdateResolutionData: Updated $count employees for $status.");
    }
}
