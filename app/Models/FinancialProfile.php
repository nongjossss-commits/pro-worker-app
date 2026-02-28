<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinancialProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'tax_id',
        'branch',
        'address',
        'phone',
        'email',
        'logo_path',
        'signature_path',
        'signature_position',
        'stamp_path',
        'stamp_position',
    ];

    protected $casts = [
        'signature_position' => 'array',
        'stamp_position' => 'array',
    ];
}
