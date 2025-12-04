<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
// Import Attribute for Accessors
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use App\Models\Employee;
use App\Traits\LogActivity;


class JobTicket extends Model
{
    use HasFactory, LogActivity, SoftDeletes;

    // Corrected $fillable
    protected $fillable = [
        'employer_user_id',
        'subject',
        'status',
        'assigned_staff_id',
        'admin_unread_count',
        'employer_unread_count',
        'hidden_for_admin_at',
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
                    'existing_employees' => collect(), // Affiliated employees
                    'external_employees' => collect(), // V2.5-S17: Non-affiliated employees (Exception for Admin/Staff)
                    'new_employees' => collect(),
                    'files' => collect(),
                ];

                $employeeMessageMap = []; // [employee_id => [message_id1, message_id2, ...]]

                // 1. Iterate through messages and categorize, associating message IDs
                if (!$this->relationLoaded('messages')) {
                    $this->load('messages');
                }

                foreach ($this->messages as $message) {
                    switch ($message->message_type) {
                        case 'attachment_employee':
                            $employeeId = (int)$message->body;
                            $employeeMessageMap[$employeeId][] = $message->id;
                            break;

                        case 'attachment_new_employee':
                            $data = json_decode($message->body);
                            if ($data) {
                                // V2.5-S4: Dynamically check all possible document fields
                                $fileFields = ['employeePhoto'];
                                for ($i = 1; $i <= 12; $i++) {
                                    $fileFields[] = 'employee_doc_' . $i;
                                }
                                // V2.5-S4: Add insurance documents
                                $fileFields[] = 'insurance_document_path_social';
                                $fileFields[] = 'insurance_document_path_hospital';
                                $fileFields[] = 'insurance_document_path_private';


                                foreach ($fileFields as $field) {
                                    if (isset($data->$field) && !empty($data->$field) && Storage::disk('public')->exists($data->$field)) {
                                        $urlField = $field . '_url';
                                        $data->$urlField = Storage::disk('public')->url($data->$field);
                                    }
                                }
                                $attachments['new_employees']->push((object)[
                                    'data' => $data,
                                    'message_id' => $message->id
                                ]);
                            }
                            break;

                        case 'attachment_file':
                            $data = json_decode($message->body);
                            if ($data) {
                                if (isset($data->path) && Storage::disk('public')->exists($data->path)) {
                                    $data->url = Storage::disk('public')->url($data->path);
                                } else {
                                    $data->url = null;
                                }
                                $attachments['files']->push((object)[
                                    'data' => $data,
                                    'message_id' => $message->id
                                ]);
                            }
                            break;
                    }
                }

                // 2. Eager Load Employee models and map back with message IDs
                if (!empty($employeeMessageMap)) {
                    // V2.5-S6 FIX: Chain append('photo_url') to add the accessor to the collection
                    $employeeModels = Employee::withoutGlobalScopes()->withTrashed()
                        ->whereIn('id', array_keys($employeeMessageMap))
                        ->get()
                        ->append('photo_url')
                        ->keyBy('id');

                    // V2.5-S17: Determine affiliation
                    // Get the ticket's employer ID
                    $ticketEmployerId = $this->employerUser?->employer?->id;

                    foreach ($employeeMessageMap as $employeeId => $messageIds) {
                        if (isset($employeeModels[$employeeId])) {
                            $emp = $employeeModels[$employeeId];

                            // Check affiliation (Handle case where ticket employer might be null/deleted)
                            $isAffiliated = $ticketEmployerId && ($emp->employer_id === $ticketEmployerId);

                            $targetCollection = $isAffiliated ? 'existing_employees' : 'external_employees';

                            foreach ($messageIds as $messageId) {
                                $attachments[$targetCollection]->push((object)[
                                    'employee' => $emp,
                                    'message_id' => $messageId
                                ]);
                            }
                        }
                    }
                }

                return (object)$attachments;
            }
        );
    }
}
