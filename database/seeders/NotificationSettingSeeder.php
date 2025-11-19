<?php

namespace Database\Seeders;

use Illuminate.Database\Seeder;
use App\Models\NotificationSetting;

class NotificationSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['notification_type' => 'ninety_day_report', 'days_before_expiry' => 90],
            ['notification_type' => 'passport_expiry', 'days_before_expiry' => 60],
            ['notification_type' => 'work_permit_mou', 'days_before_expiry' => 60],
            ['notification_type' => 'visa_expiry', 'days_before_expiry' => 60],
            ['notification_type' => 'ci_renewal', 'days_before_expiry' => 60],
            ['notification_type' => 'resolution_renewal', 'days_before_expiry' => 60],
            ['notification_type' => 'new_registration_renewal', 'days_before_expiry' => 60],
        ];

        foreach ($settings as $setting) {
            NotificationSetting::firstOrCreate(
                ['notification_type' => $setting['notification_type']],
                ['days_before_expiry' => $setting['days_before_expiry']]
            );
        }
    }
}
