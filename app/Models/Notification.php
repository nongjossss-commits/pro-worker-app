<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'due_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the days remaining dynamically.
     * Overrides the database column value.
     *
     * @param mixed $value
     * @return int
     */
    public function getDaysRemainingAttribute($value)
    {
        if ($this->due_date) {
            return (int) Carbon::now()->startOfDay()->diffInDays($this->due_date, false);
        }
        return $value;
    }

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
