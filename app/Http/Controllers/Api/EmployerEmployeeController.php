<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CountryHelper;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
// (V2.4-S11.3: คงไว้)
use Illuminate\Support\Facades\Auth;

class EmployerEmployeeController extends Controller
{
    /**
     * Get a list of employees for use in attachment modals.
     * Admin/Staff (manage-tickets) can retrieve all employees (bypassing tenancy scope).
     * Employer users are automatically limited by employerTenancy Global Scope.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasRole('employer') && !$user->can('manage-tickets')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // --- START PLAN B (V2.4-S13) IMPLEMENTATION ---
        if ($user->can('manage-tickets')) {
            // Staff/Admin can view all employees by explicitly bypassing the global scope [cite: 7]
            $query = Employee::withoutGlobalScopes(['employerTenancy'])->with('employer');
        } else {
            // Employer is fetching (Global Scope 'employerTenancy' will apply automatically) [cite: 8]
            $query = Employee::with('employer');
        }

        // Fetch necessary fields, ensuring employer_id is included for frontend filtering.
        $employees = $query
            ->whereNull('terminated_at')
            ->orderBy('employeeNameTh') // Fetch all fields needed for modal display and filtering [cite: 10]
            ->get(['id', 'employeeNameTh', 'employeeNameEn', 'employeePassport', 'companyWorkerId', 'employeePhoto', 'employeeNationality', 'employer_id']);

        // Ensure photo accessor runs [cite: 11]
        $employees->append('photo_url');
        // --- END PLAN B (V2.4-S13) IMPLEMENTATION ---

        $employeesData = $employees->map(function ($employee) {
            $nationality = $employee->employeeNationality ?? 'N/A';
            $countryCode = CountryHelper::getCountryCode($nationality);
            $flagUrl = $countryCode ? asset('images/flags/' . strtolower($countryCode) . '.png') : null;

            // Add employer information (required for Admin/Staff filter and display) [cite: 13]
            $employerName = $employee->employer->employerNameTh ?? 'N/A';
            $employerId = $employee->employer_id;

            return [
                'id' => $employee->id,
                'employeeNameTh' => $employee->employeeNameTh, // (แก้ไข: ใช้ employeeNameTh ตามโค้ดเดิม)
                'employeeNameEn' => $employee->employeeNameEn, // (แก้ไข: ใช้ employeeNameEn ตามโค้ดเดิม)
                'employeePassport' => $employee->employeePassport, // (แก้ไข: ใช้ employeePassport ตามโค้ดเดิม)
                'companyWorkerId' => $employee->companyWorkerId, // (แก้ไข: ใช้ companyWorkerId ตามโค้ดเดิม)
                'photo_url' => $employee->photo_url, // Accessor field [cite: 14]
                'nationality' => $nationality,
                'flag_url' => $flagUrl,
                'employer_id' => $employerId, // Critical for filtering [cite: 15]
                'employer_name' => $employerName, // Critical for display [cite: 15]
            ];
        });

        return response()->json($employeesData);
    }
}
