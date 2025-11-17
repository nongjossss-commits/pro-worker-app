<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'employeeTitleTh' => 'required|string|max:255',
            'employeeNameTh' => 'required|string|max:255',
            'employeeTitleEn' => 'nullable|string|max:255',
            'employeeNameEn' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'employeeDob' => 'nullable|date',
            'employeePhone' => 'nullable|string|max:255',
            'employeeNationality' => 'nullable|string|max:255',
            'employeePassport' => 'nullable|string|max:255',
            'passport_issue_date' => 'nullable|date',
            'passportExpiryDate' => 'nullable|date',
            'pinkCardNo' => 'nullable|string|max:255',
            'visa_type' => 'nullable|string|max:255',
            'visaExpiryDate' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'startDate' => 'nullable|date',
            'employeeWorkPermit' => 'nullable|string|max:255',
            'workPermitExpiryDate' => 'nullable|date',
            'ninetyDayReportDate' => 'nullable|date',
            'insurance_type' => 'nullable|string|max:255',
            'insurance_company' => 'nullable|string|max:255',
            'hospital_name' => 'nullable|string|max:255',
            'insurance_expiry_date' => 'nullable|date',
            'email' => 'nullable|email|max:255|unique:employees,email',
            'password' => 'nullable|string|min:8',
            'employeePhoto' => 'nullable|image|max:2048',
            'passport_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'visa_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'work_permit_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'pink_card_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'social_security_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'insurance_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'document_5' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_5_desc' => 'nullable|string|max:255',
            'document_6' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_6_desc' => 'nullable|string|max:255',
            'document_7' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_7_desc' => 'nullable|string|max:255',
            'document_8' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_8_desc' => 'nullable|string|max:255',
            'document_9' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_9_desc' => 'nullable|string|max:255',
            'document_10' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_10_desc' => 'nullable|string|max:255',
            'document_11' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_11_desc' => 'nullable|string|max:255',
            'document_12' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_12_desc' => 'nullable|string|max:255',
        ]);

        $data = $validated;

        if (isset($data['visa_type'])) {
            $data['visaType'] = $data['visa_type'];
            unset($data['visa_type']);
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (isset($data['insurance_type'])) {
            if ($data['insurance_type'] === 'social_security') {
                $data['insurance_company'] = null;
            } elseif ($data['insurance_type'] === 'private') {
                $data['hospital_name'] = null;
            }
        }

        $fileUploads = [
            'employeePhoto' => 'employeePhoto',
            'passport_file' => 'employeePassport',
            'visa_file' => 'employeeVisa',
            'work_permit_file' => 'employeeWorkPermit',
            'pink_card_file' => 'pinkCardNo',
            'social_security_file' => 'social_security_file',
            'insurance_file' => 'insurance_file',
        ];

        foreach($fileUploads as $requestField => $dbColumn) {
            if ($request->hasFile($requestField)) {
                $path = $request->file($requestField)->store('employee_documents', 'private');
                $data[$dbColumn] = $path;
            }
        }

        for ($i = 5; $i <= 12; $i++) {
            $fieldName = "document_{$i}";
            if ($request->hasFile($fieldName)) {
                $path = $request->file($fieldName)->store('employee_documents', 'private');
                $data[$fieldName] = $path;
            }
        }

        Employee::create($data);

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
            'employeeTitleTh' => 'required|string|max:255',
            'employeeNameTh' => 'required|string|max:255',
            'employeeTitleEn' => 'nullable|string|max:255',
            'employeeNameEn' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'employeeDob' => 'nullable|date',
            'employeePhone' => 'nullable|string|max:255',
            'employeeNationality' => 'nullable|string|max:255',
            'employeePassport' => 'nullable|string|max:255',
            'passport_issue_date' => 'nullable|date',
            'passportExpiryDate' => 'nullable|date',
            'pinkCardNo' => 'nullable|string|max:255',
            'visa_type' => 'nullable|string|max:255',
            'visaExpiryDate' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'startDate' => 'nullable|date',
            'employeeWorkPermit' => 'nullable|string|max:255',
            'workPermitExpiryDate' => 'nullable|date',
            'ninetyDayReportDate' => 'nullable|date',
            'insurance_type' => 'nullable|string|max:255',
            'insurance_company' => 'nullable|string|max:255',
            'hospital_name' => 'nullable|string|max:255',
            'insurance_expiry_date' => 'nullable|date',
            'email' => 'nullable|email|max:255|unique:employees,email,' . $employee->id,
            'password' => 'nullable|string|min:8',
            'employeePhoto' => 'nullable|image|max:2048',
            'passport_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'visa_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'work_permit_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'pink_card_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'social_security_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'insurance_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'document_5' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_5_desc' => 'nullable|string|max:255',
            'document_6' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_6_desc' => 'nullable|string|max:255',
            'document_7' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_7_desc' => 'nullable|string|max:255',
            'document_8' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_8_desc' => 'nullable|string|max:255',
            'document_9' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_9_desc' => 'nullable|string|max:255',
            'document_10' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_10_desc' => 'nullable|string|max:255',
            'document_11' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_11_desc' => 'nullable|string|max:255',
            'document_12' => 'nullable|file|mimes:pdf,jpg,jpeg,png', 'document_12_desc' => 'nullable|string|max:255',
        ]);

        $data = $validated;

        if (isset($data['visa_type'])) {
            $data['visaType'] = $data['visa_type'];
            unset($data['visa_type']);
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (isset($data['insurance_type'])) {
            if ($data['insurance_type'] === 'social_security') {
                $data['insurance_company'] = null;
            } elseif ($data['insurance_type'] === 'private') {
                $data['hospital_name'] = null;
            }
        }

        $fileUploads = [
            'employeePhoto' => 'employeePhoto',
            'passport_file' => 'employeePassport',
            'visa_file' => 'employeeVisa',
            'work_permit_file' => 'employeeWorkPermit',
            'pink_card_file' => 'pinkCardNo',
            'social_security_file' => 'social_security_file',
            'insurance_file' => 'insurance_file',
        ];

        foreach($fileUploads as $requestField => $dbColumn) {
            if ($request->hasFile($requestField)) {
                if ($employee->{$dbColumn} && Storage::disk('private')->exists($employee->{$dbColumn})) {
                    Storage::disk('private')->delete($employee->{$dbColumn});
                }
                $path = $request->file($requestField)->store('employee_documents', 'private');
                $data[$dbColumn] = $path;
            }
        }

        for ($i = 5; $i <= 12; $i++) {
            $fieldName = "document_{$i}";
            if ($request->hasFile($fieldName)) {
                 if ($employee->{$fieldName} && Storage::disk('private')->exists($employee->{$fieldName})) {
                    Storage::disk('private')->delete($employee->{$fieldName});
                }
                $path = $request->file($fieldName)->store('employee_documents', 'private');
                $data[$fieldName] = $path;
            }
        }

        $employee->update($data);

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