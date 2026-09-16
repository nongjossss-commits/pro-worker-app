<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ใบลดหนี้ — reduces a specific FinancialTransaction's effective balance.
 * See the create_credit_notes_table migration's docblock for why this is a
 * standalone, TaxInvoice-shaped model rather than a mutation of the
 * original bill or a LedgerEntry.
 */
class CreditNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'credit_note_no',
        'credit_note_date',
        'fiscal_year',
        'financial_transaction_id',
        'related_tax_invoice_id',
        'issuer_profile_id',
        'customer_name',
        'customer_tax_id',
        'customer_branch',
        'customer_address',
        'subtotal',
        'vat_rate',
        'vat_amount',
        'total_credit',
        'reason',
        'status',
        'issued_at',
        'voided_at',
        'void_reason',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'credit_note_date' => 'date',
        'issued_at' => 'datetime',
        'voided_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    public function financialTransaction()
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function relatedTaxInvoice()
    {
        return $this->belongsTo(TaxInvoice::class, 'related_tax_invoice_id');
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
