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
        'labor_bill_id',
        'entry_date',
        'description',
        'amount',
        'request_number',
        'quantity',
        'unit_rate',
        'qty_laos',
        'qty_myanmar',
        'qty_cambodia',
        'qty_vietnam',
        'qty_other',
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
            'qty_laos' => 'integer',
            'qty_myanmar' => 'integer',
            'qty_cambodia' => 'integer',
            'qty_vietnam' => 'integer',
            'qty_other' => 'integer',
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

    /** The itemized/monthly bill this entry was billed on (null = not on one). */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(LaborBill::class, 'labor_bill_id');
    }

    /**
     * Error message when this entry sits on an active itemized/monthly bill
     * (editing or deleting it would make the issued bill wrong), else null.
     */
    public function billLockMessage(): ?string
    {
        $bill = $this->labor_bill_id ? $this->bill : null;
        if (!$bill || $bill->status === 'void') {
            return null;
        }

        return __('This entry is on bill :no — void that bill first to edit or delete it.', ['no' => $bill->bill_no]);
    }

    /** Charges only — positive amounts that are not payment offsets. */
    public function scopeCharges($query)
    {
        return $query->where('amount', '>', 0)->whereNull('labor_bill_payment_id');
    }

    /**
     * Not billed yet: not on an itemized/monthly bill, and not dated inside
     * the period of a (non-void) statement bill of the same team — statement
     * bills don't tag entries, so their coverage is inferred from dates.
     */
    public function scopeUnbilled($query)
    {
        $table = $query->getModel()->getTable();

        return $query->whereNull($table . '.labor_bill_id')
            ->whereNotExists(function ($q) use ($table) {
                $q->selectRaw('1')
                    ->from('labor_bills as sb')
                    ->whereColumn('sb.labor_team_id', $table . '.labor_team_id')
                    ->where('sb.bill_type', 'statement')
                    ->where('sb.status', '!=', 'void')
                    ->whereNull('sb.deleted_at')
                    ->whereRaw('DATE(' . $table . '.entry_date) >= DATE(sb.period_start)')
                    ->whereRaw('DATE(' . $table . '.entry_date) <= DATE(sb.period_end)');
            });
    }

    /** Already billed — the exact opposite of scopeUnbilled(). */
    public function scopeBilled($query)
    {
        $table = $query->getModel()->getTable();
        $unbilledIds = $query->getModel()->newQuery()->unbilled()->select($table . '.id');

        return $query->whereNotIn($table . '.id', $unbilledIds);
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
