<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_type', 'customer_name',
        'start_date', 'end_date', 'grace_end_date',
        'attachment_1_path', 'attachment_1_original', 'attachment_1_uploaded_at',
        'attachment_2_path', 'attachment_2_original', 'attachment_2_uploaded_at',
        'attachment_3_path', 'attachment_3_original', 'attachment_3_uploaded_at',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'grace_end_date' => 'date:Y-m-d',
        'attachment_1_uploaded_at' => 'datetime',
        'attachment_2_uploaded_at' => 'datetime',
        'attachment_3_uploaded_at' => 'datetime',
    ];

    public function extensions(): HasMany
    {
        return $this->hasMany(ServiceContractExtension::class)->latest();
    }

    /**
     * The single active contract row — most recently updated. Older rows
     * remain in the table for history.
     */
    public static function current(): ?self
    {
        return static::orderByDesc('id')->first();
    }

    /**
     * Whichever date is later: the real end_date or the temporary grace
     * extension. If both are null (shouldn't happen for an active row),
     * returns null.
     */
    public function effectiveEndDate(): ?\Carbon\Carbon
    {
        $end = $this->end_date;
        $grace = $this->grace_end_date;
        if (!$end && !$grace) return null;
        if (!$grace) return $end;
        if (!$end) return $grace;
        return $end->greaterThan($grace) ? $end : $grace;
    }

    public function isInGracePeriod(): bool
    {
        if (!$this->grace_end_date || !$this->end_date) return false;
        $today = now()->startOfDay();
        return $today->greaterThan($this->end_date) && $today->lessThanOrEqualTo($this->grace_end_date);
    }
}
