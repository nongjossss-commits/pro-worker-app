<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\JobOwner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\LogActivity;
use App\Traits\SearchableByAddress;

class Employer extends Model
{
    use HasFactory, SoftDeletes, LogActivity, SearchableByAddress;

    protected static function booted(): void
    {
        static::addGlobalScope('employerTenancy', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                if ($user->hasRole('employer')) {
                    // This user is an 'employer'. Filter their view to *only*
                    // the Employer record linked to their user_id.
                    $builder->where('user_id', $user->id);
                } elseif ($user->hasRole('caretaker')) {
                    // This user is a 'caretaker'. Filter their view to *only*
                    // Employer records where they are the assigned staff.
                    $builder->where('assigned_staff_id', $user->id);
                }
            }
        });
    }

    protected $fillable = [
        'employerNameTh',
        'employerNameEn',
        'employerId',
        'employerTaxId',
        'employerEmail',
        'employerPassword',
        'employerPhone',
        'outsource_re_code',
        'outsource_password',
        'socialSecurityHospital',
        'businessType',
        'signerNameTh',
        'signerNameEn',
        'signer_2_name_th',
        'signer_2_name_en',
        'signature_1_path',
        'signature_2_path',
        'businessTypeEn',
        'regCapital',
        'regDate',
        'minimum_wage',
        'employer_doc_company',
        'employer_doc_company_expiry',
        'employer_doc_lease',
        'employer_doc_construction',
        'employer_doc_other_1',
        'employer_doc_other_1_desc',
        'employer_doc_other_2',
        'employer_doc_other_2_desc',
        'employer_doc_other_3',
        'employer_doc_other_3_desc',
        'job_owner_id',
        'user_id',
        'assigned_staff_id',
        'registration_resolution_status',
        'registration_resolution_note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'regDate' => 'date:Y-m-d',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function jobOwner()
    {
        return $this->belongsTo(JobOwner::class, 'job_owner_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedStaff()
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function customFields()
    {
        return $this->morphMany(ProductionCustomField::class, 'model');
    }
}
