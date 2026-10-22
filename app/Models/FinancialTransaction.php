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
        'discount_amount',
        'credit_amount',
        'discount_description',
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
        'wht_received_at',
        'wht_no_cert_reason',
        'financial_profile_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'wht_received_at' => 'datetime',
        'fully_paid_at' => 'datetime',
    ];

    /**
     * fully_paid_at = when the status became 'paid' (every path that sets the
     * status saves the model). The Finance popup keeps a paid installment in
     * the main list for 24 hours, then shows it under the "Fully paid" tab.
     */
    protected static function booted(): void
    {
        static::saving(function (self $transaction) {
            if ($transaction->status !== 'paid') {
                $transaction->fully_paid_at = null;
            } elseif (! $transaction->fully_paid_at || $transaction->getOriginal('status') !== 'paid') {
                $transaction->fully_paid_at = now();
            }
        });
    }

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

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }
}
