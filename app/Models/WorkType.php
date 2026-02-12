<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_system',
        'order',
        'notify_days_advance',
    ];

    public function steps()
    {
        return $this->hasMany(WorkTypeStep::class)->orderBy('order');
    }

    public function workflowSteps()
    {
        return $this->hasMany(WorkTypeStep::class)
            ->where(function ($query) {
                $query->whereNull('stage')
                      ->orWhere('stage', '!=', 'preparation');
            })
            ->orderBy('order');
    }

    public function preparationSteps()
    {
        return $this->hasMany(WorkTypeStep::class)
            ->where('stage', 'preparation')
            ->orderBy('order');
    }

    public function orders()
    {
        return $this->hasMany(ProductionOrder::class);
    }
}
