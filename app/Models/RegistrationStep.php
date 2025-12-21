<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class RegistrationStep extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'order', 'color', 'type'];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_registration_status')
                    ->withPivot('completed_at')
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include registration steps.
     */
    public function scopeRegistration(Builder $query): void
    {
        $query->where('type', 'registration');
    }

    /**
     * Scope a query to only include renewal steps.
     */
    public function scopeRenewal(Builder $query): void
    {
        $query->where('type', 'renewal');
    }
}
