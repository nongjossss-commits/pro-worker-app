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

    protected $fillable = [
 'employer_id',
 'english_prefix',
 'employeeNameTh',
 'employeeNameEn',
 'employeeNickname',
 'employeeGender',
 'employeeDob',
 'employeeAge',
 'employeeNationality',
 'employeePassport', // Passport Number
 'passport_issue_date', // NEW
 'passportExpiryDate',
 'visa_type', // NEW
 'visaExpiryDate',
 'employeeWorkPermit', // Work Permit Number
 'workPermitExpiryDate',
 'ninetyDayReportDate',
 'pinkCardNo',
 'employeePhone',
 'email',
 'password',
 'insurance_type', // NEW
 'insurance_company', // NEW
 'hospital_name', // NEW
 'insurance_expiry_date', // NEW
 // File Paths Columns
 'employeePassportFile', // Or 'passport_file' - CHECK DB SCHEMA
 'employeeVisaFile',
 'employeeWorkPermitFile',
 'pinkCardFile',
 'social_security_file',
 'insurance_file', // Loop files
 'file_5',
 'file_6',
 'file_7',
 'file_8',
 'file_9',
 'file_10',
 'file_11',
 'file_12',
 'status',
 'terminated_at'
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