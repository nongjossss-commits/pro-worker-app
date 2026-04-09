<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\JobTicket;
use App\Models\TicketMessage;
use Illuminate\Support\Str;

class ActivityLogHelper
{
    public static function formatAction($action)
    {
        $map = [
            'create' => 'สร้าง',
            'update' => 'แก้ไข',
            'delete' => 'ลบ',
            'force_delete' => 'ลบถาวร',
            'restore' => 'กู้คืน',
            'login' => 'เข้าสู่ระบบ',
            'logout' => 'ออกจากระบบ',
            'download' => 'ดาวน์โหลด',
            'export' => 'ส่งออก (Export)',
            'upload' => 'อัพโหลดไฟล์',
            'print' => 'พิมพ์เอกสาร',
            'generate_document' => 'สร้างเอกสารอัตโนมัติ',
            'bulk_action' => 'ดำเนินการแบบกลุ่ม',
            'import' => 'นำเข้าข้อมูล (Import)',
            'status_change' => 'เปลี่ยนสถานะ',
            'terminate' => 'แจ้งออก',
            'reinstate' => 'คืนสภาพ',
            'transfer' => 'ย้ายนายจ้าง',
            'workflow_process' => 'ดำเนินการในเมนูงาน',
        ];
        return $map[$action] ?? $action;
    }

    /**
     * Get a display name for a polymorphic subject (Employee, Employer, User, etc.)
     */
    public static function getSubjectName($log)
    {
        $subject = $log->relationLoaded('subject') ? $log->subject : null;

        if (!$subject) return null;

        $class = class_basename($log->subject_type);

        return match($class) {
            'Employee' => $subject->employeeNameEn ?: $subject->employeeNameTh,
            'Employer' => $subject->employerNameTh ?: $subject->employerNameEn,
            'User' => $subject->name,
            'JobTicket' => $subject->title ?? null,
            default => null,
        };
    }

    public static function formatModel($model)
    {
        $base = class_basename($model);
        $map = [
            'User' => 'ผู้ใช้งาน',
            'Employee' => 'ลูกจ้าง',
            'Employer' => 'นายจ้าง',
            'JobTicket' => 'ใบงาน',
            'TicketMessage' => 'ข้อความ/ตั๋ว',
            'Role' => 'บทบาท',
            'Permission' => 'สิทธิ์',
            'Address' => 'ที่อยู่',
            'NotificationSetting' => 'การตั้งค่าการแจ้งเตือน',
        ];
        return $map[$base] ?? $base;
    }

    public static function getFieldLabel($field)
    {
        // Mapping for both snake_case and camelCase fields
        $map = [
            // Common
            'id' => 'รหัส',
            'created_at' => 'เวลาที่สร้าง',
            'updated_at' => 'เวลาที่อัปเดต',
            'deleted_at' => 'เวลาที่ลบ',
            'status' => 'สถานะ',
            'email' => 'อีเมล',
            'password' => 'รหัสผ่าน',
            'name' => 'ชื่อ',

            // User / Auth
            'role' => 'บทบาท',
            'remember_token' => 'Remember Token',

            // Employer
            'employer_user_id' => 'ผู้ใช้นายจ้าง',
            'assigned_staff_id' => 'เจ้าหน้าที่ที่รับผิดชอบ',
            'employer_id' => 'นายจ้าง',
            'employer_name' => 'ชื่อนายจ้าง',

            // Ticket / Message
            'subject' => 'หัวข้อ',
            'message' => 'ข้อความ',
            'description' => 'รายละเอียด',
            'employer_unread_count' => 'จำนวนข้อความที่นายจ้างยังไม่อ่าน',
            'staff_unread_count' => 'จำนวนข้อความที่เจ้าหน้าที่ยังไม่อ่าน',
            'ticket_id' => 'รหัสใบงาน',
            'body' => 'เนื้อหา',
            'message_type' => 'ประเภทข้อความ',

            // Employee (camelCase)
            'employeeNameTh' => 'ชื่อ (ไทย)',
            'employeeNameEn' => 'ชื่อ (อังกฤษ)',
            'employeeDob' => 'วันเกิด',
            'employeePassport' => 'เลขพาสปอร์ต',
            'passportExpiryDate' => 'วันหมดอายุพาสปอร์ต',
            'employeeWorkPermit' => 'เลขใบอนุญาตทำงาน',
            'workPermitExpiryDate' => 'วันหมดอายุใบอนุญาตทำงาน',
            'visaExpiryDate' => 'วันหมดอายุวีซ่า',
            'employeeNationality' => 'สัญชาติ',
            'employeeTitleTh' => 'คำนำหน้า (ไทย)',
            'employeeTitleEn' => 'คำนำหน้า (อังกฤษ)',
            'employeePhone' => 'เบอร์โทรศัพท์',
            'pinkCardNo' => 'เลขบัตรชมพู',
            'socialSecurityNo' => 'เลขประกันสังคม',
            'hospital_name' => 'ชื่อโรงพยาบาล',
            'insurance_company' => 'บริษัทประกัน',
            'insurance_expiry_date' => 'วันหมดอายุประกัน',
            'insurance_type' => 'ประเภทประกัน',
            'job_title' => 'ตำแหน่งงาน',

            // Employee (snake_case fallback)
            'passport_issue_date' => 'วันที่ออกพาสปอร์ต',
            'terminated_at' => 'วันที่ถูกเลิกจ้าง',
        ];

        if (isset($map[$field])) {
            return $map[$field];
        }

        // Attempt to handle generic fields by converting to Title Case
        // e.g., some_field_name -> Some Field Name
        return Str::title(str_replace('_', ' ', Str::snake($field)));
    }

    public static function formatValue($field, $value)
    {
        if (is_null($value)) {
            return '-';
        }

        if ($field === 'password') {
            return '********';
        }

        // Handle IDs by looking up models
        // Note: To avoid N+1 issues in large lists, ideally we'd preload,
        // but for a "Day" view this direct lookup is a trade-off for simplicity.

        if (in_array($field, ['employer_user_id', 'assigned_staff_id', 'user_id'])) {
            $user = User::find($value);
            return $user ? "{$user->name}" : "User ID: $value";
        }

        if ($field === 'employer_id') {
             $employer = Employer::find($value);
             // Employer model might use name_th or similar
             // Based on memory, it might be name_th or generic name
             // Let's check if name_th exists, otherwise name
             $name = $employer->name_th ?? $employer->name ?? "Employer ID: $value";
             return $name;
        }

        if ($field === 'employee_id') {
            $employee = Employee::find($value);
            $name = $employee->employeeNameTh ?? $employee->employeeNameEn ?? "Employee ID: $value";
            return $name;
        }

        if ($field === 'ticket_id') {
            $ticket = JobTicket::find($value);
            return $ticket ? "Ticket #{$ticket->id} ({$ticket->subject})" : "Ticket ID: $value";
        }

        // Handle Status translations
        if ($field === 'status') {
             $statusMap = [
                 'pending_staff' => 'รอเจ้าหน้าที่ (Pending Staff)',
                 'in_progress' => 'กำลังดำเนินการ (In Progress)',
                 'resolved' => 'เสร็จสิ้น (Resolved)',
                 'rejected' => 'ปฏิเสธ (Rejected)',
                 'active' => 'ใช้งาน (Active)',
                 'inactive' => 'ไม่ใช้งาน (Inactive)',
             ];
             return $statusMap[$value] ?? $value;
        }

        if ($field === 'insurance_type') {
             $insuranceMap = [
                'social_security' => 'ประกันสังคม',
                'private_insurance' => 'ประกันเอกชน',
                'hospital_insurance' => 'ประกันโรงพยาบาล',
                'ประกันสังคม' => 'ประกันสังคม',
             ];
             return $insuranceMap[$value] ?? $value;
        }

        if (is_array($value)) {
            // If it's a complex array, return JSON, but formatted nicely
            return '<pre class="mb-0" style="font-size: 0.85em;">' . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</pre>';
        }

        // Dates
        if (strtotime($value) && (strlen($value) === 10 || strlen($value) > 15)) {
             // Simple check to try and format dates
             try {
                 return \Carbon\Carbon::parse($value)->format('d/m/Y H:i');
             } catch (\Exception $e) {
                 return $value;
             }
        }

        return $value;
    }

    public static function generateReadableChanges(ActivityLog $log)
    {
        $changes = [];
        $properties = $log->properties;

        if (!$properties) {
            return [];
        }

        // Case 1: Create (Only attributes)
        if ($log->action === 'create' && isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $new) {
                if (in_array($key, ['updated_at', 'created_at', 'id'])) continue;
                // Skip null values on create to reduce noise? Or show them?
                // Usually show non-null.
                if (is_null($new)) continue;

                $label = self::getFieldLabel($key);
                $val = self::formatValue($key, $new);
                $changes[] = "กำหนดค่า <span class='fw-bold text-primary'>{$label}</span> เป็น: {$val}";
            }
        }
        // Case 2: Update (Old and Attributes)
        elseif ($log->action === 'update' && isset($properties['old']) && isset($properties['attributes'])) {
             foreach ($properties['attributes'] as $key => $new) {
                 $old = $properties['old'][$key] ?? null;

                 // Use strict comparison if possible, but loosely for strings/ints usually fine
                 // Handle empty strings vs null
                 if ($old != $new) {
                     if (in_array($key, ['updated_at'])) continue;

                     $label = self::getFieldLabel($key);
                     $oldVal = self::formatValue($key, $old);
                     $newVal = self::formatValue($key, $new);

                     $changes[] = "เปลี่ยน <span class='fw-bold text-primary'>{$label}</span> จาก <em>{$oldVal}</em> เป็น <strong>{$newVal}</strong>";
                 }
             }
        }
        // Fallback
        else {
             if (isset($properties['attributes'])) {
                 foreach ($properties['attributes'] as $key => $new) {
                    if (in_array($key, ['updated_at', 'created_at'])) continue;
                    $label = self::getFieldLabel($key);
                    $val = self::formatValue($key, $new);
                    $changes[] = "<span class='fw-bold'>{$label}</span>: {$val}";
                 }
             }
        }

        return $changes;
    }

    /**
     * Log a non-CRUD action (download, export, upload, print, etc.)
     *
     * @param string $action  Action type: download, export, upload, print, generate_document, bulk_action, import, status_change
     * @param string $description  Human-readable description
     * @param string|null $subjectType  Related model class (e.g. App\Models\Employee)
     * @param int|null $subjectId  Related model ID
     * @param array $properties  Additional details to store as JSON
     */
    public static function logAction(string $action, string $description, ?string $subjectType = null, $subjectId = null, array $properties = [])
    {
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
            'properties' => !empty($properties) ? $properties : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
