<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaborLedgerEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'labor_team_id',
        'labor_team_member_id',
        'labor_charge_type_id',
        'labor_bill_payment_id',
        'entry_date',
        'description',
        'amount',
        'request_number',
        'quantity',
        'unit_rate',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'decimal:2',
            'unit_rate' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(LaborTeam::class, 'labor_team_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(LaborTeamMember::class, 'labor_team_member_id');
    }

    public function chargeType(): BelongsTo
    {
        return $this->belongsTo(LaborChargeType::class, 'labor_charge_type_id');
    }

    public function billPayment(): BelongsTo
    {
        return $this->belongsTo(LaborBillPayment::class, 'labor_bill_payment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
