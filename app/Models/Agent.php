<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agentNameEn',
        'agentLicense',
        'agentPhone',
        'agentEmail',
        'agentAddress',
    ];
}
