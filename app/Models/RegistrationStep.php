<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationStep extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'order'];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_registration_status')
                    ->withPivot('completed_at')
                    ->withTimestamps();
    }
}
