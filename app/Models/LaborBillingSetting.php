<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single-row settings model. Use LaborBillingSetting::current() rather than
 * a raw query — it lazily creates the one row if it doesn't exist yet.
 */
class LaborBillingSetting extends Model
{
    protected $fillable = [
        'financial_profile_id',
    ];

    public function financialProfile(): BelongsTo
    {
        return $this->belongsTo(FinancialProfile::class);
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
