<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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

    protected $casts = [
        'passportExpiryDate' => 'date:Y-m-d',
        'workPermitExpiryDate' => 'date:Y-m-d',
        'visaExpiryDate' => 'date:Y-m-d',
        'ninetyDayReportDate' => 'date:Y-m-d',
        'employeeDob' => 'date:Y-m-d',
        'startDate' => 'date:Y-m-d',
        'terminated_at' => 'datetime',
    ];

    /**
     * V2.4-S21: CRITICAL REFACTOR - Resolve Accessor/Column Conflict.
     * Rename employeeNameTh -> fullNameTh.
     */
    protected function fullNameTh(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $title = $attributes['employeeTitleTh'] ?? '';
                $name = $attributes['employeeNameTh'] ?? '';
                return trim($title . ' ' . $name);
            }
        );
    }

    /**
     * Get the URL to the employee's photo.
     * V2.4-S21: Stability Fix - Ensure $attributes type checking to prevent TypeError.
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $defaultAvatar = asset('images/default_avatar.png'); // Ensure this asset exists
                // V2.4-S21: CRITICAL - Ensure $attributes is accessible as an array before use.
                // This prevents "TypeError: Cannot access offset..."
                if (!is_array($attributes) && !($attributes instanceof \ArrayAccess)) {
                    return $defaultAvatar;
                }
                if (!isset($attributes['employee_photo'])) {
                    return $defaultAvatar;
                }
                $photoPath = $attributes['employee_photo'];
                if (!$photoPath) {
                    return $defaultAvatar;
                }
                $disk = 'public';
                try {
                    if (Storage::disk($disk)->exists($photoPath)) {
                        return Storage::disk($disk)->url($photoPath);
                    }
                } catch (\Exception $e) {
                    $employeeId = isset($attributes['id']) ? $attributes['id'] : 'N/A';
                    Log::warning("Storage access error for Employee photo (ID: {$employeeId}): " . $e->getMessage());
                }
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
