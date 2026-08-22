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
        'agent_doc_other_1',
        'agent_doc_other_1_desc',
        'agent_doc_other_2',
        'agent_doc_other_2_desc',
        'agent_doc_other_3',
        'agent_doc_other_3_desc',
    ];
}
