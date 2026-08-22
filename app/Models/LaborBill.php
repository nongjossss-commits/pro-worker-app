<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaborBill extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'labor_team_id',
        'bill_no',
        'financial_profile_id',
        'period_start',
        'period_end',
        'previous_balance',
        'period_charges',
        'total_due',
        'status',
        'is_auto_generated',
        'pdf_path',
        'issued_at',
        'voided_at',
        'void_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'previous_balance' => 'decimal:2',
            'period_charges' => 'decimal:2',
            'total_due' => 'decimal:2',
            'is_auto_generated' => 'boolean',
            'issued_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(LaborTeam::class, 'labor_team_id');
    }

    public function financialProfile(): BelongsTo
    {
        return $this->belongsTo(FinancialProfile::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function taxInvoices()
    {
        return $this->hasMany(LaborTaxInvoice::class);
    }

    public function whtCertificates()
    {
        return $this->hasMany(LaborWhtCertificate::class);
    }

    public function payments()
    {
        return $this->hasMany(LaborBillPayment::class);
    }

    public function getTotalPaidAttribute(): float
    {
        // When the query eager-computed the sum via withSum('payments as
        // paid_sum', 'amount') (used for list-page filtering/summaries),
        // reuse it instead of firing a per-row query.
        if (array_key_exists('paid_sum', $this->attributes)) {
            return (float) $this->attributes['paid_sum'];
        }

        return (float) $this->payments()->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return (float) $this->total_due - $this->total_paid;
    }

    /**
     * 'unpaid' | 'partial' | 'paid' — derived from total_paid vs total_due,
     * used to badge each bill row and to drive the Bills index filter.
     */
    public function getPaymentStatusAttribute(): string
    {
        if ($this->total_paid <= 0) {
            return 'unpaid';
        }

        return $this->balance_due <= 0 ? 'paid' : 'partial';
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'void');
    }
}
