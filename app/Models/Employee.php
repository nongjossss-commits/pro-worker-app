<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\LogActivity;

class Employee extends Model
{
    use HasFactory, SoftDeletes, LogActivity;

    protected static function booted(): void
    {
        static::addGlobalScope('employerTenancy', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                if ($user->hasRole('employer')) {
                    $employer = $user->employer;
                    if ($employer) {
                        $builder->where('employer_id', $employer->id);
                    } else {
                        $builder->whereRaw('1 = 0');
                    }
                } elseif ($user->hasRole('caretaker')) {
                    $builder->whereHas('employer', function ($q) use ($user) {
                        $q->where('assigned_staff_id', $user->id);
                    });
                }
            }
        });
    }

    protected $fillable = [
        'employer_id',
        'status', // Added status
        'english_prefix',
        'employeeNameTh',
        'employeeNameEn',
        'employeeNationality',
        'employeePassport',
        'passportExpiryDate',
        'employeeWorkPermit',
        'workPermitExpiryDate',
        'visaExpiryDate',
        'ninetyDayReportDate',
        'employeeTitleTh',
        'employeeTitleEn',
        'employeeDob',
        'passportType',
        'namelistNo',
        'requestNo',
        'workerRefNo',
        'personalId',
        'companyWorkerId',
        'pinkCardNo',
        'socialSecurityNo',
        'taxIdNo',
        'designatedHospital',
        'startDate',
        'employeePhone',
        'email',
        'password',
        'employeePosition',
        'workPermitMOUGroup',
        'workPermitMOUGroupOther',
        'employeePhoto',
        'document_1',
        'document_2',
        'document_3',
        'document_4',
        'document_description_4',
        'document_5',
        'document_description_5',
        'document_6',
        'document_description_6',
        'nature_of_work',
        'terminated_at',
        'termination_reason',
        'name_list_number',
        'request_number',
        'employee_id_number',
        'tax_id_number',
        'employer_employee_id',
        'employee_reference_id',
        'father_name',
        'mother_name',
        'passport_issue_date',
        'passport_type_cambodia',
        'insurance_type',
        'insurance_detail',
        'insurance_expiry_date',
        'insurance_detail_hospital',
        'insurance_expiry_date_hospital',
        'insurance_detail_private',
        'insurance_expiry_date_private',
        'social_security_number',
        'visaType',
        'employee_doc_1',
        'employee_doc_2',
        'employee_doc_3',
        'employee_doc_4',
        'employee_doc_5',
        'employee_doc_6',
        'employee_doc_7',
        'employee_doc_8',
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
        'other_doc_1_desc',
        'other_doc_2_desc',
        'other_doc_3_desc',
        'other_doc_4_desc',
        'other_doc_5_desc',
        'other_doc_6_desc',
        'other_doc_7_desc',
        'other_doc_8_desc',
        'other_doc_9_desc',
        'other_doc_10_desc',
        'insurance_document_path',
        'insurance_document_path_private',
        'job_title',
        'job_description',
        'visa_type',
        'insurance_company',
        'hospital_name',
        'passport_file_path',
        'visa_file_path',
        'work_permit_file_path',
        'pink_card_file_path',
        'insurance_attachment_path',
    ];

    protected $casts = [
        'passportExpiryDate' => 'date:Y-m-d',
        'workPermitExpiryDate' => 'date:Y-m-d',
        'visaExpiryDate' => 'date:Y-m-d',
        'ninetyDayReportDate' => 'date:Y-m-d',
        'employeeDob' => 'date:Y-m-d',
        'startDate' => 'date:Y-m-d',
        'passport_issue_date' => 'date:Y-m-d',
        'insurance_expiry_date' => 'date:Y-m-d',
        'insurance_expiry_date_hospital' => 'date:Y-m-d',
        'insurance_expiry_date_private' => 'date:Y-m-d',
        'terminated_at' => 'datetime',
    ];

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->employeePhoto && Storage::disk('public')->exists($this->employeePhoto)) {
                    return Storage::disk('public')->url($this->employeePhoto);
                }
                $name = urlencode($this->employeeNameTh ?? $this->employeeNameEn ?? 'User');
                return "https://ui-avatars.com/api/?name={$name}&color=FFFFFF&background=F97316&size=128";
            }
        );
    }

    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->employeeDob ? $this->employeeDob->age : 'N/A',
        );
    }

    protected function gender(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->employeeTitleTh === 'นาย') {
                    return 'ชาย';
                }
                if (in_array($this->employeeTitleTh, ['นาง', 'นางสาว'])) {
                    return 'หญิง';
                }
                return 'N/A';
            }
        );
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function teams()
    {
        return $this->belongsToMany(EmployeeTeam::class, 'employee_team_members', 'employee_id', 'employee_team_id')
                    ->withTimestamps();
    }

    // --- New Relationships for Registration Process ---
    public function registrationSteps()
    {
        return $this->belongsToMany(RegistrationStep::class, 'employee_registration_status')
                    ->withPivot('completed_at')
                    ->withTimestamps();
    }

    // --- New Relationships for Renewal Process ---
    public function renewalSteps()
    {
        return $this->belongsToMany(RenewalStep::class, 'employee_renewal_status')
                    ->withPivot('completed_at')
                    ->withTimestamps();
    }

    public function customFields()
    {
        return $this->hasMany(EmployeeCustomField::class);
    }

    public function generatedDocuments()
    {
        return $this->hasMany(EmployeeGeneratedDocument::class);
    }

    protected function daysSinceTermination(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->terminated_at) {
                    return 0;
                }
                return floor(now()->diffInDays($this->terminated_at));
            }
        );
    }
}
