<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomeCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'default_vat_treatment',
        'default_wht_type',
        'default_wht_rate',
        'is_active',
    ];

    protected $casts = [
        'default_wht_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function ledgerEntries()
    {
        return $this->morphMany(LedgerEntry::class, 'category', 'category_type', 'category_id')
            ->where('category_type', 'income');
    }
}
