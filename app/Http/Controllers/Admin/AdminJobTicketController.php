<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminJobTicketRequest;
use App\Models\JobTicket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class AdminJobTicketController extends Controller
{
    /**
     * Show the form for creating a new job ticket by an admin.
     */
    public function create(): View
    {
        // V2.4-S12: Fetch all users with the 'employer' role for the dropdown.
        // Eager load the 'employer' relationship to get the company name.
        $employers = User::role('employer')
            ->with('employer') // Make sure the 'employer' relationship exists on the User model
            ->whereHas('employer') // Ensure we only get users linked to an employer profile
            ->get()
            ->sortBy('employer.employerNameTh'); // Sort by the company name

        return view('admin.tickets.create', compact('employers'));
    }

/** * Store a new job ticket created by an admin. * V2.4-S15: Rewritten to follow blueprint logic (based on TicketController@store) */ public function store(StoreAdminJobTicketRequest $request): RedirectResponse { $validated = $request->validated(); $adminUser = Auth::user(); $employerUserId = $validated['employer_user_id']; $attachments = $validated['attachments'] ?? []; $movedFiles = []; $storageDisk = 'public'; try { // Use Transaction to ensure data integrity DB::beginTransaction(); // 1. Create the Job Ticket $ticket = JobTicket::create([ 'employer_user_id' => $employerUserId, 'subject' => $validated['subject'], 'status' => 'pending_staff', 'assigned_staff_id' => $adminUser->id, // Assign to the admin who created it ]); $permanentBasePath = "ticket_attachments/{$ticket->id}"; // 2. Create the Initial Message (Comment) // This message is authored by the Admin $ticket->messages()->create([ 'job_ticket_id' => $ticket->id, 'user_id' => $adminUser->id, 'message_type' => 'comment', 'body' => $validated['message'], ]); // --- Attachment Processing --- // 3. Process General Files (attachment_file) if (!empty($attachments['files'])) { foreach ($attachments['files'] as $fileData) { $tempPath = $fileData['path']; $permanentPath = $permanentBasePath . '/files/' . basename($tempPath); if (Storage::disk($storageDisk)->move($tempPath, $permanentPath)) { $movedFiles[] = $permanentPath; $ticket->messages()->create([ 'job_ticket_id' => $ticket->id, 'user_id' => $adminUser->id, // Admin is the author 'message_type' => 'attachment_file', 'body' => json_encode([ 'path' => $permanentPath, 'name' => $fileData['name'], 'size' => $fileData['size'], ]), ]); } else { throw new Exception("Failed to move file from {$tempPath} to {$permanentPath}"); } } } // 4. Process Existing Employees (attachment_employee) if (!empty($attachments['existing_employees'])) { $messagesToInsert = []; foreach ($attachments['existing_employees'] as $employeeId) { $messagesToInsert[] = [ 'job_ticket_id' => $ticket->id, 'user_id' => $adminUser->id, // Admin is the author 'message_type' => 'attachment_employee', 'body' => $employeeId, // Store just the ID 'created_at' => now(), 'updated_at' => now(), ]; } TicketMessage::insert($messagesToInsert); } // 5. Process New Employees (attachment_new_employee) if (!empty($attachments['new_employees'])) { $fileFields = ['employeePhoto', 'document_1']; foreach ($attachments['new_employees'] as $newEmployeeJson) { $data = json_decode($newEmployeeJson, true); if (is_null($data)) { throw new Exception("Invalid JSON data detected for new employee."); } // Move files associated with this new employee foreach ($fileFields as $field) { if (isset($data[$field]) && $data[$field] && str_starts_with($data[$field], 'temp_uploads/')) { $tempPath = $data[$field]; $permanentPath = $permanentBasePath . '/new_employees/' . basename($tempPath); if (Storage::disk($storageDisk)->exists($tempPath)) { if (Storage::disk($storageDisk)->move($tempPath, $permanentPath)) { $movedFiles[] = $permanentPath; $data[$field] = $permanentPath; // Update path in JSON data } else { throw new Exception("Failed to move new employee file from {$tempPath} to {$permanentPath}"); } } else { $data[$field] = null; // File missing, set to null } } } // Create the message record with the updated JSON $ticket->messages()->create([ 'job_ticket_id' => $ticket->id, 'user_id' => $adminUser->id, // Admin is the author 'message_type' => 'attachment_new_employee', 'body' => json_encode($data), ]); } } // --- End of Attachment Processing --- DB::commit(); return redirect()->route('admin.tickets.show', $ticket->id) ->with('success', 'Ticket created successfully.'); } catch (Exception $e) { DB::rollBack(); if (count($movedFiles) > 0) { Log::warning('Admin Ticket creation failed. Cleaning up moved files.', ['files' => $movedFiles]); Storage::disk($storageDisk)->delete($movedFiles); } Log::error('Admin Ticket creation failed (S15): ' . $e->getMessage(), [ 'user_id' => $adminUser->id, 'trace' => $e->getTraceAsString() ]); return back()->withInput()->with('danger', 'Failed to create ticket: ' . $e->getMessage()); } }

}
