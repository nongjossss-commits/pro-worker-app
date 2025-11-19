<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'employer_id',
        'type',
        'message',
        'due_date',
        'status',
        'days_remaining',
        'cancellation_reason',
        'cancelled_at',
    ];

    /**
     * Get the employee that the notification belongs to.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the employer that the notification belongs to.
     */
    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }
}