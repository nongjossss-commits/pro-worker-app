<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
// Import Attribute for Accessors
use Illuminate\Database\Eloquent\Casts\Attribute;

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

    // --- V2.4-S3: Status Accessors ---
    /**
     * Get the human-readable status name (Thai).
     * Usage in Blade: {{ $ticket->status_name }}
     */
    protected function statusName(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
                'pending_staff' => 'รอเจ้าหน้าที่ดำเนินการ',
                'pending_employer' => 'รอนายจ้างตอบกลับ',
                'in_progress' => 'กำลังดำเนินการ',
                'resolved' => 'เสร็จสิ้น',
                'rejected' => 'ถูกปฏิเสธ',
                default => 'ไม่ทราบสถานะ',
            },
        );
    }

    /**
     * Get the Bootstrap color class for the status.
     * Usage in Blade: class="bg-{{ $ticket->status_color }}"
     */
    protected function statusColor(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
                'pending_staff' => 'warning', // Yellow/Orange
                'pending_employer' => 'info', // Light Blue
                // CRITICAL CORRECTION: Ensure 'in_progress' maps to 'primary' (Blue)
                'in_progress' => 'primary',
                'resolved' => 'success', // Green
                'rejected' => 'danger', // Red
                default => 'secondary',// Gray
            },
        );
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
