<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCheckSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'started_at',
        'ended_at',
        'business_date',
        'sequence_in_day',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'business_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function snapshots()
    {
        return $this->hasMany(JobCheckSessionSnapshot::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
