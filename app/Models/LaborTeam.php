<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaborTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'auto_billing_enabled',
        'billing_cadence',
        'billing_day_of_week',
        'billing_day_of_month',
        'last_auto_billed_on',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'auto_billing_enabled' => 'boolean',
            'last_auto_billed_on' => 'date',
        ];
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LaborLedgerEntry::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(LaborTeamMember::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'labor_team_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(LaborBill::class);
    }

    public function getTotalOwedAttribute(): float
    {
        return (float) $this->ledgerEntries()->sum('amount');
    }
}
