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

        $query = Employee::whereNull('terminated_at')->orderBy('employeeNameTh');

        // Check for specific IDs (V2.5-FIX for Bulk Send Data)
        $requestedIds = $request->input('ids');
        if ($requestedIds && is_array($requestedIds)) {
             if ($user->can('manage-tickets')) {
                 // Admins can fetch any specific employees by ID, regardless of employer
                 $query->withoutGlobalScopes()->whereIn('id', $requestedIds);
             } elseif ($user->hasRole('employer')) {
                 // Employers can only fetch their own
                  if ($user->employer) {
                     $query->whereIn('id', $requestedIds)->where('employer_id', $user->employer->id);
                  } else {
                      return response()->json([]);
                  }
             }
             // When specific IDs are requested, we return them directly
        }
        else {
            // --- Standard Listing Logic ---
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
                    $query->withoutGlobalScopes()->where('employer_id', $employerId);
                } else {
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

        return response()->json($employeesData);
    }
}
