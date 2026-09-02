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
        'user_id',
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

    /**
     * The login account this team member is matched to, if any — optional,
     * see the migration's docblock. Independent of `team()`: matching isn't
     * restricted to a User whose own labor_team_id is this same team.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
