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
        $settings = NotificationSetting::all();
        // A simple mapping for Thai labels in the view
        $typeLabels = [
            'ninety_day_report' => 'รายงานตัว 90 วัน',
            'passport_expiry' => 'Passport',
            'work_permit_mou' => 'ใบอนุญาตทำงาน (MOU)',
            'visa_expiry' => 'วีซ่า',
            'ci_renewal' => 'ต่ออายุ CI',
            'resolution_renewal' => 'ต่ออายุมติ',
            'new_registration_renewal' => 'มติขึ้นทะเบียนใหม่',
        ];
        return view('admin.notification_settings.index', compact('settings', 'typeLabels'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.days_before_expiry' => 'required|integer|min:0',
        ]);

        foreach ($request->settings as $type => $settingData) {
            NotificationSetting::where('notification_type', $type)
                ->update(['days_before_expiry' => $settingData['days_before_expiry']]);
        }

        // Trigger the expiry check command
        Artisan::call('app:check-expiries');

        return back()->with('success', 'ตั้งค่าการแจ้งเตือนถูกบันทึกเรียบร้อยแล้ว และระบบได้ทำการรีเฟรชข้อมูลล่าสุดแล้ว');
    }
}
