<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionFinancialGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'production_order_id',
        'name',
        'financial_data',
    ];

    protected $casts = [
        'financial_data' => 'array',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function transactions()
    {
        return $this->hasMany(FinancialTransaction::class, 'production_financial_group_id');
    }

    public function advanceItems()
    {
        return $this->hasMany(FinancialAdvanceItem::class, 'production_financial_group_id');
    }
}
