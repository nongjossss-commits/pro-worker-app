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

        // Default: Show List of Employers (Employer Boxes/Table)
        // Refactored for Search, View Toggle, and Pagination (User Request)

        $perPage = request('per_page', 12); // Default 12 for grid
        $view = request('view', 'card');
        $search = request('search');

        // Query Users who have submitted tickets
        $query = User::whereHas('submittedTickets')
            ->with('employer'); // Eager load employer profile

        // Add Search Filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('employer', function($subQ) use ($search) {
                      $subQ->where('employerNameTh', 'like', "%{$search}%")
                           ->orWhere('employerNameEn', 'like', "%{$search}%");
                  });
            });
        }

        // Add Counts and Last Activity via Subqueries
        $query->withCount(['submittedTickets as total_tickets']);

        // Count unread tickets (where admin_unread_count > 0)
        $query->withCount(['submittedTickets as unread_tickets_count' => function ($q) {
            $q->where('admin_unread_count', '>', 0);
        }]);

        // Add Last Activity (Max updated_at of tickets)
        // We use addSelect with a subquery for sorting
        $query->addSelect(['last_activity' => JobTicket::selectRaw('MAX(updated_at)')
            ->whereColumn('employer_user_id', 'users.id')
        ]);

        // Order by Last Activity (Recent first)
        $query->orderByDesc('last_activity');

        $employersWithTickets = $query->paginate($perPage)->withQueryString();

        return view('admin.tickets.employers', compact('employersWithTickets', 'view', 'search', 'perPage'));
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
