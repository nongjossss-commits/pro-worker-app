<?php
// app/Http/Controllers/TicketReplyController.php
namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketReplyRequest;
use App\Models\JobTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class TicketReplyController extends Controller
{
    /**
     * Store a newly created reply in storage.
     */
    public function store(StoreTicketReplyRequest $request, JobTicket $ticket): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        // --- V2.4-S10: Authorization and Status Check ---
        // 1. Check if the ticket is closed
        if (in_array($ticket->status, ['resolved', 'rejected'])) {
            return back()->with('error', 'ไม่สามารถตอบกลับได้ เนื่องจากตั๋วงานนี้ถูกปิดแล้ว');
        }

        // 2. Check Authorization (Who can reply?)
        $isStaff = $user->can('manage-tickets');
        $isOwner = $ticket->employer_user_id === $user->id;

        if (!$isStaff && !$isOwner) {
            abort(403, 'Unauthorized action.');
        }

        // --- V2.4-S10: Processing Logic (Transaction & File Management) ---
        $attachments = $validated['attachments'] ?? [];
        $movedFiles = [];
        $storageDisk = 'public';
        $permanentBasePath = "ticket_attachments/{$ticket->id}";

        try {
            DB::beginTransaction();

            // 3. Process Message (comment)
            if (!empty($validated['message'])) {
                $ticket->messages()->create([
                    'user_id' => $user->id,
                    'message_type' => 'comment',
                    'body' => $validated['message'],
                ]);
            }

            // 4. Process File Attachments (attachment_file)
            // (Logic reused from V2.4-S8 TicketController@store)
            if (!empty($attachments['files'])) {
                foreach ($attachments['files'] as $fileData) {
                    $tempPath = $fileData['path'];
                    // Use the existing 'files' subdirectory
                    $permanentPath = $permanentBasePath . '/files/' . basename($tempPath);

                    if (Storage::disk($storageDisk)->move($tempPath, $permanentPath)) {
                        $movedFiles[] = $permanentPath;
                        $ticket->messages()->create([
                            'user_id' => $user->id,
                            'message_type' => 'attachment_file',
                            'body' => json_encode([
                                'path' => $permanentPath,
                                'name' => $fileData['name'],
                                'size' => $fileData['size'],
                            ]),
                        ]);
                    } else {
                        throw new Exception("Failed to move reply file from {$tempPath} to {$permanentPath}");
                    }
                }
            }

            // 5. Workflow Automation: Update Ticket Status
            // If Staff replies, wait for Employer. If Employer replies, wait for Staff.
            $newStatus = $isStaff ? 'pending_employer' : 'pending_staff';

            // Update the ticket status (and updated_at timestamp)
            if ($ticket->status !== $newStatus) {
                $ticket->update(['status' => $newStatus]);
            } else {
                // If status didn't change, we should still update the timestamp to reflect recent activity.
                $ticket->touch();
            }

            DB::commit();

            // Redirect back to the ticket view (handles both Admin and standard routes)
            // Using back() is simplest here as the user is already on the correct view.
            return back()->with('success', 'ส่งข้อความตอบกลับเรียบร้อยแล้ว');

        } catch (Exception $e) {
            DB::rollBack();

            // Cleanup moved files on failure
            if (count($movedFiles) > 0) {
                Log::warning('Ticket reply transaction failed. Cleaning up moved files.', ['files' => $movedFiles]);
                Storage::disk($storageDisk)->delete($movedFiles);
            }

            Log::error('Ticket reply failed (S10): ' . $e->getMessage(), ['user_id' => $user->id, 'ticket_id' => $ticket->id]);

            // Redirect back with input data restored (Alpine init handles file restoration)
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาดในการส่งข้อความตอบกลับ กรุณาลองใหม่อีกครั้ง.');
        }
    }
}
