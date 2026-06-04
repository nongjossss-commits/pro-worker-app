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
        'authorized_signatory_name',
        'logo_path',
        'signature_path',
        'signature_position',
        'stamp_path',
        'stamp_position',
        'is_vat_registered',
        'vat_rate',
    ];

    protected $casts = [
        'signature_position' => 'array',
        'stamp_position' => 'array',
        'is_vat_registered' => 'boolean',
        'vat_rate' => 'decimal:2',
    ];

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }
}
