<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // ... (The index method remains unchanged)
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // ... (The create method remains unchanged)
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // ... (The store method remains unchanged)
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
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'employeeNameTh' => 'required|string|max:255',
            'employeeNameEn' => 'nullable|string|max:255',
            'employeePassport' => ['required', 'string', 'max:255', Rule::unique('employees')->ignore($employee->id)],
            // Add all other fields to be validated
            'employeeTitleTh' => 'nullable|string',
            'employeeTitleEn' => 'nullable|string',
            'employeeDob' => 'nullable|date',
            'employeeNationality' => 'nullable|string',
            'passportType' => 'nullable|string',
            'passportExpiryDate' => 'nullable|date',
            'namelistNo' => 'nullable|string',
            'requestNo' => 'nullable|string',
            'workerRefNo' => 'nullable|string',
            'personalId' => 'nullable|string',
            'companyWorkerId' => 'nullable|string',
            'pinkCardNo' => 'nullable|string',
            'socialSecurityNo' => 'nullable|string',
            'taxIdNo' => 'nullable|string',
            'designatedHospital' => 'nullable|string',
            'employeeWorkPermit' => 'nullable|string',
            'workPermitExpiryDate' => 'nullable|date',
            'workPermitMOUGroup' => 'nullable|string',
            'workPermitMOUGroupOther' => 'nullable|string',
            'visaExpiryDate' => 'nullable|date',
            'ninetyDayReportDate' => 'nullable|date',
            'startDate' => 'nullable|date',
            'employeePhone' => 'nullable|string',
            'employeePosition' => 'nullable|string',
            'employeePhoto' => 'nullable|image|max:2048',
            // --- FIX: Added validation for document files ---
            'document_1' => 'nullable|file|max:5120',
            'document_2' => 'nullable|file|max:5120',
            'document_3' => 'nullable|file|max:5120',
            'document_4' => 'nullable|file|max:5120',
            'document_5' => 'nullable|file|max:5120',
            'document_6' => 'nullable|file|max:5120',
        ]);

        // --- FIX: Update the employee model with all validated data ---
        $employee->update($validated);

        // --- FIX: Handle file uploads for all documents ---
        $fileFields = ['employeePhoto', 'document_1', 'document_2', 'document_3', 'document_4', 'document_5', 'document_6'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                // Delete old file if it exists
                if ($employee->{$field}) {
                    Storage::disk('public')->delete($employee->{$field});
                }
                // Store new file
                $path = $request->file($field)->store($field . '_files', 'public');
                $employee->{$field} = $path;
            }
        }

        $employee->save();

        $employer = $employee->employer;

        return redirect()->route('employers.edit', $employer)
            ->with('success', 'อัปเดตข้อมูลพนักงานเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employer $employer, Employee $employee)
    {
        // ... (The destroy method remains unchanged)
    }

    public function locate(Employee $employee)
    {
        // ... (The locate method remains unchanged)
    }
}
