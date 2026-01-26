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
    ];

    public function steps()
    {
        return $this->hasMany(WorkTypeStep::class)->orderBy('order');
    }

    public function orders()
    {
        return $this->hasMany(ProductionOrder::class);
    }
}
