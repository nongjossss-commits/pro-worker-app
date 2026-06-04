<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'branch',
        'account_type',
        'financial_profile_id',
        'tax_id',
        'initial_balance',
        'current_balance',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function financialProfile()
    {
        return $this->belongsTo(FinancialProfile::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function isPersonal(): bool
    {
        return $this->account_type === 'personal';
    }

    public function isCompany(): bool
    {
        return $this->account_type === 'company';
    }
}
