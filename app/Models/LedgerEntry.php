<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LedgerEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'entry_no',
        'entry_date',
        'type',
        'bank_account_id',
        'category_id',
        'category_type',
        'counterparty_name',
        'counterparty_tax_id',
        'gross_amount',
        'vat_treatment',
        'vat_rate',
        'vat_amount',
        'subtotal',
        'wht_type',
        'wht_rate',
        'wht_amount',
        'net_amount',
        'tax_invoice_no',
        'tax_invoice_date',
        'receipt_path',
        'attached_files',
        'ai_source',
        'ai_extracted_data',
        'ai_confidence',
        'ai_status',
        'source_type',
        'source_id',
        'description',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'tax_invoice_date' => 'date',
        'gross_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'wht_rate' => 'decimal:2',
        'wht_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'ai_confidence' => 'decimal:2',
        'attached_files' => 'array',
        'ai_extracted_data' => 'array',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function category()
    {
        if ($this->category_type === 'income') {
            return $this->belongsTo(IncomeCategory::class, 'category_id');
        }
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function getSignedNetAmountAttribute(): float
    {
        $sign = $this->type === 'income' ? 1 : -1;
        return $sign * (float) $this->net_amount;
    }

    public function scopeForReporting($query)
    {
        return $query->whereHas('bankAccount', fn($q) => $q->where('account_type', 'company'));
    }
}
