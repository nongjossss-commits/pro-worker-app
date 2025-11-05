<?php

namespace App\Http\Controllers\Api\Web; // (หมายเหตุ: ลีลูแก้ไข Path ให้ตรงกับ web.php [cite: 74-81])

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
            return response()->json(['error' => 'Unauthorized'], 403); [cite: 104]
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
        $employees = $query->with('employer') [cite: 108]
            ->whereNull('terminated_at')
            ->orderBy('employeeNameTh') // Add employer_id for client-side filtering (Plan B requirement)
            ->get(['id', 'employeeNameTh', 'employeeNameEn', 'employeePassport', 'companyWorkerId', 'employeePhoto', 'employeeNationality', 'employer_id']); [cite: 109]
        // --- END PLAN B IMPLEMENTATION ---

        // CRITICAL: Append the accessor so it's included in the JSON response.
        $employees->append('photo_url');

        $employeesData = $employees->map(function ($employee) {
            $nationality = $employee->employeeNationality;
            $countryCode = CountryHelper::getCountryCode($nationality);
            $flagUrl = $countryCode ? asset('images/flags/' . strtolower($countryCode) . '.png') : null;

            // Add employer information
            $employerName = $employee->employer->employerNameTh ?? 'N/A'; [cite: 111]
            $employerId = $employee->employer_id;

            return [
                'id' => $employee->id,
                'employeeNameTh' => $employee->employeeNameTh, [cite: 112]
                'employeeNameEn' => $employee->employeeNameEn, [cite: 112]
                'employeePassport' => $employee->employeePassport, [cite: 112]
                'companyWorkerId' => $employee->companyWorkerId,
                'photo_url' => $employee->photo_url, [cite: 112]
                'nationality' => $nationality,
                'flag_url' => $flagUrl,
                'employer_id' => $employerId, [cite: 113]
                'employer_name' => $employerName, [cite: 113]
            ];
        });

        return response()->json($employeesData);
    }
}
