<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeCustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'field_name',
        'field_type',
        'field_value',
        'file_path',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
