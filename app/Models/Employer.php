<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\JobOwner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class Employer extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope('employerTenancy', function (Builder $builder) {
            if (Auth::check() && Auth::user()->hasRole('employer')) {
                // This user is an 'employer'. Filter their view to *only*
                // the Employer record linked to their user_id.
                $builder->where('user_id', Auth::id());
            }
        });
    }

    protected $fillable = [
        'employerNameTh',
        'employerNameEn',
        'employerId',
        'employerTaxId',
        'businessType',
        'signerNameTh',
        'signerNameEn',
        'businessTypeEn',
        'regCapital',
        'regDate',
        'minimum_wage',
        'document_company_registration',
        'document_vat_registration',
        'document_map',
        'job_owner_id',
        'user_id',
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
}
