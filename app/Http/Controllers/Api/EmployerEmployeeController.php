<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EmployerEmployeeController extends Controller
{
    /**
     * Get a list of employees for the authenticated employer.
     * Security relies on the employerTenancy Global Scope.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        // Defense-in-depth: Ensure the user is an employer (or staff/admin).
        if (!$user->hasRole('employer') && !$user->can('manage-tickets')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Fetch active employees. The Global Scope automatically filters this list
        // based on the logged-in user's role and linked employer ID.
        $employees = Employee::whereNull('terminated_at')
            ->orderBy('employeeNameTh')
            // Select only necessary fields for the picker modal
            ->get(['id', 'employeeNameTh', 'employeePassport', 'companyWorkerId']);

        return response()->json($employees);
    }
}
