<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
// Import Attribute for Accessors
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use App\Models\Employee;


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
                'in_progress' => 'primary', // Blue
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

    // --- V2.4-S9: Attachment Processing Accessor ---
    /**
     * Process all messages and return categorized attachments.
     * Optimizes data loading and ensures Historical Integrity.
     * Usage in Blade: $ticket->categorized_attachments
     */
    protected function categorizedAttachments(): Attribute
    {
        return Attribute::make(
            get: function () {
                $attachments = [
                    'existing_employees' => collect(), // Will hold Employee models
                    'new_employees' => collect(), // Will hold decoded JSON data (objects)
                    'files' => collect(), // Will hold file metadata (objects)
                ];

                $existingEmployeeIds = [];

                // 1. Iterate through messages and categorize
                // Ensure messages are loaded (Controller should handle this)
                if (!$this->relationLoaded('messages')) {
                    $this->load('messages');
                }

                foreach ($this->messages as $message) {
                    switch ($message->message_type) {
                        case 'attachment_employee':
                            // Collect IDs for Eager Loading
                            $existingEmployeeIds[] = (int)$message->body;
                            break;
                        case 'attachment_new_employee':
                            // Decode JSON string into an object
                            $data = json_decode($message->body);
                            if ($data) {
                                // V2.4-S9: Generate URLs for files within the JSON data
                                $fileFields = ['employeePhoto', 'document_1']; // Add others if needed
                                foreach ($fileFields as $field) {
                                    // Check if field exists, has a value, AND the file exists on disk
                                    if (isset($data->$field) && $data->$field && Storage::disk('public')->exists($data->$field)) {
                                        // Add a new property for the URL (e.g., employeePhoto_url)
                                        $urlField = $field . '_url';
                                        $data->$urlField = Storage::disk('public')->url($data->$field);
                                    }
                                }
                                $attachments['new_employees']->push($data);
                            }
                            break;
                        case 'attachment_file':
                            // Decode JSON string into an object
                            $data = json_decode($message->body);
                            if ($data) {
                                // V2.4-S9: Generate URL for the file and check existence
                                if (isset($data->path) && Storage::disk('public')->exists($data->path)) {
                                    $data->url = Storage::disk('public')->url($data->path);
                                } else {
                                    $data->url = null; // Indicate file is missing
                                }
                                $attachments['files']->push($data);
                            }
                            break;
                        // 'comment' and 'system_activity' are ignored here (handled by Chat History)
                    }
                }

                // 2. Optimization & Historical Integrity: Eager Load Employee models
                if (!empty($existingEmployeeIds)) {
                    // CRITICAL: Use withTrashed() to include soft-deleted records.
                    // Use withoutGlobalScopes() to bypass tenancy if the viewer (e.g. Staff) shouldn't normally see this employee.
                    $employeeModels = Employee::withoutGlobalScopes()->withTrashed()
                        ->whereIn('id', array_unique($existingEmployeeIds))
                        ->get()
                        ->keyBy('id');

                    // Map the loaded models back (maintaining order if duplicates were attached)
                    foreach ($existingEmployeeIds as $id) {
                        if (isset($employeeModels[$id])) {
                            $attachments['existing_employees']->push($employeeModels[$id]);
                        }
                    }
                }

                return (object)$attachments;
            }
        );
    }
}
