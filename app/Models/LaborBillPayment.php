<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaborBillPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'labor_bill_id',
        'amount',
        'paid_at',
        'payment_method',
        'bank_account_id',
        'slip_path',
        'wht_certificate_id',
        'receipt_no',
        'receipt_generated_at',
        'receipt_pdf_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
        'receipt_generated_at' => 'datetime',
    ];

    public function bill()
    {
        return $this->belongsTo(LaborBill::class, 'labor_bill_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function whtCertificate()
    {
        return $this->belongsTo(LaborWhtCertificate::class, 'wht_certificate_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasReceipt(): bool
    {
        return (bool) $this->receipt_no;
    }
}
