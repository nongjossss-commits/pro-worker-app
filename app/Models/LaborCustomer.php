<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A team's own external customer — see the migration's docblock for the
 * full rationale (distinct from LaborTeam.customer_*, which is the team's
 * own identity as a bill-to party, not a third party the team itself bills).
 */
class LaborCustomer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'labor_team_id',
        'name',
        'tax_id',
        'branch',
        'address',
        'contact_name',
        'contact_phone',
        'notes',
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

    public function taxInvoices(): HasMany
    {
        return $this->hasMany(LaborTaxInvoice::class);
    }
}
