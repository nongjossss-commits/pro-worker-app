<?php

namespace App\Http\Controllers\ApiWeb;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
// V2.4-S13-P2: Import Session facade
use Illuminate\Support\Facades\Session;

class EmployerEmployeeApiController extends Controller
{
    /**
     * Display a listing of the resource (Employees).
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        // V2.4-S13-P2: CRITICAL FIX - Session Lock Mitigation
        // Since this is a read-only API request, we close the session for writing
        // immediately after authentication (which is handled by middleware).
        // This prevents session file locking issues, common with the 'file' driver.
        Session::save();

        $query = Employee::query();

        // Define the fields required
        $requiredFields = [
            'id',
            'employeeNameTh',
            'employeeNameEn',
            'employeePassport',
            'employee_photo',
            'employer_id'
        ];

        // 1. Authorization & Scoping Logic (V2.4-S13 Logic)
        if ($user->can('manage-tickets')) {
            // --- Admin/Staff Logic ---
            $requestedEmployerId = $request->query('employer_id');
            if ($requestedEmployerId) {
                $query->where('employer_id', $requestedEmployerId);
            } else {
                if ($request->header('X-Context') === 'smart-ticket-create') {
                    return response()->json([]);
                }
                // Otherwise (e.g. other admin views), return all (default admin behavior)
            }
        } elseif ($user->employer) {
            // --- Employer Logic ---
            $query->where('employer_id', $user->employer->id);
        } else {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // 2. Data Retrieval and Formatting
        $employees = $query->select($requiredFields)->latest()->get();

        // 3. Append Accessors (This is where serialization happens)
        $employees->each(function ($employee) {
            $employee->append('photo_url');
        });

        return response()->json($employees);
    }
}
