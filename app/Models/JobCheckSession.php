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

    /**
     * Active OR paused — a session that "exists" from the user's point of
     * view (still shows in the widget, still confines its owning tab) even
     * though pausing releases the menu confinement itself. Kept distinct
     * from scopeActive(), which the middleware needs to stay strict about.
     */
    public function scopeCurrent($query)
    {
        return $query->whereIn('status', ['active', 'paused']);
    }
}
