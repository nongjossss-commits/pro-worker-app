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
    public function create(Request $request)
    {
        $employer_id = $request->query('employer_id');

        // Use findOrFail to automatically handle cases where the employer is not found
        $employer = Employer::findOrFail($employer_id);

        return view('employees.create', compact('employer'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employer_id' => 'required|exists:employers,id',
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
            // ... include other document fields if they are in the form
        ]);

        $employer = Employer::findOrFail($validated['employer_id']);

        $data = $validated;

        if ($request->hasFile('employeePhoto')) {
            $path = $request->file('employeePhoto')->store('employee_photos', 'public');
            $data['employeePhoto'] = $path;
        }

        // ... (Add loops for other document uploads if necessary) ...

        $employer->employees()->create($data);

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
    public function update(Request $request, Employee $employee)
    {
        // Validation logic... (Ensure unique rule ignores the current employee)
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
            // Add validation for other document fields if necessary
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

        // The update method is now simpler
        $employee->update($data);

        // Retrieve the employer FROM the employee relationship
        $employer = $employee->employer;

        // Redirect correctly
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
