<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_transaction_id',
        'amount',
        'paid_at',
        'bank_account_id',
        'slip_path',
        'notes',
        'receipt_generated_at',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'receipt_generated_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(FinancialTransaction::class, 'financial_transaction_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The LedgerEntry this payment posted into "บันทึกรายรับรายจ่าย" (see
     * FinancialController::storePayment() and LedgerService) — null if the
     * payment has no bank_account_id (unattributed, notes-only) or predates
     * this integration.
     */
    public function ledgerEntry()
    {
        return $this->morphOne(LedgerEntry::class, 'source');
    }
}
