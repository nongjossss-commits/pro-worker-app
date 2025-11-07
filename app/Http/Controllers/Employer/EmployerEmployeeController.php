<?php
namespace App\Http\Controllers\Employer;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;
class EmployerEmployeeController extends Controller {
    /**
     * Display a listing of the resource (Employees API Endpoint).
     * V2.4-S20: Implement combined fixes (Scoping, Integrity, Session Lock) in the CORRECT controller.
     */
    public function index(Request $request): JsonResponse {
        // Authentication is guaranteed by 'auth' middleware applied in web.php.
        $user = Auth::user();
        // CRITICAL FIX: Session Lock Mitigation
        // Close the session for writing immediately to prevent file locking issues (common with 'file' driver).
        Session::save();
        // Robustness Check (Defense in depth)
        if (!$user) {
            // If 'auth' middleware somehow fails, this prevents the "employer on null" error.
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }
        $query = Employee::query();
        // Define the fields required for the Smart Ticket modals
        $requiredFields = [
            'id',
            'employeeNameTh',
            'employeeNameEn',
            'employeePassport',
            'employee_photo',
            'employer_id'
        ];
        // 1. Authorization, Scoping & Data Integrity Logic
        if ($user->can('manage-tickets')) {
            // --- Admin/Staff Logic ---
            // Data Integrity: Robustly Override Soft Deletes for Admin view
            // Use class_uses_recursive to reliably detect the SoftDeletes trait.
            if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(Employee::class))) {
                $query->withTrashed();
            }
            // Contextual Scoping
            $requestedEmployerId = $request->query('employer_id');
            if ($requestedEmployerId) {
                // If a specific employer is requested, scope the query.
                $query->where('employer_id', $requestedEmployerId);
            } else {
                // If context is 'smart-ticket-create', return empty until an employer is selected.
                if ($request->header('X-Context') === 'smart-ticket-create') {
                    return response()->json([]);
                }
                // Otherwise (e.g. other admin views), return all (default admin behavior)
            }
        } elseif ($user->employer) {
            // --- Employer Logic ---
            // Employers can only see their own employees (and NOT soft-deleted ones).
            $query->where('employer_id', $user->employer->id);
        } else {
            // Unauthorized or configuration issue.
            return response()->json(['error' => 'Unauthorized access or configuration issue.'], 403);
        }
        // 2. Data Retrieval and Formatting
        // Order by Name for consistency
        $employees = $query->select($requiredFields)->orderBy('employeeNameTh')->get();
        // 3. Append Accessors (e.g., photo_url)
        $employees->each(function ($employee) {
            $employee->append('photo_url');
        });
        // Return JSON response
        return response()->json($employees);
    }
}
