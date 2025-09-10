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
    public function index(Request $request)
    {
        // Define options for items per page
        $cardPerPageOptions = [10, 15, 20];
        $tablePerPageOptions = [25, 50, 100];

        // Determine the current view, default to 'card'
        $currentView = $request->input('view', 'card');

        // Determine per_page based on the current view
        $defaultPerPage = ($currentView === 'card') ? $cardPerPageOptions[0] : $tablePerPageOptions[0];
        $currentPerPage = $request->input('per_page', $defaultPerPage);

        // Determine which set of options to pass to the view
        $perPageOptions = ($currentView === 'card') ? $cardPerPageOptions : $tablePerPageOptions;

        // Build the query
        $query = Employee::with(['employer', 'documents']); // Eager load relationships

        // Handle search if present
        if ($request->has('search') && $request->input('search') != '') {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('employeeNameTh', 'like', "%{$search}%")
                  ->orWhere('employeeNameEn', 'like', "%{$search}%")
                  ->orWhere('employeePassport', 'like', "%{$search}%")
                  ->orWhereHas('employer', function($q_employer) use ($search) {
                      $q_employer->where('employerNameTh', 'like', "%{$search}%");
                  });
            });
        }

        $employees = $query->latest()->paginate($currentPerPage)->withQueryString();

        return view('employees.index', compact('employees', 'currentView', 'currentPerPage', 'perPageOptions'));
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
            'employeePhoto' => 'nullable|image|max:2048',
            'document_1' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_2' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_3' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_4' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_description_4' => 'nullable|string|max:255',
            'document_5' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_description_5' => 'nullable|string|max:255',
            'document_6' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_description_6' => 'nullable|string|max:255',
            'nature_of_work' => 'nullable|string',
        ]);

        $data = $validated;
        $data['employer_id'] = $employer->id;

        if ($request->hasFile('employeePhoto')) {
            $path = $request->file('employeePhoto')->store('employee_photos', 'public');
            $data['employeePhoto'] = $path;
        }

        for ($i = 1; $i <= 6; $i++) {
            $field = 'document_' . $i;
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
            'employeePhoto' => 'nullable|image|max:2048',
            'document_1' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_2' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_3' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_4' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_description_4' => 'nullable|string|max:255',
            'document_5' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_description_5' => 'nullable|string|max:255',
            'document_6' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_description_6' => 'nullable|string|max:255',
            'nature_of_work' => 'nullable|string',
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

        for ($i = 1; $i <= 6; $i++) {
            $field = 'document_' . $i;
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
        for ($i = 1; $i <= 6; $i++) {
            $field = 'document_' . $i;
            if ($employee->{$field}) {
                Storage::disk('public')->delete($employee->{$field});
            }
        }

        $employee->delete();

        return redirect()->route('employers.edit', $employer)
            ->with('success', 'ลบข้อมูลพนักงานเรียบร้อยแล้ว');
    }
}
