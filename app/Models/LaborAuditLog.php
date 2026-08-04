<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaborAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'labor_team_id',
        'labor_ledger_entry_id',
        'labor_bill_id',
        'action',
        'changes',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(LaborTeam::class, 'labor_team_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(LaborBill::class, 'labor_bill_id');
    }
}
