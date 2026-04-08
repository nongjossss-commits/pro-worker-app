<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuperAdminSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'is_visible',
        'access_password',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];
}
