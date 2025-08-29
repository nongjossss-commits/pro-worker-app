<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'agentNameEn',
        'agentLicense',
        'agentPhone',
        'agentEmail',
        'agentAddress',
    ];
}
