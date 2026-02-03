<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class ProductionItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'production_order_id',
        'employee_id', // Nullable now
        'group_name', // NEW: Group/Batch name
        'appointment_date',
        'appointment_location', // NEW
        'last_checked_at', // NEW
        'appointment_completed_at', // NEW: Appointment finished
        'status',
        'new_employee_data', // JSON for temp employees
    ];

    protected $casts = [
        'new_employee_data' => 'array',
        'appointment_date' => 'datetime',
        'last_checked_at' => 'datetime',
        'appointment_completed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Legacy / Ad-hoc fields
    public function steps()
    {
        // Ordered chronologically
        return $this->hasMany(WorkflowStep::class)->orderBy('created_at', 'asc');
    }

    // NEW: Checklist Steps from WorkType
    public function completedWorkTypeSteps()
    {
        return $this->belongsToMany(WorkTypeStep::class, 'production_item_step')
                    ->withPivot('completed_at', 'completed_by')
                    ->withTimestamps();
    }

    // ACCESSORS

    public function getIsCheckedTodayAttribute()
    {
        if (!$this->last_checked_at) {
            return false;
        }
        return $this->last_checked_at->isToday();
    }

    public function getDaysSinceLastCheckAttribute()
    {
        if (!$this->last_checked_at) {
            // If never checked, return null or a high number?
            // If created_at is used as fallback:
            return $this->created_at->startOfDay()->diffInDays(Carbon::today());
        }
        return $this->last_checked_at->startOfDay()->diffInDays(Carbon::today());
    }
}
