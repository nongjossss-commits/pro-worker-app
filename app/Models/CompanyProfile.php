<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'tax_id',
        'phone',
        'logo_path',
        'is_default',
        'signature_path',
        'stamp_path',
        'signature_pos',
        'stamp_pos',
        'use_signature',
        'use_stamp',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'use_signature' => 'boolean',
        'use_stamp' => 'boolean',
        'signature_pos' => 'array',
        'stamp_pos' => 'array',
    ];
}
