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
        // ... (Authorization logic remains the same)
        $user = Auth::user();
        if (!$user->hasRole('employer') && !$user->can('manage-tickets')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // V2.4-S6 Update: Fetch richer data
        $employees = Employee::whereNull('terminated_at')
            ->orderBy('employeeNameTh')
            // Select necessary fields including NameEn and Photo (for the accessor to work)
            ->get(['id', 'employeeNameTh', 'employeeNameEn', 'employeePassport', 'companyWorkerId', 'employeePhoto']);

        // CRITICAL: Append the accessor so it's included in the JSON response.
        $employees->append('photo_url');

        return response()->json($employees);
    }
}
