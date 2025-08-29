<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employer extends Model
{
    use HasFactory;

    protected $fillable = [
        'employerNameTh',
        'employerId',
        'employerTaxId',
        'businessType',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
