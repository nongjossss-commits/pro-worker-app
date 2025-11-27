<?php

// app/Http/Controllers/TicketController.php
namespace App\Http\Controllers;

use App\Models\JobTicket;
// Import TicketMessage for bulk insert
use App\Models\TicketMessage;
use App\Http\Requests\StoreTicketRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
// Import Storage and Log facades
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
// Import Exception for robust error handling
use Exception;
// Ensure Illuminate\Http\Request is NOT imported if it conflicts or is unused.

class TicketController extends Controller
{
    /**
     * Display a listing of the user's own tickets (My Tickets).
     */
    public function index(): View | RedirectResponse
    {
        // Interface Enforcement: If the user can manage tickets (Staff/Admin), redirect to admin interface.
        if (Auth::user()->can('manage-tickets')) {
            return redirect()->route('admin.tickets.index');
        }

        // V2.4-S3: Handle Per Page selection using request() helper, default to 25
        $perPage = request('per_page', 25);

        // Enforce Tenancy
        $tickets = JobTicket::where('employer_user_id', Auth::id())
            ->latest()
            ->paginate($perPage);

        return view('tickets.index', compact('tickets'));
    }

    /**
     * Show the form for creating a new ticket.
     */
    public function create(): View
    {
        // Security Check: Ensure the user has the 'employer' role.
        if (!Auth::user()->hasRole('employer')) {
            abort(403, 'Unauthorized action. Only employers can submit requests.');
        }

        return view('tickets.create');
    }

    /**
     * Store a newly created ticket.
     * V2.4-S8: Comprehensive Backend Logic Implementation.
     */
    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();
        $attachments = $validated['attachments'] ?? [];

        // V2.4-S8: Track successfully moved files for cleanup on failure
        $movedFiles = [];
        $storageDisk = 'public'; // We are using the public disk

        try {
            // Use Transaction to ensure data integrity (DB + File operations)
            DB::beginTransaction();

            // 1. Create the Job Ticket
            $ticket = JobTicket::create([
                'employer_user_id' => $user->id,
                'subject' => $validated['subject'],
                'status' => 'pending_staff',
                'admin_unread_count' => 1, // Employer creates -> Admin has 1 unread message
            ]);

            // V2.5.1: Unhide Employer Ticket Box if Hidden (Activity Detected)
            // When an employer creates a new ticket, they should become visible.
            if ($user->is_ticket_hidden) {
                $user->update(['is_ticket_hidden' => false]);
            }

            // Define the permanent storage directory for this ticket
            $permanentBasePath = "ticket_attachments/{$ticket->id}";

            // 2. Create the Initial Message (Conditional)
            if (!empty($validated['message'])) {
                $ticket->messages()->create([
                    'user_id' => $user->id,
                    'message_type' => 'comment',
                    'body' => $validated['message'],
                ]);
            }

            // --- V2.4-S8: Attachment Processing Logic ---

            // 3. Process General Files (attachment_file)
            if (!empty($attachments['files'])) {
                foreach ($attachments['files'] as $fileData) {
                    $tempPath = $fileData['path'];
                    // Generate permanent path (e.g., ticket_attachments/1/files/uuid.ext)
                    $permanentPath = $permanentBasePath . '/files/' . basename($tempPath);

                    // CRITICAL: Move the file. StoreTicketRequest already validated existence.
                    if (Storage::disk($storageDisk)->move($tempPath, $permanentPath)) {
                        $movedFiles[] = $permanentPath; // Track success

                        // Create the message record
                        $ticket->messages()->create([
                            'user_id' => $user->id,
                            'message_type' => 'attachment_file',
                            // Store metadata as JSON in the body
                            'body' => json_encode([
                                'path' => $permanentPath,
                                'name' => $fileData['name'],
                                'size' => $fileData['size'],
                            ]),
                        ]);
                    } else {
                        // If move fails (e.g., permissions issue), throw exception to trigger rollback/cleanup
                        throw new Exception("Failed to move file from {$tempPath} to {$permanentPath}");
                    }
                }
            }

            // 4. Process Existing Employees (attachment_employee)
            if (!empty($attachments['existing_employees'])) {
                $messagesToInsert = [];
                foreach ($attachments['existing_employees'] as $employeeId) {
                    $messagesToInsert[] = [
                        'job_ticket_id' => $ticket->id,
                        'user_id' => $user->id,
                        'message_type' => 'attachment_employee',
                        'body' => $employeeId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                // Use Eloquent insert() for efficient bulk creation
                TicketMessage::insert($messagesToInsert);
            }

            // 5. Process New Employees (attachment_new_employee)
            if (!empty($attachments['new_employees'])) {
                // Define all possible file fields that need to be processed
                $fileFields = [
                    'employeePhoto',
                    'insurance_document_path_social',
                    'insurance_document_path_hospital',
                    'insurance_document_path_private'
                ];
                for ($i = 1; $i <= 12; $i++) {
                    $fileFields[] = 'employee_doc_' . $i;
                }

                foreach ($attachments['new_employees'] as $newEmployeeJson) {
                    // Fix: Handle array (from validation) or string. StoreTicketRequest converts JSON strings to arrays.
                    $data = is_array($newEmployeeJson) ? $newEmployeeJson : json_decode($newEmployeeJson, true);

                    if (!is_array($data)) {
                        throw new Exception("Invalid JSON data detected during processing.");
                    }

                    // Iterate over potential file fields and move them
                    foreach ($fileFields as $field) {
                        // Check if the field exists, has a value, and looks like a temp path
                        if (isset($data[$field]) && $data[$field] && str_starts_with($data[$field], 'temp_uploads/')) {
                            $tempPath = $data[$field];
                            $permanentPath = $permanentBasePath . '/new_employees/' . basename($tempPath);

                            // Defense-in-depth: Check existence right before moving
                            if (Storage::disk($storageDisk)->exists($tempPath)) {
                                if (Storage::disk($storageDisk)->move($tempPath, $permanentPath)) {
                                    $movedFiles[] = $permanentPath;
                                    // CRITICAL: Update the path in the data array to the permanent location
                                    $data[$field] = $permanentPath;
                                } else {
                                    throw new Exception("Failed to move new employee file from {$tempPath} to {$permanentPath}");
                                }
                            } else {
                                // If temp file is missing (e.g., expired, although validation should prevent this), treat as null
                                $data[$field] = null;
                            }
                        }
                    }

                    // Create the message record with the updated JSON
                    $ticket->messages()->create([
                        'user_id' => $user->id,
                        'message_type' => 'attachment_new_employee',
                        'body' => json_encode($data),
                    ]);
                }
            }

            // --- End of Attachment Processing ---

            DB::commit();

            // Redirect to the newly created ticket's detail page
            return redirect()->route('tickets.show', $ticket)->with('success', 'สร้างคำขอและประมวลผลสิ่งที่แนบมาเรียบร้อยแล้ว');

        } catch (Exception $e) {
            DB::rollBack();

            // V2.4-S8: CRITICAL CLEANUP - Delete moved files if transaction fails
            if (count($movedFiles) > 0) {
                Log::warning('Ticket creation transaction failed. Cleaning up moved files.', ['files' => $movedFiles]);
                // Attempt to delete files that were successfully moved before the failure occurred
                Storage::disk($storageDisk)->delete($movedFiles);
            }

            // Log the error for debugging
            Log::error('Ticket creation failed during processing (S8): ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            // Provide a user-friendly error message
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาดร้ายแรงในการประมวลผลคำขอหรือการจัดการไฟล์ กรุณาลองใหม่อีกครั้ง.');
        }
    }

    /**
     * Display the specified ticket (User View).
     */
    public function show(JobTicket $ticket): View | RedirectResponse
    {
        // Interface Enforcement: If the user can manage tickets, redirect to the admin view of the ticket.
        if (Auth::user()->can('manage-tickets')) {
            return redirect()->route('admin.tickets.show', $ticket);
        }

        // Enforce Tenancy: Authorize the user to view this ticket (Must be the owner).
        if ($ticket->employer_user_id !== Auth::id()) {
            abort(403, 'Unauthorized action. You do not own this ticket.');
        }

        // Reset Unread Count for Employer
        if ($ticket->employer_unread_count > 0) {
            $ticket->update(['employer_unread_count' => 0]);
        }

        // V2.4-S9 Optimization: Load messages ordered by creation time (for history)
        $ticket->load(['messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }, 'messages.user']);

        // The categorized_attachments accessor will handle the rest of the data loading.
        return view('tickets.show', compact('ticket'));
    }
}
