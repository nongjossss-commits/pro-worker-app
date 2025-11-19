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

        // Define available fields (grouped for better UI)
        $fieldGroups = [
            'Personal Info' => [
                'employeeNameTh' => 'Name (TH)',
                'employeeNameEn' => 'Name (EN)',
                'employeeTitleTh' => 'Title (TH)',
                'employeeTitleEn' => 'Title (EN)',
                'employeeDob' => 'Date of Birth',
                'employeeNationality' => 'Nationality',
                'employeePhone' => 'Phone',
                'email' => 'Email',
                'employeePosition' => 'Position',
                'job_title' => 'Job Title',
                'job_description' => 'Job Description',
            ],
            'Identification' => [
                'employeePassport' => 'Passport No',
                'passportExpiryDate' => 'Passport Expiry',
                'passport_issue_date' => 'Passport Issue Date',
                'passportType' => 'Passport Type (Myanmar)',
                'passport_type_cambodia' => 'Passport Type (Cambodia)',
                'pinkCardNo' => 'Pink Card No',
                'employeeWorkPermit' => 'Work Permit No',
                'workPermitExpiryDate' => 'Work Permit Expiry',
                'visaType' => 'Visa Type',
                'visaExpiryDate' => 'Visa Expiry',
                'ninetyDayReportDate' => '90 Day Report',
            ],
            'Insurance' => [
                'insurance_type' => 'Insurance Type',
                'social_security_number' => 'Social Security No',
                'insurance_detail_hospital' => 'Hospital Insurance Detail',
                'insurance_expiry_date_hospital' => 'Hospital Insurance Expiry',
                'insurance_detail_private' => 'Private Insurance Detail',
                'insurance_expiry_date_private' => 'Private Insurance Expiry',
            ],
            'Documents (Images/Files)' => [
                'employeePhoto' => 'Photo',
                'passport_file_path' => 'Passport File',
                'work_permit_file_path' => 'Work Permit File',
                'visa_file_path' => 'Visa File',
                'pink_card_file_path' => 'Pink Card File',
                'insurance_attachment_path' => 'Insurance File',
            ],
            // Add more as needed based on Employee $fillable
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
