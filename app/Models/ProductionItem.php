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
        'employee_id',
        'request_number',
        'group_name', // NEW: Group/Batch name
        'appointment_date',
        'appointment_location', // NEW
        'last_checked_at', // NEW
        'appointment_completed_at', // NEW: Appointment finished
        'appointment_updated_by',
        'appointment_updated_at',
        'status',
        'completed_at', // NEW: Workflow finished
        'is_transfer_processed', // NEW: Flag to prevent duplicate delayed transfers
        'workflow_settings_applied', // Flag for ApplyWorkflowSettings (24h MOU auto-apply)
        'new_employee_data', // JSON for temp employees
        'operator_id',
        'custom_operator_name', // NEW: Assigned Operator
        'remarks', // Added remarks
    ];

    protected $appends = ['has_visa'];

    /**
     * Bump the parent ProductionOrder's updated_at whenever this item changes.
     * Lets the 4 menus (Pre-Prod / Workflow / Registration / Renewal) sort
     * employer cards by latest activity on the next page load — not in real
     * time, so the user's current click flow doesn't get reshuffled mid-edit.
     */
    protected $touches = ['order'];

    protected $casts = [
        'new_employee_data' => 'array',
        'appointment_date' => 'datetime',
        'last_checked_at' => 'datetime',
        'appointment_completed_at' => 'datetime',
        'appointment_updated_at' => 'datetime',
        'completed_at' => 'datetime',
        'workflow_settings_applied' => 'boolean',
    ];

    protected static function booted()
    {
        $callback = function ($item) {
            if ($item->order && auth()->check()) {
                // Update parent order timestamp and updated_by
                $item->order->update(['updated_by' => auth()->id()]);
            }
        };

        static::created($callback);
        static::updated($callback);
        static::deleted($callback);
    }

    public function order()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
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

    public function transactions()
    {
        return $this->belongsToMany(FinancialTransaction::class, 'financial_transaction_items');
    }

    // ACCESSORS

    public function getHasVisaAttribute()
    {
        return $this->employee ? !empty($this->employee->visaExpiryDate) : false;
    }

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
