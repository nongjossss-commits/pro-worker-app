<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeTeam extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'employee_group_id'];

    public function group()
    {
        return $this->belongsTo(EmployeeGroup::class, 'employee_group_id');
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_team_members', 'employee_team_id', 'employee_id')
                    ->withTimestamps();
    }
}
