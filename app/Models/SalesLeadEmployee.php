<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesLeadEmployee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sales_lead_id',
        'employee_id',
        'employeeNameEn',
        'employeeNameTh',
        'employeeGender',
        'employeeDob',
        'employeePassport',
        'employeeWorkPermit',
        'insurance_type',
        'pinkCardNo',
        'employeeNationality',
        'workPermitMOUGroup',
        'passportExpiryDate',
        'workPermitExpiryDate',
        'system_employee_id',
        'employer_employee_id',
        'name_list_number',
        'employeeAddress',
        'employeePhone'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'employeeDob' => 'date',
        'passportExpiryDate' => 'date',
        'workPermitExpiryDate' => 'date',
    ];

    /**
     * Get the sales lead that owns the employee.
     */
    public function salesLead()
    {
        return $this->belongsTo(SalesLead::class, 'sales_lead_id');
    }

    /**
     * Get the real employee if linked.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
