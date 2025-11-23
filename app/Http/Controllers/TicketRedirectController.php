<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employer;

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
            'target_employer_id' => 'nullable|exists:employers,id', // Added target employer
        ]);

        $employeeIds = $validated['employee_ids'];
        $targetEmployerId = $validated['target_employer_id'] ?? null;

        // Fetch employees to check affiliation
        $employees = Employee::whereIn('id', $employeeIds)->get();

        // Separate employees into "Existing" (Matches Target) and "External" (Mismatch)
        $existingEmployeeIds = [];
        $externalEmployeeIds = [];

        foreach ($employees as $employee) {
            if ($targetEmployerId && $employee->employer_id == $targetEmployerId) {
                $existingEmployeeIds[] = $employee->id;
            } else {
                $externalEmployeeIds[] = $employee->id;
            }
        }

        // Flash data to session
        if (!empty($existingEmployeeIds)) {
            session()->flash('preselected_employee_ids', $existingEmployeeIds);
        }
        if (!empty($externalEmployeeIds)) {
            session()->flash('preselected_external_employee_ids', $externalEmployeeIds);
        }

        // Determine redirect route based on user role
        if (Auth::user()->can('manage-tickets')) {
            // Admin or Staff
            // If target employer is set, we need the associated User ID for the admin form
            $employerUser = null;
            if ($targetEmployerId) {
                $employer = \App\Models\Employer::find($targetEmployerId);
                // Assuming Employer model has user_id which is the Employer User ID
                // Check memory: "The `JobTicket` table schema uses `employer_user_id` to link to the creating employer"
                // But the form expects `employer_user_id` which is the ID of the User model.
                // Employer table has `user_id` column.
                if ($employer) {
                    // Flash as old input so the form picks it up
                    session()->flashInput(['employer_user_id' => $employer->user_id]);
                }
            }

            return redirect()->route('admin.tickets.create');
        } elseif (Auth::user()->hasRole('employer')) {
            // Employer - they can only create for themselves, so target_employer_id validation matches their own ID is handled by policy ideally,
            // but here we just redirect. The TicketController will use Auth::user() context anyway.
            return redirect()->route('tickets.create');
        }

        // Fallback if role is unclear (shouldn't happen with middleware)
        abort(403, 'Unauthorized action.');
    }
}
