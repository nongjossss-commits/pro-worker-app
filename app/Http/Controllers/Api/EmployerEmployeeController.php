<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CountryHelper;
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

        // V2.4-S6 Update: Fetch richer data, V2.5-S2 adds nationality
        $employees = Employee::whereNull('terminated_at')
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
