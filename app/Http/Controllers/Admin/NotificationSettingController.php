<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class NotificationSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage-users');
    }

    public function index()
    {
        $settings = NotificationSetting::all()->keyBy('notification_type');

        // A simple mapping for Thai labels in the view
        $typeLabels = [
            'ninety_day_report' => 'รายงานตัว 90 วัน',
            'passport_expiry' => 'Passport',
            'work_permit_mou' => 'ใบอนุญาตทำงาน (MOU)',
            'visa_expiry' => 'วีซ่า',
            'ci_renewal' => 'ต่ออายุ CI',
            'resolution_renewal' => 'ต่ออายุมติ',
            'new_registration_renewal' => 'มติขึ้นทะเบียนใหม่',
            'employer_document_expiry' => 'เอกสารนายจ้าง',
            'employee_insurance_expiry' => 'ประกันลูกจ้าง',
            'pink_card_missing' => 'แจ้งเตือนบัตรชมพู', // New Type
            'residence_permit_missing' => 'แจ้งเตือนแจ้งที่พักอาศัย', // New Type
        ];

        // Ensure all types are present in the settings collection, even if not in DB yet
        // (Though migration should have seeded them, this is a fallback for display)
        foreach ($typeLabels as $type => $label) {
            if (!$settings->has($type)) {
                $settings[$type] = new NotificationSetting([
                    'notification_type' => $type,
                    'days_before_expiry' => 30,
                    'is_enabled' => true
                ]);
            }
        }

        return view('admin.notification_settings.index', compact('settings', 'typeLabels'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.days_before_expiry' => 'nullable|integer|min:0',
            'settings.*.is_enabled' => 'nullable|boolean',
        ]);

        foreach ($request->settings as $type => $settingData) {
            $data = [];

            if (isset($settingData['days_before_expiry'])) {
                $data['days_before_expiry'] = $settingData['days_before_expiry'];
            }

            // Handle the checkbox (if unchecked, it won't be in the request, so default to 0/false)
            $data['is_enabled'] = isset($settingData['is_enabled']) ? 1 : 0;

            NotificationSetting::updateOrCreate(
                ['notification_type' => $type],
                $data
            );
        }

        // Trigger the expiry check command
        Artisan::call('app:check-expiries');

        return back()->with('success', 'ตั้งค่าการแจ้งเตือนถูกบันทึกเรียบร้อยแล้ว และระบบได้ทำการรีเฟรชข้อมูลล่าสุดแล้ว');
    }
}
