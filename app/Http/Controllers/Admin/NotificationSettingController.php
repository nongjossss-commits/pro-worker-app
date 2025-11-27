<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class NotificationSettingController extends Controller
{
    // Define the list of all supported notification types
    protected $typeLabels = [
        'ninety_day_report' => 'รายงานตัว 90 วัน',
        'passport_expiry' => 'Passport',
        'work_permit_mou' => 'ใบอนุญาตทำงาน (MOU)',
        'visa_expiry' => 'วีซ่า',
        'ci_renewal' => 'ต่ออายุ CI',
        'resolution_renewal' => 'ต่ออายุมติ',
        'new_registration_renewal' => 'มติขึ้นทะเบียนใหม่',
        'employer_document_expiry' => 'เอกสารนายจ้าง',
        'employee_insurance_expiry' => 'ประกันลูกจ้าง',
        'pink_card_missing' => 'แจ้งเตือนบัตรชมพู',
        'residence_permit_missing' => 'แจ้งเตือนแจ้งที่พักอาศัย',
    ];

    public function __construct()
    {
        $this->middleware('permission:manage-users');
    }

    public function index()
    {
        $settings = NotificationSetting::all()->keyBy('notification_type');
        $typeLabels = $this->typeLabels;

        // Ensure all types are present in the settings collection for display
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

        $submittedSettings = $request->input('settings', []);

        // Iterate through ALL known types to ensure we handle unchecked checkboxes (missing keys) correctly
        foreach ($this->typeLabels as $type => $label) {
            $settingData = $submittedSettings[$type] ?? null;

            // Prepare data for update
            $data = [];

            if ($settingData) {
                // If data exists in request, use it.
                // Checkbox: if present in array, check is_enabled.
                // Note: If the array key exists but is_enabled is missing inside it, it means unchecked.
                $data['is_enabled'] = isset($settingData['is_enabled']) ? 1 : 0;

                if (isset($settingData['days_before_expiry'])) {
                    $data['days_before_expiry'] = $settingData['days_before_expiry'];
                }
            } else {
                // If the entire key is missing from the request but we know it's a valid type.
                // This usually happens if the form only had a checkbox for this item (no text inputs)
                // and the checkbox was unchecked.
                // WE MUST ASSUME IT IS DISABLED.
                $data['is_enabled'] = 0;
            }

            NotificationSetting::updateOrCreate(
                ['notification_type' => $type],
                $data
            );
        }

        // Trigger the expiry check command to update notifications immediately
        Artisan::call('app:check-expiries');

        return back()->with('success', 'ตั้งค่าการแจ้งเตือนถูกบันทึกเรียบร้อยแล้ว และระบบได้ทำการรีเฟรชข้อมูลล่าสุดแล้ว');
    }
}
