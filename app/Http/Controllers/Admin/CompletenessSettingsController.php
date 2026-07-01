<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use App\Models\Employee;
use Illuminate\Http\Request;

class CompletenessSettingsController extends Controller
{
    public function index()
    {
        // Fetch current settings
        $config = SystemConfig::where('key', 'employee_mandatory_fields')->first();
        $selectedFields = $config ? json_decode($config->value, true) : [];

        // Define available fields to match the Employee Edit Form (edit.blade.php) exactly
        // Keys must be valid columns in the 'employees' table.
        $fieldGroups = [
            '1. Personal Information (ข้อมูลส่วนตัว)' => [
                'employeeTitleTh' => 'คำนำหน้าชื่อ (ไทย) / Title (TH)',
                'employeeNameTh' => 'ชื่อ-สกุล (ไทย) / Name (TH)',
                'employeeTitleEn' => 'Prefix (EN)',
                'employeeNameEn' => 'Full Name (EN)',
                'father_name' => 'ชื่อพ่อ / Father Name',
                'mother_name' => 'ชื่อแม่ / Mother Name',
                'employeeDob' => 'วันเดือนปีเกิด / Date of Birth',
                'employeePhoto' => 'รูปภาพพนักงาน / Photo',
            ],
            '2. Contact & Nationality (ข้อมูลการติดต่อและสัญชาติ)' => [
                'employeePhone' => 'เบอร์โทรศัพท์ / Phone',
                'employeeNationality' => 'สัญชาติ / Nationality',
                'passportType' => 'ประเภทหนังสือเดินทาง (พม่า) / Passport Type (Myanmar)',
                'passport_type_cambodia' => 'ประเภทหนังสือเดินทาง (กัมพูชา) / Passport Type (Cambodia)',
            ],
            '3. Passport & Visa (ข้อมูลหนังสือเดินทางและวีซ่า)' => [
                'employeePassport' => 'เลขพาสปอร์ต / Passport No',
                'passport_issue_date' => 'วันออกพาสปอร์ต / Passport Issue Date',
                'passportExpiryDate' => 'วันหมดอายุพาสปอร์ต / Passport Expiry',
                'pinkCardNo' => 'เลขบัตรชมพู / Pink Card No',
                'visaType' => 'ประเภทวีซ่า / Visa Type',
                'visaEndorsementDate' => 'วันที่ตรวจลงตราวีซ่า / Visa Endorsement Date',
                'visaEndorsementNo' => 'เลขที่ตรวจลงตราวีซ่า / Visa Endorsement No',
                'visaExpiryDate' => 'วันหมดอายุวีซ่า / Visa Expiry',
            ],
            '4. Employment & Work IDs (ข้อมูลการจ้างงานและเอกสาร)' => [
                'job_title' => 'ตำแหน่งงาน / Job Title',
                'job_description' => 'ลักษณะงาน / Job Description',
                'startDate' => 'วันที่เริ่มงาน / Start Date',
                'employeeWorkPermit' => 'เลข Work Permit / Work Permit No',
                'workPermitExpiryDate' => 'วันหมดอายุ Work Permit / Work Permit Expiry',
                'ninetyDayReportDate' => 'วันรายงานตัว 90 วัน / 90 Day Report',
                'workPermitMOUGroup' => 'ประเภทใบอนุญาตทำงาน / MOU Group',
                'workPermitMOUGroupOther' => 'ระบุประเภทอื่นๆ / Other MOU Group',
                'name_list_number' => 'เลข Name List',
                'request_number' => 'เลขที่คำขอ / Request Number',
                'employee_id_number' => 'เลขประจำตัว / ID Number',
                'tax_id_number' => 'เลขประจำตัวผู้เสียภาษี / Tax ID',
                'employer_employee_id' => 'รหัสคนงาน-นายจ้าง / Employer-Employee ID',
                'employee_reference_id' => 'เลขอ้างอิงคนงาน / Reference ID',
            ],
            '5. Health Insurance (ข้อมูลประกันสุขภาพ)' => [
                'insurance_type' => 'ประเภทประกัน / Insurance Type',
                // Social Security
                'social_security_number' => 'เลขประกันสังคม / Social Security No',
                'insurance_detail' => 'สิทธิ์โรงพยาบาล / Hospital Rights (Social)',
                // Hospital Insurance
                'insurance_detail_hospital' => 'ชื่อโรงพยาบาล / Hospital Name',
                'insurance_expiry_date_hospital' => 'วันหมดอายุประกัน (รพ) / Hospital Insurance Expiry',
                // Private Insurance
                'insurance_detail_private' => 'บริษัทประกัน / Insurance Company',
                'insurance_expiry_date_private' => 'วันหมดอายุประกัน (เอกชน) / Private Insurance Expiry',
                // Private Insurance File (Stored in 'insurance_document_path_private' column per controller logic)
                'insurance_document_path_private' => 'ไฟล์เอกสารประกัน (เอกชน) / Private Insurance File',
            ],
            '6. Login Information (ข้อมูลการเข้าสู่ระบบ)' => [
                'email' => 'อีเมล / Email',
                'password' => 'รหัสผ่าน / Password',
            ],
            '7. File Attachments (ส่วนแนบไฟล์เอกสาร)' => [
                // The form uses 'employee_doc_X'
                'employee_doc_1' => '1. พาสปอร์ต / Passport',
                'employee_doc_2' => '2. วีซ่า / Visa',
                'employee_doc_3' => '3. ใบเสร็จ Work Permit / Work Permit Receipt',
                'employee_doc_4' => '4. บัตรชมพู / Pink Card',
                'employee_doc_5' => '5. ทร. 38 / Tor Ror 38',
                'employee_doc_6' => '6. รายงานตัว 90 วัน / 90 Day Report',
                'employee_doc_7' => '7. ใบแจ้งที่พักอาศัย / Residence Notification',
                'employee_doc_8' => '8. เอกสารบ้านเกิด / Home Country Doc',
                'employee_doc_9' => '9. เอกสารอื่นๆ 1 / Other 1',
                'employee_doc_10' => '10. เอกสารอื่นๆ 2 / Other 2',
                'employee_doc_11' => '11. เอกสารอื่นๆ 3 / Other 3',
                'employee_doc_12' => '12. เอกสารอื่นๆ 4 / Other 4',
            ],
        ];

        return view('admin.settings.completeness', compact('selectedFields', 'fieldGroups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fields' => 'array',
            'fields.*' => 'string',
        ]);

        SystemConfig::updateOrCreate(
            ['key' => 'employee_mandatory_fields'],
            ['value' => json_encode($data['fields'] ?? [])]
        );

        return back()->with('success', 'Completeness settings updated successfully.');
    }
}
