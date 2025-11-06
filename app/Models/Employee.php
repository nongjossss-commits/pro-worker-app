<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

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

    /**
     * Get the URL to the employee's photo.
     * V2.4-S13-P2: CRITICAL FIX - Ensure robustness during API serialization.
     * Handle nulls, missing attributes, and missing files defensively to prevent crashes.
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                // Define default placeholder (Ensure 'public/images/default_avatar.png' exists in your project)
                $defaultAvatar = asset('images/default_avatar.png');

                // 1. Check if 'employee_photo' attribute exists in the attributes array
                // (Crucial when using specific select() queries)
                if (!isset($attributes['employee_photo'])) {
                    return $defaultAvatar;
                }

                $photoPath = $attributes['employee_photo'];

                // 2. Check if the path is null or empty
                if (!$photoPath) {
                    return $defaultAvatar;
                }

                // 3. Determine the storage disk (assuming 'public')
                $disk = 'public';

                // 4. Check if the file actually exists on the disk before generating URL
                try {
                    if (Storage::disk($disk)->exists($photoPath)) {
                        return Storage::disk($disk)->url($photoPath);
                    }
                } catch (\Exception $e) {
                    // Log the error if storage access fails (e.g., permission issues), but do not crash the API
                    $employeeId = $attributes['id'] ?? 'N/A';
                    Log::warning("Storage access error for Employee photo (ID: {$employeeId}): " . $e->getMessage());
                }

                // 5. Fallback if file is missing or error occurred
                return $defaultAvatar;
            },
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
