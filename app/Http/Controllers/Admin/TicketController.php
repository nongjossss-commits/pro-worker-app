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

        // V2.5-S15: Determine visibility
        // Admin and Staff can see ALL tickets.
        // Other roles (like 'caretaker') are restricted to assigned employers.
        $currentUser = Auth::user();
        $canViewAllTickets = $currentUser->hasRole(['admin', 'staff']);

        if ($employerId) {
            // Show tickets list for a specific employer
            $perPage = request('per_page', 25);
            $ticketsQuery = JobTicket::with(['employerUser.employer', 'assignedStaff'])
                ->where('employer_user_id', $employerId)
                ->latest();

            // Apply Caretaker Scope if not Admin/Staff
            if (!$canViewAllTickets) {
                // Check if this employer is assigned to the current restricted user
                // We do this via the employerUser relationship
                 $ticketsQuery->whereHas('employerUser.employer', function ($q) use ($currentUser) {
                    $q->where('assigned_staff_id', $currentUser->id);
                });
            }

            $tickets = $ticketsQuery->paginate($perPage);

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

        // V2.5.1: Handle Hidden Filter
        $showHidden = request('hidden');

        if ($showHidden) {
             $query->where('is_ticket_hidden', true);
        } elseif (!$search) {
             $query->where('is_ticket_hidden', false);
        }

        // V2.5-S15: Apply Caretaker Visibility Scope for non-admins/staff
        if (!$canViewAllTickets) {
             // Restrict to users whose Employer profile is assigned to the current restricted user
            $query->whereHas('employer', function ($q) use ($currentUser) {
                $q->where('assigned_staff_id', $currentUser->id);
            });
        }

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
        // Authorization Check for Caretakers
        // Admin/Staff can view all. Caretakers can only view if assigned to the employer.
        $currentUser = Auth::user();
        $canViewAllTickets = $currentUser->hasRole(['admin', 'staff']);

        if (!$canViewAllTickets) {
            // Check assignment
            // Note: $ticket->employerUser is the User model of the employer.
            // We need to check $ticket->employerUser->employer->assigned_staff_id
            $employer = $ticket->employerUser->employer;
            if (!$employer || $employer->assigned_staff_id !== $currentUser->id) {
                abort(403, 'Unauthorized access to this ticket.');
            }
        }

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

    /**
     * (V2.5.1) Hide an employer's ticket box from the list.
     * It will reappear when there is new activity.
     */
    public function hideEmployer(User $user)
    {
        if (!Auth::user()->can('manage-tickets')) {
            abort(403, 'Unauthorized action.');
        }

        $user->update(['is_ticket_hidden' => true]);

        return back()->with('success', 'Employer ticket box hidden.');
    }

    /**
     * (V2.5.2) Unhide an employer's ticket box (restore to main list).
     */
    public function unhideEmployer(User $user)
    {
        if (!Auth::user()->can('manage-tickets')) {
            abort(403, 'Unauthorized action.');
        }

        $user->update(['is_ticket_hidden' => false]);

        return back()->with('success', 'Employer ticket box restored to view.');
    }
}
