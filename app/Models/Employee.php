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
            if (Auth::check()) {
                $user = Auth::user();
                if ($user->hasRole('employer')) {
                    // Find the employer record linked to this user
                    $employer = $user->employer;
                    if ($employer) {
                        // This user is an 'employer'. Filter their view to *only*
                        // Employees who belong to their linked Employer ID.
                        $builder->where('employer_id', $employer->id);
                    } else {
                        // This employer user is not linked to any employer record.
                        // Show them nothing.
                        $builder->whereRaw('1 = 0'); // Forces query to return empty
                    }
                } elseif ($user->hasRole('caretaker')) {
                    // This user is a 'caretaker'. Filter their view to *only*
                    // Employees whose Employer has this user assigned as staff.
                    $builder->whereHas('employer', function ($q) use ($user) {
                        $q->where('assigned_staff_id', $user->id);
                    });
                }
            }
        });
    }

    // The $fillable array is correct as it matches the camelCase schema.
    protected $fillable = [
        'employer_id',
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
        'other_doc_1_desc',
        'other_doc_2_desc',
        'other_doc_3_desc',
        'other_doc_4_desc',
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
        'passport_issue_date' => 'date:Y-m-d',
        'insurance_expiry_date' => 'date:Y-m-d',
        'insurance_expiry_date_hospital' => 'date:Y-m-d',
        'insurance_expiry_date_private' => 'date:Y-m-d',
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

    /**
     * Calculate the employee's age from their date of birth.
     * Usage in Blade/API: $employee->age
     */
    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->employeeDob ? $this->employeeDob->age : 'N/A',
        );
    }

    /**
     * Determine the employee's gender from their Thai title.
     * Usage in Blade/API: $employee->gender
     */
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

    /**
     * Get the number of days since the employee was terminated.
     * Usage in Blade/API: $employee->days_since_termination
     */
    protected function daysSinceTermination(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->terminated_at) {
                    return 0;
                }
                // Use floor to ensure we get a whole number of days.
                return floor(now()->diffInDays($this->terminated_at));
            }
        );
    }
}