<?php

// app/Http/Controllers/Admin/TicketController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobTicket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct()
    {
        // Defense-in-depth: Ensure 'manage-tickets' permission is required for all methods.
        $this->middleware('permission:manage-tickets');
    }

    /**
     * Display a listing of all job tickets (Admin Inbox).
     */
    public function index(): View
    {
        // Placeholder: Fetch all tickets.
        $tickets = JobTicket::with(['employerUser', 'assignedStaff'])
            ->latest()
            ->paginate(20);

        return view('admin.tickets.index', compact('tickets'));
    }

    /**
     * Display the specified ticket (Admin View).
     */
    public function show(JobTicket $ticket): View
    {
        // Admin/Staff can view any ticket details.
        $ticket->load('messages.user');

        return view('admin.tickets.show', compact('ticket'));
    }
}
