<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaborChargeType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LaborLedgerEntry::class, 'labor_charge_type_id');
    }

    /**
     * Total headcount per charge type, broken down by nationality — shared by
     * the Central Billing page and the module Dashboard so the two numbers
     * can never drift apart. Dynamic by construction: iterates whatever
     * charge types currently exist, so a newly added type appears with no
     * code change, and one with zero entries is dropped rather than shown
     * as an empty bar.
     *
     * 'unspecified' is entries recorded before the per-nationality
     * breakdown existed (all 4 qty_* columns still NULL) — their headcount
     * is real and must stay in 'total', but nobody has said which
     * nationality they belong to yet, so it's surfaced as its own bucket
     * instead of silently vanishing from the total.
     */
    public static function nationalityStats(): \Illuminate\Support\Collection
    {
        return static::orderBy('name')->get()->map(function ($type) {
            $row = LaborLedgerEntry::where('labor_charge_type_id', $type->id)
                ->selectRaw(
                    'COALESCE(SUM(quantity),0) as total_qty, COALESCE(SUM(qty_laos),0) as laos, '
                    . 'COALESCE(SUM(qty_myanmar),0) as myanmar, COALESCE(SUM(qty_cambodia),0) as cambodia, '
                    . 'COALESCE(SUM(qty_vietnam),0) as vietnam'
                )
                ->first();

            $laos = (int) $row->laos;
            $myanmar = (int) $row->myanmar;
            $cambodia = (int) $row->cambodia;
            $vietnam = (int) $row->vietnam;
            $total = (int) $row->total_qty;

            return [
                'type' => $type,
                'total' => $total,
                'laos' => $laos,
                'myanmar' => $myanmar,
                'cambodia' => $cambodia,
                'vietnam' => $vietnam,
                'unspecified' => max(0, $total - ($laos + $myanmar + $cambodia + $vietnam)),
            ];
        })->filter(fn ($s) => $s['total'] > 0)->values();
    }
}
