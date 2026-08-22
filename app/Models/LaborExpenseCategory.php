<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaborExpenseCategory extends Model
{
    protected $fillable = [
        'name',
        'note',
        'is_tax_deductible',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_tax_deductible' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LaborBookTransaction::class);
    }
}
