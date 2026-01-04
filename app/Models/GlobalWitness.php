<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalWitness extends Model
{
    protected $fillable = [
        'alias',
        'name_th',
        'name_en',
        'signature_path'
    ];
}
