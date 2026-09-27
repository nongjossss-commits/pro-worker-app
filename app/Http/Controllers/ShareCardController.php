<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Notification;
use App\Services\ShareCardService;
use Illuminate\Http\Request;

/**
 * Drag / copy / share an employee or employer card outside the program
 * (LINE, e-mail …). See ShareCardService and partials/_share_card_scripts.
 */
class ShareCardController extends Controller
{
    public const METHODS = [
        'drag' => 'ลากไปวางในโปรแกรมอื่น',
        'copy_text' => 'คัดลอกเป็นข้อความ',
        'copy_image' => 'คัดลอกเป็นรูปการ์ด',
        'download_image' => 'บันทึกรูปการ์ด',
        'share' => 'แชร์ผ่านเมนูแชร์ของเครื่อง',
    ];

    public function show(Request $request, string $type, int $id, ShareCardService $service)
    {
        $notification = $this->notification($request);
        $withPhoto = $request->boolean('photo');

        // Model global scopes (employer tenancy) apply — a user only gets
        // cards they could already see.
        $data = match ($type) {
            'employee' => $service->forEmployee(Employee::findOrFail($id), $notification, $withPhoto),
            'employer' => $service->forEmployer(Employer::findOrFail($id), $notification),
            default => abort(404),
        };

        return response()->json($data);
    }

    public function log(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:employee,employer',
            'id' => 'required|integer',
            'method' => 'required|in:' . implode(',', array_keys(self::METHODS)),
        ]);

        $model = $validated['type'] === 'employee'
            ? Employee::findOrFail($validated['id'])
            : Employer::findOrFail($validated['id']);

        $name = $validated['type'] === 'employee'
            ? ($model->employeeNameEn ?: $model->employeeNameTh)
            : ($model->employerNameTh ?: $model->employerNameEn);
        $what = $validated['type'] === 'employee' ? 'ลูกจ้าง' : 'นายจ้าง';

        ActivityLogHelper::logAction(
            'share',
            "ส่งข้อมูล{$what} {$name} (รหัส {$model->id}) ออกนอกโปรแกรม — " . self::METHODS[$validated['method']],
            get_class($model),
            $model->id,
            [
                'method' => $validated['method'],
                'fields' => ShareCardService::allowedFields()[$validated['type']],
            ]
        );

        return response()->json(['success' => true]);
    }

    public function settings()
    {
        return view('admin.settings.share', [
            'employeeFields' => ShareCardService::EMPLOYEE_FIELDS,
            'employerFields' => ShareCardService::EMPLOYER_FIELDS,
            'allowed' => ShareCardService::allowedFields(),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'employee' => 'nullable|array',
            'employee.*' => 'string',
            'employer' => 'nullable|array',
            'employer.*' => 'string',
        ]);

        $before = ShareCardService::allowedFields();
        ShareCardService::saveAllowedFields($validated['employee'] ?? [], $validated['employer'] ?? []);

        ActivityLogHelper::logAction('update', 'แก้ไขการตั้งค่าข้อมูลที่ส่งออก (ลาก/คัดลอก/แชร์การ์ด)', null, null, [
            'old' => $before,
            'attributes' => ShareCardService::allowedFields(),
        ]);

        return back()->with('success', __('Share settings saved.'));
    }

    protected function notification(Request $request): ?Notification
    {
        $id = $request->integer('notification');
        return $id ? Notification::find($id) : null;
    }
}
