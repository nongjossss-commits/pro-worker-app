<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_item_id',
        'step_type', // 'text', 'date', 'file', 'barrier'
        'label',
        'value_text',
        'value_date',
        'file_path',
        'barrier_id',
        'created_by',
    ];

    protected $casts = [
        'value_date' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(ProductionItem::class, 'production_item_id');
    }

    public function barrier()
    {
        return $this->belongsTo(WorkflowBarrier::class, 'barrier_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
