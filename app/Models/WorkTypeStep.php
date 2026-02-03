<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkTypeStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_type_id',
        'name',
        'order',
        'stage', // Added
    ];

    public function workType()
    {
        return $this->belongsTo(WorkType::class);
    }
}
