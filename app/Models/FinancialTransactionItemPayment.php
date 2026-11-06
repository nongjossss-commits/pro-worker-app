<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One employee's one payment, on a bill collected individually
 * (FinancialTransaction.payment_collection_mode === 'per_employee'). A batch
 * of these rolls up into a single FinancialPayment (financial_payment_id)
 * when the user finishes a collection round — see
 * FinancialController::finalizeItemPayments(). Rows with financial_payment_id
 * still null haven't been rolled up into a receipt-worthy total yet.
 */
class FinancialTransactionItemPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_transaction_id',
        'production_item_id',
        'financial_payment_id',
        'amount',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function transaction()
    {
        return $this->belongsTo(FinancialTransaction::class, 'financial_transaction_id');
    }

    public function productionItem()
    {
        return $this->belongsTo(ProductionItem::class);
    }

    public function payment()
    {
        return $this->belongsTo(FinancialPayment::class, 'financial_payment_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
