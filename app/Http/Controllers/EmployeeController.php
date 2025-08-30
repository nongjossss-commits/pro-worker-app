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
        $validated = $request->validate([
            'employeeNameTh' => 'required|string|max:255',
            'employeeNameEn' => 'nullable|string|max:255',
            'employeeTitleTh' => 'nullable|string|max:255',
            'employeeTitleEn' => 'nullable|string|max:255',
            'employeeDob' => 'nullable|date',
            'employeeNationality' => 'nullable|string|max:255',
            'employeePassport' => 'required|string|max:255|unique:employees,employeePassport',
            'passportType' => 'nullable|string|max:255',
            'passportExpiryDate' => 'nullable|date',
            'namelistNo' => 'nullable|string|max:255',
            'requestNo' => 'nullable|string|max:255',
            'workerRefNo' => 'nullable|string|max:255',
            'personalId' => 'nullable|string|max:255',
            'companyWorkerId' => 'nullable|string|max:255',
            'pinkCardNo' => 'nullable|string|max:255',
            'socialSecurityNo' => 'nullable|string|max:255',
            'taxIdNo' => 'nullable|string|max:255',
            'designatedHospital' => 'nullable|string|max:255',
            'employeeWorkPermit' => 'nullable|string|max:255',
            'workPermitExpiryDate' => 'nullable|date',
            'workPermitMOUGroup' => 'nullable|string|max:255',
            'workPermitMOUGroupOther' => 'nullable|string|max:255',
            'visaExpiryDate' => 'nullable|date',
            'ninetyDayReportDate' => 'nullable|date',
            'startDate' => 'nullable|date',
            'employeePhone' => 'nullable|string|max:255',
            'employeePosition' => 'nullable|string|max:255',
            'employeePassportFile' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'employeeWorkPermitFile' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'pinkCardFile' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'employeePhoto' => 'nullable|image|max:2048',
        ]);

        $data = $validated;
        $data['employer_id'] = $employer->id;

        if ($request->hasFile('employeePhoto')) {
            $path = $request->file('employeePhoto')->store('employee_photos', 'public');
            $data['employeePhoto'] = $path;
        }

        $fileFields = ['employeePassportFile', 'employeeWorkPermitFile', 'pinkCardFile'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store('employee_documents', 'public');
                $data[$field] = $path;
            }
        }

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
        $validated = $request->validate([
            'employeeNameTh' => 'required|string|max:255',
            'employeeNameEn' => 'nullable|string|max:255',
            'employeeTitleTh' => 'nullable|string|max:255',
            'employeeTitleEn' => 'nullable|string|max:255',
            'employeeDob' => 'nullable|date',
            'employeeNationality' => 'nullable|string|max:255',
            'employeePassport' => 'required|string|max:255|unique:employees,employeePassport,' . $employee->id,
            'passportType' => 'nullable|string|max:255',
            'passportExpiryDate' => 'nullable|date',
            'namelistNo' => 'nullable|string|max:255',
            'requestNo' => 'nullable|string|max:255',
            'workerRefNo' => 'nullable|string|max:255',
            'personalId' => 'nullable|string|max:255',
            'companyWorkerId' => 'nullable|string|max:255',
            'pinkCardNo' => 'nullable|string|max:255',
            'socialSecurityNo' => 'nullable|string|max:255',
            'taxIdNo' => 'nullable|string|max:255',
            'designatedHospital' => 'nullable|string|max:255',
            'employeeWorkPermit' => 'nullable|string|max:255',
            'workPermitExpiryDate' => 'nullable|date',
            'workPermitMOUGroup' => 'nullable|string|max:255',
            'workPermitMOUGroupOther' => 'nullable|string|max:255',
            'visaExpiryDate' => 'nullable|date',
            'ninetyDayReportDate' => 'nullable|date',
            'startDate' => 'nullable|date',
            'employeePhone' => 'nullable|string|max:255',
            'employeePosition' => 'nullable|string|max:255',
            'employeePassportFile' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'employeeWorkPermitFile' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'pinkCardFile' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'employeePhoto' => 'nullable|image|max:2048',
        ]);

        $data = $validated;

        if ($request->hasFile('employeePhoto')) {
            // Delete old photo if it exists
            if ($employee->employeePhoto) {
                Storage::disk('public')->delete($employee->employeePhoto);
            }
            // Store new photo
            $path = $request->file('employeePhoto')->store('employee_photos', 'public');
            $data['employeePhoto'] = $path;
        }

        $fileFields = ['employeePassportFile', 'employeeWorkPermitFile', 'pinkCardFile'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                // Delete old file if it exists
                if ($employee->{$field}) {
                    Storage::disk('public')->delete($employee->{$field});
                }
                // Store new file
                $path = $request->file($field)->store('employee_documents', 'public');
                $data[$field] = $path;
            }
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

        // Delete document files from storage
        $fileFields = ['employeePassportFile', 'employeeWorkPermitFile', 'pinkCardFile'];
        foreach ($fileFields as $field) {
            if ($employee->{$field}) {
                Storage::disk('public')->delete($employee->{$field});
            }
        }

        $employee->delete();

        return redirect()->route('employers.edit', $employer)
            ->with('success', 'ลบข้อมูลพนักงานเรียบร้อยแล้ว');
    }
}
