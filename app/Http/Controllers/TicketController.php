<?php

// app/Http/Controllers/TicketController.php
namespace App\Http\Controllers;

use App\Models\JobTicket;
use Illuminate\Support\Facades\Auth;
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

        return view('tickets.create');
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request): RedirectResponse
    {
        // Placeholder: Complex storage logic (Hybrid Form) will be implemented later.
        return redirect()->route('tickets.index')->with('success', 'Ticket creation logic pending.');
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
