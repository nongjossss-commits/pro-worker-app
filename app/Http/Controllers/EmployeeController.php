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
     * Terminate the specified employee.
     */
    public function terminate(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'termination_date' => 'required|date',
            'termination_reason' => 'nullable|string',
        ]);

        $employee->update([
            'terminated_at' => $validated['termination_date'],
            'termination_reason' => $validated['termination_reason'],
        ]);

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
    $this->authorize('terminate', $employee); // Use the 'terminate' policy action
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
              ->orWhere('pinkCardNo', 'like', $searchTerm)
              ->orWhere('employeeWorkPermit', 'like', $searchTerm)
              ->orWhereHas('employer', function ($employerQuery) use ($searchTerm) {
                  $employerQuery->where('employerNameTh', 'like', $searchTerm)
                                ->orWhere('employerNameEn', 'like', $searchTerm);
              });
        });
    }
    if ($request->filled('work_permit_expiry_date')) {
        $query->whereDate('workPermitExpiryDate', $request->input('work_permit_expiry_date'));
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

    if ($request->filled('passport_type')) {
        $passportType = $request->input('passport_type');
        $query->where(function ($q) use ($passportType) {
            $q->where('passportType', $passportType)
                ->orWhere('passport_type_cambodia', $passportType);
        });
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
        // --- V6: Step 1: Validate ALL text/date data (new and old) ---
        $validated = $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'passportType' => 'nullable|string|max:255',
            'employeeTitleTh' => 'required|string|max:255',
            'employeeNameTh' => 'required|string|max:255',
            'employeeTitleEn' => 'nullable|string|max:255',
            'employeeNameEn' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'employeeGender' => 'nullable|string|max:255',
            'employeeDob' => 'nullable|date',
            'employeeAge' => 'nullable|integer',
            'employeePhone' => 'nullable|string|max:255',
            'employeeNationality' => 'nullable|string|max:255',
            'passport_type_cambodia' => 'nullable|string|max:255',
            'employeePassport' => 'nullable|string|max:255',
            'passport_issue_date' => 'nullable|date',
            'passportExpiryDate' => 'nullable|date',
            'pinkCardNo' => 'nullable|string|max:255',
            'visaType' => 'nullable|string|max:255',
            'visaExpiryDate' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'job_description' => 'nullable|string',
            'startDate' => 'nullable|date',
            'employeeWorkPermit' => 'nullable|string|max:255',
            'workPermitExpiryDate' => 'nullable|date',
            'workPermitType' => 'nullable|string|max:255',
            'workPermitMOUGroup' => 'nullable|string|max:255',
            'workPermitMOUGroupOther' => 'nullable|string|max:255',
            'ninetyDayReportDate' => 'nullable|date',
            'name_list_number' => 'nullable|string|max:255',
            'request_number' => 'nullable|string|max:255',
            'employee_id_number' => 'nullable|string|max:255',
            'tax_id_number' => 'nullable|string|max:255',
            'employer_employee_id' => 'nullable|string|max:255',
            'employee_reference_id' => 'nullable|string|max:255',
            'insurance_type' => 'nullable|string|max:255',
            'insurance_detail' => 'nullable|string',
            'insurance_expiry_date' => 'nullable|date',
            'social_security_number' => 'nullable|string|max:255',
            'insurance_detail_hospital' => 'nullable|string|max:255',
            'insurance_detail_private' => 'nullable|string|max:255',
            'insurance_expiry_date_private' => 'nullable|string|max:255',
            'insurance_expiry_date_hospital' => 'nullable|string|max:255',
            'insurance_detail_social' => 'nullable|string|max:255',
            'employeeEmail' => 'nullable|email|max:255|unique:employees,email',
            'employeePassword' => 'nullable|string|min:8',
            'other_doc_1_desc' => 'nullable|string|max:255',
            'other_doc_2_desc' => 'nullable|string|max:255',
            'other_doc_3_desc' => 'nullable|string|max:255',
            'other_doc_4_desc' => 'nullable|string|max:255',
            'employeePhoto' => 'nullable|image|max:2048',
            'insurance_document_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'insurance_document_path_private' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_4' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_5' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_6' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_7' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_8' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_9' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_10' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_11' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_12' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
        ]);

        // --- V6 Step 1.5: Map insurance data to correct model properties ---
        $validated['insuranceType'] = $validated['insurance_type'] ?? null;

        if ($validated['insuranceType'] === 'ประกันสังคม') {
            $validated['socialSecurityNumber'] = $validated['social_security_number'] ?? null;
            $validated['hospitalName'] = $validated['insurance_detail_social'] ?? null;
            $validated['insuranceCompany'] = null;
            $validated['insuranceExpiryDate'] = null;
        } elseif ($validated['insuranceType'] === 'ประกันเอกชน') {
            $validated['insuranceCompany'] = $validated['insurance_detail_private'] ?? null;
            $validated['insuranceExpiryDate'] = $validated['insurance_expiry_date_private'] ?? null;
            $validated['socialSecurityNumber'] = null;
            $validated['hospitalName'] = null;
        } elseif ($validated['insuranceType'] === 'ประกันโรงพยาบาล') {
            $validated['hospitalName'] = $validated['insurance_detail_hospital'] ?? null;
            $validated['insuranceExpiryDate'] = $validated['insurance_expiry_date_hospital'] ?? null;
            $validated['socialSecurityNumber'] = null;
            $validated['insuranceCompany'] = null;
        } else {
            $validated['socialSecurityNumber'] = null;
            $validated['hospitalName'] = null;
            $validated['insuranceCompany'] = null;
            $validated['insuranceExpiryDate'] = null;
        }

        // --- V6: Step 2: Handle email and password mapping & hashing ---
        $validated['email'] = $validated['employeeEmail'] ?? null;
        unset($validated['employeeEmail']);

        if (!empty($validated['employeePassword'])) {
            $validated['password'] = $validated['employeePassword']; // Save as plain text per user request
        }
        unset($validated['employeePassword']);

        // --- V6: Step 3: Unified File Upload Loop ---
        $fileFields = [
            'employeePhoto', 'insurance_document_path','insurance_document_path_private',
            'employee_doc_1', 'employee_doc_2', 'employee_doc_3', 'employee_doc_4',
            'employee_doc_5', 'employee_doc_6', 'employee_doc_7', 'employee_doc_8',
            'employee_doc_9', 'employee_doc_10', 'employee_doc_11', 'employee_doc_12'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs("employee_files/{$validated['employer_id']}", $filename, 'public');
                $validated[$field] = $path;
            }
        }

        // --- V6: Step 4: Create Employee ---
        Employee::create($validated);

        // --- V6: Step 5: Redirect ---
        $redirectRoute = $request->has('source_employer_id')
            ? route('employers.edit', $request->source_employer_id) . '#employees'
            : route('employees.index');

        return redirect($redirectRoute)->with('success', 'Employee created successfully.');
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
        // --- V6: Step 1: Validate ALL text/date data (new and old) ---
        $validated = $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'passportType' => 'nullable|string|max:255',
            'employeeTitleTh' => 'required|string|max:255',
            'employeeNameTh' => 'required|string|max:255',
            'employeeTitleEn' => 'nullable|string|max:255',
            'employeeNameEn' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'employeeGender' => 'nullable|string|max:255',
            'employeeDob' => 'nullable|date',
            'employeeAge' => 'nullable|integer',
            'employeePhone' => 'nullable|string|max:255',
            'employeeNationality' => 'nullable|string|max:255',
            'passport_type_cambodia' => 'nullable|string|max:255',
            'employeePassport' => 'nullable|string|max:255',
            'passport_issue_date' => 'nullable|date',
            'passportExpiryDate' => 'nullable|date',
            'pinkCardNo' => 'nullable|string|max:255',
            'visaType' => 'nullable|string|max:255',
            'visaExpiryDate' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'job_description' => 'nullable|string',
            'startDate' => 'nullable|date',
            'employeeWorkPermit' => 'nullable|string|max:255',
            'workPermitExpiryDate' => 'nullable|date',
            'workPermitType' => 'nullable|string|max:255',
            'workPermitMOUGroup' => 'nullable|string|max:255',
            'workPermitMOUGroupOther' => 'nullable|string|max:255',
            'ninetyDayReportDate' => 'nullable|date',
            'name_list_number' => 'nullable|string|max:255',
            'request_number' => 'nullable|string|max:255',
            'employee_id_number' => 'nullable|string|max:255',
            'tax_id_number' => 'nullable|string|max:255',
            'employer_employee_id' => 'nullable|string|max:255',
            'employee_reference_id' => 'nullable|string|max:255',
            'insurance_type' => 'nullable|string|max:255',
            'insurance_detail' => 'nullable|string',
            'insurance_expiry_date' => 'nullable|date',
            'social_security_number' => 'nullable|string|max:255',
            'insurance_detail_hospital' => 'nullable|string|max:255',
            'insurance_expiry_date_private' => 'nullable|string|max:255',
            'insurance_expiry_date_hospital' => 'nullable|string|max:255',
            'insurance_detail_private' => 'nullable|string|max:255',
            'insurance_detail_social' => 'nullable|string|max:255',
            'employeeEmail' => 'nullable|email|max:255|unique:employees,email,' . $employee->id,
            'password' => 'nullable|string|min:8',
            'other_doc_1_desc' => 'nullable|string|max:255',
            'other_doc_2_desc' => 'nullable|string|max:255',
            'other_doc_3_desc' => 'nullable|string|max:255',
            'other_doc_4_desc' => 'nullable|string|max:255',
            'employeePhoto' => 'nullable|image|max:2048',
            'insurance_document_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'insurance_document_path_private' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_4' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_5' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_6' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_7' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_8' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_9' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_10' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_11' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_12' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'passport_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'visa_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'work_permit_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'pink_card_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'insurance_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // --- V6 Step 1.5: Map insurance data to correct model properties ---
        $validated['insuranceType'] = $validated['insurance_type'] ?? null;

        if ($validated['insuranceType'] === 'ประกันสังคม') {
            $validated['socialSecurityNumber'] = $validated['social_security_number'] ?? null;
            $validated['hospitalName'] = $validated['insurance_detail_social'] ?? null;
            $validated['insuranceCompany'] = null;
            $validated['insuranceExpiryDate'] = null;
        } elseif ($validated['insuranceType'] === 'ประกันเอกชน') {
            $validated['insuranceCompany'] = $validated['insurance_detail_private'] ?? null;
            $validated['insuranceExpiryDate'] = $validated['insurance_expiry_date_private'] ?? null;
            $validated['socialSecurityNumber'] = null;
            $validated['hospitalName'] = null;
        } elseif ($validated['insuranceType'] === 'ประกันโรงพยาบาล') {
            $validated['hospitalName'] = $validated['insurance_detail_hospital'] ?? null;
            $validated['insuranceExpiryDate'] = $validated['insurance_expiry_date_hospital'] ?? null;
            $validated['socialSecurityNumber'] = null;
            $validated['insuranceCompany'] = null;
        } else {
            // For 'None' or other types, null out all specific fields
            $validated['socialSecurityNumber'] = null;
            $validated['hospitalName'] = null;
            $validated['insuranceCompany'] = null;
            $validated['insuranceExpiryDate'] = null;
        }


        // --- V6: Step 2: Handle email and password mapping & hashing ---
        $data = $validated;
        $data['email'] = $validated['employeeEmail'] ?? null;
        unset($data['employeeEmail']);
        if ($request->hasFile('passport_file')) {
            if ($employee->passport_file_path) {
                Storage::disk('private')->delete($employee->passport_file_path);
            }
            $path = $request->file('passport_file')->store('employee_documents', 'private');
            $data['passport_file_path'] = $path;
        }
        if ($request->hasFile('visa_file')) {
            if ($employee->visa_file_path) {
                Storage::disk('private')->delete($employee->visa_file_path);
            }
            $path = $request->file('visa_file')->store('employee_documents', 'private');
            $data['visa_file_path'] = $path;
        }
        if ($request->hasFile('work_permit_file')) {
            if ($employee->work_permit_file_path) {
                Storage::disk('private')->delete($employee->work_permit_file_path);
            }
            $path = $request->file('work_permit_file')->store('employee_documents', 'private');
            $data['work_permit_file_path'] = $path;
        }
        if ($request->hasFile('pink_card_file')) {
            if ($employee->pink_card_file_path) {
                Storage::disk('private')->delete($employee->pink_card_file_path);
            }
            $path = $request->file('pink_card_file')->store('employee_documents', 'private');
            $data['pink_card_file_path'] = $path;
        }
        if ($request->hasFile('insurance_attachment')) {
            if ($employee->insurance_attachment_path) {
                Storage::disk('private')->delete($employee->insurance_attachment_path);
            }
            $path = $request->file('insurance_attachment')->store('employee_documents', 'private');
            $data['insurance_attachment_path'] = $path;
        }

        // If 'password' field is filled, it's already in $data from validation.
        // If it's empty, we should not update the password.
        if (empty($data['password'])) {
            unset($data['password']); // Prevent updating with an empty value
        }

        $validated = $data;
        // --- V-6: Step 3: Define ALL 18 File Fields ---
        $fileFields = [
            'employeePhoto', 'insurance_document_path','insurance_document_path_private',
            'employee_doc_1', 'employee_doc_2', 'employee_doc_3', 'employee_doc_4',
            'employee_doc_5', 'employee_doc_6', 'employee_doc_7', 'employee_doc_8',
            'employee_doc_9', 'employee_doc_10', 'employee_doc_11', 'employee_doc_12'
        ];

        // --- V-6: Step 4: Unified File Deletion Loop (FIX) ---
        foreach ($fileFields as $field) {
            if ($request->has('remove_' . $field)) {
                if ($employee->{$field} && Storage::disk('public')->exists($employee->{$field})) {
                    Storage::disk('public')->delete($employee->{$field});
                }
                $validated[$field] = null;
            }
        }

        // --- V-6: Step 5: Unified File Upload/Update Loop ---
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                if ($employee->{$field} && Storage::disk('public')->exists($employee->{$field})) {
                    Storage::disk('public')->delete($employee->{$field});
                }
                $file = $request->file($field);
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs("employee_files/{$employee->employer_id}", $filename, 'public');
                $validated[$field] = $path;
            }
        }

        // --- V6: Step 6: Update Employee ---
        $employee->update($validated);

        return redirect($request->input('_previous', route('employees.index')))
            ->with('success', 'Employee updated successfully.');
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
                  ->orWhere('pinkCardNo', 'like', $searchTerm)
                  ->orWhere('employeeWorkPermit', 'like', $searchTerm)
                  ->orWhereHas('employer', function ($employerQuery) use ($searchTerm) {
                      $employerQuery->where('employerNameTh', 'like', $searchTerm)
                                    ->orWhere('employerNameEn', 'like', $searchTerm);
                  });
            });
        }

        if ($request->filled('work_permit_expiry_date')) {
            $query->whereDate('workPermitExpiryDate', $request->input('work_permit_expiry_date'));
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

        if ($request->filled('passport_type')) {
            $passportType = $request->input('passport_type');
            $query->where(function ($q) use ($passportType) {
                $q->where('passportType', $passportType)
                    ->orWhere('passport_type_cambodia', $passportType);
            });
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

    public function historyIndex(Request $request)
    {
        $this->authorize('view-employees');

        $query = Employee::query()->whereNotNull('terminated_at');

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', $searchTerm)
                  ->orWhere('employeeNameEn', 'like', $searchTerm)
                  ->orWhere('employeePassport', 'like', $searchTerm)
                  ->orWhere('pinkCardNo', 'like', $searchTerm)
                  ->orWhere('employeeWorkPermit', 'like', $searchTerm)
                  ->orWhereHas('employer', function ($employerQuery) use ($searchTerm) {
                      $employerQuery->where('employerNameTh', 'like', $searchTerm)
                                    ->orWhere('employerNameEn', 'like', $searchTerm);
                  });
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

        if ($request->filled('passport_type')) {
            $passportType = $request->input('passport_type');
            $query->where(function ($q) use ($passportType) {
                $q->where('passportType', $passportType)
                    ->orWhere('passport_type_cambodia', $passportType);
            });
        }

        $totalEmployees = (clone $query)->count();

        $perPageOptions = [25, 50, 100];
        $currentPerPage = $request->input('per_page', 25);

        $employees = $query->with('employer')->latest('terminated_at')->paginate($currentPerPage)->withQueryString();

        return view('employees.history', compact(
            'employees',
            'totalEmployees',
            'perPageOptions',
            'currentPerPage'
        ))->with('currentView', $request->input('view', 'card'));
    }

    /**
     * Serve a private document for a given employee.
     *
     * @param  \App\Models\Employee  $employee
     * @param  string  $field
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
     */
    public function serveDocument(Employee $employee, $field)
    {
        // Whitelist of allowed document fields to prevent security risks
        $allowedFields = [
            'employee_doc_1', 'employee_doc_2', 'employee_doc_3', 'employee_doc_4',
            'insurance_document_path_private',
            // Keep old fields for backward compatibility if needed
            'passport_file_path', 'visa_file_path', 'work_permit_file_path',
            'pink_card_file_path', 'insurance_attachment_path'
        ];

        if (!in_array($field, $allowedFields)) {
            abort(404, 'Document type not found.');
        }

        // Use a policy to check if the user can view the employee
        $this->authorize('view', $employee);

        $filePath = $employee->{$field};

        if (!$filePath || !Storage::disk('private')->exists($filePath)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('private')->response($filePath);
    }

    /**
     * Transfer an employee to a new employer.
     */
    public function transfer(Request $request, Employee $employee)
    {
        // Use the 'transfer' policy action.
        $this->authorize('transfer', $employee);

        $validated = $request->validate([
            'new_employer_id' => 'required|exists:employers,id',
        ]);

        // Ensure the employee is actually terminated before transferring
        if (is_null($employee->terminated_at)) {
            return response()->json(['success' => false, 'message' => 'Employee is not terminated and cannot be transferred.'], 422);
        }

        $employee->update([
            'employer_id' => $validated['new_employer_id'],
            'terminated_at' => null, // Make the employee active again
            'termination_reason' => null, // Clear the old reason
        ]);

        return response()->json(['success' => true, 'message' => 'Employee transferred successfully.']);
    }

    /**
     * Transfer multiple employees to a new employer.
     */
    public function bulkTransfer(Request $request)
    {
        // Authorize based on the policy for a generic Employee class,
        // as we don't have a specific instance for a bulk action.
        // The policy will fall back to checking the user's base permission.
        $this->authorize('transfer', Employee::class);

        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'new_employer_id' => 'required|exists:employers,id',
        ]);

        // Additional security: Explicitly check that the user owns ALL employees they are trying to transfer.
        // This prevents an employer from maliciously including another employer's employee ID in the request.
        if (!auth()->user()->can('manage-tickets')) { // Skip this check for admins/staff
            $employeesToTransfer = Employee::whereIn('id', $validated['employee_ids'])->get();
            foreach ($employeesToTransfer as $employee) {
                if ($employee->employer->user_id !== auth()->id()) {
                    return response()->json(['success' => false, 'message' => 'You are not authorized to transfer one or more of the selected employees.'], 403);
                }
            }
        }

        Employee::whereIn('id', $validated['employee_ids'])
            ->whereNotNull('terminated_at') // Ensure we only transfer terminated employees
            ->update([
                'employer_id' => $validated['new_employer_id'],
                'terminated_at' => null,
                'termination_reason' => null,
            ]);

        return response()->json(['success' => true, 'message' => 'Selected employees transferred successfully.']);
    }
}