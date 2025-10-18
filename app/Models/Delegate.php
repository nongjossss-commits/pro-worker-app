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
        'delegatePhoto',
    ];
}
