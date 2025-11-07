<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminJobTicketRequest;
use App\Models\Employer;
use App\Models\JobTicket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdminJobTicketController extends Controller
{
    /**
     * Show the form for creating a new resource.
     * V2.4-S20: Fix BadMethodCallException using Collection Sorting and Robust Soft Delete check.
     */
    public function create()
    {
        // V2.4-S21: We can now safely use orderBy() on the actual column name.
        $query = Employer::select('id', 'employerNameTh', 'employerNameEn')
            ->orderBy('employerNameTh'); // Robustly Override Soft Deletes

        // Use class_uses_recursive to reliably detect the SoftDeletes trait.
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(Employer::class))) {
            $query->withTrashed();
        }

        $employers = $query->get();
        // (Jules: ลบ Collection Sorting ->sortBy(...) ที่เคยใช้เป็น Workaround ออก หากมี)
        return view('admin.tickets.create', compact('employers'));
    }

    /**
     * Store a new job ticket created by an admin.
     */
    public function store(StoreAdminJobTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $movedFiles = []; // Initialize here to be in scope for the catch block

        DB::beginTransaction();
        try {
            // 1. Create the Job Ticket
            $ticket = JobTicket::create([
                'employer_user_id' => $validated['employer_user_id'],
                'subject' => $validated['subject'],
                'status' => 'pending_staff', // Default status for new tickets
                // Admins can create tickets for others, but they are the initial assigned staff.
                'assigned_staff_id' => Auth::id(),
            ]);

            // 2. Create the initial Ticket Message
            $message = TicketMessage::create([
                'job_ticket_id' => $ticket->id,
                'user_id' => Auth::id(), // The admin is the author of this first message
                'message_type' => 'comment',
                'body' => $validated['message'],
            ]);

            $attachments = $validated['attachments'] ?? [];
            $this->processAttachments($message, $attachments, $ticket->id, $validated['employer_user_id'], $movedFiles);


            DB::commit();

            return redirect()->route('admin.tickets.show', $ticket->id)
                ->with('success', 'Ticket created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Also clean up any files that were moved from temp
            if (!empty($movedFiles)) {
                Storage::disk('public')->delete($movedFiles);
            }
            return back()->withInput()->with('danger', 'Failed to create ticket: ' . $e->getMessage());
        }
    }

     /**
     * Process attachments from the request.
     *
     * @param TicketMessage $message The parent message model.
     * @param array $attachments The attachments data from the validated request.
     * @param int $ticketId The ID of the job ticket.
     * @param int $employerUserId The user ID of the employer this ticket belongs to.
     * @param array &movedFiles A reference to an array tracking moved files for potential rollback.
     */
    private function processAttachments(TicketMessage $message, array $attachments, int $ticketId, int $employerUserId, array &$movedFiles): void
    {
        // Associate Existing Employees
        if (!empty($attachments['existing_employees'])) {
            $message->employees()->attach($attachments['existing_employees']);
        }

        // Create New Employees
        if (!empty($attachments['new_employees'])) {
            foreach ($attachments['new_employees'] as $newEmployeeData) {
                // The data is already a decoded array from the FormRequest
                $newEmployeeRecord = Employee::create([
                    // Find the employer_id from the user_id
                    'employer_id' => User::find($employerUserId)->employer->id,
                    'employeeTitleTh' => $newEmployeeData['employeeTitleTh'],
                    'employeeNameTh' => $newEmployeeData['employeeNameTh'],
                    'employeeDob' => $newEmployeeData['employeeDob'],
                    'employeeNationality' => $newEmployeeData['employeeNationality'],
                    'employeePassport' => $newEmployeeData['employeePassport'] ?? null,
                    // Handle file paths
                    'employeePhoto' => $this->moveAttachment($newEmployeeData['employeePhoto'], 'employee_photos', $ticketId, $movedFiles),
                    'document_1' => $this->moveAttachment($newEmployeeData['document_1'], 'employee_documents', $ticketId, $movedFiles),
                ]);

                // Attach the newly created employee to the message
                $message->employees()->attach($newEmployeeRecord->id);
            }
        }

        // Attach General Files
        if (!empty($attachments['files'])) {
            foreach ($attachments['files'] as $fileData) {
                 $finalPath = $this->moveAttachment($fileData['path'], 'ticket_attachments', $ticketId, $movedFiles);
                 // Assuming you have a relationship on TicketMessage to store general files
                 // If not, this part needs adjustment (e.g., storing JSON in the body)
            }
        }
    }

    /**
     * Move a file from a temporary path to a permanent one.
     *
     * @param string|null $tempPath The temporary file path.
     * @param string $destinationFolder The target directory within the public disk.
     * @param int $ticketId The ticket ID for namespacing.
     * @param array &$movedFiles A reference to an array tracking moved files for potential rollback.
     * @return string|null The new path or null if the input was empty.
     */
    private function moveAttachment(?string $tempPath, string $destinationFolder, int $ticketId, array &$movedFiles): ?string
    {
        if (empty($tempPath) || !Storage::disk('temp')->exists($tempPath)) {
            return null;
        }

        $newPath = "{$destinationFolder}/{$ticketId}/" . basename($tempPath);
        Storage::disk('public')->move($tempPath, $newPath);
        $movedFiles[] = $newPath; // Track for rollback

        return $newPath;
    }
}
