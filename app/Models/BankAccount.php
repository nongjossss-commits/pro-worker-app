<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'bank_code',
        'bank_type',
        'account_name',
        'account_number',
        'promptpay_id',
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

    /**
     * Resolves the brand-colour + display names from config/thai_banks.php.
     * Returns null when bank_type isn't 'thai_bank' or the code is missing
     * from the preset (custom / international entries).
     */
    public function getBankPresetAttribute(): ?array
    {
        if ($this->bank_type !== 'thai_bank' || !$this->bank_code) {
            return null;
        }
        foreach (config('thai_banks', []) as $bank) {
            if ($bank['code'] === $this->bank_code) {
                return $bank;
            }
        }
        return null;
    }

    /**
     * The badge glyph + colour used by both the picker UI and the
     * Tax-Invoice PDF footer block. Falls back to a grey generic
     * letter for "other" / unrecognised codes.
     */
    public function getBadgeAttribute(): array
    {
        $preset = $this->bank_preset;
        if ($preset) {
            return ['initial' => $preset['initial'], 'color' => $preset['color']];
        }
        if ($this->bank_type === 'promptpay') {
            return ['initial' => 'PP', 'color' => '#0C3CFC'];
        }
        $first = mb_substr($this->bank_name ?: '?', 0, 1);
        return ['initial' => mb_strtoupper($first), 'color' => '#6C757D'];
    }
}
