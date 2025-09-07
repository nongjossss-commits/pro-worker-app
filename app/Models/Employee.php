<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    // This is the cleaned up and correct fillable array.
    protected $fillable = [
        'employer_id',
        'employeeNameTh',
        'employeeNameEn',
        'employeeNationality',
        'employeePassport',
        'passportExpiryDate', // Correct camelCase
        'employeeWorkPermit',
        'workPermitExpiryDate', // Correct camelCase
        'visaExpiryDate',       // Correct camelCase
        'ninetyDayReportDate',  // Correct camelCase
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

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}