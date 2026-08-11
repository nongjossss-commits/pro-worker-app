<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A Pro Walker Labor "สมุดบัญชี" — one of the company's own bank
 * accounts/cashbooks. Separate from the main app's `bank_accounts`
 * (see migration docblock).
 */
class LaborBookAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'bank_name',
        'account_number',
        'account_name',
        'opening_balance',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(LaborBookTransaction::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Live balance — never stored, always opening_balance + SUM(income) -
     * SUM(expense) from labor_book_transactions, so it can never drift out
     * of sync with the underlying rows.
     */
    public function getCurrentBalanceAttribute(): float
    {
        $income = (float) $this->transactions()->where('type', 'income')->sum('amount');
        $expense = (float) $this->transactions()->where('type', 'expense')->sum('amount');

        return (float) $this->opening_balance + $income - $expense;
    }
}
