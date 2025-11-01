<?php

// app/Http/Controllers/TicketController.php
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\JobTicket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

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

        // V2.4-S4: Fetch the employer's active employees to be attached to the ticket.
        $user = Auth::user();
        $employees = $user->employer ? $user->employer->employees()->whereNull('terminated_at')->get() : collect();

        return view('tickets.create', compact('employees'));
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request): RedirectResponse
    {
        // Security Check: Ensure the user has the 'employer' role.
        if (!Auth::user()->hasRole('employer')) {
            abort(403, 'Unauthorized action.');
        }

        // V2.4-S4: Validate the hybrid form data
        $validatedData = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        // V2.4-S4: Create the JobTicket and its initial message within a database transaction
        try {
            DB::beginTransaction();

            $ticket = JobTicket::create([
                'employer_user_id' => Auth::id(),
                'subject' => $validatedData['subject'],
                'status' => 'Pending', // Default status
                'priority' => 'Normal', // Default priority
            ]);

            // Attach employees if any are selected
            if (!empty($validatedData['employee_ids'])) {
                $ticket->employees()->attach($validatedData['employee_ids']);
            }

            // Create the initial message
            TicketMessage::create([
                'job_ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'body' => $validatedData['message'],
                'message_type' => 'text', // Default message type
            ]);

            DB::commit();

            return redirect()->route('tickets.index')->with('success', 'Your ticket has been successfully submitted.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Log the exception message for debugging
            logger()->error('Ticket creation failed: ' . $e->getMessage());
            return back()->with('danger', 'An unexpected error occurred. Please try again.')->withInput();
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

        $ticket->load('messages.user');

        return view('tickets.show', compact('ticket'));
    }
}
