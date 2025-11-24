<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'type', 'employer_id'];

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function teams()
    {
        return $this->hasMany(EmployeeTeam::class);
    }
}
