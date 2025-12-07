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
        'employee_id',
        'current_barrier_id',
    ];

    public function order()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function currentBarrier()
    {
        return $this->belongsTo(WorkflowBarrier::class, 'current_barrier_id');
    }

    public function steps()
    {
        // Ordered chronologically
        return $this->hasMany(WorkflowStep::class)->orderBy('created_at', 'asc');
    }
}
