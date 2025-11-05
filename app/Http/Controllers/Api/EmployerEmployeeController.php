<?php

namespace App\Http\Controllers\Api; // Corrected namespace to match routes/web.php

use App\Helpers\CountryHelper;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployerEmployeeController extends Controller
{
    /**
     * Get a list of employees for the authenticated user.
     * Admin/Staff can retrieve all employees (bypassing tenancy scope).
     * Employer users are automatically limited by employerTenancy Global Scope.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user->hasRole('employer') && !$user->can('manage-tickets')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // --- START PLAN B: Remove broken ticket_employer_id logic and fetch based on role ---
        if ($user->can('manage-tickets')) {
            // Staff/Admin can view all employees by bypassing the global scope
            $query = Employee::withoutGlobalScopes(['employerTenancy']); // (แก้ไข: ระบุ Scope ที่จะข้าม)
        } else {
            // Employer is fetching (Global Scope 'employerTenancy' will apply automatically)
            $query = Employee::query();
        }

        // Apply termination filter and eager load employer for display info.
        // We ensure employer is eager loaded to fetch employerNameTh below.
        $employees = $query->with('employer')
            ->whereNull('terminated_at')
            ->orderBy('employeeNameTh')
            // Add employer_id for client-side filtering (Plan B requirement)
            ->get(['id', 'employeeNameTh', 'employeeNameEn', 'employeePassport', 'companyWorkerId', 'employeePhoto', 'employeeNationality', 'employer_id']);
        // --- END PLAN B IMPLEMENTATION ---

        // CRITICAL: Append the accessor so it's included in the JSON response.
        $employees->append('photo_url');

        $employeesData = $employees->map(function ($employee) {
            $nationality = $employee->employeeNationality;
            $countryCode = CountryHelper::getCountryCode($nationality);
            $flagUrl = $countryCode ? asset('images/flags/' . strtolower($countryCode) . '.png') : null;

            // Add employer information
            $employerName = $employee->employer->employerNameTh ?? 'N/A';
            $employerId = $employee->employer_id;

            return [
                'id' => $employee->id,
                'employeeNameTh' => $employee->employeeNameTh,
                'employeeNameEn' => $employee->employeeNameEn,
                'employeePassport' => $employee->employeePassport,
                'companyWorkerId' => $employee->companyWorkerId,
                'photo_url' => $employee->photo_url,
                'nationality' => $nationality,
                'flag_url' => $flagUrl,
                'employer_id' => $employerId,
                'employer_name' => $employerName,
            ];
        });

        return response()->json($employeesData);
    }
}
