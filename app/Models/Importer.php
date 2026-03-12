<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Importer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'importerNameTh',
        'importerNameEn',
        'importerId',
        'importerLicenseNo',
        'importerLicenseIssueDate',
        'importerLicenseExpiryDate',
        'importerSignerTh',
        'importerSignerEn',
        'importer_doc_other_1',
        'importer_doc_other_1_desc',
        'importer_doc_other_2',
        'importer_doc_other_2_desc',
        'importer_doc_other_3',
        'importer_doc_other_3_desc',
    ];

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }
}
