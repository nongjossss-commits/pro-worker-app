<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\JobTicket;
use Illuminate\Support\Str;

class ActivityLogHelper
{
    // === Lookup tables (class-level constants ไม่ต้อง redeclare ทุกครั้งที่ method ถูกเรียก) ===

    /** ป้ายชื่อภาษาไทยสำหรับ action ใน activity log */
    private const ACTION_LABELS = [
        'create'             => 'สร้าง',
        'update'             => 'แก้ไข',
        'delete'             => 'ลบ',
        'force_delete'       => 'ลบถาวร',
        'restore'            => 'กู้คืน',
        'login'              => 'เข้าสู่ระบบ',
        'logout'             => 'ออกจากระบบ',
        'download'           => 'ดาวน์โหลด',
        'export'             => 'ส่งออก (Export)',
        'upload'             => 'อัพโหลดไฟล์',
        'print'              => 'พิมพ์เอกสาร',
        'generate_document'  => 'สร้างเอกสารอัตโนมัติ',
        'bulk_action'        => 'ดำเนินการแบบกลุ่ม',
        'import'             => 'นำเข้าข้อมูล (Import)',
        'status_change'      => 'เปลี่ยนสถานะ',
        'terminate'          => 'แจ้งออก',
        'reinstate'          => 'คืนสภาพ',
        'transfer'           => 'ย้ายนายจ้าง',
        'workflow_process'   => 'ดำเนินการในเมนูงาน',
    ];

    /** ป้ายชื่อภาษาไทยสำหรับชื่อ Model (basename) */
    private const MODEL_LABELS = [
        'User'                => 'ผู้ใช้งาน',
        'Employee'            => 'ลูกจ้าง',
        'Employer'            => 'นายจ้าง',
        'JobTicket'           => 'ใบงาน',
        'TicketMessage'       => 'ข้อความ/ตั๋ว',
        'Role'                => 'บทบาท',
        'Permission'          => 'สิทธิ์',
        'Address'             => 'ที่อยู่',
        'NotificationSetting' => 'การตั้งค่าการแจ้งเตือน',
    ];

    /** ป้ายชื่อภาษาไทยสำหรับ field name (รองรับทั้ง snake_case และ camelCase) */
    private const FIELD_LABELS = [
        // Common
        'id'                       => 'รหัส',
        'created_at'               => 'เวลาที่สร้าง',
        'updated_at'               => 'เวลาที่อัปเดต',
        'deleted_at'               => 'เวลาที่ลบ',
        'status'                   => 'สถานะ',
        'email'                    => 'อีเมล',
        'password'                 => 'รหัสผ่าน',
        'name'                     => 'ชื่อ',

        // User / Auth
        'role'                     => 'บทบาท',
        'remember_token'           => 'Remember Token',

        // Employer
        'employer_user_id'         => 'ผู้ใช้นายจ้าง',
        'assigned_staff_id'        => 'เจ้าหน้าที่ที่รับผิดชอบ',
        'employer_id'              => 'นายจ้าง',
        'employer_name'            => 'ชื่อนายจ้าง',

        // Ticket / Message
        'subject'                  => 'หัวข้อ',
        'message'                  => 'ข้อความ',
        'description'              => 'รายละเอียด',
        'employer_unread_count'    => 'จำนวนข้อความที่นายจ้างยังไม่อ่าน',
        'staff_unread_count'       => 'จำนวนข้อความที่เจ้าหน้าที่ยังไม่อ่าน',
        'ticket_id'                => 'รหัสใบงาน',
        'body'                     => 'เนื้อหา',
        'message_type'             => 'ประเภทข้อความ',

        // Employee (camelCase)
        'employeeNameTh'           => 'ชื่อ (ไทย)',
        'employeeNameEn'           => 'ชื่อ (อังกฤษ)',
        'employeeDob'              => 'วันเกิด',
        'employeePassport'         => 'เลขพาสปอร์ต',
        'passportExpiryDate'       => 'วันหมดอายุพาสปอร์ต',
        'employeeWorkPermit'       => 'เลขใบอนุญาตทำงาน',
        'workPermitIssueDate'      => 'วันที่ออกใบอนุญาตทำงาน',
        'workPermitExpiryDate'     => 'วันหมดอายุใบอนุญาตทำงาน',
        'visaEndorsementDate'      => 'วันที่ตรวจลงตราวีซ่า',
        'visaEndorsementNo'        => 'เลขที่ตรวจลงตราวีซ่า',
        'visaExpiryDate'           => 'วันหมดอายุวีซ่า',
        'employeeNationality'      => 'สัญชาติ',
        'employeeTitleTh'          => 'คำนำหน้า (ไทย)',
        'employeeTitleEn'          => 'คำนำหน้า (อังกฤษ)',
        'employeePhone'            => 'เบอร์โทรศัพท์',
        'pinkCardNo'               => 'เลขบัตรชมพู',
        'socialSecurityNo'         => 'เลขประกันสังคม',
        'hospital_name'            => 'ชื่อโรงพยาบาล',
        'insurance_company'        => 'บริษัทประกัน',
        'insurance_expiry_date'    => 'วันหมดอายุประกัน',
        'insurance_type'           => 'ประเภทประกัน',
        'job_title'                => 'ตำแหน่งงาน',

        // Employee (snake_case fallback)
        'passport_issue_date'      => 'วันที่ออกพาสปอร์ต',
        'terminated_at'            => 'วันที่ถูกเลิกจ้าง',
    ];

    /** ป้ายชื่อภาษาไทยของ status — ใช้กับ field 'status' */
    private const STATUS_LABELS = [
        'pending_staff' => 'รอเจ้าหน้าที่ (Pending Staff)',
        'in_progress'   => 'กำลังดำเนินการ (In Progress)',
        'resolved'      => 'เสร็จสิ้น (Resolved)',
        'rejected'      => 'ปฏิเสธ (Rejected)',
        'active'        => 'ใช้งาน (Active)',
        'inactive'      => 'ไม่ใช้งาน (Inactive)',
    ];

    /** ป้ายชื่อภาษาไทยของประเภทประกัน — ใช้กับ field 'insurance_type' */
    private const INSURANCE_LABELS = [
        'social_security'    => 'ประกันสังคม',
        'private_insurance'  => 'ประกันเอกชน',
        'hospital_insurance' => 'ประกันโรงพยาบาล',
        'ประกันสังคม'         => 'ประกันสังคม',
    ];

    /** Field ที่อ้างอิง User table — ใช้ formatUserId */
    private const USER_REF_FIELDS = ['employer_user_id', 'assigned_staff_id', 'user_id'];

    // === Public API ===

    public static function formatAction($action)
    {
        return self::ACTION_LABELS[$action] ?? $action;
    }

    /**
     * คืนชื่อสำหรับแสดงของ subject แบบ polymorphic (Employee, Employer, User, JobTicket)
     *
     * ลำดับการหาแหล่งข้อมูล:
     *  1) Snapshot ใน properties.subject_name (เก็บไว้ตอนสร้าง log) — ทนทาน force_delete
     *  2) Relation subject (auto eager loaded) ถ้ายังมีอยู่
     *  3) ถ้า log เป็น delete/force_delete/restore → ลองหา withTrashed
     */
    public static function getSubjectName($log)
    {
        // 1) Snapshot ใน properties — ใช้ได้แม้ record ถูกลบถาวรไปแล้ว
        $props = $log->properties;
        if (is_array($props) && !empty($props['subject_name'])) {
            return $props['subject_name'];
        }

        // 2) Relation ปกติ (จะ null ถ้า subject ถูก soft-deleted)
        $subject = $log->relationLoaded('subject') ? $log->subject : null;

        // 3) สำหรับ action ที่อาจชี้ไปยัง record ที่ถูก soft-delete แล้ว → ลองหา withTrashed
        if (!$subject && $log->subject_type && $log->subject_id) {
            $class = $log->subject_type;
            if (class_exists($class)) {
                $traits = class_uses_recursive($class);
                if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, $traits, true)) {
                    // bypass tenancy scopes (เช่น Employee.employerTenancy) ให้ activity log
                    // เห็น subject ที่อยู่นอก scope ของ user ปัจจุบันได้
                    $subject = $class::withoutGlobalScopes()->withTrashed()->find($log->subject_id);
                }
            }
        }

        if (!$subject) return null;

        $class = class_basename($log->subject_type);

        return match ($class) {
            'Employee'  => $subject->employeeNameEn ?: $subject->employeeNameTh,
            'Employer'  => $subject->employerNameTh ?: $subject->employerNameEn,
            'User'      => $subject->name,
            'JobTicket' => $subject->title ?? null,
            default     => null,
        };
    }

    public static function formatModel($model)
    {
        $base = class_basename($model);
        return self::MODEL_LABELS[$base] ?? $base;
    }

    public static function getFieldLabel($field)
    {
        if (isset(self::FIELD_LABELS[$field])) {
            return self::FIELD_LABELS[$field];
        }
        // Fallback: แปลง some_field_name → Some Field Name
        return Str::title(str_replace('_', ' ', Str::snake($field)));
    }

    /**
     * แปลงค่าให้พร้อมแสดงผล รวมการ lookup ID → ชื่อจริง, แปลงรหัส status → ภาษาไทย
     * และจัดรูปแบบ array/date ให้อ่านง่าย
     *
     * NOTE: ลำดับการเช็คสำคัญ — ห้ามสลับ
     *  - null/password ก่อนสุด (early return)
     *  - ID-based fields ก่อน status/insurance (เพราะต้อง DB lookup)
     *  - is_array ก่อน date (เพราะ array ก็ผ่าน strtotime ได้บ้าง)
     */
    public static function formatValue($field, $value)
    {
        if (is_null($value)) return '-';
        // Mask ทุก field ที่มีคำว่า "password" — ครอบคลุม employerPassword, outsource_password ด้วย
        if (str_contains(strtolower($field), 'password')) return '********';

        // ID-based fields → DB lookup
        // หมายเหตุ: ในหน้า "Day view" ที่ log จำนวนน้อยไม่ค่อย N+1 — ถ้า log เยอะค่อย preload
        if (in_array($field, self::USER_REF_FIELDS, true)) {
            return self::formatUserId($value);
        }
        if ($field === 'employer_id') return self::formatEmployerId($value);
        if ($field === 'employee_id') return self::formatEmployeeId($value);
        if ($field === 'ticket_id')   return self::formatTicketId($value);

        // Status / enum-like fields
        if ($field === 'status')         return self::formatStatusLabel($value);
        if ($field === 'insurance_type') return self::formatInsuranceLabel($value);

        // Complex array → JSON (formatted)
        if (is_array($value)) return self::formatArrayValue($value);

        // Date detection (heuristic)
        if (self::looksLikeDate($value)) return self::formatDateValue($value);

        return $value;
    }

    public static function generateReadableChanges(ActivityLog $log)
    {
        $changes = [];
        $properties = $log->properties;
        if (!$properties) return [];

        // กรณี create: แสดงเฉพาะ attributes ที่ไม่ใช่ null และไม่ใช่ metadata
        if ($log->action === 'create' && isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $new) {
                if (in_array($key, ['updated_at', 'created_at', 'id'])) continue;
                if (is_null($new)) continue;

                $label = self::getFieldLabel($key);
                $val = self::formatValue($key, $new);
                $changes[] = "กำหนดค่า <span class='fw-bold text-primary'>{$label}</span> เป็น: {$val}";
            }
            return $changes;
        }

        // กรณี update: เปรียบเทียบ old กับ attributes แสดงเฉพาะที่ต่างกัน
        if ($log->action === 'update' && isset($properties['old'], $properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $new) {
                $old = $properties['old'][$key] ?? null;
                // loose comparison เพราะ DB อาจคืน string vs int — ปกติ != พอ
                if ($old != $new) {
                    if (in_array($key, ['updated_at'])) continue;

                    $label = self::getFieldLabel($key);
                    $oldVal = self::formatValue($key, $old);
                    $newVal = self::formatValue($key, $new);
                    $changes[] = "เปลี่ยน <span class='fw-bold text-primary'>{$label}</span> จาก <em>{$oldVal}</em> เป็น <strong>{$newVal}</strong>";
                }
            }
            return $changes;
        }

        // Fallback: action อื่นๆ ที่มี attributes
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $new) {
                if (in_array($key, ['updated_at', 'created_at'])) continue;
                $label = self::getFieldLabel($key);
                $val = self::formatValue($key, $new);
                $changes[] = "<span class='fw-bold'>{$label}</span>: {$val}";
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
        ActivityLog::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'description'  => $description,
            'properties'   => !empty($properties) ? $properties : null,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
        ]);
    }

    // === Private formatters (extracted จาก formatValue) ===

    private static function formatUserId($value): string
    {
        $user = User::find($value);
        return $user ? "{$user->name}" : "User ID: $value";
    }

    private static function formatEmployerId($value): string
    {
        $employer = Employer::find($value);
        if (!$employer) return "Employer ID: $value";
        return $employer->name_th ?? $employer->name ?? "Employer ID: $value";
    }

    private static function formatEmployeeId($value): string
    {
        $employee = Employee::find($value);
        if (!$employee) return "Employee ID: $value";
        return $employee->employeeNameTh ?? $employee->employeeNameEn ?? "Employee ID: $value";
    }

    private static function formatTicketId($value): string
    {
        $ticket = JobTicket::find($value);
        return $ticket ? "Ticket #{$ticket->id} ({$ticket->subject})" : "Ticket ID: $value";
    }

    private static function formatStatusLabel($value)
    {
        return self::STATUS_LABELS[$value] ?? $value;
    }

    private static function formatInsuranceLabel($value)
    {
        return self::INSURANCE_LABELS[$value] ?? $value;
    }

    private static function formatArrayValue(array $value): string
    {
        return '<pre class="mb-0" style="font-size: 0.85em;">'
            . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . '</pre>';
    }

    /**
     * Heuristic ตรวจว่า string น่าจะเป็น date หรือ datetime ไหม
     *  - ความยาว 10 (Y-m-d) หรือ > 15 (Y-m-d H:i:s + อื่นๆ)
     *  - strtotime parse ได้
     */
    private static function looksLikeDate($value): bool
    {
        if (!is_string($value)) return false;
        $len = strlen($value);
        return ($len === 10 || $len > 15) && strtotime($value) !== false;
    }

    private static function formatDateValue($value)
    {
        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Exception $e) {
            return $value;
        }
    }
}
