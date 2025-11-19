<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationSetting;
use Illuminate\Support\Facades\DB;

class NotificationSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('notification_settings')->truncate();

        $notificationTypes = [
            ['notification_type' => 'ninety_day_report', 'days_before_expiry' => 30],
            ['notification_type' => 'passport_expiry', 'days_before_expiry' => 90],
            ['notification_type' => 'work_permit_mou', 'days_before_expiry' => 60],
            ['notification_type' => 'visa_expiry', 'days_before_expiry' => 30],
            ['notification_type' => 'ci_renewal', 'days_before_expiry' => 45],
            ['notification_type' => 'resolution_renewal', 'days_before_expiry' => 60],
            ['notification_type' => 'new_registration_renewal', 'days_before_expiry' => 60],
            ['notification_type' => 'employer_document_expiry', 'days_before_expiry' => 30],
            ['notification_type' => 'employee_insurance_expiry', 'days_before_expiry' => 30],
        ];

        foreach ($notificationTypes as $type) {
            NotificationSetting::firstOrCreate(
                ['notification_type' => $type['notification_type']],
                ['days_before_expiry' => $type['days_before_expiry']]
            );
        }
    }
}
