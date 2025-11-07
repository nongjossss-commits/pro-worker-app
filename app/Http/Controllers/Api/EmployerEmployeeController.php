<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CountryHelper;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class EmployerEmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        // Ensure Session Lock Mitigation remains in place (from previous fix)
        Session::save();

        $query = Employee::query();

        $requiredFields = [
            'id', 'employeeNameTh', 'employeeNameEn', 'employeePassport', 'employee_photo', 'employer_id'
        ];

        // 1. Authorization & Scoping Logic
        if ($user->can('manage-tickets')) {
            // --- Admin/Staff Logic ---
            // V2.4-S15: CRITICAL - Override Soft Deletes for Admin view
            if (method_exists(Employee::class, 'trashed')) {
                $query->withTrashed();
            }

            $requestedEmployerId = $request->query('employer_id');

            if ($requestedEmployerId) {
                $query->where('employer_id', $requestedEmployerId);
            } else {
                if ($request->header('X-Context') === 'smart-ticket-create') {
                    return response()->json([]);
                }
            }
        } elseif ($user->employer) {
            // --- Employer Logic ---
            // (Employers should NOT see soft-deleted employees, so no withTrashed() here)
            $query->where('employer_id', $user->employer->id);
        } else {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // 2. Data Retrieval and Formatting
        $employees = $query->select($requiredFields)->latest()->get();

        // 3. Append Accessors
        $employees->each(function ($employee) {
            $employee->append('photo_url');
        });

        return response()->json($employees);
    }
}
