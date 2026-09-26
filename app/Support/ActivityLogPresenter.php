<?php

namespace App\Support;

use App\Helpers\ActivityLogHelper;
use App\Models\ActivityLog;
use App\Models\Employer;
use App\Models\ResolutionTab;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Turns an ActivityLog row into sentences a user can read — used by the
 * Activity Logs pages (day view + per-employee/employer history):
 *
 *   sentence()     "แก้ไขข้อมูลลูกจ้าง Ophelia Langworth (รหัส 128) — 2 รายการ"
 *   changeLines()  ["เลข RA (outsource): จาก - เป็น 6516…",
 *                   "แนบไฟล์ในช่อง 1. พาสปอร์ต: passport.pdf", …]
 *   device()       "Chrome · Windows"
 *
 * Works on the data already stored (old logs included) — nothing is
 * rewritten in the database. Field names use the same wording as the forms.
 */
class ActivityLogPresenter
{
    /** Thai name of each logged model. */
    public const MODEL_NAMES = [
        'Employee' => 'ลูกจ้าง',
        'Employer' => 'นายจ้าง',
        'Agent' => 'ตัวแทน',
        'Importer' => 'บริษัทนำเข้า',
        'Delegate' => 'ผู้แทน',
        'User' => 'บัญชีผู้ใช้',
        'JobTicket' => 'ใบงาน',
        'TicketMessage' => 'ข้อความในใบงาน',
        'ProWorkerContract' => 'สัญญา',
    ];

    /** Columns that are bookkeeping, not something a user changed. */
    protected const SKIP_FIELDS = [
        'updated_at', 'created_at', 'deleted_at', 'remember_token', 'last_daily_checked_at',
        'resolution_settings_applied', 'appointment_updated_by', 'appointment_updated_at', 'email_verified_at',
    ];

    protected const EMPLOYEE_FIELDS = [
        'employeeTitleTh' => 'คำนำหน้าชื่อ (ไทย)', 'employeeNameTh' => 'ชื่อ-สกุล (ไทย)',
        'employeeTitleEn' => 'คำนำหน้าชื่อ (อังกฤษ)', 'employeeNameEn' => 'ชื่อ-สกุล (อังกฤษ)',
        'english_prefix' => 'Prefix (EN)', 'name_suffix' => 'ต่อท้ายชื่อ (EN)',
        'father_name' => 'ชื่อพ่อ', 'mother_name' => 'ชื่อแม่', 'employeeDob' => 'วันเดือนปีเกิด',
        'employeePhoto' => 'รูปถ่ายลูกจ้าง', 'height' => 'ส่วนสูง', 'weight' => 'น้ำหนัก',
        'employeePhone' => 'เบอร์โทรศัพท์', 'employeeNationality' => 'สัญชาติ',
        'passportType' => 'ประเภทหนังสือเดินทาง (เมียนมา)', 'passport_type_cambodia' => 'ประเภทหนังสือเดินทาง (กัมพูชา)',
        'employeePassport' => 'เลขพาสปอร์ต', 'passport_issue_date' => 'วันออกพาสปอร์ต',
        'passport_issue_place' => 'สถานที่ออกพาสปอร์ต', 'passportExpiryDate' => 'วันหมดอายุพาสปอร์ต',
        'pinkCardNo' => 'เลขบัตรชมพู', 'visaType' => 'ประเภทวีซ่า', 'visa_issue_place' => 'สถานที่ออกวีซ่า',
        'visaEndorsementDate' => 'วันที่ตรวจลงตราวีซ่า', 'visaEndorsementNo' => 'เลขที่ตรวจลงตราวีซ่า',
        'visaExpiryDate' => 'วันหมดอายุวีซ่า', 'job_title' => 'ตำแหน่งงาน', 'employeePosition' => 'ตำแหน่ง',
        'job_description' => 'ลักษณะงาน', 'nature_of_work' => 'ลักษณะงาน (Nature of Work)', 'department' => 'แผนก',
        'startDate' => 'วันที่เริ่มงาน', 'employeeWorkPermit' => 'เลขใบอนุญาตทำงาน',
        'workPermitIssueDate' => 'วันที่ออกใบอนุญาตทำงาน', 'workPermitExpiryDate' => 'วันหมดอายุใบอนุญาตทำงาน',
        'ninetyDayReportDate' => 'วันรายงานตัว 90 วัน', 'workPermitMOUGroup' => 'ประเภทใบอนุญาตทำงาน',
        'workPermitMOUGroupOther' => 'ประเภทใบอนุญาตทำงาน (อื่นๆ)', 'name_list_number' => 'เลข RA (outsource)',
        'request_number' => 'เลขที่คำขอ', 'registration_request_number' => 'เลขที่คำขอ (มติลงทะเบียน)',
        'renewal_request_number' => 'เลขที่คำขอ (มติต่ออายุ)', 'employee_id_number' => 'เลขประจำตัว',
        'tax_id_number' => 'เลขประจำตัวผู้เสียภาษี', 'employer_employee_id' => 'รหัสคนงาน-นายจ้าง',
        'employee_reference_id' => 'เลขอ้างอิงคนงาน', 'outsource_code' => 'รหัส outsource',
        'insurance_type' => 'ประเภทประกัน', 'social_security_number' => 'เลขประกันสังคม',
        'insurance_detail' => 'สิทธิ์โรงพยาบาล (ประกันสังคม)', 'insurance_expiry_date' => 'วันหมดอายุประกัน',
        'insurance_detail_hospital' => 'ชื่อโรงพยาบาล (ประกันโรงพยาบาล)', 'insurance_expiry_date_hospital' => 'วันหมดอายุประกัน (โรงพยาบาล)',
        'insurance_detail_private' => 'บริษัทประกัน', 'insurance_expiry_date_private' => 'วันหมดอายุประกัน (เอกชน)',
        'insurance_document_path' => 'ไฟล์เอกสารประกัน', 'insurance_document_path_private' => 'ไฟล์เอกสารประกัน (เอกชน)',
        'sso_issue_date' => 'วันออกบัตรประกันสังคม', 'sso_expiry_date' => 'วันหมดอายุบัตรประกันสังคม',
        'designatedHospital' => 'โรงพยาบาลที่กำหนด', 'medical_certificate_path' => 'ใบรับรองแพทย์',
        'medical_hospital_name' => 'โรงพยาบาลที่ออกใบรับรองแพทย์', 'email' => 'อีเมล', 'password' => 'รหัสผ่าน',
        'bank_name' => 'ธนาคาร', 'bank_account_number' => 'เลขบัญชีธนาคาร', 'employer_id' => 'นายจ้าง',
        'operator_id' => 'ผู้ดำเนินการ', 'custom_operator_name' => 'ผู้ดำเนินการ (ระบุเอง)', 'status' => 'สถานะ',
        'terminated_at' => 'วันที่แจ้งออก', 'termination_reason' => 'เหตุผลที่แจ้งออก',
        'biometrics_collected_at' => 'วันที่เก็บข้อมูลชีวมิติ', 'appointment_date' => 'วันนัดหมาย',
        'appointment_location' => 'สถานที่นัดหมาย', 'appointment_completed_at' => 'วันที่ไปตามนัดแล้ว',
        'resolution_completed_at' => 'วันที่ดำเนินการมติเสร็จ', 'registration_remarks' => 'หมายเหตุ (มติลงทะเบียน)',
        'renewal_remarks' => 'หมายเหตุ (มติต่ออายุ)', 'resolution_tab_id' => 'แถบมติ', 'signature_path' => 'ลายเซ็น',
        'daily_check_enabled' => 'เช็คงานรายวัน',
        'employee_doc_1' => 'ไฟล์ 1. พาสปอร์ต', 'employee_doc_2' => 'ไฟล์ 2. วีซ่า',
        'employee_doc_3' => 'ไฟล์ 3. ใบเสร็จ Work Permit', 'employee_doc_4' => 'ไฟล์ 4. บัตรชมพู',
        'employee_doc_5' => 'ไฟล์ 5. ทร. 38', 'employee_doc_6' => 'ไฟล์ 6. รายงานตัว 90 วัน',
        'employee_doc_7' => 'ไฟล์ 7. ใบแจ้งที่พักอาศัย', 'employee_doc_8' => 'ไฟล์ 8. เอกสารบ้านเกิด',
    ];

    protected const EMPLOYER_FIELDS = [
        'employerNameTh' => 'ชื่อนายจ้าง (ไทย)', 'employerNameEn' => 'ชื่อนายจ้าง (อังกฤษ)', 'name_suffix' => 'ต่อท้ายชื่อ',
        'employerId' => 'รหัสนายจ้าง', 'employerTaxId' => 'เลขประจำตัวนายจ้าง', 'businessType' => 'ประเภทกิจการ (ไทย)',
        'businessTypeEn' => 'ประเภทกิจการ (อังกฤษ)', 'regCapital' => 'ทุนจดทะเบียน', 'regDate' => 'วันที่จดทะเบียน',
        'minimum_wage' => 'ค่าแรงขั้นต่ำ', 'signerNameTh' => 'ผู้ลงนาม 1 (ไทย)', 'signerNameEn' => 'ผู้ลงนาม 1 (อังกฤษ)',
        'signer_2_name_th' => 'ผู้ลงนาม 2 (ไทย)', 'signer_2_name_en' => 'ผู้ลงนาม 2 (อังกฤษ)',
        'signature_1_path' => 'ลายเซ็นผู้ลงนาม 1', 'signature_2_path' => 'ลายเซ็นผู้ลงนาม 2',
        'employer_stamp_path' => 'ตราประทับบริษัท', 'employer_stamp_width_mm' => 'ความกว้างตราประทับ (มม.)',
        'employer_stamp_height_mm' => 'ความสูงตราประทับ (มม.)', 'assigned_staff_id' => 'ผู้ดูแล (Admin)',
        'job_owner_id' => 'เจ้าของงาน', 'user_id' => 'บัญชีผู้ใช้ของนายจ้าง', 'employerEmail' => 'อีเมล',
        'employerPassword' => 'รหัสผ่าน', 'employerPhone' => 'เบอร์โทร', 'socialSecurityHospital' => 'รพ. ประกันสังคม',
        'employer_doc_company' => 'ไฟล์หนังสือรับรองบริษัท / บัตรประชาชน', 'employer_doc_company_expiry' => 'วันหมดอายุหนังสือรับรอง',
        'employer_doc_lease' => 'ไฟล์สัญญาเช่า / ทะเบียนบ้าน', 'employer_doc_construction' => 'ไฟล์สัญญาก่อสร้าง / แผนที่',
        'outsource_re_code' => 'RE Code', 'outsource_password' => 'รหัสผ่าน outsource',
        'registration_resolution_status' => 'สถานะมติลงทะเบียน', 'registration_resolution_note' => 'หมายเหตุมติลงทะเบียน',
        'renewal_resolution_note' => 'หมายเหตุมติต่ออายุ',
    ];

    protected const AGENT_FIELDS = [
        'agentNameEn' => 'ชื่อตัวแทน', 'agentLicense' => 'เลขใบอนุญาต', 'agentPhone' => 'เบอร์โทร',
        'agentEmail' => 'อีเมล', 'agentAddress' => 'ที่อยู่',
    ];

    protected const IMPORTER_FIELDS = [
        'importerNameTh' => 'ชื่อบริษัทนำเข้า (ไทย)', 'importerNameEn' => 'ชื่อบริษัทนำเข้า (อังกฤษ)',
        'importerId' => 'เลขประจำตัว', 'importerLicenseNo' => 'เลขใบอนุญาต', 'importerLicenseIssueDate' => 'วันออกใบอนุญาต',
        'importerLicenseExpiryDate' => 'วันหมดอายุใบอนุญาต', 'importerSignerTh' => 'ผู้ลงนาม 1 (ไทย)',
        'importerSignerEn' => 'ผู้ลงนาม 1 (อังกฤษ)', 'signer_2_name_th' => 'ผู้ลงนาม 2 (ไทย)', 'signer_2_name_en' => 'ผู้ลงนาม 2 (อังกฤษ)',
        'signature_1_path' => 'ลายเซ็นผู้ลงนาม 1', 'signature_2_path' => 'ลายเซ็นผู้ลงนาม 2', 'importer_stamp_path' => 'ตราประทับบริษัท',
        'importer_stamp_width_mm' => 'ความกว้างตราประทับ (มม.)', 'importer_stamp_height_mm' => 'ความสูงตราประทับ (มม.)',
    ];

    protected const DELEGATE_FIELDS = [
        'delegateNameTh' => 'ชื่อผู้แทน (ไทย)', 'delegateNameEn' => 'ชื่อผู้แทน (อังกฤษ)', 'delegateId' => 'เลขบัตรประชาชน',
        'delegateEmployeeId' => 'รหัสพนักงาน', 'delegateIssueDate' => 'วันออกบัตร', 'delegateExpiryDate' => 'วันหมดอายุบัตร',
        'delegatePhone' => 'เบอร์โทร', 'delegateEmail' => 'อีเมล', 'delegatePhoto' => 'รูปถ่ายผู้แทน',
        'signature_path' => 'ลายเซ็น', 'outsource_password' => 'รหัสผ่าน outsource',
    ];

    protected const USER_FIELDS = [
        'name' => 'ชื่อ', 'email' => 'อีเมล', 'password' => 'รหัสผ่าน', 'labor_access_level' => 'สิทธิ์เข้าโมดูล Labour',
    ];

    protected const STATUS_VALUES = [
        'registration_pending' => 'มติลงทะเบียน — รอดำเนินการ', 'registration_completed' => 'มติลงทะเบียน — เสร็จแล้ว',
        'registration_cancelled' => 'มติลงทะเบียน — ยกเลิก', 'renewal_pending' => 'มติต่ออายุ — รอดำเนินการ',
        'renewal_completed' => 'มติต่ออายุ — เสร็จแล้ว', 'renewal_cancelled' => 'มติต่ออายุ — ยกเลิก',
        'active' => 'ใช้งาน', 'inactive' => 'ไม่ใช้งาน', 'terminated' => 'แจ้งออกแล้ว', 'pending' => 'รอดำเนินการ',
        'completed' => 'เสร็จแล้ว', 'cancelled' => 'ยกเลิก', 'pending_staff' => 'รอเจ้าหน้าที่',
        'in_progress' => 'กำลังดำเนินการ', 'resolved' => 'เสร็จสิ้น', 'rejected' => 'ปฏิเสธ',
    ];

    protected const FILE_EXTENSIONS = '/\.(pdf|jpe?g|png|gif|webp|heic|bmp|tiff?|docx?|xlsx?|zip)$/i';

    // ---------------------------------------------------------------- public

    public static function modelName(?string $class): string
    {
        $base = $class ? class_basename($class) : '';
        return self::MODEL_NAMES[$base] ?? ActivityLogHelper::formatModel($class ?? '');
    }

    /** One readable sentence for the "รายละเอียด" column. */
    public static function sentence(ActivityLog $log): string
    {
        $props = is_array($log->properties) ? $log->properties : [];

        switch ($log->action) {
            case 'login':
                return 'เข้าสู่ระบบสำเร็จ';
            case 'login_failed':
                return 'เข้าสู่ระบบไม่สำเร็จ (รหัสผ่านหรืออีเมลไม่ถูกต้อง)'
                    . (!empty($props['email']) ? ' — อีเมลที่ใช้: ' . $props['email'] : '');
            case 'logout':
                return match ($props['reason'] ?? null) {
                    'inactive' => 'ระบบออกจากระบบให้อัตโนมัติ (ปิดเบราว์เซอร์ / ปิดแอป หรือไม่ได้เปิดโปรแกรมเกิน 5 นาที)',
                    default => 'ออกจากระบบ',
                };
        }

        if (!in_array($log->action, ['create', 'update', 'delete', 'force_delete', 'restore'], true)
            || !$log->subject_type
            || !self::isGenericDescription($log->description)) {
            return (string) $log->description;
        }

        $what = self::modelName($log->subject_type);
        $who = self::subjectLabel($log);

        return match ($log->action) {
            'create' => "เพิ่ม{$what}ใหม่{$who}",
            'update' => "แก้ไขข้อมูล{$what}{$who}" . (($n = count(self::changeLines($log))) ? " — {$n} รายการ" : ''),
            'delete' => "ย้าย{$what}{$who} ไปถังขยะ",
            'force_delete' => "ลบ{$what}{$who} ออกถาวร",
            'restore' => "กู้คืน{$what}{$who} จากถังขยะ",
        };
    }

    /**
     * Human lines describing what changed. HTML-safe (values are escaped);
     * file names link to the file when it still exists.
     */
    public static function changeLines(ActivityLog $log): array
    {
        $props = is_array($log->properties) ? $log->properties : [];
        $model = class_basename((string) $log->subject_type);

        if ($log->action === 'update' && isset($props['attributes'])) {
            return self::updateLines($log, $model, $props['old'] ?? [], $props['attributes']);
        }

        if ($log->action === 'create' && isset($props['attributes'])) {
            $lines = [];
            $files = 0;
            foreach ($props['attributes'] as $field => $value) {
                if ($value === null || $value === '' || in_array($field, self::SKIP_FIELDS, true) || $field === 'id') {
                    continue;
                }
                if (self::isFileField($field, $value)) {
                    $files++;
                    $lines[] = 'แนบไฟล์ในช่อง <strong>' . e(self::label($log, $model, $field)) . '</strong>: ' . self::fileLink($value);
                    continue;
                }
                if (self::isSecret($field)) {
                    continue;
                }
                $lines[] = '<strong>' . e(self::label($log, $model, $field)) . '</strong>: ' . e(self::value($field, $value));
            }
            return $lines;
        }

        // Other actions (export, transfer, …) that carry extra details
        $lines = [];
        foreach ($props as $key => $value) {
            if (in_array($key, ['subject_name', 'reason', 'device', 'email'], true) || is_array($value) && $key === 'old') {
                continue;
            }
            if (is_array($value)) {
                $value = implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE), $value));
            }
            $lines[] = '<strong>' . e(self::label($log, $model, (string) $key)) . '</strong>: ' . e(self::value((string) $key, $value));
        }
        return $lines;
    }

    /** "Chrome · Windows" from a user-agent string. */
    public static function device(?string $ua): string
    {
        if (!$ua) {
            return '-';
        }
        $browser = match (true) {
            str_contains($ua, 'Line/') => 'LINE',
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') => 'Opera',
            str_contains($ua, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'เบราว์เซอร์อื่น',
        };
        $os = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Mac OS') => 'Mac',
            str_contains($ua, 'Linux') => 'Linux',
            default => '',
        };
        return $browser . ($os ? ' · ' . $os : '');
    }

    // ---------------------------------------------------------------- internals

    protected static function updateLines(ActivityLog $log, string $model, array $old, array $new): array
    {
        $lines = [];
        $changed = [];
        foreach ($new as $field => $value) {
            $before = $old[$field] ?? null;
            if (in_array($field, self::SKIP_FIELDS, true) || $before == $value) {
                continue;
            }
            $changed[$field] = [$before, $value];
        }

        // File moved from one slot to another (the "move attachment files" tool)
        $used = [];
        foreach ($changed as $to => [$toBefore, $toAfter]) {
            if (!self::isFileField($to, $toAfter) || !$toAfter) {
                continue;
            }
            foreach ($changed as $from => [$fromBefore, $fromAfter]) {
                if ($from !== $to && !isset($used[$from]) && $fromBefore === $toAfter && self::isFileField($from, $fromBefore)) {
                    $lines[] = 'ย้ายไฟล์จากช่อง <strong>' . e(self::label($log, $model, $from)) . '</strong> ไปช่อง <strong>'
                        . e(self::label($log, $model, $to)) . '</strong>: ' . self::fileLink($toAfter);
                    $used[$from] = $used[$to] = true;
                    break;
                }
            }
        }

        foreach ($changed as $field => [$before, $after]) {
            if (isset($used[$field])) {
                // A moved-away slot that received nothing new stays covered by the move line.
                continue;
            }
            $label = '<strong>' . e(self::label($log, $model, $field)) . '</strong>';

            if (self::isFileField($field, $after) || self::isFileField($field, $before)) {
                if (!$before) {
                    $lines[] = "แนบไฟล์ในช่อง {$label}: " . self::fileLink($after);
                } elseif (!$after) {
                    $lines[] = "ลบไฟล์ออกจากช่อง {$label} (ไฟล์เดิม: " . e(basename((string) $before)) . ')';
                } else {
                    $lines[] = "เปลี่ยนไฟล์ในช่อง {$label}: " . e(basename((string) $before)) . ' → ' . self::fileLink($after);
                }
                continue;
            }

            if (self::isSecret($field)) {
                $lines[] = "เปลี่ยน{$label}";
                continue;
            }

            $b = self::value($field, $before);
            $a = self::value($field, $after);
            if ($before === null || $before === '') {
                $lines[] = "กรอก{$label}: <strong>" . e($a) . '</strong>';
            } elseif ($after === null || $after === '') {
                $lines[] = "ลบค่า{$label} (เดิม: " . e($b) . ')';
            } else {
                $lines[] = "{$label}: จาก <span class=\"text-muted\">" . e($b) . '</span> เป็น <strong>' . e($a) . '</strong>';
            }
        }

        return $lines;
    }

    protected static function subjectLabel(ActivityLog $log): string
    {
        $name = ActivityLogHelper::getSubjectName($log);
        $id = $log->subject_id ? 'รหัส ' . $log->subject_id : '';
        if ($name && $id) {
            return " {$name} ({$id})";
        }
        return $name ? " {$name}" : ($id ? " ({$id})" : '');
    }

    /** Stored descriptions like "Update Employee" say nothing — rebuild those. */
    protected static function isGenericDescription(?string $description): bool
    {
        return $description === null || $description === ''
            || (bool) preg_match('/^(Create|Update|Delete|Force_delete|Restore) [A-Za-z]+$/', $description);
    }

    protected static function label(ActivityLog $log, string $model, string $field): string
    {
        $props = is_array($log->properties) ? $log->properties : [];
        $record = $props['attributes'] ?? [];
        if (preg_match('/doc_(other_)?\d+$/', $field)) {
            // "Other documents" slots are named per record — use that record's description
            try {
                $subject = $log->relationLoaded('subject') ? $log->subject : null;
                if ($subject) {
                    $record += $subject->getAttributes();
                }
            } catch (\Throwable $e) {
            }
        }
        return self::fieldLabel($model, $field, $record);
    }

    /**
     * Field name as shown on the forms. $model is the class basename
     * (Employee, Employer, …); $record (column => value) lets "other
     * document" slots show their own description, e.g. "ไฟล์เอกสารอื่นๆ 1 (ใบเปลี่ยนนายจ้าง)".
     */
    public static function fieldLabel(string $model, string $field, array $record = []): string
    {
        $map = match ($model) {
            'Employee' => self::EMPLOYEE_FIELDS,
            'Employer' => self::EMPLOYER_FIELDS,
            'Agent' => self::AGENT_FIELDS,
            'Importer' => self::IMPORTER_FIELDS,
            'Delegate' => self::DELEGATE_FIELDS,
            'User' => self::USER_FIELDS,
            default => [],
        };
        if (isset($map[$field])) {
            return $map[$field];
        }

        $desc = fn (string $descField) => !empty($record[$descField]) ? ' (' . $record[$descField] . ')' : '';

        if ($model === 'Employee' && preg_match('/^employee_doc_(\d+)$/', $field, $m) && (int) $m[1] >= 9) {
            $n = (int) $m[1] - 8;
            return 'ไฟล์เอกสารอื่นๆ ' . $n . $desc("other_doc_{$n}_desc");
        }
        if ($model === 'Employee' && preg_match('/^other_doc_(\d+)_desc$/', $field, $m)) {
            return 'ชื่อเอกสารอื่นๆ ' . $m[1];
        }
        if (preg_match('/^(employer|agent|importer|delegate)_doc_other_(\d+)(_desc)?$/', $field, $m)) {
            return empty($m[3])
                ? 'ไฟล์เอกสารอื่นๆ ' . $m[2] . $desc("{$m[1]}_doc_other_{$m[2]}_desc")
                : 'ชื่อเอกสารอื่นๆ ' . $m[2];
        }
        if (preg_match('/^qty_(\w+)$/', $field, $m)) {
            return 'จำนวน (' . $m[1] . ')';
        }

        return ActivityLogHelper::getFieldLabel($field);
    }

    protected static function isSecret(string $field): bool
    {
        return str_contains(strtolower($field), 'password') || $field === 'remember_token';
    }

    protected static function isFileField(string $field, $value): bool
    {
        if (preg_match('/(_desc|_expiry|_mm|_date|_at)$/i', $field)) {
            return false;
        }
        if (is_string($value) && $value !== '' && str_contains($value, '/') && preg_match(self::FILE_EXTENSIONS, $value)) {
            return true;
        }
        return (bool) preg_match('/(employee_doc_\d+|_doc_|_path(_private)?$|Photo$|photo$)/', $field) && ($value === null || is_string($value));
    }

    protected static function fileLink($path): string
    {
        if (!$path) {
            return '-';
        }
        $name = e(basename((string) $path));
        try {
            if (Storage::disk('public')->exists($path)) {
                return '<a href="' . e(asset('storage/' . ltrim($path, '/'))) . '" target="_blank" rel="noopener"><i class="bi bi-paperclip"></i> ' . $name . '</a>';
            }
        } catch (\Throwable $e) {
        }
        return '<i class="bi bi-paperclip"></i> ' . $name;
    }

    protected static function value(string $field, $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }
        if (is_bool($value) || in_array($field, ['daily_check_enabled', 'is_active', 'is_visible'], true)) {
            return $value ? 'เปิด' : 'ปิด';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        if (in_array($field, ['employer_id'], true)) {
            $e = Employer::withoutGlobalScopes()->withTrashed()->find($value);
            return $e ? ($e->employerNameTh ?: $e->employerNameEn) . " (รหัส {$value})" : "นายจ้างรหัส {$value}";
        }
        if (in_array($field, ['operator_id', 'assigned_staff_id', 'job_owner_id', 'user_id', 'employer_user_id', 'created_by'], true)) {
            $u = User::find($value);
            return $u ? $u->name : "ผู้ใช้รหัส {$value}";
        }
        if ($field === 'resolution_tab_id') {
            $t = ResolutionTab::withTrashed()->find($value);
            return $t ? $t->name : "แถบรหัส {$value}";
        }
        if (in_array($field, ['status', 'registration_resolution_status'], true)) {
            return self::STATUS_VALUES[$value] ?? (string) $value;
        }
        $str = (string) $value;
        if (preg_match('/^\d{4}-\d{2}-\d{2}( 00:00:00)?$/', $str) || preg_match('/^\d{4}-\d{2}-\d{2}T00:00:00/', $str)) {
            return Carbon::parse($str)->format('d/m/Y');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/', $str)) {
            return Carbon::parse($str)->timezone(config('app.timezone'))->format('d/m/Y H:i');
        }
        return mb_strlen($str) > 150 ? mb_substr($str, 0, 150) . '…' : $str;
    }
}
