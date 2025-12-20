<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialAdvanceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_financial_group_id',
        'description',
        'quantity',
        'unit_price',
        'total',
    ];

    public function financialGroup()
    {
        return $this->belongsTo(ProductionFinancialGroup::class, 'production_financial_group_id');
    }
}
