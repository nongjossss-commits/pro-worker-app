<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaborTeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'labor_team_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(LaborTeam::class, 'labor_team_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LaborLedgerEntry::class);
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->ledgerEntries()->sum('amount');
    }
}
