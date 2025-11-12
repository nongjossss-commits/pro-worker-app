<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Employer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Exception;

class PreviewController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $type = $request->input('type');
        $id = $request->input('id');

        try {
            switch ($type) {
                case 'employee':
                    $employee = Employee::with(['employer'])->withTrashed()->findOrFail($id);
                    return view('previews._employee_data', ['employee' => $employee]);

                case 'employer':
                    $employer = Employer::withTrashed()->findOrFail($id);
                    $stats = $this->getEmployeeStats($id);
                    return view('previews._employer_data', ['employer' => $employer, 'stats' => $stats]);

                default:
                    return response()->json(['error' => 'Invalid preview type specified.'], 400);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'The requested resource was not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    /**
     * Get employee statistics for a given employer.
     *
     * @param  int  $employer_id
     * @return object
     */
    private function getEmployeeStats($employer_id)
    {
        $employees = Employee::where('employer_id', $employer_id)->withTrashed()->get();

        $total = $employees->count();
        $male = $employees->where('employeeTitleTh', 'นาย')->count();
        $female = $employees->whereIn('employeeTitleTh', ['นางสาว', 'นาง'])->count();

        $breakdown = $employees->groupBy('employeeNationality')->map(function ($group) {
            return (object) [
                'male_count' => $group->where('employeeTitleTh', 'นาย')->count(),
                'female_count' => $group->whereIn('employeeTitleTh', ['นางสาว', 'นาง'])->count(),
                'total_count' => $group->count(),
            ];
        });

        return (object) [
            'total' => $total,
            'male' => $male,
            'female' => $female,
            'breakdown' => $breakdown,
        ];
    }
}
