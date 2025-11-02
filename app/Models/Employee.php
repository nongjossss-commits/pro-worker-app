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