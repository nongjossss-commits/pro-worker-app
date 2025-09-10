<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employer extends Model
{
    use HasFactory;

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
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }
}
