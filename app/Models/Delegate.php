<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delegate extends Model
{
    use HasFactory;

    protected $fillable = [
        'delegateNameTh',
        'delegateNameEn',
        'delegateId',
        'delegateEmployeeId',
        'delegateIssueDate',
        'delegateExpiryDate',
        'delegatePhone',
        'delegateEmail',
        'delegatePhoto',
    ];
}
