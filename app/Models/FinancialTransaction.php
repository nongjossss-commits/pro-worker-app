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
        'bank_account_id',
        'withholding_tax_amount',
        'wht_document_path',
        'wht_status',
        'financial_profile_id',
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

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function financialProfile()
    {
        return $this->belongsTo(FinancialProfile::class);
    }

    public function payments()
    {
        return $this->hasMany(FinancialPayment::class);
    }
}
