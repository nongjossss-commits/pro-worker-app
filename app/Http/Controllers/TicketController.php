<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Models\JobTicket;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class TicketController extends Controller
{
    /**
     * Display a listing of the user's own tickets (My Tickets).
     */
    public function index(): View | RedirectResponse
    {
        if (Auth::user()->can('manage-tickets')) {
            return redirect()->route('admin.tickets.index');
        }

        $perPage = request('per_page', 25);
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
        if (!Auth::user()->hasRole('employer')) {
            abort(403, 'Unauthorized action. Only employers can submit requests.');
        }

        return view('tickets.create');
    }

    /**
     * Store a newly created ticket along with its attachments.
     */
    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();

        try {
            DB::beginTransaction();

            // 1. Create the parent JobTicket
            $ticket = JobTicket::create([
                'employer_user_id' => $user->id,
                'subject' => $validated['subject'],
                'status' => 'pending_staff',
            ]);

            // 2. Create the initial text message, if provided
            if (!empty($validated['message'])) {
                $ticket->messages()->create([
                    'user_id' => $user->id,
                    'message_type' => 'comment',
                    'body' => $validated['message'],
                ]);
            }

            $attachments = $validated['attachments'];

            // 3. Process Existing Employees
            if (!empty($attachments['existing_employees'])) {
                foreach ($attachments['existing_employees'] as $employeeId) {
                    $ticket->messages()->create([
                        'user_id' => $user->id,
                        'message_type' => 'attach_employee_existing',
                        'related_model_type' => Employee::class,
                        'related_model_id' => $employeeId,
                    ]);
                }
            }

            // 4. Process New Employees
            if (!empty($attachments['new_employees'])) {
                foreach ($attachments['new_employees'] as $employeeData) {
                    $ticket->messages()->create([
                        'user_id' => $user->id,
                        'message_type' => 'attach_employee_new',
                        'body' => json_encode($employeeData), // Store the validated data as a JSON string
                    ]);
                }
            }

            // 5. Process File Uploads
            if (!empty($attachments['files'])) {
                foreach ($attachments['files'] as $fileData) {
                    $tempPath = $fileData['path'];
                    $permanentPath = 'ticket_attachments/' . $ticket->id . '/' . basename($tempPath);

                    // Move file from temp to permanent storage
                    Storage::disk('public')->move($tempPath, $permanentPath);

                    $ticket->messages()->create([
                        'user_id' => $user->id,
                        'message_type' => 'attach_file',
                        'body' => json_encode([
                            'file_path' => $permanentPath,
                            'file_name' => $fileData['name'],
                            'file_size' => $fileData['size'],
                        ]),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('tickets.show', $ticket)->with('success', 'สร้างคำขอพร้อมแนบไฟล์เรียบร้อยแล้ว');

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Ticket creation failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with('danger', 'เกิดข้อผิดพลาดร้ายแรงในการสร้างคำขอ');
        }
    }


    /**
     * Display the specified ticket (User View).
     */
    public function show(JobTicket $ticket): View | RedirectResponse
    {
        if (Auth::user()->can('manage-tickets')) {
            return redirect()->route('admin.tickets.show', $ticket);
        }

        if ($ticket->employer_user_id !== Auth::id()) {
            abort(403, 'Unauthorized action. You do not own this ticket.');
        }

        $ticket->load('messages.user');

        return view('tickets.show', compact('ticket'));
    }
}
