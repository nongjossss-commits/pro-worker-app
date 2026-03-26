<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesLead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employer_id',
        'employerNameTh',
        'employerNameEn',
        'employerId',
        'employerTaxId',
        'employerPhone',
        'jobOwner',
        'requires_special_attention',
        'status', // quoted, deciding, confirmed, transitioned, cancelled
        'workflow_destination',
        'created_by',
        'confirmed_by',
        'employerEmail',
        'employerPassword',
        'outsource_re_code',
        'outsource_password',
        'socialSecurityHospital',
        'businessType',
        'businessTypeEn',
        'signerNameTh',
        'signerNameEn',
        'signer_2_name_th',
        'signer_2_name_en',
        'employer_doc_company',
        'employer_doc_company_expiry',
        'employer_doc_lease',
        'employer_doc_construction',
        'employer_doc_other_1',
        'employer_doc_other_1_desc',
        'employer_doc_other_2',
        'employer_doc_other_2_desc',
        'employer_doc_other_3',
        'employer_doc_other_3_desc'
    ];

    /**
     * Get the real employer if linked.
     */
    public function employer()
    {
        return $this->belongsTo(Employer::class, 'employer_id');
    }

    /**
     * Get the employees associated with this sales lead.
     */
    public function employees()
    {
        return $this->hasMany(SalesLeadEmployee::class, 'sales_lead_id');
    }

    /**
     * Get the quotation associated with this sales lead.
     */
    public function quotation()
    {
        return $this->hasOne(SalesQuotation::class, 'sales_lead_id');
    }
}
