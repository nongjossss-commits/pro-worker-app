<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaborTaxInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_no',
        'invoice_date',
        'fiscal_year',
        'labor_bill_id',
        'issuer_profile_id',
        'customer_name',
        'customer_tax_id',
        'customer_branch',
        'customer_address',
        'subtotal',
        'vat_rate',
        'vat_amount',
        'total',
        'status',
        'issued_at',
        'voided_at',
        'void_reason',
        'notes',
        'payment_methods',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'issued_at' => 'datetime',
        'voided_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'payment_methods' => 'array',
    ];

    public function bill()
    {
        return $this->belongsTo(LaborBill::class, 'labor_bill_id');
    }

    public function issuerProfile()
    {
        return $this->belongsTo(FinancialProfile::class, 'issuer_profile_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['issued', 'void'], true);
    }

    public function scopeForFiscalYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'void');
    }
}
