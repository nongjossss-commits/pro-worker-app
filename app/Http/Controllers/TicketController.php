<?php

// app/Http/Controllers/TicketController.php
namespace App\Http\Controllers;

use App\Models\JobTicket;
// Import the new Form Request
use App\Http\Requests\StoreTicketRequest;
use Illuminate\Support\Facades\Auth;
// Import DB facade for Transactions
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
// Ensure Illuminate\Http\Request is NOT imported if it conflicts or is unused.

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
     * CRITICAL: Use StoreTicketRequest for validation.
     */
    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();

        try {
            // Use Transaction to ensure data integrity (Ticket + Messages must succeed together)
            DB::beginTransaction();

            // 1. Create the Job Ticket
            $ticket = JobTicket::create([
                'employer_user_id' => $user->id,
                'subject' => $validated['subject'],
                'status' => 'pending_staff', // Explicitly set status
            ]);

            // 2. Create the Initial Message (Conditional: only if provided)
            if (!empty($validated['message'])) {
                $ticket->messages()->create([
                    'user_id' => $user->id,
                    'message_type' => 'comment',
                    'body' => $validated['message'],
                ]);
            }

            // 3. Handle Attachments (Future Logic Placeholder)
            // In future steps (S5+), we will process the $validated['attachments'] array here.
            // For 'new_employees', we will need to json_decode each element in the array.

            DB::commit();

            // Redirect to the newly created ticket's detail page (Better UX).
            return redirect()->route('tickets.show', $ticket)->with('success', 'สร้างคำขอเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();

            // Log the error for debugging
            \Log::error('Ticket creation failed: ' . $e->getMessage(), ['user_id' => $user->id]);

            return back()->withInput()->with('error', 'เกิดข้อผิดพลาดในการสร้างคำขอ กรุณาลองใหม่อีกครั้ง');
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
