<?php

namespace App\Models;

// ... (Existing imports)
// Add Attribute and Storage imports
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope('employerTenancy', function (Builder $builder) {
            if (Auth::check() && Auth::user()->hasRole('employer')) {
                // Find the employer record linked to this user
                $employer = Auth::user()->employer;
                if ($employer) {
                    // This user is an 'employer'. Filter their view to *only*
                    // Employees who belong to their linked Employer ID.
                    $builder->where('employer_id', $employer->id);
                } else {
                    // This employer user is not linked to any employer record.
                    // Show them nothing.
                    $builder->whereRaw('1 = 0'); // Forces query to return empty
                }
            }
        });
    }

    // The $fillable array is correct as it matches the camelCase schema.
    protected $fillable = [
        'passportIssueDate',
        'passportExpiryDate',
        'visaType',
        'visaExpiryDate',
        'workPermitExpiryDate',
        'pinkCardNo',
        'companyWorkerId',
        'designatedHospital',
        'document_1',
        'document_2',
        'document_3',
        'document_4',
        'document_description_4',
        'document_description_5',
        'document_description_6',
        'email',
        'employeeDob',
        'employeeNameEn',
        'employeeNameTh',
        'employeeNationality',
        'employeePassport',
        'employeePhone',
        'employeePhoto',
        'employeePosition',
        'employeeTitleEn',
        'employeeTitleTh',
        'employeeWorkPermit',
        'employee_doc_10',
        'employee_doc_11',
        'employee_doc_12',
        'employee_doc_5',
        'employee_doc_6',
        'employee_doc_7',
        'employee_doc_8',
        'employee_doc_9',
        'employee_id_number',
        'employee_reference_id',
        'employer_employee_id',
        'employer_id',
        'english_prefix',
        'father_name',
        'hospital_name',
        'insurance_attachment_path',
        'insurance_company',
        'insurance_detail',
        'insurance_document_path',
        'insurance_expiry_date',
        'insurance_type',
        'job_description',
        'job_title',
        'mother_name',
        'name_list_number',
        'namelistNo',
        'nature_of_work',
        'ninetyDayReportDate',
        'other_doc_1_desc',
        'other_doc_2_desc',
        'other_doc_3_desc',
        'other_doc_4_desc',
        'passportExpiryDate',
        'passportType',
        'passport_file_path',
        'passport_issue_date',
        'passport_type_cambodia',
        'password',
        'personalId',
        'pinkCardNo',
        'pink_card_file_path',
        'requestNo',
        'request_number',
        'socialSecurityNo',
        'social_security_number',
        'startDate',
        'taxIdNo',
        'tax_id_number',
        'terminated_at',
        'termination_reason',
        'visaExpiryDate',
        'visa_file_path',
        'visa_type',
        'workPermitExpiryDate',
        'workPermitMOUGroup',
        'workPermitMOUGroupOther',
        'work_permit_file_path',
        'workerRefNo',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // --- FIX: The keys MUST be camelCase to match the database columns ---
        'passportExpiryDate' => 'date:Y-m-d',
        'workPermitExpiryDate' => 'date:Y-m-d',
        'visaExpiryDate' => 'date:Y-m-d',
        'ninetyDayReportDate' => 'date:Y-m-d',
        'employeeDob' => 'date:Y-m-d',
        'startDate' => 'date:Y-m-d',
        'terminated_at' => 'datetime',
    ];

    // --- V2.4-S6: Accessor for Photo URL ---
    /**
     * Get the full URL for the employee's photo, with a fallback avatar.
     * Usage in Blade/API: $employee->photo_url
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Robust check: Ensure property is set AND file exists on disk
                if ($this->employeePhoto && Storage::disk('public')->exists($this->employeePhoto)) {
                    return Storage::disk('public')->url($this->employeePhoto);
                }
                // Fallback using ui-avatars.com based on the name
                $name = urlencode($this->employeeNameTh ?? $this->employeeNameEn ?? 'User');
                // Use primary color defined in app.blade.php (F97316 - Orange)
                return "https://ui-avatars.com/api/?name={$name}&color=FFFFFF&background=F97316&size=128";
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
}