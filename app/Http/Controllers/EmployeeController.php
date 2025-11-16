<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $this->middleware('permission:terminate-employees', ['only' => ['terminate']]);
        $this->middleware('permission:terminate-employees', ['only' => ['reinstate']]);
        $this->middleware('permission:restore-employees', ['only' => ['restore']]);
        $this->middleware('permission:force-delete-employees', ['only' => ['forceDelete']]);
    }

    /**
     * Soft delete the specified employee.
     */
    public function terminate(Employee $employee)
    {
        $employee->update(['terminated_at' => now()]);
        return back()->with('success', 'Employee terminated successfully.');
    }

    /**
     * Restore the specified soft-deleted employee.
     */
    public function restore(Employee $employee)
    {
        $employee->restore();
        return response()->json(['success' => 'Employee restored successfully.']);
    }

    /**
     * Permanently delete the specified employee.
     */
    public function forceDelete(Employee $employee)
    {
        // Permanently delete the employee's photo and documents first.
        if ($employee->employeePhoto) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($employee->employeePhoto);
        }
        for ($i = 1; $i <= 6; $i++) {
            if ($employee->{"document_$i"}) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($employee->{"document_$i"});
            }
        }

        $employee->forceDelete();
        return response()->json(['success' => 'Employee permanently deleted successfully.']);
    }

/**
 * Restore a terminated employee from "History" menu.
 */
public function reinstate(Employee $employee)
{
    $this->authorize('terminate-employees'); // Re-using permission, or create a new 'reinstate-employees' if needed
    $employee->update(['terminated_at' => null]);

    return response()->json(['success' => 'Employee reinstated successfully.']);
}

    public function index(Request $request)
{
    $query = Employee::query()->whereNull('terminated_at');

    // --- START: ADDED FILTERING LOGIC ---
    if ($request->filled('search')) {
        $searchTerm = '%' . $request->input('search') . '%';
        $query->where(function ($q) use ($searchTerm) {
            $q->where('employeeNameTh', 'like', $searchTerm)
              ->orWhere('employeeNameEn', 'like', $searchTerm)
              ->orWhere('employeePassport', 'like', $searchTerm)
              ->orWhere('pinkCardNo', 'like', $searchTerm);
        });
    }

    if ($request->filled('nationality')) {
        $query->where('employeeNationality', $request->input('nationality'));
    }

    if ($request->filled('mou_group')) {
        $query->where('workPermitMOUGroup', $request->input('mou_group'));
    }

    if ($request->filled('pink_card')) {
        if ($request->input('pink_card') === 'yes') {
            $query->where(function ($q) {
                $q->whereNotNull('pinkCardNo')->where('pinkCardNo', '!=', '');
            });
        } elseif ($request->input('pink_card') === 'no') {
            $query->where(function ($q) {
                $q->whereNull('pinkCardNo')->orWhere('pinkCardNo', '=', '');
            });
        }
    }
    // --- END: ADDED FILTERING LOGIC ---

    $totalEmployees = (clone $query)->count();

    $perPageOptions = [25, 50, 100]; // Defined options here
    $currentPerPage = $request->input('per_page', 25);

    $employees = $query->with('employer')->latest()->paginate($currentPerPage)->withQueryString(); // Added withQueryString() to preserve filters on pagination

    return view('employees.index', compact(
        'employees',
        'totalEmployees',
        'perPageOptions',
        'currentPerPage'
    ))->with('currentView', $request->input('view', 'card'));
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
            'email' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
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
            'name_list_number' => 'nullable|string|max:255',
            'request_number' => 'nullable|string|max:255',
            'employee_id_number' => 'nullable|string|max:255',
            'tax_id_number' => 'nullable|string|max:255',
            'employer_employee_id' => 'nullable|string|max:255',
            'employee_reference_id' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'passport_issue_date' => 'nullable|date',
            'passport_type_cambodia' => 'nullable|string|max:255',
            'insurance_type' => 'nullable|string|max:255',
            'insurance_detail' => 'nullable|string',
            'insurance_expiry_date' => 'nullable|date',
            'social_security_number' => 'nullable|string|max:255',
            'other_doc_1_desc' => 'nullable|string|max:255',
            'other_doc_2_desc' => 'nullable|string|max:255',
            'other_doc_3_desc' => 'nullable|string|max:255',
            'other_doc_4_desc' => 'nullable|string|max:255',
            // V6 Fields
            'job_title' => 'nullable|string|max:255',
            'job_description' => 'nullable|string',
            'employeeEmail' => 'nullable|email|max:255',
            'employeePassword' => 'nullable|string|min:8',
            'insurance_document_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
        ]);

        $fileFields = [
            'employeePhoto',
            'employee_doc_1', 'employee_doc_2', 'employee_doc_3', 'employee_doc_4',
            'employee_doc_5', 'employee_doc_6', 'employee_doc_7', 'employee_doc_8',
            'employee_doc_9', 'employee_doc_10', 'employee_doc_11', 'employee_doc_12'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $employerId = $request->input('employer_id', 'temp');
                $path = $request->file($field)->store("employee_files/{$employerId}", 'public');
                $validated[$field] = $path;
            }
        }

        // Manually add/overwrite all V6 fields
        $validated['passport_issue_date'] = $request->passport_issue_date;
        $validated['passport_type_cambodia'] = $request->passport_type_cambodia;
        $validated['job_title'] = $request->job_title;
        $validated['job_description'] = $request->job_description;
        $validated['insurance_type'] = $request->insurance_type;
        $validated['insurance_detail'] = $request->insurance_detail;
        $validated['insurance_expiry_date'] = $request->insurance_expiry_date;
        $validated['social_security_number'] = $request->social_security_number;
        $validated['email'] = $request->employeeEmail; // Map from form name to DB column

        // Handle password hashing from the correct field
        if ($request->filled('employeePassword')) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($request->employeePassword);
        } else {
            // If the new field is empty, make sure the old one is gone too.
            unset($validated['password']);
        }

        if ($request->hasFile('insurance_document_path')) {
            $path = $request->file('insurance_document_path')->store("employee_files/{$validated['employer_id']}", 'public');
            $validated['insurance_document_path'] = $path;
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
            'email' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
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
            'name_list_number' => 'nullable|string|max:255',
            'request_number' => 'nullable|string|max:255',
            'employee_id_number' => 'nullable|string|max:255',
            'tax_id_number' => 'nullable|string|max:255',
            'employer_employee_id' => 'nullable|string|max:255',
            'employee_reference_id' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'passport_issue_date' => 'nullable|date',
            'passport_type_cambodia' => 'nullable|string|max:255',
            'insurance_type' => 'nullable|string|max:255',
            'insurance_detail' => 'nullable|string',
            'insurance_expiry_date' => 'nullable|date',
            'social_security_number' => 'nullable|string|max:255',
            'other_doc_1_desc' => 'nullable|string|max:255',
            'other_doc_2_desc' => 'nullable|string|max:255',
            'other_doc_3_desc' => 'nullable|string|max:255',
            'other_doc_4_desc' => 'nullable|string|max:255',
            // V6 Fields
            'job_title' => 'nullable|string|max:255',
            'job_description' => 'nullable|string',
            'employeeEmail' => 'nullable|email|max:255',
            'employeePassword' => 'nullable|string|min:8',
            'insurance_document_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
        ]);

        $fileFields = [
            'employeePhoto',
            'employee_doc_1', 'employee_doc_2', 'employee_doc_3', 'employee_doc_4',
            'employee_doc_5', 'employee_doc_6', 'employee_doc_7', 'employee_doc_8',
            'employee_doc_9', 'employee_doc_10', 'employee_doc_11', 'employee_doc_12'
        ];

        // Handle file deletions
        foreach ($fileFields as $field) {
            $removeCheckbox = 'remove_' . $field;
            if ($request->has($removeCheckbox)) {
                if ($employee->{$field} && Storage::disk('public')->exists($employee->{$field})) {
                    Storage::disk('public')->delete($employee->{$field});
                }
                $validated[$field] = null;
            }
        }

        // Handle file uploads
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                if ($employee->{$field} && Storage::disk('public')->exists($employee->{$field})) {
                    Storage::disk('public')->delete($employee->{$field});
                }
                $path = $request->file($field)->store("employee_files/{$employee->employer_id}", 'public');
                $validated[$field] = $path;
            }
        }

        // Manually add all V6 fields
        $validated['passport_issue_date'] = $request->passport_issue_date;
        $validated['passport_type_cambodia'] = $request->passport_type_cambodia;
        $validated['job_title'] = $request->job_title;
        $validated['job_description'] = $request->job_description;
        $validated['insurance_type'] = $request->insurance_type;
        $validated['insurance_detail'] = $request->insurance_detail;
        $validated['insurance_expiry_date'] = $request->insurance_expiry_date;
        $validated['social_security_number'] = $request->social_security_number;
        $validated['email'] = $request->employeeEmail; // Map form field to db column

        // Correctly handle password update
        if ($request->filled('employeePassword')) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($request->employeePassword);
        } else {
            // If the new field is empty, make sure the old one is gone too.
             unset($validated['password']);
        }

        // Handle insurance document separately
        if ($request->has('remove_insurance_document_path')) {
            if ($employee->insurance_document_path && Storage::disk('public')->exists($employee->insurance_document_path)) {
                Storage::disk('public')->delete($employee->insurance_document_path);
            }
            $validated['insurance_document_path'] = null;
        }

        if ($request->hasFile('insurance_document_path')) {
            if ($employee->insurance_document_path && Storage::disk('public')->exists($employee->insurance_document_path)) {
                Storage::disk('public')->delete($employee->insurance_document_path);
            }
            $path = $request->file('insurance_document_path')->store("employee_files/{$employee->employer_id}", 'public');
            $validated['insurance_document_path'] = $path;
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
        $employee->delete();
        return response()->json(['success' => 'Employee moved to trash successfully.']);
    }

    public function export(Request $request)
    {
        $this->authorize('view-employees');

        $query = Employee::query()->whereNull('terminated_at');

        // Reuse the same filtering logic from the index method
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', $searchTerm)
                  ->orWhere('employeeNameEn', 'like', $searchTerm)
                  ->orWhere('employeePassport', 'like', $searchTerm)
                  ->orWhere('pinkCardNo', 'like', $searchTerm);
            });
        }

        if ($request->filled('nationality')) {
            $query->where('employeeNationality', $request->input('nationality'));
        }

        if ($request->filled('mou_group')) {
            $query->where('workPermitMOUGroup', $request->input('mou_group'));
        }

        if ($request->filled('pink_card')) {
            if ($request->input('pink_card') === 'yes') {
                $query->where(fn($q) => $q->whereNotNull('pinkCardNo')->where('pinkCardNo', '!=', ''));
            } elseif ($request->input('pink_card') === 'no') {
                $query->where(fn($q) => $q->whereNull('pinkCardNo')->orWhere('pinkCardNo', '=', ''));
            }
        }

        $employees = $query->with('employer')->get();

        $fileName = 'employees_export_' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Encoding"    => "UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Employer Name', 'Employee Name (TH)', 'Employee Name (EN)',
            'Nationality', 'Passport No', 'Passport Expiry',
            'Work Permit No', 'Work Permit Expiry', 'Visa Expiry',
            '90 Day Report', 'Pink Card No'
        ];

        $callback = function() use($employees, $columns) {
            $file = fopen('php://output', 'w');
            // Add BOM to support UTF-8 in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);

            foreach ($employees as $employee) {
                $row['Employer Name']        = $employee->employer->employerNameTh ?? 'N/A';
                $row['Employee Name (TH)']   = $employee->employeeNameTh;
                $row['Employee Name (EN)']   = $employee->employeeNameEn;
                $row['Nationality']          = $employee->employeeNationality;
                $row['Passport No']          = $employee->employeePassport;
                $row['Passport Expiry']      = $employee->passportExpiryDate;
                $row['Work Permit No']       = $employee->employeeWorkPermit;
                $row['Work Permit Expiry']   = $employee->workPermitExpiryDate;
                $row['Visa Expiry']          = $employee->visaExpiryDate;
                $row['90 Day Report']        = $employee->ninetyDayReportDate;
                $row['Pink Card No']         = $employee->pinkCardNo;

                fputcsv($file, array_values($row));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}