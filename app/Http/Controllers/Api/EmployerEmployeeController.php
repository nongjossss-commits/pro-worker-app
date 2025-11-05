<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CountryHelper;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request; // V2.4-S11.3: Add Request
use Illuminate\Support\Facades\Auth;

class EmployerEmployeeController extends Controller
{
    /**
     * Get a list of employees for the authenticated employer.
     * Security relies on the employerTenancy Global Scope.
     */
    public function index(Request $request): JsonResponse // V2.4-S11.3: Inject Request
    {
        // ... (Authorization logic remains the same)
        $user = Auth::user();
        if (!$user->hasRole('employer') && !$user->can('manage-tickets')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // V2.4-S11.3: Admin/Staff Security Scope
        if ($user->can('manage-tickets') && $request->has('ticket_employer_id')) {
            // Admin is fetching for a specific ticket's employer
            $query = Employee::query()
                ->where('employer_user_id', $request->input('ticket_employer_id'));
        } else {
            // Employer is fetching (Global Scope 'employerTenancy' will apply automatically)
            $query = Employee::query();
        }


        // V2.4-S6 Update: Fetch richer data, V2.5-S2 adds nationality
        $employees = $query->whereNull('terminated_at')
            ->orderBy('employeeNameTh')
            ->get(['id', 'employeeNameTh', 'employeeNameEn', 'employeePassport', 'companyWorkerId', 'employeePhoto', 'employeeNationality']);

        // CRITICAL: Append the accessor so it's included in the JSON response.
        $employees->append('photo_url');

        // V2.5-S2: Add nationality and flag URL
        $employeesData = $employees->map(function ($employee) {
            $nationality = $employee->employeeNationality;
            $countryCode = CountryHelper::getCountryCode($nationality);
            $flagUrl = $countryCode ? asset('images/flags/' . strtolower($countryCode) . '.png') : null;

            return [
                'id' => $employee->id,
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
