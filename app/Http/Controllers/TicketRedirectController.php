<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketRedirectController extends Controller
{
    /**
     * Handle the bulk action redirection to ticket creation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkToTicket(Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        // Store the IDs in the session
        // Using 'flash' session data so it's available for the next request only
        session()->flash('preselected_employee_ids', $validated['employee_ids']);

        // Determine redirect route based on user role
        if (Auth::user()->can('manage-tickets')) {
            // Admin or Staff
            return redirect()->route('admin.tickets.create');
        } elseif (Auth::user()->hasRole('employer')) {
            // Employer
            return redirect()->route('tickets.create');
        }

        // Fallback if role is unclear (shouldn't happen with middleware)
        abort(403, 'Unauthorized action.');
    }
}
