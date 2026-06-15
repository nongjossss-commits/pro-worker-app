<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhtCertificate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cert_no',
        'type',
        'wht_type',
        'tax_period_year',
        'tax_period_month',
        'payer_name',
        'payer_tax_id',
        'payee_name',
        'payee_tax_id',
        'income_type',
        'amount_paid',
        'wht_rate',
        'wht_amount',
        'paid_at',
        'source_type',
        'source_id',
        'certificate_path',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount_paid' => 'decimal:2',
        'wht_rate' => 'decimal:2',
        'wht_amount' => 'decimal:2',
    ];

    public function source()
    {
        return $this->morphTo();
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
        return $this->status === 'submitted';
    }

    public function scopeIssued($query)
    {
        return $query->where('type', 'issued');
    }

    public function scopeReceived($query)
    {
        return $query->where('type', 'received');
    }

    public function scopeForPeriod($query, int $year, ?int $month = null)
    {
        $q = $query->where('tax_period_year', $year);
        if ($month) {
            $q->where('tax_period_month', $month);
        }
        return $q;
    }
}
