<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'production_financial_group_id',
        'type', // installment, down_payment, full_payment
        'amount',
        'discount_amount',
        'credit_amount',
        'discount_description',
        'due_date',
        'paid_at',
        'paid_amount',
        'slip_path',
        'status', // pending, partial, paid, overdue
        'notes',
        'line_items',
        'bank_account_id',
        'withholding_tax_amount',
        'wht_document_path',
        'wht_status',
        'wht_received_at',
        'wht_no_cert_reason',
        'financial_profile_id',
        'payment_collection_mode', // 'lump_sum' (default) | 'per_employee'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'line_items' => 'array',
        'discount_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'wht_received_at' => 'datetime',
        'fully_paid_at' => 'datetime',
    ];

    /**
     * fully_paid_at = when the status became 'paid' (every path that sets the
     * status saves the model). The Finance popup keeps a paid installment in
     * the main list for 24 hours, then shows it under the "Fully paid" tab.
     */
    protected static function booted(): void
    {
        static::saving(function (self $transaction) {
            if ($transaction->status !== 'paid') {
                $transaction->fully_paid_at = null;
            } elseif (! $transaction->fully_paid_at || $transaction->getOriginal('status') !== 'paid') {
                $transaction->fully_paid_at = now();
            }
        });
    }

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function financialGroup()
    {
        return $this->belongsTo(ProductionFinancialGroup::class, 'production_financial_group_id');
    }

    public function items()
    {
        return $this->belongsToMany(ProductionItem::class, 'financial_transaction_items')->withPivot('unit_price');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function financialProfile()
    {
        return $this->belongsTo(FinancialProfile::class);
    }

    public function payments()
    {
        return $this->hasMany(FinancialPayment::class);
    }

    /**
     * Per-employee payment ledger (payment_collection_mode === 'per_employee'
     * only) — see FinancialTransactionItemPayment's docblock.
     */
    public function itemPayments()
    {
        return $this->hasMany(FinancialTransactionItemPayment::class);
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }

    /**
     * Fully paid = nothing left to collect. Such a bill is a closed record:
     * it cannot be deleted (corrections go through a credit note).
     */
    public function isFullyPaid(): bool
    {
        if ($this->status === 'paid') {
            return true;
        }
        $effective = (float) $this->amount - (float) ($this->discount_amount ?? 0) - (float) ($this->credit_amount ?? 0);

        return (float) $this->paid_amount > 0 && (float) $this->paid_amount + 0.005 >= $effective;
    }

    /**
     * Price of one employee from a group's per-head tiers — explicit tier
     * membership only (same rule as getTierForItem() on the finance screen).
     * Tier ids may be the item id, or emp_{id} / {id} of the employee.
     */
    public static function tierPriceFor(array $tiers, $itemId, $employeeId = null): ?float
    {
        foreach ($tiers as $tier) {
            $ids = array_map('strval', $tier['item_ids'] ?? []);
            if (in_array((string) $itemId, $ids, true)
                || in_array('emp_' . $itemId, $ids, true)
                || ($employeeId && (in_array('emp_' . $employeeId, $ids, true) || in_array((string) $employeeId, $ids, true)))) {
                return (float) ($tier['price'] ?? 0);
            }
        }

        return null;
    }

    /**
     * Snapshot the per-head price of every employee on this bill that has
     * none yet. Employees already on the bill keep the price they were
     * billed at even when the tier is later edited or deleted; only taking
     * them off the bill (or deleting it) drops the snapshot.
     */
    public function snapshotItemPrices(): void
    {
        $group = $this->financialGroup()->first();
        $data = $group?->financial_data ?? [];
        if (($data['pricing_mode'] ?? 'per_head') !== 'per_head') {
            return;
        }
        $tiers = $data['pricing_tiers'] ?? [];

        foreach ($this->items()->wherePivotNull('unit_price')->get() as $item) {
            $price = self::tierPriceFor($tiers, $item->id, $item->employee_id);
            if ($price !== null && $price > 0) {
                $this->items()->updateExistingPivot($item->id, ['unit_price' => $price]);
            }
        }
    }
}
