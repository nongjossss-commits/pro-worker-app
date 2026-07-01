<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceContractExtension extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_contract_id', 'extended_by_user_id',
        'action', 'previous_end', 'new_end', 'days_added', 'reason',
    ];

    protected $casts = [
        'previous_end' => 'date:Y-m-d',
        'new_end' => 'date:Y-m-d',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(ServiceContract::class, 'service_contract_id');
    }

    public function extender(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'extended_by_user_id');
    }
}
