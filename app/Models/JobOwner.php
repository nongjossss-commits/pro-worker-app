<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOwner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /** Employers under this job owner (two-stage search: pick an owner → all their employers). */
    public function employers()
    {
        return $this->hasMany(Employer::class, 'job_owner_id');
    }
}
