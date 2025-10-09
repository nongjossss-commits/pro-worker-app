<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Apply permission middleware to the controller.
     */
    public function __construct()
    {
        $this->middleware('permission:view-employees', ['only' => ['index', 'show']]);
        $this->middleware('permission:edit-employees', ['only' => ['edit', 'update']]);
        $this->middleware('permission:create-employees', ['only' => ['create', 'store']]);
        $this->middleware('permission:delete-employees', ['only' => ['destroy']]);
    }

public function index(Request $request)
{
    $query = Employee::where('termination_date', null)->with('employer')->latest();

    $query->when($request->filled('search'), function ($q) use ($request) {
        $searchTerm = $request->search;
        $q->where(function ($subQuery) use ($searchTerm) {
            $subQuery->where('employeeNameTh', 'like', "%{$searchTerm}%")
                     ->orWhere('employeeNameEn', 'like', "%{$searchTerm}%")
                     ->orWhere('employeePassport', 'like', "%{$searchTerm}%");
        });
    });

    $query->when($request->filled('nationality'), fn($q) => $q->where('employeeNationality', $request->nationality));
    $query->when($request->filled('mou_type'), fn($q) => $q->where('workPermitMOUGroup', $request->mou_type));

    $employees = $query->paginate(10)->withQueryString();

    $totalEmployees = Employee::where('termination_date', null)->count();
    $maleCount = Employee::where('termination_date', null)->whereIn('employeeTitleTh', ['นาย'])->count();
    $femaleCount = Employee::where('termination_date', null)->whereIn('employeeTitleTh', ['นางสาว', 'นาง'])->count();

    return view('employees.index', compact('employees', 'totalEmployees', 'maleCount', 'femaleCount'));
}

public function create(Request $request) // เพิ่ม Request $request เข้ามา
{
    $employers = \App\Models\Employer::orderBy('employerNameTh')->get();
    $selectedEmployer = null;

    // ตรวจสอบว่ามี employer_id ส่งมากับ URL หรือไม่
    if ($request->has('employer_id')) {
        $selectedEmployer = \App\Models\Employer::find($request->employer_id);
    }

    // ส่ง $selectedEmployer ไปในชื่อ $employer เพื่อให้ View ใช้งานได้
    return view('employees.create', [
        'employers' => $employers,
        'employer' => $selectedEmployer
    ]);
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'employeeNameTh' => 'required|string|max:255',
            'employeeNameEn' => 'nullable|string|max:255',
            'employeeNationality' => 'nullable|string|max:255',
            'employeePassport' => 'nullable|string|max:255',
            'passportExpiryDate' => 'nullable|date',
            'employeeWorkPermit' => 'nullable|string|max:255',
            'workPermitExpiryDate' => 'nullable|date',
            'visaExpiryDate' => 'nullable|date',
            'ninetyDayReportDate' => 'nullable|date',
            'employeeTitleTh' => 'nullable|string|max:255',
            'employeeTitleEn' => 'nullable|string|max:255',
            'employeeDob' => 'nullable|date',
            'passportType' => 'nullable|string|max:255',
            'namelistNo' => 'nullable|string|max:255',
            'requestNo' => 'nullable|string|max:255',
            'workerRefNo' => 'nullable|string|max:255',
            'personalId' => 'nullable|string|max:255',
            'companyWorkerId' => 'nullable|string|max:255',
            'pinkCardNo' => 'nullable|string|max:255',
            'socialSecurityNo' => 'nullable|string|max:255',
            'taxIdNo' => 'nullable|string|max:255',
            'designatedHospital' => 'nullable|string|max:255',
            'startDate' => 'nullable|date',
            'employeePhone' => 'nullable|string|max:255',
            'employeePosition' => 'nullable|string|max:255',
            'workPermitMOUGroup' => 'nullable|string|max:255',
            'workPermitMOUGroupOther' => 'nullable|string|max:255',
            'employeePhoto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'document_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_4' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_description_4' => 'nullable|string|max:255',
            'document_5' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_description_5' => 'nullable|string|max:255',
            'document_6' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_description_6' => 'nullable|string|max:255',
            'nature_of_work' => 'nullable|string',
        ]);

        if ($request->hasFile('employeePhoto')) {
            $validated['employeePhoto'] = $request->file('employeePhoto')->store('employee_photos', 'public');
        }
        // Add other file uploads here...
        for ($i = 1; $i <= 6; $i++) {
            if ($request->hasFile("document_$i")) {
                $validated["document_$i"] = $request->file("document_$i")->store("employee_documents", 'public');
            }
        }

        Employee::create($validated);
        if ($request->has('source_employer_id')) {
            return redirect()->route('employers.edit', $request->source_employer_id)->with('success', 'Employee created successfully.');
        }
        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee)
    {
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $employers = \App\Models\Employer::orderBy('employerNameTh')->get();
        return view('employees.edit', compact('employee', 'employers'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'employeeNameTh' => 'required|string|max:255',
            'employeeNameEn' => 'nullable|string|max:255',
            'employeeNationality' => 'nullable|string|max:255',
            'employeePassport' => 'nullable|string|max:255',
            'passportExpiryDate' => 'nullable|date',
            'employeeWorkPermit' => 'nullable|string|max:255',
            'workPermitExpiryDate' => 'nullable|date',
            'visaExpiryDate' => 'nullable|date',
            'ninetyDayReportDate' => 'nullable|date',
            'employeeTitleTh' => 'nullable|string|max:255',
            'employeeTitleEn' => 'nullable|string|max:255',
            'employeeDob' => 'nullable|date',
            'passportType' => 'nullable|string|max:255',
            'namelistNo' => 'nullable|string|max:255',
            'requestNo' => 'nullable|string|max:255',
            'workerRefNo' => 'nullable|string|max:255',
            'personalId' => 'nullable|string|max:255',
            'companyWorkerId' => 'nullable|string|max:255',
            'pinkCardNo' => 'nullable|string|max:255',
            'socialSecurityNo' => 'nullable|string|max:255',
            'taxIdNo' => 'nullable|string|max:255',
            'designatedHospital' => 'nullable|string|max:255',
            'startDate' => 'nullable|date',
            'employeePhone' => 'nullable|string|max:255',
            'employeePosition' => 'nullable|string|max:255',
            'workPermitMOUGroup' => 'nullable|string|max:255',
            'workPermitMOUGroupOther' => 'nullable|string|max:255',
            'employeePhoto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'document_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_4' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_description_4' => 'nullable|string|max:255',
            'document_5' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_description_5' => 'nullable|string|max:255',
            'document_6' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_description_6' => 'nullable|string|max:255',
            'nature_of_work' => 'nullable|string',
        ]);

        if ($request->hasFile('employeePhoto')) {
            if ($employee->employeePhoto) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($employee->employeePhoto);
            }
            $validated['employeePhoto'] = $request->file('employeePhoto')->store('employee_photos', 'public');
        }
        // Add other file updates here...
        for ($i = 1; $i <= 6; $i++) {
            if ($request->hasFile("document_$i")) {
                if ($employee->{"document_$i"}) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($employee->{"document_$i"});
                }
                $validated["document_$i"] = $request->file("document_$i")->store("employee_documents", 'public');
            }
        }

        $employee->update($validated);
        if ($request->has('source_employer_id')) {
            return redirect()->route('employers.edit', $request->source_employer_id)->with('success', 'Employee updated successfully.');
        }
        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function locate(Employee $employee)
    {
        return redirect()->route('employers.edit', $employee->employer_id)
                         ->with('highlight_employee', $employee->id);
    }

    public function destroy(Employee $employee)
    {
        if ($employee->employeePhoto) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($employee->employeePhoto);
        }
        // Add other file deletions here...
        for ($i = 1; $i <= 6; $i++) {
            if ($employee->{"document_$i"}) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($employee->{"document_$i"});
            }
        }
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}