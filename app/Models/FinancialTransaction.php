<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'production_financial_group_id',
        'type', // installment, down_payment, full_payment
        'amount',
        'due_date',
        'paid_at',
        'paid_amount',
        'slip_path',
        'status', // pending, partial, paid, overdue
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function financialGroup()
    {
        return $this->belongsTo(ProductionFinancialGroup::class, 'production_financial_group_id');
    }

    public function items()
    {
        return $this->belongsToMany(ProductionItem::class, 'financial_transaction_items');
    }
}
