<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Employer;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Not used for now
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Employer $employer)
    {
        return view('employees.create', compact('employer'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Employer $employer)
    {
        $request->validate([
            'employeeNameTh' => 'required',
            'employeePassport' => 'required|unique:employees',
        ]);

        $data = $request->all();
        $data['employer_id'] = $employer->id;

        Employee::create($data);

        return redirect()->route('employers.edit', $employer)
            ->with('success', 'เพิ่มข้อมูลพนักงานเรียบร้อยแล้ว');
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        // Not used for now
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'employeeNameTh' => 'required',
            'employeePassport' => 'required|unique:employees,employeePassport,' . $employee->id,
        ]);

        $employee->update($request->all());

        return redirect()->route('employers.edit', $employee->employer_id)
            ->with('success', 'อัปเดตข้อมูลพนักงานเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        $employer_id = $employee->employer_id;
        $employee->delete();

        return redirect()->route('employers.edit', $employer_id)
            ->with('success', 'ลบข้อมูลพนักงานเรียบร้อยแล้ว');
    }
}
