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
use Illuminate\Validation\Rule;

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
     * V2.4-S4-Patch3: Hard Reset of store() method.
     */
    public function store(Request $request): RedirectResponse
    {
        // Security Check: Enforce 'employer' role.
        if (!Auth::user()->hasRole('employer')) {
            abort(403, 'Unauthorized action.');
        }

        // V2.4 Blueprint Validation: Aligned with create.blade.php name attributes.
        $validatedData = $request->validate([
            'ticket_subject' => 'required|string|max:255',
            'message_body' => 'required|string',
            'attached_employees' => 'nullable|array',
            'attached_employees.*' => [
                'required',
                // Tenancy Check: Ensures the submitted employee IDs belong to the user's own company.
                Rule::exists('employees', 'id')->where(function ($query) {
                    $query->where('employer_id', Auth::user()->employer->id);
                }),
            ],
        ]);

        try {
            DB::beginTransaction();

            // 1. Create the JobTicket
            $ticket = JobTicket::create([
                'employer_user_id' => Auth::id(),
                'subject' => $validatedData['ticket_subject'],
                'status' => 'pending_staff', // Bug A Fix: Status is now V2.4 compliant.
                'priority' => 'Normal',
            ]);

            // 2. Create the initial TicketMessage (the employer's request)
            $message = TicketMessage::create([
                'job_ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'body' => $validatedData['message_body'],
                'message_type' => 'text',
            ]);

            // 3. (Optional) Attach Employees via the TicketMessage (V2.4 Blueprint)
            // Bug B Fix: Logic moved from a direct pivot table to the message itself.
            if (!empty($validatedData['attached_employees'])) {
                $message->employees()->attach($validatedData['attached_employees']);
            }

            DB::commit();

            return redirect()->route('tickets.index')->with('success', 'Your ticket has been successfully submitted.');

        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Ticket Creation Failed: '. $e->getMessage());
            // Bug C Fix: Return the specific error message to the user via the session.
            return back()->with('danger', 'An unexpected error occurred while creating the ticket. Please try again.')->withInput();
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
