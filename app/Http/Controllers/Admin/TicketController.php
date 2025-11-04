<?php

// app/Http/Controllers/Admin/TicketController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobTicket;
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
        // V2.4-S3: Handle Per Page selection using request() helper, default to 25
        $perPage = request('per_page', 25);

        // V2.4-S3: Optimization - Eager Load nested relationships
        $tickets = JobTicket::with([
            'employerUser.employer', // Load User AND their linked Employer record (for Company Name)
            'assignedStaff'
        ])
            ->latest()
            ->paginate($perPage);

        return view('admin.tickets.index', compact('tickets'));
    }

    /**
     * Display the specified ticket (Admin View).
     */
    public function show(JobTicket $ticket): View
    {
        // V2.4-S9 Optimization: Load messages, user info, and employer details
        $ticket->load([
            'messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            },
            'messages.user',
            'employerUser.employer', // Load employer details for the sidebar
            'assignedStaff'
        ]);

        // The categorized_attachments accessor will handle the rest.
        return view('admin.tickets.show', compact('ticket'));
    }
}
