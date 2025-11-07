<?php

// app/Http/Controllers/Admin/TicketController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobTicket;
use Illuminate\View\View;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $isClosed = in_array($ticket->status, ['resolved', 'rejected']);
        // (S11.4) ดึงรายชื่อ Admin และ Staff ทั้งหมดสำหรับ Dropdown
        $staffAndAdmins = User::role(['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
        return view('admin.tickets.show', compact('ticket', 'isClosed', 'staffAndAdmins'));
    }

    /**
     * (S11.4) Update the assigned user for a specific ticket.
     */
    public function updateAssignment(Request $request, JobTicket $ticket)
    {
        if (!Auth::user()->can('manage-tickets')) {
            abort(403, 'Unauthorized action.');
        }

        // 1. ตรวจสอบข้อมูล
        $validated = $request->validate([
            'assigned_to_user_id' => 'required|exists:users,id',
        ]);

        $newUser = User::findOrFail($validated['assigned_to_user_id']);

        // 2. อัปเดตตั๋ว
        $ticket->update([
            'assigned_to_user_id' => $newUser->id,
        ]);

        // 3. บันทึก System Message
        $ticket->messages()->create([
            'user_id' => Auth::id(),
            'message_type' => 'system_activity',
            'body' => 'Assignment changed to ' . $newUser->name . ' by ' . Auth::user()->name,
        ]);

        return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Ticket assignment updated successfully.');
    }
}
