<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;

class EmployerEmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        Session::save(); // Session Lock Mitigation

        // Defense in depth against Auth failure (Crucial if middleware fails)
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $query = Employee::query();
        $requiredFields = [
            'id', 'employeeNameTh', 'employeeNameEn', 'employeePassport',
            'employee_photo', 'employer_id'
        ];

        // 1. Authorization, Scoping & Data Integrity Logic
        if ($user->can('manage-tickets')) {
            // Admin Logic
            if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(Employee::class))) {
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
            // Employer Logic
            $query->where('employer_id', $user->employer->id);
        } else {
            return response()->json(['error' => 'Unauthorized access or configuration issue.'], 403);
        }

        // 2. Data Retrieval and Formatting
        // V2.4-S21: Safely use orderBy() on the actual column name.
        $employees = $query->select($requiredFields)->orderBy('employeeNameTh')->get();

        // 3. Append Accessors
        $employees->each(function ($employee) {
            $employee->append('photo_url');
        });

        return response()->json($employees);
    }
}
