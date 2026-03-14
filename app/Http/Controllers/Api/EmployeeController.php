<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
     */
    public function index(Request $request)
    {
        // Simple search query based on employee_reference_id or name
        $search = $request->query('search');

        $employees = Employee::query()
            ->with(['employer:id,company_name_en,company_name_th'])
            ->when($search, function ($query, $search) {
                return $query->where('employee_reference_id', 'like', "%{$search}%")
                    ->orWhere('employeeFirstName', 'like', "%{$search}%")
                    ->orWhere('employeeLastName', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
            ]
        ], 200);
    }

    /**
     * Display the specified employee.
     */
    public function show($id)
    {
        $employee = Employee::with([
            'employer:id,company_name_en,company_name_th',
            'nationality',
        ])->find($id);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $employee
        ], 200);
    }
}
