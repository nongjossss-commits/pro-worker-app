<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesLeadEmployee extends Model
{
    use HasFactory, SoftDeletes;

    protected function employeeNameEn(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $suffix = $this->attributes['name_suffix'] ?? null;
                return $suffix ? $value . ' (' . $suffix . ')' : $value;
            },
        );
    }

    protected $fillable = [
        'sales_lead_id',
        'employee_id',
        // Personal Info
        'employeeTitleTh',
        'employeeTitleEn',
        'employeeNameEn',
        'name_suffix',
        'employeeNameTh',
        'employeeGender',
        'employeeDob',
        'height',
        'weight',
        'father_name',
        'mother_name',
        'photo_path',
        // Contact & Nationality
        'employeePhone',
        'employeeNationality',
        'passportType',
        'passport_type_cambodia',
        // Passport & Visa
        'employeePassport',
        'passport_issue_place',
        'passport_issue_date',
        'passportExpiryDate',
        'pinkCardNo',
        'visaType',
        'visa_issue_place',
        'visaExpiryDate',
        // Employment & Work IDs
        'job_title',
        'job_description',
        'startDate',
        'employeeWorkPermit',
        'workPermitExpiryDate',
        'ninetyDayReportDate',
        'workPermitMOUGroup',
        'workPermitMOUGroupOther',
        'name_list_number',
        'request_number',
        'employee_id_number',
        'tax_id_number',
        'employer_employee_id',
        'department',
        'employee_reference_id',
        'bank_name',
        'bank_account_number',
        'system_employee_id',
        'employeeAddress',
        // Health Insurance
        'insurance_type',
        'social_security_number',
        'insurance_detail_social',
        'insurance_document_path_social',
        'sso_issue_date',
        'sso_expiry_date',
        'insurance_detail_hospital',
        'insurance_expiry_date_hospital',
        'insurance_document_path_hospital',
        'insurance_detail_private',
        'insurance_expiry_date_private',
        'insurance_document_path_private',
        'medical_certificate_path',
        'medical_hospital_name',
        // Login/Other
        'employeeEmail',
        'employeePassword',
        'outsource_code',
        // Documents (1-4 standard + visa)
        'employee_doc_1',
        'employee_doc_2',
        'employee_doc_visa',
        'employee_doc_3',
        'employee_doc_4',
        'employee_doc_5',
        'employee_doc_6',
        'employee_doc_7',
        'employee_doc_8',
        // Documents (9-18 other)
        'employee_doc_9',
        'employee_doc_10',
        'employee_doc_11',
        'employee_doc_12',
        'employee_doc_13',
        'employee_doc_14',
        'employee_doc_15',
        'employee_doc_16',
        'employee_doc_17',
        'employee_doc_18',
        // Other doc descriptions (legacy)
        'employee_doc_other_1',
        'other_doc_1_desc',
        'employee_doc_other_2',
        'other_doc_2_desc',
        'employee_doc_other_3',
        'other_doc_3_desc',
        'other_doc_4_desc',
        'other_doc_5_desc',
        'other_doc_6_desc',
        'other_doc_7_desc',
        'other_doc_8_desc',
        'other_doc_9_desc',
        'other_doc_10_desc',
    ];

    protected $casts = [
        'employeeDob' => 'date',
        'passportExpiryDate' => 'date',
        'workPermitExpiryDate' => 'date',
        'visaExpiryDate' => 'date',
        'passport_issue_date' => 'date',
        'startDate' => 'date',
        'ninetyDayReportDate' => 'date',
        'sso_issue_date' => 'date',
        'sso_expiry_date' => 'date',
        'insurance_expiry_date_hospital' => 'date',
        'insurance_expiry_date_private' => 'date',
    ];

    public function salesLead()
    {
        return $this->belongsTo(SalesLead::class, 'sales_lead_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
