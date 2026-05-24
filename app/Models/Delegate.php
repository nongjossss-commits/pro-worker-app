<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Delegate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'delegateNameTh',
        'delegateNameEn',
        'delegateId',
        'delegateEmployeeId',
        'delegateIssueDate',
        'delegateExpiryDate',
        'delegatePhone',
        'delegateEmail',
        'outsource_password',
        'delegatePhoto',
        'signature_path',
        'delegate_doc_other_1',
        'delegate_doc_other_1_desc',
        'delegate_doc_other_2',
        'delegate_doc_other_2_desc',
        'delegate_doc_other_3',
        'delegate_doc_other_3_desc',
    ];

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }
}
