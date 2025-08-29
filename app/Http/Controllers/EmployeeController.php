<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
    public function edit(Employer $employer, Employee $employee)
    {
        return view('employees.edit', compact('employer', 'employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employer $employer, Employee $employee)
    {
        $request->validate([
            'employeeNameTh' => 'required',
            'employeePassport' => 'required|unique:employees,employeePassport,' . $employee->id,
            'employeePhoto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('employeePhoto')) {
            // Delete old photo if it exists
            if ($employee->employeePhoto) {
                Storage::disk('public')->delete($employee->employeePhoto);
            }
            // Store new photo
            $path = $request->file('employeePhoto')->store('employee_photos', 'public');
            $data['employeePhoto'] = $path;
        }

        $employee->update($data);

        return redirect()->route('employers.edit', $employer)
            ->with('success', 'อัปเดตข้อมูลพนักงานเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employer $employer, Employee $employee)
    {
        // Delete photo from storage if it exists
        if ($employee->employeePhoto) {
            Storage::disk('public')->delete($employee->employeePhoto);
        }

        $employee->delete();

        return redirect()->route('employers.edit', $employer)
            ->with('success', 'ลบข้อมูลพนักงานเรียบร้อยแล้ว');
    }
}
