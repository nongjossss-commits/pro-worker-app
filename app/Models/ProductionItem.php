<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'production_order_id',
        'employee_id', // Nullable now
        'group_name', // NEW: Group/Batch name
        'appointment_date',
        'status',
        'new_employee_data', // JSON for temp employees
    ];

    protected $casts = [
        'new_employee_data' => 'array',
        'appointment_date' => 'datetime',
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
}
