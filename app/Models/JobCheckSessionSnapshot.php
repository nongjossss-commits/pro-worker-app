<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCheckSessionSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_check_session_id',
        'menu',
        'subject_type',
        'subject_id',
        'employer_id',
        'resolution_tab_id',
        'production_order_id',
        'initial_state',
    ];

    protected $casts = [
        'initial_state' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(JobCheckSession::class, 'job_check_session_id');
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }
}
