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
        // V2.5: Check if filtering by employer
        $employerId = request('employer_id');

        if ($employerId) {
            // Show tickets list for a specific employer
            $perPage = request('per_page', 25);
            $tickets = JobTicket::with(['employerUser.employer', 'assignedStaff'])
                ->where('employer_user_id', $employerId)
                ->latest()
                ->paginate($perPage);

            // Get Employer Name for the header
            $employerUser = User::with('employer')->find($employerId);
            $employerName = $employerUser?->employer?->employerNameTh ?? $employerUser?->name ?? 'Unknown Employer';

            return view('admin.tickets.index', compact('tickets', 'employerName'));
        }

        // Default: Show List of Employers (Employer Boxes)
        // Group by employer_user_id and calculate stats
        $employersWithTickets = JobTicket::selectRaw('
                employer_user_id,
                COUNT(*) as total_tickets,
                SUM(CASE WHEN admin_unread_count > 0 THEN 1 ELSE 0 END) as unread_tickets_count,
                MAX(updated_at) as last_activity
            ')
            ->groupBy('employer_user_id')
            ->orderByDesc('last_activity') // Employers with recent activity first
            ->with('employerUser.employer') // Eager load User and Employer profile
            ->get();

        return view('admin.tickets.employers', compact('employersWithTickets'));
    }

    /**
     * Display the specified ticket (Admin View).
     */
    public function show(JobTicket $ticket): View
    {
        // Reset Unread Count for Admin
        if ($ticket->admin_unread_count > 0) {
            $ticket->update(['admin_unread_count' => 0]);
        }

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
            'assigned_staff_id' => $newUser->id,
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
