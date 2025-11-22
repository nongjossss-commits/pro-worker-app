<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CountryHelper;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployerEmployeeController extends Controller
{
    /**
     * Get a list of employees.
     * - For 'employer' roles, it's scoped by tenancy.
     * - For 'manage-tickets' roles (Admin/Staff), it can be filtered by employer_id.
     * - Supports fetching specific employees by IDs (bypassing employer filter for admins).
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user->hasRole('employer') && !$user->can('manage-tickets')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // 1. Handle Specific IDs Request (e.g. from "Send Data")
        $requestedIds = $request->input('ids');
        if ($requestedIds && is_array($requestedIds)) {
            // Start a fresh query to avoid any scope pollution
            $query = Employee::query();

            // Basic filter for active employees (unless specific requirement to include terminated)
            $query->whereNull('terminated_at');

            if ($user->can('manage-tickets')) {
                 // Admin/Staff: Bypass employer tenancy scope to find any employee by ID
                 // We also need to ensure we don't accidentally include soft-deleted records
                 // unless we explicitly want them (usually not for ticket creation).
                 $query->withoutGlobalScopes()
                       ->whereNull('deleted_at') // Re-apply soft delete check
                       ->whereIn('id', $requestedIds);
            } elseif ($user->hasRole('employer')) {
                 // Employer: Must own the employees
                  if ($user->employer) {
                     $query->where('employer_id', $user->employer->id)
                           ->whereIn('id', $requestedIds);
                  } else {
                      return response()->json([]);
                  }
            } else {
                return response()->json([]);
            }

            // Retrieve data with necessary fields
             $employees = $query->get(['id', 'employer_id', 'employeeNameTh', 'employeeNameEn', 'employeePassport', 'companyWorkerId', 'employeePhoto', 'employeeNationality']);

             return response()->json($this->formatEmployees($employees));
        }

        // 2. Handle Standard List Request (Filtered by Context)
        $query = Employee::whereNull('terminated_at')->orderBy('employeeNameTh');

        // V2.4-S13: API Scoping Logic for Admin/Staff
        if ($user->can('manage-tickets')) {
            $employerId = $request->input('employer_id');
            $employerUserId = $request->input('employer_user_id');

            // V2.4-S14 FIX: If employer_user_id is provided, find its linked employer_id
            if ($employerUserId && !$employerId) {
                $employerUser = \App\Models\User::find($employerUserId);
                if ($employerUser && $employerUser->employer) {
                    $employerId = $employerUser->employer->id;
                }
            }

            if ($employerId) {
                // For listing by employer, we need to bypass the default scope if the admin
                // is viewing an employer that isn't "theirs" (though admin usually sees all).
                // But specifically, withoutGlobalScopes ensures we see that employer's data.
                $query->withoutGlobalScopes()
                      ->whereNull('deleted_at') // Re-apply soft delete check
                      ->where('employer_id', $employerId);
            } else {
                // If no employer context is provided for admin, return empty list
                // (unless we want to list ALL employees, which is usually too many)
                return response()->json([]);
            }
        }
        // V2.5-S7 FIX: Explicitly apply tenancy for employers
        else if ($user->hasRole('employer')) {
            if ($user->employer) {
                $query->where('employer_id', $user->employer->id);
            } else {
                return response()->json([]);
            }
        }

        $employees = $query->get(['id', 'employer_id', 'employeeNameTh', 'employeeNameEn', 'employeePassport', 'companyWorkerId', 'employeePhoto', 'employeeNationality']);

        return response()->json($this->formatEmployees($employees));
    }

    /**
     * Format employee collection for JSON response.
     */
    private function formatEmployees($employees)
    {
        // CRITICAL: Append the accessor so it's included in the JSON response.
        $employees->append('photo_url');

        // V2.5-S2: Add nationality and flag URL
        return $employees->map(function ($employee) {
            $nationality = $employee->employeeNationality;
            $countryCode = CountryHelper::getCountryCode($nationality);
            $flagUrl = $countryCode ? asset('images/flags/' . strtolower($countryCode) . '.png') : null;

            return [
                'id' => $employee->id,
                'employer_name' => $employee->employer->employerNameTh ?? 'N/A', // Useful for admins to see owner
                'employeeNameTh' => $employee->employeeNameTh,
                'employeeNameEn' => $employee->employeeNameEn,
                'employeePassport' => $employee->employeePassport,
                'companyWorkerId' => $employee->companyWorkerId,
                'photo_url' => $employee->photo_url, // Accessor field
                'nationality' => $nationality,
                'flag_url' => $flagUrl,
            ];
        });
    }
}
