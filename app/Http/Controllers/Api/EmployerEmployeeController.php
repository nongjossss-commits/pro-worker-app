<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CountryHelper;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request; // Import Request
use Illuminate\Support\Facades\Auth;

class EmployerEmployeeController extends Controller
{
    /**
     * Get a list of employees.
     * - For 'employer' roles, it's scoped by tenancy.
     * - For 'manage-tickets' roles (Admin/Staff), it can be filtered by employer_id.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user->hasRole('employer') && !$user->can('manage-tickets')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Employee::whereNull('terminated_at')->orderBy('employeeNameTh');

        // V2.4-S13: API Scoping Logic for Admin/Staff
        if ($user->can('manage-tickets')) {
            $employerId = $request->input('employer_id');
            $employerUserId = $request->input('employer_user_id'); // <-- ADDED
            // V2.4-S14 FIX: If employer_user_id is provided (from admin create page), find its linked employer_id
            if ($employerUserId && !$employerId) { // <-- MODIFIED
                $employerUser = \App\Models\User::find($employerUserId);
                if ($employerUser && $employerUser->employer) {
                    $employerId = $employerUser->employer->id; // <-- Get the correct employer ID
                }
            } // <-- ADDED
            // Admin/Staff MUST provide an employer_id to get results.
            if ($employerId) {
                // We remove the global tenancy scope to search across all employers,
                // and then apply a specific filter for the requested employer.
                $query->withoutGlobalScopes()->where('employer_id', $employerId);
            } else {
                // If no employer_id is provided (or found), return an empty list.
                return response()->json([]);
            }
        }
        // V2.5-S7 FIX: Explicitly apply tenancy for employers to prevent global scope issues.
        } else if ($user->hasRole('employer')) {
            if ($user->employer) {
                $query->where('employer_id', $user->employer->id);
            } else {
                // If employer user is not linked to an employer record, return empty.
                return response()->json([]);
            }
        }

        $employees = $query->get(['id', 'employer_id', 'employeeNameTh', 'employeeNameEn', 'employeePassport', 'companyWorkerId', 'employeePhoto', 'employeeNationality']);

        // CRITICAL: Append the accessor so it's included in the JSON response.
        $employees->append('photo_url');

        // V2.5-S2: Add nationality and flag URL
        $employeesData = $employees->map(function ($employee) {
            $nationality = $employee->employeeNationality;
            $countryCode = CountryHelper::getCountryCode($nationality);
            $flagUrl = $countryCode ? asset('images/flags/' . strtolower($countryCode) . '.png') : null;

            return [
                'id' => $employee->id,
                'employeeNameTh' => $employee->employeeNameTh,
                'employeeNameEn' => $employee->employeeNameEn,
                'employeePassport' => $employee->employeePassport,
                'companyWorkerId' => $employee->companyWorkerId,
                'photo_url' => $employee->photo_url, // Accessor field
                'nationality' => $nationality,
                'flag_url' => $flagUrl,
            ];
        });

        return response()->json($employeesData);
    }
}
