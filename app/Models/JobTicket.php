<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobTicket extends Model
{
    use HasFactory;

    // Corrected $fillable
    protected $fillable = [
        'employer_user_id',
        'subject',
        'status',
        'assigned_staff_id',
    ];

    // Ensure $casts array is either absent or empty if it was added. We don't need it for DB Enums.
    // If using modern Laravel structure, ensure the casts() method is clean:
    protected function casts(): array
    {
        return [];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    // Corrected Relationship (using employer_user_id)
    public function employerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_user_id');
    }

    // Corrected Relationship (using assigned_staff_id)
    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }
}
