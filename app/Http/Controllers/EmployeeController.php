<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Models\Employee;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\AddressFilterTrait;
use Illuminate\Validation\Rule;
use Symfony\Component\Process\Process;

class EmployeeController extends Controller
{
    use AddressFilterTrait;

    /**
     * Apply permission middleware to the controller.
     */
    public function __construct()
    {
        $this->middleware('permission:view-employees', ['only' => ['index', 'show']]);
        // Bulk edit methods must gate on edit-employees (not manage-tickets),
        // otherwise caretakers get through to a form that silently discards
        // their input when the runtime check inside bulkUpdate() bails.
        $this->middleware('permission:edit-employees', ['only' => ['edit', 'update', 'bulkEditSelectFields', 'bulkEditForm', 'bulkUpdate']]);
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

        $employerName = $employee->employer ? $employee->employer->getRawOriginal('employerNameTh') : 'N/A';

        $employee->update([
            'terminated_at' => $validated['termination_date'],
            'termination_reason' => $validated['termination_reason'],
        ]);

        ActivityLogHelper::logAction('terminate', 'แจ้งออกลูกจ้าง ' . $employee->getRawOriginal('employeeNameEn') . ' จากนายจ้าง ' . $employerName, Employee::class, $employee->id, [
            'employer_id' => $employee->employer_id,
            'employer_name' => $employerName,
            'termination_date' => $validated['termination_date'],
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

    $employerName = $employee->employer ? $employee->employer->getRawOriginal('employerNameTh') : 'N/A';

    $employee->update(['terminated_at' => null]);

    ActivityLogHelper::logAction('reinstate', 'คืนสภาพลูกจ้าง ' . $employee->getRawOriginal('employeeNameEn') . ' กลับเข้านายจ้าง ' . $employerName, Employee::class, $employee->id, [
        'employer_id' => $employee->employer_id,
        'employer_name' => $employerName,
    ]);

    return response()->json(['success' => 'Employee reinstated successfully.']);
}

    public function search(Request $request)
    {
        $searchTerm = $request->input("q");
        $query = Employee::query()
            ->whereNull("terminated_at")
            ->where(function($q) {
                $q->whereNotIn("status", ["registration_cancelled"])
                  ->orWhereNull("status");
            });

        // Filter by employer_id (for bulk import in Sales Lead)
        if ($request->filled('employer_id')) {
            $query->where('employer_id', $request->input('employer_id'));
        }

        if ($searchTerm) {
            $term = "%" . $searchTerm . "%";
            $query->where(function ($q) use ($term) {
                $q->where("employeeNameTh", "like", $term)
                  ->orWhere("employeeNameEn", "like", $term)
                  ->orWhere("name_suffix", "like", $term)
                  ->orWhere("employeePassport", "like", $term)
                  ->orWhere("pinkCardNo", "like", $term)
                  ->orWhere("employeeWorkPermit", "like", $term)
                  ->orWhere("employee_id_number", "like", $term)
                  ->orWhere("name_list_number", "like", $term)
                  ->orWhere("request_number", "like", $term)
                  ->orWhere("employer_employee_id", "like", $term);
            });
        }

        $limit = min((int) $request->input('limit', 30), 200);
        $employees = $query->select("id", "employeeNameEn", "employeeNameTh", "employeePassport", "employeeTitleEn", "employeePhoto", "name_suffix")
            ->limit($limit)
            ->get();

        // Add display text and photo URL for Select2/list usage
        $employees->each(function ($emp) {
            $emp->text = ($emp->employeeTitleEn ? $emp->employeeTitleEn . ' ' : '') . ($emp->employeeNameEn ?: $emp->employeeNameTh ?: 'ID:'.$emp->id)
                . ($emp->employeePassport ? ' (PP: '.$emp->employeePassport.')' : '');
            $emp->photo_url = $emp->employeePhoto ? asset('storage/' . $emp->employeePhoto) : null;
        });

        return response()->json($employees);
    }

    public function index(Request $request)
{
    // Filter out 'registration_cancelled' statuses so they don't appear until finalized
    // Note: 'registration_pending' is now INCLUDED as per user request to show Resolution employees in the main list.
    $query = Employee::query()
        ->whereNull('terminated_at')
        ->where(function($q) {
            $q->whereNotIn('status', ['registration_cancelled'])
              ->orWhereNull('status');
        });

    // --- START: ADDED FILTERING LOGIC ---
    if ($request->filled('search')) {
        $rawSearch = trim($request->input('search'));

        // Support ID:123 format for direct ID lookup
        if (preg_match('/^ID:\s*(\d+)$/i', $rawSearch, $matches)) {
            $query->where('id', (int) $matches[1]);
        } else {
            $searchTerm = '%' . $rawSearch . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', $searchTerm)
                  ->orWhere('employeeNameEn', 'like', $searchTerm)
                  ->orWhere('name_suffix', 'like', $searchTerm)
                  ->orWhere('employeePassport', 'like', $searchTerm)
                  ->orWhere('pinkCardNo', 'like', $searchTerm)
                  ->orWhere('employeeWorkPermit', 'like', $searchTerm)
                  ->orWhere('employee_id_number', 'like', $searchTerm)
                  ->orWhere('name_list_number', 'like', $searchTerm)
                  ->orWhere('request_number', 'like', $searchTerm)
                  ->orWhere('employer_employee_id', 'like', $searchTerm)
                  ->orWhereHas('employer', function ($employerQuery) use ($searchTerm) {
                      $employerQuery->where('employerNameTh', 'like', $searchTerm)
                                    ->orWhere('employerNameEn', 'like', $searchTerm)
                                    ->orWhere('name_suffix', 'like', $searchTerm)
                                    ->orWhere(function($addrQ) use ($searchTerm) {
                                        $addrQ->filterByAddress($searchTerm);
                                    });
                  });
            });
        }
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

    if ($request->filled('insurance_type')) {
        $insuranceType = $request->input('insurance_type');
        if ($insuranceType === 'none') {
            $query->where(function ($q) {
                $q->whereNull('insurance_type')->orWhere('insurance_type', '=', '');
            });
        } else {
            $query->where('insurance_type', $insuranceType);
        }
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

    if ($request->filled('passport_type_myanmar')) {
        $query->where('passportType', $request->input('passport_type_myanmar'))
              ->where('employeeNationality', 'เมียนมา');
    }

    if ($request->filled('passport_type_cambodia')) {
        $query->where('passport_type_cambodia', $request->input('passport_type_cambodia'))
              ->where('employeeNationality', 'กัมพูชา');
    }

    if ($request->filled('passport_status')) {
        if ($request->input('passport_status') === 'has_passport') {
            $query->where(function ($q) {
                $q->whereNotNull('employeePassport')->where('employeePassport', '!=', '');
            });
        } elseif ($request->input('passport_status') === 'no_passport') {
            $query->where(function ($q) {
                $q->whereNull('employeePassport')->orWhere('employeePassport', '=', '');
            });
        }
    }

    if ($request->filled('visa_status')) {
        if ($request->input('visa_status') === 'has_visa') {
            $query->where(function ($q) {
                $q->whereNotNull('visaExpiryDate')->where('visaExpiryDate', '!=', '');
            });
        } elseif ($request->input('visa_status') === 'no_visa') {
            $query->where(function ($q) {
                $q->whereNull('visaExpiryDate')->orWhere('visaExpiryDate', '=', '');
            });
        }
    }

    if ($request->filled('bank_account_status')) {
        $status = $request->input('bank_account_status');
        if ($status === 'opened') {
            // Opened: Both bank name and account number must be present
            $query->whereNotNull('bank_name')->where('bank_name', '!=', '')
                  ->whereNotNull('bank_account_number')->where('bank_account_number', '!=', '');
        } elseif ($status === 'not_opened') {
            // Not Opened: Either one is missing
            $query->where(function ($q) {
                $q->whereNull('bank_name')
                  ->orWhere('bank_name', '')
                  ->orWhereNull('bank_account_number')
                  ->orWhere('bank_account_number', '');
            });
        }
    }
    // --- END: ADDED FILTERING LOGIC ---

    // Address options (before address filtering)
    $addressOptions = $this->getAddressOptions($query, 'employer_id');

    // Apply address filters
    $query = $this->applyAddressFilters($query, $request, 'employer');

    $totalEmployees = (clone $query)->count();

    $perPageOptions = [25, 50, 100]; // Defined options here
    $currentPerPage = $request->input('per_page', 25);

    $employees = $query->with(['employer.addresses', 'teams.group'])->latest()->paginate($currentPerPage)->withQueryString(); // Added withQueryString() to preserve filters on pagination

    return view('employees.index', compact(
        'employees',
        'totalEmployees',
        'perPageOptions',
        'currentPerPage',
        'addressOptions'
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
            'employeeTitleTh' => 'nullable|string|max:255',
            'employeeNameTh' => 'nullable|string|max:255',
            'employeeTitleEn' => 'nullable|string|max:255',
            'employeeNameEn' => 'required|string|max:255',
            'name_suffix' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'height' => 'nullable|string|max:255',
            'weight' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'employeeGender' => 'nullable|string|max:255',
            'employeeDob' => 'nullable|date',
            'employeeAge' => 'nullable|integer',
            'employeePhone' => 'nullable|string|max:255',
            'employeeNationality' => 'nullable|string|max:255',
            'passport_type_cambodia' => 'nullable|string|max:255',
            'employeePassport' => 'nullable|string|max:255',
            'passport_issue_place' => 'nullable|string|max:255',
            'passport_issue_date' => 'nullable|date',
            'passportExpiryDate' => 'nullable|date',
            'pinkCardNo' => 'nullable|string|max:255',
            'visaType' => 'nullable|string|max:255',
            'visa_issue_place' => 'nullable|string|max:255',
            'visaEndorsementDate' => 'nullable|date',
            'visaEndorsementNo' => 'nullable|string|max:50',
            'visaExpiryDate' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'job_description' => 'nullable|string',
            'outsource_code' => 'nullable|string|max:255',
            'employee_reference_id' => 'nullable|string|max:255',
            'startDate' => 'nullable|date',
            'employeeWorkPermit' => 'nullable|string|max:255',
            'workPermitIssueDate' => 'nullable|date',
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
            'sso_issue_date' => 'nullable|date',
            'sso_expiry_date' => 'nullable|date',
            'insurance_detail_hospital' => 'nullable|string|max:255',
            'insurance_detail_private' => 'nullable|string|max:255',
            'insurance_expiry_date_private' => 'nullable|string|max:255',
            'insurance_expiry_date_hospital' => 'nullable|string|max:255',
            'insurance_detail_social' => 'nullable|string|max:255',
            'medical_hospital_name' => 'nullable|string|max:255',
            'outsource_code' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'employeeEmail' => 'nullable|string|max:255|unique:employees,email',
            'employeePassword' => 'nullable|string|min:8',
            'other_doc_1_desc' => 'nullable|string|max:255',
            'other_doc_2_desc' => 'nullable|string|max:255',
            'other_doc_3_desc' => 'nullable|string|max:255',
            'other_doc_4_desc' => 'nullable|string|max:255',
            'other_doc_5_desc' => 'nullable|string|max:255',
            'other_doc_6_desc' => 'nullable|string|max:255',
            'other_doc_7_desc' => 'nullable|string|max:255',
            'other_doc_8_desc' => 'nullable|string|max:255',
            'other_doc_9_desc' => 'nullable|string|max:255',
            'other_doc_10_desc' => 'nullable|string|max:255',
            'employeePhoto' => 'nullable|image|max:2048',
            'insurance_document_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'insurance_document_path_private' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'medical_certificate_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
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
            'employee_doc_13' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_14' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_15' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_16' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_17' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_18' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
        ]);

        // --- V6 Step 1.5: Map insurance data to correct model properties ---
        $validated['insuranceType'] = $validated['insurance_type'] ?? null;

        if ($validated['insuranceType'] === 'ประกันสังคม') {
            $validated['socialSecurityNumber'] = $validated['social_security_number'] ?? null;
            $validated['hospitalName'] = $validated['insurance_detail_social'] ?? null;
            $validated['sso_issue_date'] = $validated['sso_issue_date'] ?? null;
            $validated['sso_expiry_date'] = $validated['sso_expiry_date'] ?? null;
            $validated['insuranceCompany'] = null;
            $validated['insuranceExpiryDate'] = null;
        } elseif ($validated['insuranceType'] === 'ประกันเอกชน') {
            $validated['insuranceCompany'] = $validated['insurance_detail_private'] ?? null;
            $validated['insuranceExpiryDate'] = $validated['insurance_expiry_date_private'] ?? null;
            $validated['socialSecurityNumber'] = null;
            $validated['hospitalName'] = null;
            $validated['sso_issue_date'] = null;
            $validated['sso_expiry_date'] = null;
        } elseif ($validated['insuranceType'] === 'ประกันโรงพยาบาล') {
            $validated['hospitalName'] = $validated['insurance_detail_hospital'] ?? null;
            $validated['insuranceExpiryDate'] = $validated['insurance_expiry_date_hospital'] ?? null;
            $validated['socialSecurityNumber'] = null;
            $validated['insuranceCompany'] = null;
            $validated['sso_issue_date'] = null;
            $validated['sso_expiry_date'] = null;
        } else {
            $validated['socialSecurityNumber'] = null;
            $validated['hospitalName'] = null;
            $validated['insuranceCompany'] = null;
            $validated['insuranceExpiryDate'] = null;
            $validated['sso_issue_date'] = null;
            $validated['sso_expiry_date'] = null;
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
            'employeePhoto', 'insurance_document_path','insurance_document_path_private', 'medical_certificate_path',
            'employee_doc_1', 'employee_doc_2', 'employee_doc_3', 'employee_doc_4',
            'employee_doc_5', 'employee_doc_6', 'employee_doc_7', 'employee_doc_8',
            'employee_doc_9', 'employee_doc_10', 'employee_doc_11', 'employee_doc_12',
            'employee_doc_13', 'employee_doc_14', 'employee_doc_15', 'employee_doc_16',
            'employee_doc_17', 'employee_doc_18'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs("employee_files/{$validated['employer_id']}", $filename, 'public');
                $validated[$field] = $path;
            }
        }

        // Apply SuperAdmin defaults for Other Document Descriptions if not provided
        $defaults = \App\Models\SuperAdminSetting::where('key', 'like', 'employee_other_%_desc')->pluck('value', 'key');
        for ($i = 1; $i <= 10; $i++) {
            $descField = "other_doc_{$i}_desc";
            $settingKey = "employee_other_{$i}_desc";
            if (!isset($validated[$descField]) || trim($validated[$descField]) === '') {
                if (isset($defaults[$settingKey])) {
                    $validated[$descField] = $defaults[$settingKey];
                }
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

    public function enhancePhoto(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120', // 5MB max
        ]);

        $file = $request->file('image');
        $filename = 'enhance_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();

        // Store in public disk to be accessible
        $path = $file->storeAs('temp/enhance', $filename, 'public');
        $fullPath = Storage::disk('public')->path($path);

        $outputFilename = 'enhanced_' . $filename;
        $outputPath = 'temp/enhance/' . $outputFilename;
        $fullOutputPath = Storage::disk('public')->path($outputPath);

        // Run Python Script
        $scriptPath = public_path('py/face_enhance.py');
        if (!file_exists($scriptPath)) {
            return response()->json(['error' => 'Enhancement script not found.'], 500);
        }

        $pythonCmd = $this->getPythonCommand();

        if (!$pythonCmd) {
            return response()->json(['error' => "Python is not installed. Please install Python to use this feature. See SETUP_PYTHON.md for details."], 500);
        }

        // Use detected python command
        $env = [
            'PATH' => getenv('PATH'),
            'TEMP' => getenv('TEMP'),
            'TMP' => getenv('TMP'),
        ];

        // Pass necessary environment variables for Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $env['SystemRoot'] = getenv('SystemRoot') ?: 'C:\\Windows';
            $env['COMSPEC'] = getenv('COMSPEC') ?: 'C:\\Windows\\System32\\cmd.exe';
        }

        $process = new Process([$pythonCmd, $scriptPath, '--input', $fullPath, '--output', $fullOutputPath], null, $env);
        $process->setTimeout(120); // Increased timeout to 120s for model download/processing
        $process->run();

        // Check if successful
        if (!$process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput();
            \Illuminate\Support\Facades\Log::error('Face Enhancement Failed: ' . $errorOutput);

            // Check for common errors
            if (empty($errorOutput) || str_contains($errorOutput, 'not found') || str_contains($errorOutput, 'is not recognized')) {
                return response()->json(['error' => "Python execution failed. Please check your installation. (Command tried: $pythonCmd)"], 500);
            }

            if (str_contains($errorOutput, 'ModuleNotFoundError') || str_contains($errorOutput, 'ImportError') || str_contains($errorOutput, 'No module named')) {
                return response()->json(['error' => "Python dependencies are missing. Please run `scripts/install_python_deps.bat` (Windows) or `sh scripts/install_python_deps.sh` (Linux/Mac)."], 500);
            }

            // Fallback: If script fails but we have input, check if we can return useful error
            return response()->json(['error' => 'Processing error: ' . $errorOutput], 500);
        }

        if (!file_exists($fullOutputPath)) {
             return response()->json(['error' => 'Output file not generated.'], 500);
        }

        return response()->json([
            'success' => true,
            'url' => Storage::disk('public')->url($outputPath)
        ]);
    }

    private function getPythonCommand()
    {
        // 1. Check Config
        $configPath = config('services.python.path');
        if ($configPath) {
            return $configPath;
        }

        // 2. Try standard commands
        $commands = ['python3', 'python', 'py'];
        foreach ($commands as $cmd) {
            $process = new Process([$cmd, '--version']);
            $process->run();
            if ($process->isSuccessful()) {
                return $cmd;
            }
        }

        // Default fallback
        return null;
    }

    public function show(Employee $employee)
    {
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $employers = \App\Models\Employer::orderBy('employerNameTh')->get();

        // Fetch missing fields to highlight in the view
        $missingFields = \App\Helpers\CompletenessHelper::getMissingFields($employee);

        $returnUrl = request('return_url');

        if (request()->ajax()) {
            return view('employees.partials._edit_form', compact('employee', 'employers', 'missingFields', 'returnUrl'));
        }

        return view('employees.edit', compact('employee', 'employers', 'missingFields', 'returnUrl'));
    }

    public function updateMenuFields(Request $request, Employee $employee)
    {
        $request->validate([
            'type' => 'required|in:registration,renewal',
            'request_number' => 'nullable|string|max:255',
        ]);

        if ($request->type === 'registration') {
            $employee->update(['registration_request_number' => $request->request_number]);
        } elseif ($request->type === 'renewal') {
            $employee->update(['renewal_request_number' => $request->request_number]);
        }

        return response()->json(['success' => true]);
    }

    public function update(Request $request, Employee $employee)
    {
        // --- V6: Step 1: Validate ALL text/date data (new and old) ---
        $validated = $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'passportType' => 'nullable|string|max:255',
            'employeeTitleTh' => 'nullable|string|max:255',
            'employeeNameTh' => 'nullable|string|max:255',
            'employeeTitleEn' => 'nullable|string|max:255',
            'employeeNameEn' => 'required|string|max:255',
            'name_suffix' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'height' => 'nullable|string|max:255',
            'weight' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'employeeGender' => 'nullable|string|max:255',
            'employeeDob' => 'nullable|date',
            'employeeAge' => 'nullable|integer',
            'employeePhone' => 'nullable|string|max:255',
            'employeeNationality' => 'nullable|string|max:255',
            'passport_type_cambodia' => 'nullable|string|max:255',
            'employeePassport' => 'nullable|string|max:255',
            'passport_issue_place' => 'nullable|string|max:255',
            'passport_issue_date' => 'nullable|date',
            'passportExpiryDate' => 'nullable|date',
            'pinkCardNo' => 'nullable|string|max:255',
            'visaType' => 'nullable|string|max:255',
            'visa_issue_place' => 'nullable|string|max:255',
            'visaEndorsementDate' => 'nullable|date',
            'visaEndorsementNo' => 'nullable|string|max:50',
            'visaExpiryDate' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'job_description' => 'nullable|string',
            'outsource_code' => 'nullable|string|max:255',
            'employee_reference_id' => 'nullable|string|max:255',
            'startDate' => 'nullable|date',
            'employeeWorkPermit' => 'nullable|string|max:255',
            'workPermitIssueDate' => 'nullable|date',
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
            'sso_issue_date' => 'nullable|date',
            'sso_expiry_date' => 'nullable|date',
            'insurance_detail_hospital' => 'nullable|string|max:255',
            'insurance_expiry_date_private' => 'nullable|string|max:255',
            'insurance_expiry_date_hospital' => 'nullable|string|max:255',
            'insurance_detail_private' => 'nullable|string|max:255',
            'insurance_detail_social' => 'nullable|string|max:255',
            'medical_hospital_name' => 'nullable|string|max:255',
            'outsource_code' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'employeeEmail' => 'nullable|string|max:255|unique:employees,email,' . $employee->id,
            'password' => 'nullable|string|min:8',
            'other_doc_1_desc' => 'nullable|string|max:255',
            'other_doc_2_desc' => 'nullable|string|max:255',
            'other_doc_3_desc' => 'nullable|string|max:255',
            'other_doc_4_desc' => 'nullable|string|max:255',
            'other_doc_5_desc' => 'nullable|string|max:255',
            'other_doc_6_desc' => 'nullable|string|max:255',
            'other_doc_7_desc' => 'nullable|string|max:255',
            'other_doc_8_desc' => 'nullable|string|max:255',
            'other_doc_9_desc' => 'nullable|string|max:255',
            'other_doc_10_desc' => 'nullable|string|max:255',
            'employeePhoto' => 'nullable|image|max:2048',
            'insurance_document_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'insurance_document_path_private' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'medical_certificate_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
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
            'employee_doc_13' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_14' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_15' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_16' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_17' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_18' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
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
            $validated['sso_issue_date'] = $validated['sso_issue_date'] ?? null;
            $validated['sso_expiry_date'] = $validated['sso_expiry_date'] ?? null;
            $validated['insuranceCompany'] = null;
            $validated['insuranceExpiryDate'] = null;
        } elseif ($validated['insuranceType'] === 'ประกันเอกชน') {
            $validated['insuranceCompany'] = $validated['insurance_detail_private'] ?? null;
            $validated['insuranceExpiryDate'] = $validated['insurance_expiry_date_private'] ?? null;
            $validated['socialSecurityNumber'] = null;
            $validated['hospitalName'] = null;
            $validated['sso_issue_date'] = null;
            $validated['sso_expiry_date'] = null;
        } elseif ($validated['insuranceType'] === 'ประกันโรงพยาบาล') {
            $validated['hospitalName'] = $validated['insurance_detail_hospital'] ?? null;
            $validated['insuranceExpiryDate'] = $validated['insurance_expiry_date_hospital'] ?? null;
            $validated['socialSecurityNumber'] = null;
            $validated['insuranceCompany'] = null;
            $validated['sso_issue_date'] = null;
            $validated['sso_expiry_date'] = null;
        } else {
            // For 'None' or other types, null out all specific fields
            $validated['socialSecurityNumber'] = null;
            $validated['hospitalName'] = null;
            $validated['insuranceCompany'] = null;
            $validated['insuranceExpiryDate'] = null;
            $validated['sso_issue_date'] = null;
            $validated['sso_expiry_date'] = null;
        }


        // --- V6: Step 2: Handle email and password mapping & hashing ---
        $data = $validated;

        // Fix: Only update email if 'employeeEmail' is present in the request
        // Otherwise, forms that do not contain the email field (like quick inline updates) will clear it out.
        if (array_key_exists('employeeEmail', $validated)) {
            $data['email'] = $validated['employeeEmail'];
            unset($data['employeeEmail']);
        } else {
            unset($data['employeeEmail']);
        }

        // Helper to map and upload file
        // REVERTED: Sensitive files are now stored in 'public' disk to prevent 403 Forbidden errors in views.
        $handleFileUpload = function ($fileInputName, $dbColumnName) use ($request, &$data, $employee) {
            if ($request->hasFile($fileInputName)) {
                $disk = 'public';
                $folder = "employee_files/{$employee->employer_id}";

                // 2. Cleanup: Delete old file from BOTH public and private to ensure clean migration
                if ($employee->$dbColumnName) {
                    if (Storage::disk('public')->exists($employee->$dbColumnName)) {
                        Storage::disk('public')->delete($employee->$dbColumnName);
                    }
                    if (Storage::disk('private')->exists($employee->$dbColumnName)) {
                        Storage::disk('private')->delete($employee->$dbColumnName);
                    }
                }

                // 3. Upload new file
                $file = $request->file($fileInputName);
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs($folder, $filename, $disk);

                $data[$dbColumnName] = $path;
            }
        };

        // Map legacy named inputs to actual DB columns (employee_doc_X)
        $handleFileUpload('passport_file', 'employee_doc_1');
        $handleFileUpload('visa_file', 'employee_doc_2');
        $handleFileUpload('work_permit_file', 'employee_doc_3');
        $handleFileUpload('pink_card_file', 'employee_doc_4');
        $handleFileUpload('insurance_attachment', 'insurance_document_path');

        // If 'password' field is filled, it's already in $data from validation.
        // If it's empty, we should not update the password.
        if (empty($data['password'])) {
            unset($data['password']); // Prevent updating with an empty value
        }

        // Prevent outsource_code from being cleared out by inline updates
        if (!array_key_exists('outsource_code', $request->all())) {
            unset($data['outsource_code']);
        }

        $validated = $data;
        // --- V-6: Step 3: Define ALL 18 File Fields ---
        $fileFields = [
            'employeePhoto', 'insurance_document_path','insurance_document_path_private', 'medical_certificate_path',
            'employee_doc_1', 'employee_doc_2', 'employee_doc_3', 'employee_doc_4',
            'employee_doc_5', 'employee_doc_6', 'employee_doc_7', 'employee_doc_8',
            'employee_doc_9', 'employee_doc_10', 'employee_doc_11', 'employee_doc_12',
            'employee_doc_13', 'employee_doc_14', 'employee_doc_15', 'employee_doc_16',
            'employee_doc_17', 'employee_doc_18'
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

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'employee' => $employee->fresh(['employer']) // Return fresh data for frontend update
            ]);
        }

        // Determine redirect target
        $redirectUrl = $request->input('_previous', route('employees.index'));

        // Ensure the anchor is present for highlighting
        if (strpos($redirectUrl, '#') === false) {
            $redirectUrl .= '#employee-card-' . $employee->id;
        }

        return redirect($redirectUrl)
            ->with('success', 'Employee updated successfully.')
            ->with('highlight_employee', $employee->id); // For legacy highlighting logic
    }

    /**
     * Soft duplicate check for identity fields that legitimately can repeat
     * in rare real-world cases (reissued documents) but almost always
     * shouldn't — used by resources/js/duplicate-check.js to warn (not
     * block) before save. Bypasses the employerTenancy scope so a worker
     * previously registered under a different employer is still caught —
     * that's the exact scenario this feature exists for (a terminated
     * worker re-hired and accidentally re-entered as a new record).
     * `email` is flagged 'blocking' since it still has a real DB unique
     * constraint (see store()/update()) — everything else is warn-only.
     */
    public function checkDuplicate(Request $request)
    {
        $fieldMap = [
            'employeePassport' => ['column' => 'employeePassport', 'label' => 'เลขพาสปอร์ต', 'blocking' => false],
            'employeeWorkPermit' => ['column' => 'employeeWorkPermit', 'label' => 'เลขที่ใบอนุญาตทำงาน', 'blocking' => false],
            'pinkCardNo' => ['column' => 'pinkCardNo', 'label' => 'เลขบัตรชมพู', 'blocking' => false],
            'employee_id_number' => ['column' => 'employee_id_number', 'label' => 'เลขประจำตัว', 'blocking' => false],
            'employeeEmail' => ['column' => 'email', 'label' => 'อีเมล', 'blocking' => true],
        ];

        $excludeId = $request->input('exclude_id');
        $duplicates = [];

        foreach ($fieldMap as $inputKey => $meta) {
            $value = trim((string) $request->input($inputKey, ''));
            if ($value === '') {
                continue;
            }

            $match = Employee::withoutGlobalScope('employerTenancy')
                ->with('employer')
                ->where($meta['column'], $value)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->first();

            if ($match) {
                $duplicates[] = [
                    'field' => $inputKey,
                    'label' => $meta['label'],
                    'value' => $value,
                    'blocking' => $meta['blocking'],
                    'record' => [
                        'id' => $match->id,
                        'name' => $match->employeeNameTh ?: $match->employeeNameEn,
                        'employer_name' => $match->employer->employerNameTh ?? '-',
                        'photo_url' => $match->photo_url,
                        'edit_url' => route('employees.edit', $match->id),
                        'terminated' => (bool) $match->terminated_at,
                    ],
                ];
            }
        }

        return response()->json(['duplicates' => $duplicates]);
    }

    public function locate(Employee $employee)
    {
        // GPS Logic: Always go to Employer Edit page and locate the employee card

        $perPage = 10; // Default pagination size in EmployerController

        // Base query matching EmployerController@edit default view
        $query = $employee->employer->employees()
            ->whereNull('terminated_at')
            ->where(function($q) {
                $q->whereNotIn('status', ['registration_cancelled'])
                  ->orWhereNull('status');
            });

        // Count employees appearing BEFORE the target based on sorting: created_at DESC, id DESC
        // In DESC sort, "before" means (created_at > target) OR (created_at == target AND id > target)
        $position = (clone $query)->where(function ($q) use ($employee) {
            $q->where('created_at', '>', $employee->created_at)
              ->orWhere(function ($subQ) use ($employee) {
                  $subQ->where('created_at', $employee->created_at)
                       ->where('id', '>', $employee->id);
              });
        })->count();

        $page = floor($position / $perPage) + 1;

        // Redirect to Employer Structure (Employer Edit Page) with calculated page
        return redirect()->route('employers.edit', [
                'employer' => $employee->employer_id,
                'page' => $page
            ])
            ->with('highlight_employee', $employee->id);
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        if (request()->ajax() || request()->wantsJson()) {
             return response()->json(['success' => true, 'message' => 'Employee moved to trash successfully.']);
        }

        return redirect()->back()->with('success', 'Employee moved to trash successfully.');
    }

    public function export(Request $request)
    {
        $this->authorize('view-employees');

        $isHistoryExport = $request->has('history');

        ActivityLogHelper::logAction('export', 'ส่งออกข้อมูลลูกจ้าง (' . ($isHistoryExport ? 'ประวัติ' : 'ปัจจุบัน') . ') Excel', null, null, [
            'type' => $isHistoryExport ? 'history' : 'active',
        ]);

        // Determine scope: history (terminated) or active

        if ($isHistoryExport) {
            $query = Employee::query()->whereNotNull('terminated_at');
        } else {
            $query = Employee::query()
                ->whereNull('terminated_at')
                ->where(function($q) {
                    // Note: 'registration_pending' is now INCLUDED as per user request.
                    $q->whereNotIn('status', ['registration_cancelled'])
                      ->orWhereNull('status');
                });
        }

        // Reuse the same filtering logic from the index method
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', $searchTerm)
                  ->orWhere('employeeNameEn', 'like', $searchTerm)
                  ->orWhere('name_suffix', 'like', $searchTerm)
                  ->orWhere('employeePassport', 'like', $searchTerm)
                  ->orWhere('pinkCardNo', 'like', $searchTerm)
                  ->orWhere('employeeWorkPermit', 'like', $searchTerm)
                  ->orWhere('employee_id_number', 'like', $searchTerm)
                  ->orWhere('name_list_number', 'like', $searchTerm)
                  ->orWhere('request_number', 'like', $searchTerm)
                  ->orWhere('employer_employee_id', 'like', $searchTerm)
                  ->orWhereHas('employer', function ($employerQuery) use ($searchTerm) {
                      $employerQuery->where('employerNameTh', 'like', $searchTerm)
                                    ->orWhere('employerNameEn', 'like', $searchTerm)
                                    ->orWhere('name_suffix', 'like', $searchTerm)
                                    ->orWhere(function($addrQ) use ($searchTerm) {
                                        $addrQ->filterByAddress($searchTerm);
                                    });
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

        if ($request->filled('insurance_type')) {
            $insuranceType = $request->input('insurance_type');
            if ($insuranceType === 'none') {
                $query->where(function ($q) {
                    $q->whereNull('insurance_type')->orWhere('insurance_type', '=', '');
                });
            } else {
                $query->where('insurance_type', $insuranceType);
            }
        }

        if ($request->filled('pink_card')) {
            if ($request->input('pink_card') === 'yes') {
                $query->where(fn($q) => $q->whereNotNull('pinkCardNo')->where('pinkCardNo', '!=', ''));
            } elseif ($request->input('pink_card') === 'no') {
                $query->where(fn($q) => $q->whereNull('pinkCardNo')->orWhere('pinkCardNo', '=', ''));
            }
        }

        if ($request->filled('passport_status')) {
            if ($request->input('passport_status') === 'has_passport') {
                $query->where(function ($q) {
                    $q->whereNotNull('employeePassport')->where('employeePassport', '!=', '');
                });
            } elseif ($request->input('passport_status') === 'no_passport') {
                $query->where(function ($q) {
                    $q->whereNull('employeePassport')->orWhere('employeePassport', '=', '');
                });
            }
        }


        if ($request->filled('passport_type_myanmar')) {
            $query->where('passportType', $request->input('passport_type_myanmar'))
                  ->where('employeeNationality', 'เมียนมา');
        }

        if ($request->filled('passport_type_cambodia')) {
            $query->where('passport_type_cambodia', $request->input('passport_type_cambodia'))
                  ->where('employeeNationality', 'กัมพูชา');
        }

        $employees = $query->with('employer')->get();

        $exportType = $isHistoryExport ? 'history' : 'active';
        $fileName = "employees_{$exportType}_export_" . date('Y-m-d') . '.xlsx';

        $columns = [
            'Employer Name', 'Employee Name (TH)', 'Employee Name (EN)',
            'Nationality', 'Passport No', 'Passport Expiry',
            'Work Permit No', 'Work Permit Issue Date', 'Work Permit Expiry',
            'Visa Endorsement Date', 'Visa Endorsement No', 'Visa Expiry',
            '90 Day Report', 'Pink Card No'
        ];

        if ($isHistoryExport) {
            $columns[] = 'Terminated At';
            $columns[] = 'Termination Reason';
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Write Header
        $columnIndex = 1;
        foreach ($columns as $col) {
            $cell = $sheet->getCell([$columnIndex, 1]);
            $cell->setValue($col);
            $sheet->getStyle([$columnIndex, 1])->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF2F2F2'], // Light gray
                ],
            ]);
            $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
            $columnIndex++;
        }

        // Write Data Rows
        $currentRow = 2;
        $textColumns = ['Passport No', 'Work Permit No', 'Pink Card No', 'Visa Endorsement No'];

        foreach ($employees as $employee) {
            $row = [
                'Employer Name'      => $employee->employer->getRawOriginal('employerNameTh') ?? 'N/A',
                'Employee Name (TH)' => $employee->employeeNameTh,
                'Employee Name (EN)' => $employee->getRawOriginal('employeeNameEn'),
                'Nationality'        => $employee->employeeNationality,
                'Passport No'        => $employee->employeePassport,
                'Passport Expiry'    => $employee->passportExpiryDate ? \Carbon\Carbon::parse($employee->passportExpiryDate)->format('d/m/Y') : '-',
                'Work Permit No'     => $employee->employeeWorkPermit,
                'Work Permit Issue Date' => $employee->workPermitIssueDate ? \Carbon\Carbon::parse($employee->workPermitIssueDate)->format('d/m/Y') : '-',
                'Work Permit Expiry' => $employee->workPermitExpiryDate ? \Carbon\Carbon::parse($employee->workPermitExpiryDate)->format('d/m/Y') : '-',
                'Visa Endorsement Date' => $employee->visaEndorsementDate ? \Carbon\Carbon::parse($employee->visaEndorsementDate)->format('d/m/Y') : '-',
                'Visa Endorsement No' => $employee->visaEndorsementNo ?? '-',
                'Visa Expiry'        => $employee->visaExpiryDate ? \Carbon\Carbon::parse($employee->visaExpiryDate)->format('d/m/Y') : '-',
                '90 Day Report'      => $employee->ninetyDayReportDate ? \Carbon\Carbon::parse($employee->ninetyDayReportDate)->format('d/m/Y') : '-',
                'Pink Card No'       => $employee->pinkCardNo,
            ];

            if ($isHistoryExport) {
                $row['Terminated At'] = $employee->terminated_at ? \Carbon\Carbon::parse($employee->terminated_at)->format('d/m/Y H:i:s') : '-';
                $row['Termination Reason'] = $employee->termination_reason;
            }

            $columnIndex = 1;
            foreach ($columns as $col) {
                $val = $row[$col] ?? '-';
                $cell = $sheet->getCell([$columnIndex, $currentRow]);

                if (in_array($col, $textColumns) && $val !== '' && $val !== '-') {
                    $cell->setValueExplicit($val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $cell->setValue($val);
                }

                $sheet->getStyle([$columnIndex, $currentRow])->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ]
                ]);
                $columnIndex++;
            }
            $currentRow++;
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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
                  ->orWhere('name_suffix', 'like', $searchTerm)
                  ->orWhere('employeePassport', 'like', $searchTerm)
                  ->orWhere('pinkCardNo', 'like', $searchTerm)
                  ->orWhere('employeeWorkPermit', 'like', $searchTerm)
                  ->orWhere('employee_id_number', 'like', $searchTerm)
                  ->orWhere('name_list_number', 'like', $searchTerm)
              ->orWhere('request_number', 'like', $searchTerm)
              ->orWhere('employer_employee_id', 'like', $searchTerm)
                  ->orWhereHas('employer', function ($employerQuery) use ($searchTerm) {
                      $employerQuery->where('employerNameTh', 'like', $searchTerm)
                                    ->orWhere('employerNameEn', 'like', $searchTerm)
                                    ->orWhere('name_suffix', 'like', $searchTerm)
                                    ->orWhere(function($addrQ) use ($searchTerm) {
                                        $addrQ->filterByAddress($searchTerm);
                                    });
                  });
            });
        }

        if ($request->filled('nationality')) {
            $query->where('employeeNationality', $request->input('nationality'));
        }

        if ($request->filled('mou_group')) {
            $query->where('workPermitMOUGroup', $request->input('mou_group'));
        }

        if ($request->filled('insurance_type')) {
            $insuranceType = $request->input('insurance_type');
            if ($insuranceType === 'none') {
                $query->where(function ($q) {
                    $q->whereNull('insurance_type')->orWhere('insurance_type', '=', '');
                });
            } else {
                $query->where('insurance_type', $insuranceType);
            }
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

        if ($request->filled('passport_status')) {
            if ($request->input('passport_status') === 'has_passport') {
                $query->where(function ($q) {
                    $q->whereNotNull('employeePassport')->where('employeePassport', '!=', '');
                });
            } elseif ($request->input('passport_status') === 'no_passport') {
                $query->where(function ($q) {
                    $q->whereNull('employeePassport')->orWhere('employeePassport', '=', '');
                });
            }
        }

        if ($request->filled('visa_status')) {
            if ($request->input('visa_status') === 'has_visa') {
                $query->where(function ($q) {
                    $q->whereNotNull('visaExpiryDate')->where('visaExpiryDate', '!=', '');
                });
            } elseif ($request->input('visa_status') === 'no_visa') {
                $query->where(function ($q) {
                    $q->whereNull('visaExpiryDate')->orWhere('visaExpiryDate', '=', '');
                });
            }
        }

        if ($request->filled('passport_type_myanmar')) {
            $query->where('passportType', $request->input('passport_type_myanmar'))
                  ->where('employeeNationality', 'เมียนมา');
        }

        if ($request->filled('passport_type_cambodia')) {
            $query->where('passport_type_cambodia', $request->input('passport_type_cambodia'))
                  ->where('employeeNationality', 'กัมพูชา');
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
     * Download a document as PDF (converting images if necessary).
     */
    public function downloadDocumentAsPdf(Request $request, Employee $employee, $field)
    {
        // Allowed fields (same as serveDocument, plus 'insurance_document_path')
        $allowedFields = [
            'employee_doc_1', 'employee_doc_2', 'employee_doc_3', 'employee_doc_4',
            'employee_doc_5', 'employee_doc_6', 'employee_doc_7', 'employee_doc_8',
            'employee_doc_9', 'employee_doc_10', 'employee_doc_11', 'employee_doc_12',
            'employee_doc_13', 'employee_doc_14', 'employee_doc_15', 'employee_doc_16',
            'employee_doc_17', 'employee_doc_18',
            'insurance_document_path',
            'insurance_document_path_private',
            'medical_certificate_path',
            // Legacy fields
            'passport_file_path', 'visa_file_path', 'work_permit_file_path',
            'pink_card_file_path'
        ];

        if (!in_array($field, $allowedFields)) {
            abort(404, 'Document type not found.');
        }

        $this->authorize('view', $employee);

        $filePath = $employee->{$field};

        // Check public disk first (standard for newer uploads)
        $disk = 'public';
        if (!$filePath || !Storage::disk($disk)->exists($filePath)) {
            // Check private disk (legacy)
            $disk = 'private';
            if (!$filePath || !Storage::disk($disk)->exists($filePath)) {
                abort(404, 'File not found.');
            }
        }

        $disposition = $request->input('disposition', 'inline');

        // Map field to readable name
        $fieldMapping = [
            'employee_doc_1' => 'Passport',
            'employee_doc_2' => 'Visa',
            'employee_doc_3' => 'Work_Permit',
            'employee_doc_4' => 'Pink_Card',
            'insurance_document_path' => 'Social_Security_Insurance',
            'insurance_document_path_private' => 'Private_Insurance',
            'medical_certificate_path' => 'Medical_Certificate',
            // Legacy
            'passport_file_path' => 'Passport',
            'visa_file_path' => 'Visa',
            'work_permit_file_path' => 'Work_Permit',
            'pink_card_file_path' => 'Pink_Card',
        ];

        $docName = $fieldMapping[$field] ?? $field;

        // Handle generic doc 5-18
        if (str_starts_with($field, 'employee_doc_')) {
            $num = (int)str_replace('employee_doc_', '', $field);
            if ($num >= 5) {
                 $docName = "Other_Document_{$num}";
            }
        }

        // Use English name for filename compatibility
        $employeeName = $employee->employeeNameEn ?: 'Employee_' . $employee->id;
        // Sanitize name to be safe (remove non-alphanumeric except hyphen and underscore)
        $employeeName = preg_replace('/[^A-Za-z0-9\-\_]/', '_', $employeeName);

        $empId = $employee->employer_employee_id ?: $employee->id;
        $empId = preg_replace('/[^A-Za-z0-9\-\_]/', '_', (string)$empId);

        // Construct filename: [DocName]_[EmployeeName]_[ID].pdf
        $filename = "{$docName}_{$employeeName}_{$empId}.pdf";

        return \App\Helpers\PdfHelper::streamFile($disk, $filePath, $disposition, $filename);
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
            'employee_doc_5', 'employee_doc_6', 'employee_doc_7', 'employee_doc_8',
            'employee_doc_9', 'employee_doc_10', 'employee_doc_11', 'employee_doc_12',
            'employee_doc_13', 'employee_doc_14', 'employee_doc_15', 'employee_doc_16',
            'employee_doc_17', 'employee_doc_18',
            'insurance_document_path_private',
            'medical_certificate_path',
            'insurance_document_path',
            // Keep old fields for backward compatibility if needed
            'passport_file_path', 'visa_file_path', 'work_permit_file_path',
            'pink_card_file_path'
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

        $oldEmployer = $employee->employer;
        $oldEmployerName = $oldEmployer ? $oldEmployer->getRawOriginal('employerNameTh') : 'N/A';
        $oldEmployerId = $employee->employer_id;

        $newEmployer = Employer::find($validated['new_employer_id']);
        $newEmployerName = $newEmployer ? $newEmployer->getRawOriginal('employerNameTh') : 'N/A';

        $employee->update([
            'employer_id' => $validated['new_employer_id'],
            'terminated_at' => null, // Make the employee active again
            'termination_reason' => null, // Clear the old reason
        ]);

        ActivityLogHelper::logAction('transfer', 'ย้ายลูกจ้าง ' . $employee->getRawOriginal('employeeNameEn') . ' จาก ' . $oldEmployerName . ' ไป ' . $newEmployerName, Employee::class, $employee->id, [
            'old_employer_id' => $oldEmployerId,
            'old_employer_name' => $oldEmployerName,
            'new_employer_id' => $validated['new_employer_id'],
            'new_employer_name' => $newEmployerName,
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
        // Use `terminate-employees` to match EmployeePolicy::transfer — caretakers hold this permission,
        // so the previous `manage-tickets` gate was falsely blocking them from bulk transfers.
        if (!auth()->user()->can('terminate-employees')) { // Skip this check for admins/staff/caretakers
            $employeesToTransfer = Employee::whereIn('id', $validated['employee_ids'])->get();
            foreach ($employeesToTransfer as $employee) {
                if (optional($employee->employer)->user_id !== auth()->id()) {
                    return response()->json(['success' => false, 'message' => 'You are not authorized to transfer one or more of the selected employees.'], 403);
                }
            }
        }

        $newEmployer = Employer::find($validated['new_employer_id']);
        $newEmployerName = $newEmployer ? $newEmployer->getRawOriginal('employerNameTh') : 'N/A';

        // Log each employee transfer individually for full audit trail
        $employeesToTransfer = Employee::whereIn('id', $validated['employee_ids'])
            ->whereNotNull('terminated_at')
            ->with('employer')
            ->get();

        foreach ($employeesToTransfer as $emp) {
            $oldEmployerName = $emp->employer ? $emp->employer->getRawOriginal('employerNameTh') : 'N/A';
            $oldEmployerId = $emp->employer_id;

            $emp->update([
                'employer_id' => $validated['new_employer_id'],
                'terminated_at' => null,
                'termination_reason' => null,
            ]);

            ActivityLogHelper::logAction('transfer', 'ย้ายลูกจ้าง ' . $emp->getRawOriginal('employeeNameEn') . ' จาก ' . $oldEmployerName . ' ไป ' . $newEmployerName . ' (ย้ายแบบกลุ่ม)', Employee::class, $emp->id, [
                'old_employer_id' => $oldEmployerId,
                'old_employer_name' => $oldEmployerName,
                'new_employer_id' => $validated['new_employer_id'],
                'new_employer_name' => $newEmployerName,
                'bulk_transfer' => true,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Selected employees transferred successfully.']);
    }

    /**
     * Super Admin only — move/swap/merge one attachment slot into another
     * for a checkbox-selected batch of employees. Replaces the old
     * settings-page "swap all employees system-wide" tool, which was too
     * blunt: different resolution groups often have their attachments in
     * different positions, so a blanket move would corrupt unrelated
     * employees. This only ever touches the IDs explicitly selected.
     */
    public function bulkMoveAttachments(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $allowedFields = array_map(fn ($i) => "employee_doc_{$i}", range(1, 18));

        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:employees,id',
            'from_field' => ['required', 'string', Rule::in($allowedFields)],
            'to_field' => ['required', 'string', Rule::in($allowedFields), 'different:from_field'],
            'mode' => 'required|in:swap,move_delete,merge_append',
        ]);

        $employeeIds = array_values(array_unique(array_map('intval', $validated['employee_ids'])));

        // Called directly (not via Bus::dispatchSync) — for a ShouldQueue
        // job, dispatchSync() runs it through the sync queue connection and
        // does NOT hand back handle()'s return value, only a push result.
        // Still implements ShouldQueue so this could be switched to a real
        // queued dispatch() later without any other code changes.
        $result = (new \App\Jobs\ProcessBulkAttachmentMove(
            $employeeIds,
            $validated['from_field'],
            $validated['to_field'],
            $validated['mode'],
        ))->handle();

        ActivityLogHelper::logAction(
            'bulk_move_attachments',
            "ย้ายไฟล์แนบ ({$validated['mode']}) จาก {$validated['from_field']} ไป {$validated['to_field']} — เลือก " . count($employeeIds) . " คน, ย้ายสำเร็จ {$result['moved']} คน" . ($result['failed'] > 0 ? ", ล้มเหลว {$result['failed']} คน" : ''),
            Employee::class,
            null,
            [
                'mode' => $validated['mode'],
                'from_field' => $validated['from_field'],
                'to_field' => $validated['to_field'],
                'employee_count' => count($employeeIds),
                'employee_ids' => array_slice($employeeIds, 0, 50),
                'moved' => $result['moved'],
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ]
        );

        return response()->json([
            'success' => true,
            'moved' => $result['moved'],
            'skipped' => $result['skipped'],
            'failed' => $result['failed'],
            'message' => "ย้ายไฟล์สำเร็จ {$result['moved']} คน"
                . ($result['skipped'] > 0 ? ", ไม่มีไฟล์ให้ย้าย {$result['skipped']} คน" : '')
                . ($result['failed'] > 0 ? ", ล้มเหลว {$result['failed']} คน (ดู log)" : ''),
        ]);
    }

    /**
     * Step 1: Render the Field Selection Page (Mock Form)
     */
    public function bulkEditSelectFields(Request $request)
    {
        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $employeeIds = $request->input('employee_ids');
        $redirectTo = $request->input('redirect_to');

        // Define all available fields grouped by category
        $fieldGroups = [
            'Personal Information' => [
                'employeePhoto' => 'Photo (Upload)',
                'employer_employee_id' => 'Employee ID (Employer)',
                'employee_id_number' => 'Personal ID',
                'employeeTitleTh' => 'Title (TH)',
                'employeeNameTh' => 'Name (TH)',
                'employeeTitleEn' => 'Title (EN)',
                'employeeNameEn' => 'Name (EN)',
                'father_name' => 'Father Name',
                'mother_name' => 'Mother Name',
                'employeeGender' => 'Gender',
                'employeeDob' => 'Date of Birth',
                'employeePhone' => 'Phone',
                'employeeNationality' => 'Nationality',
                'employee_reference_id' => 'Reference ID',
                'email' => 'Email',
                'password' => 'Password',
                'height' => 'Height',
                'weight' => 'Weight',
                'bank_name' => 'Bank Name',
                'bank_account_number' => 'Bank Account Number',
            ],
            'Passport & Visa' => [
                'employeePassport' => 'Passport Number',
                'passport_issue_date' => 'Passport Issue Date',
                'passportExpiryDate' => 'Passport Expiry Date',
                'passport_issue_place' => 'Passport Issue Place',
                'passportType' => 'Passport Type',
                'passport_type_cambodia' => 'Passport Type (Cambodia)',
                'visaType' => 'Visa Type',
                'visaEndorsementDate' => 'วันที่ตรวจลงตราวีซ่า (Visa Endorsement Date)',
                'visaEndorsementNo' => 'เลขที่ตรวจลงตราวีซ่า (Visa Endorsement No.)',
                'visaExpiryDate' => 'Visa Expiry Date',
                'visa_issue_place' => 'Visa Issue Place',
                'passport_file' => 'Passport File (Upload)',
                'visa_file' => 'Visa File (Upload)',
            ],
            'Work Permit & Pink Card' => [
                'employeeWorkPermit' => 'Work Permit Number',
                'workPermitIssueDate' => 'Work Permit Issue Date (วันที่ออกใบอนุญาตทำงาน)',
                'workPermitExpiryDate' => 'Work Permit Expiry',
                'workPermitMOUGroup' => 'MOU Group',
                'workPermitMOUGroupOther' => 'Other MOU Group',
                'pinkCardNo' => 'Pink Card Number',
                'tax_id_number' => 'Tax ID',
                'request_number' => 'Request Number',
                'name_list_number' => 'Name List Number',
                'work_permit_file' => 'Work Permit File (Upload)',
                'pink_card_file' => 'Pink Card File (Upload)',
            ],
            'Job Details' => [
                'job_title' => 'Job Title',
                'job_description' => 'Job Description',
                'employeePosition' => 'Position',
                'department' => 'Department',
                'nature_of_work' => 'Nature of Work',
                'startDate' => 'Start Date',
                'outsource_code' => 'Outsource Code',
            ],
            'Insurance' => [
                'insurance_type' => 'Insurance Type',
                'social_security_number' => 'Social Security Number',
                'sso_issue_date' => 'Social Security Issue Date',
                'sso_expiry_date' => 'Social Security Expiry Date',
                'insurance_detail_hospital' => 'Hospital Name',
                'insurance_expiry_date_hospital' => 'Hospital Expiry',
                'insurance_detail_private' => 'Private Insurance Company',
                'insurance_expiry_date_private' => 'Private Insurance Expiry',
                'insurance_attachment' => 'Insurance File (Upload)',
            ],
             'Specific Documents' => [
                'employee_doc_5' => 'Tor Ror 38 (ทร.38)',
                'employee_doc_6' => '90-Day Report (รายงานตัว 90 วัน)',
                'ninetyDayReportDate' => '90-Day Report Date',
                'employee_doc_7' => 'Residence Notification (แจ้งที่พักอาศัย)',
                'employee_doc_8' => 'Hometown Document (เอกสารบ้านเกิด)',
            ],
             'Other Documents' => [
                'employee_doc_9' => 'Document 1',
                'employee_doc_10' => 'Document 2',
                'employee_doc_11' => 'Document 3',
                'employee_doc_12' => 'Document 4',
                'employee_doc_13' => 'Document 5',
                'employee_doc_14' => 'Document 6',
                'employee_doc_15' => 'Document 7',
                'employee_doc_16' => 'Document 8',
                'employee_doc_17' => 'Document 9',
                'employee_doc_18' => 'Document 10',
            ],
        ];

        return view('employees.bulk_edit_selector', compact('employeeIds', 'fieldGroups', 'redirectTo'));
    }

    /**
     * Step 2: Render the Bulk Edit Form (Master + Individual)
     */
    public function bulkEditForm(Request $request)
    {
        $request->validate([
            'employee_ids' => 'required|array',
            'selected_fields' => 'required|array|max:5', // Limit to 5 fields
        ]);

        $employees = Employee::whereIn('id', $request->input('employee_ids'))->get();
        $selectedFields = $request->input('selected_fields');
        $redirectTo = $request->input('redirect_to');

        // Define metadata for fields to render correct inputs
        $fileFields = [
            'employeePhoto',
            'passport_file', 'visa_file', 'work_permit_file', 'pink_card_file', 'insurance_attachment',
            'employee_doc_5', 'employee_doc_6', 'employee_doc_7', 'employee_doc_8',
            'employee_doc_9', 'employee_doc_10', 'employee_doc_11', 'employee_doc_12',
            'employee_doc_13', 'employee_doc_14', 'employee_doc_15', 'employee_doc_16',
            'employee_doc_17', 'employee_doc_18'
        ];

        $dateFields = [
            'employeeDob', 'passport_issue_date', 'passportExpiryDate',
            'visaEndorsementDate',
            'visaExpiryDate', 'workPermitIssueDate', 'workPermitExpiryDate', 'startDate',
            'insurance_expiry_date_hospital', 'insurance_expiry_date_private',
            'sso_issue_date', 'sso_expiry_date',
            'ninetyDayReportDate'
        ];

        $options = [
            'employeeNationality' => ['เมียนมา' => 'เมียนมา', 'ลาว' => 'ลาว', 'กัมพูชา' => 'กัมพูชา', 'เวียดนาม' => 'เวียดนาม'],
            'employeeGender' => ['ชาย' => 'ชาย', 'หญิง' => 'หญิง'],
            'employeeTitleTh' => ['นาย' => 'นาย', 'นางสาว' => 'นางสาว', 'นาง' => 'นาง'],
            'employeeTitleEn' => ['Mr.' => 'Mr.', 'Miss' => 'Miss', 'Mrs.' => 'Mrs.'],
            'passportType' => ['PJ' => 'เล่ม PJ', 'CI' => 'เล่ม CI'],
            'passport_type_cambodia' => ['เล่ม TD' => 'เล่ม TD', 'เล่มอินเตอร์' => 'เล่มอินเตอร์'],
            'insurance_type' => ['ประกันสังคม' => 'ประกันสังคม', 'ประกันโรงพยาบาล' => 'ประกันโรงพยาบาล', 'ประกันเอกชน' => 'ประกันเอกชน'],
            // Note: 'workPermitType' in bulk selection often maps to the MOU Group dropdown in Create/Edit forms
            'workPermitMOUGroup' => \App\Models\WorkPermitType::ordered()->pluck('name', 'name')->union(['อื่นๆ' => 'อื่นๆ'])->all(),
        ];

        // Map keys to labels (simplified version of what was in selectFields)
        // Ideally this should be a shared constant or helper.
        $fieldLabels = [
            'employeePhoto' => 'Photo (Upload)',
            'employer_employee_id' => 'Employee ID (Employer)',
            'employee_id_number' => 'Personal ID',
            'employeeNameTh' => 'Name (TH)',
            'employeeNameEn' => 'Name (EN)',
            'employeeTitleTh' => 'Title (TH)',
            'employeeTitleEn' => 'Title (EN)',
            'father_name' => 'Father Name',
            'mother_name' => 'Mother Name',
            'employeeGender' => 'Gender',
            'employeeDob' => 'Date of Birth',
            'employeePhone' => 'Phone',
            'employeeNationality' => 'Nationality',
            'employee_reference_id' => 'Reference ID',
            'email' => 'Email',
            'password' => 'Password',
            'height' => 'Height',
            'weight' => 'Weight',
            'bank_name' => 'Bank Name',
            'bank_account_number' => 'Bank Account Number',
            'employeePassport' => 'Passport No.',
            'passport_issue_date' => 'Passport Issue Date',
            'passportExpiryDate' => 'Passport Expiry',
            'passportType' => 'Passport Type',
            'passport_type_cambodia' => 'Passport Type (Cambodia)',
            'passport_issue_place' => 'Passport Issue Place',
            'visaType' => 'Visa Type',
            'visaEndorsementDate' => 'Visa Endorsement Date (วันที่ตรวจลงตราวีซ่า)',
            'visaEndorsementNo' => 'Visa Endorsement No. (เลขที่ตรวจลงตราวีซ่า)',
            'visaExpiryDate' => 'Visa Expiry Date',
            'visa_issue_place' => 'Visa Issue Place',
            'employeeWorkPermit' => 'Work Permit No.',
            'workPermitIssueDate' => 'Work Permit Issue Date (วันที่ออกใบอนุญาตทำงาน)',
            'workPermitExpiryDate' => 'Work Permit Expiry',
            'workPermitMOUGroup' => 'MOU Group',
            'workPermitMOUGroupOther' => 'Other MOU Group',
            'pinkCardNo' => 'Pink Card No.',
            'tax_id_number' => 'Tax ID',
            'request_number' => 'Request Number',
            'name_list_number' => 'Name List No',
            'job_title' => 'Job Title',
            'job_description' => 'Job Description',
            'employeePosition' => 'Position',
            'department' => 'Department',
            'nature_of_work' => 'Nature of Work',
            'startDate' => 'Start Date',
            'outsource_code' => 'Outsource Code',
            'insurance_type' => 'Insurance Type',
            'social_security_number' => 'Social Security Number',
            'sso_issue_date' => 'Social Security Issue Date',
            'sso_expiry_date' => 'Social Security Expiry Date',
            'insurance_detail_hospital' => 'Hospital Name',
            'insurance_expiry_date_hospital' => 'Hospital Expiry',
            'insurance_detail_private' => 'Private Insurance Company',
            'insurance_expiry_date_private' => 'Private Insurance Expiry',
            'insurance_attachment' => 'Insurance File (Upload)',
            'passport_file' => 'Passport File (Upload)',
            'visa_file' => 'Visa File (Upload)',
            'work_permit_file' => 'Work Permit File (Upload)',
            'pink_card_file' => 'Pink Card File (Upload)',
            'employee_doc_5' => 'Tor Ror 38 (ทร.38)',
            'employee_doc_6' => '90-Day Report (รายงานตัว 90 วัน)',
            'ninetyDayReportDate' => '90-Day Report Date',
            'employee_doc_7' => 'Residence Notification (แจ้งที่พักอาศัย)',
            'employee_doc_8' => 'Hometown Document (เอกสารบ้านเกิด)',
            'employee_doc_9' => 'Document 1',
            'employee_doc_10' => 'Document 2',
            'employee_doc_11' => 'Document 3',
            'employee_doc_12' => 'Document 4',
            'employee_doc_13' => 'Document 5',
            'employee_doc_14' => 'Document 6',
            'employee_doc_15' => 'Document 7',
            'employee_doc_16' => 'Document 8',
            'employee_doc_17' => 'Document 9',
            'employee_doc_18' => 'Document 10',
        ];

        return view('employees.bulk_edit_form', compact('employees', 'selectedFields', 'fileFields', 'dateFields', 'options', 'fieldLabels', 'redirectTo'));
    }

    /**
     * Step 3: Process Bulk Update
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            // We rely on 'employee_ids' to iterate, as 'data' might be partial or empty if only files are uploaded
            'employee_ids' => 'required|array',
            'selected_fields' => 'required|array',
        ]);

        // $data contains text inputs. Files are in $request->file('data')
        $inputData = $request->input('data', []);
        $employeeIds = $request->input('employee_ids');
        $selectedFields = $request->input('selected_fields');
        $redirectTo = $request->input('redirect_to');
        $updatedCount = 0;

        // Define mapping for legacy file fields to actual database columns
        $fieldMapping = [
            'employeePhoto'      => 'employeePhoto',
            'passport_file'      => 'employee_doc_1',
            'visa_file'          => 'employee_doc_2',
            'work_permit_file'   => 'employee_doc_3',
            'pink_card_file'     => 'employee_doc_4',
            'insurance_attachment' => 'insurance_document_path_private',
        ];

        // Fields where a blank submission must be treated as "no change" rather
        // than "clear to null" — prevents accidental data loss when the user
        // ticks a bulk-edit column but leaves the cell empty for some rows.
        //
        // Original scope: only date columns (visa/wp/passport). Expanded after
        // field reports that RA numbers / request numbers were quietly getting
        // wiped when operators bulk-edited an unrelated field but the outsource
        // ID columns were also in the selected set with empty cells.
        //
        // Rule of thumb: any high-value identifier field that should NOT be
        // "cleared to null" as a side effect of bulk editing goes here. Users
        // wanting to actually clear a field can still do it via the per-employee
        // Advanced Edit modal.
        $dateFieldsSkipIfBlank = [
            // Dates
            'employeeDob', 'passport_issue_date', 'passportExpiryDate',
            'visaEndorsementDate', 'visaExpiryDate',
            'workPermitIssueDate', 'workPermitExpiryDate',
            'startDate', 'ninetyDayReportDate',
            'insurance_expiry_date', 'insurance_expiry_date_hospital',
            'insurance_expiry_date_private', 'sso_issue_date', 'sso_expiry_date',
            // Critical outsource / gov / bank identifiers
            'name_list_number', 'request_number',
            'registration_request_number', 'renewal_request_number',
            'employee_reference_id', 'employee_id_number',
            'employer_employee_id', 'tax_id_number',
            'employeePassport', 'employeeWorkPermit', 'pinkCardNo',
            'visaEndorsementNo', 'social_security_number',
            'bank_account_number',
        ];
        $bulkEditSkippedBlank = [];

        foreach ($employeeIds as $id) {
            $employee = Employee::find($id);
            if (!$employee) continue;

            // Authorize — admins/staff/caretakers have `edit-employees`; employers
            // fall back to ownership. Was previously `manage-tickets`, which
            // silently skipped caretakers (who intentionally lack that ticket
            // permission) and caused their bulk edits to appear to no-op.
            if (!auth()->user()->can('edit-employees') && optional($employee->employer)->user_id !== auth()->id()) {
                continue;
            }

            $updateData = [];
            $employeeInput = $inputData[$id] ?? [];

            foreach ($selectedFields as $field) {
                // Determine the actual database column name
                $dbColumn = $fieldMapping[$field] ?? $field;

                // 1. Handle File Uploads
                if ($request->hasFile("data.{$id}.{$field}")) {
                    $file = $request->file("data.{$id}.{$field}");

                    // REVERTED: Use 'public' disk to prevent 403 Forbidden errors.
                    $disk = 'public';
                    $folder = "employee_files/{$employee->employer_id}";

                    // Cleanup old files from BOTH disks
                    if ($employee->$dbColumn) {
                        if (Storage::disk('public')->exists($employee->$dbColumn)) {
                            Storage::disk('public')->delete($employee->$dbColumn);
                        }
                        if (Storage::disk('private')->exists($employee->$dbColumn)) {
                            Storage::disk('private')->delete($employee->$dbColumn);
                        }
                    }

                    $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs($folder, $filename, $disk);

                    $updateData[$dbColumn] = $path;

                }
                // 2. Handle Text/Date Inputs
                // We check array_key_exists because the value might be null or empty string, which we want to save.
                elseif (array_key_exists($field, $employeeInput)) {
                    $value = $employeeInput[$field];
                    // Guard: skip blank date submissions on protected date fields
                    // so a "select column but leave cell empty" mistake does
                    // NOT null out an existing visa/wp/passport date.
                    if (in_array($field, $dateFieldsSkipIfBlank, true)
                        && ($value === null || $value === '')) {
                        $bulkEditSkippedBlank[$id][] = $field;
                        continue;
                    }
                    $updateData[$dbColumn] = $value;
                }

                // 3. Handle Other Document Descriptions (auto-mapping)
                // If the current field is employee_doc_9 through 18, check for corresponding description
                if (preg_match('/^employee_doc_([9]|1[0-8])$/', $field, $matches)) {
                    $docIndex = (int)$matches[1]; // 9 to 18
                    $descIndex = $docIndex - 8;   // 1 to 10
                    $descField = "other_doc_{$descIndex}_desc";

                    if (array_key_exists($descField, $employeeInput)) {
                        $updateData[$descField] = $employeeInput[$descField];
                    }
                }
            }

            if (!empty($updateData)) {
                $employee->update($updateData);
                $updatedCount++;
            }
        }

        // Build a warning line when we protected some cells from being nulled
        // — so the operator immediately sees "these 3 rows had a protected
        // field left blank and were NOT cleared" instead of quietly losing data.
        $blankNotice = '';
        if (!empty($bulkEditSkippedBlank)) {
            $rowCount = count($bulkEditSkippedBlank);
            $blankNotice = " ข้าม {$rowCount} แถวที่มีช่องสำคัญปล่อยว่าง (วันที่ / เลข RA / เลขคำขอ / passport / work permit ฯลฯ) — ค่าเดิมยังอยู่ ไม่ถูกลบ";
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'skipped_blank_date_fields' => $bulkEditSkippedBlank,
                'notice' => $blankNotice,
            ]);
        }

        if ($redirectTo) {
            return redirect($redirectTo)->with('success', "Bulk updated {$updatedCount} employees successfully.{$blankNotice}");
        }

        return redirect()->route('employees.index')->with('success', "Bulk updated {$updatedCount} employees successfully.{$blankNotice}");
    }

    /**
     * Export selected employees with selected columns.
     */
    public function advancedExport(Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|string',
            'columns' => 'required|array|max:15',
            'export_source_menu' => 'nullable|string',
        ]);

        $ids = explode(',', $validated['employee_ids']);
        ActivityLogHelper::logAction('export', 'ส่งออกขั้นสูง (Advanced Export) ลูกจ้าง ' . count($ids) . ' ราย จาก ' . ($validated['export_source_menu'] ?? 'ไม่ระบุ'), null, null, [
            'employee_count' => count($ids),
            'columns' => $validated['columns'],
            'source_menu' => $validated['export_source_menu'] ?? null,
        ]);

        $employeeIds = json_decode($validated['employee_ids'], true);
        $selectedColumns = $validated['columns'];
        $sourceMenu = $validated['export_source_menu'] ?? null;
        $includeNameSuffix = $request->boolean('include_name_suffix');

        if (empty($employeeIds)) {
            return back()->with('error', 'No employees selected.');
        }

        $employeesQuery = Employee::whereIn('id', $employeeIds)->with('employer.addresses', 'employer.jobOwner');

        if (in_array($sourceMenu, ['pre_production', 'workflow'])) {
             $employeesQuery->with(['productionItems' => function ($query) {
                 $query->latest();
             }]);
        }

        $employeesUnsorted = $employeesQuery->get();

        // Sort employees by the order of IDs in the request (preserves user selection order)
        $idOrder = array_flip(array_map('intval', $employeeIds));
        $employees = $employeesUnsorted->sort(function ($a, $b) use ($idOrder) {
            return ($idOrder[$a->id] ?? PHP_INT_MAX) - ($idOrder[$b->id] ?? PHP_INT_MAX);
        })->values();

        // Define labels for the header
        $columnLabels = [
            'employeePhoto' => 'Photo',
            'employeeTitleTh' => 'Title (TH)',
            'employeeNameTh' => 'Name (TH)',
            'employeeTitleEn' => 'Prefix (EN)',
            'employeeNameEn' => 'Name (EN)',
            'father_name' => 'Father Name',
            'mother_name' => 'Mother Name',
            'employeeGender' => 'Gender',
            'employeeDob' => 'Date of Birth',
            'employeeAge' => 'Age',
            'employeePhone' => 'Phone',
            'employeeNationality' => 'Nationality',
            'passportType' => 'Passport Type (MM)',
            'passport_type_cambodia' => 'Passport Type (KH)',
            'employeePassport' => 'Passport No',
            'passport_issue_date' => 'Passport Issue Date',
            'passport_issue_place' => 'Passport Issue Place',
            'passportExpiryDate' => 'Passport Expiry Date',
            'pinkCardNo' => 'Pink Card No',
            'visaType' => 'Visa Type',
            'visaEndorsementDate' => 'Visa Endorsement Date',
            'visaEndorsementNo' => 'Visa Endorsement No',
            'visaExpiryDate' => 'Visa Expiry',
            'visa_issue_place' => 'Visa Issue Place',
            'job_title' => 'Job Title',
            'job_description' => 'Nature of Work',
            'department' => 'Department',
            'height' => 'Height (cm)',
            'weight' => 'Weight (kg)',
            'medical_hospital_name' => 'Medical Check-up Hospital',
            'startDate' => 'Start Date',
            'employeeWorkPermit' => 'Work Permit No',
            'workPermitIssueDate' => 'Work Permit Issue Date',
            'workPermitExpiryDate' => 'Work Permit Expiry',
            'ninetyDayReportDate' => '90 Day Report',
            'workPermitMOUGroup' => 'WP Type',
            'workPermitMOUGroupOther' => 'WP Other',
            'name_list_number' => 'Name List No',
            'request_number' => 'Request No',
            'employee_id_number' => 'Personal ID',
            'tax_id_number' => 'Tax ID',
            'employer_employee_id' => 'Employer-Worker ID',
            'employee_reference_id' => 'Reference ID',
            'insurance_type' => 'Insurance Type',
            'social_security_number' => 'SS Number',
            'sso_issue_date' => 'SS Issue Date',
            'sso_expiry_date' => 'SS Expiry Date',
            'insurance_detail' => 'Hospital Rights (SS)',
            'insurance_detail_hospital' => 'Hospital Name',
            'insurance_expiry_date_hospital' => 'Hospital Expiry',
            'insurance_detail_private' => 'Private Company',
            'insurance_expiry_date_private' => 'Private Expiry',
            'email' => 'Email',
            'password' => 'รหัสสำหรับอีเมล',
            'outsource_code' => 'รหัสสำหรับระบบ Outsource',
            'bank_name' => 'ชื่อธนาคาร',
            'bank_account_number' => 'เลขบัญชีธนาคาร',
            'employerTaxId' => 'เลขประจำตัวนายจ้าง',
            'businessType' => 'ประเภทกิจการ',
            'job_owner_id' => 'ชื่อเจ้าของงาน',
            'employerNameTh' => 'Employer Name (TH)',
            'employerNameEn' => 'Employer Name (EN)',
            'employerAddressTh' => 'Employer Address (TH)',
            'employerAddressEn' => 'Employer Address (EN)',
        ];

        // Handle Photo Column Logic: Must be first if selected
        $hasPhoto = in_array('employeePhoto', $selectedColumns);
        if ($hasPhoto) {
            // Remove 'employeePhoto' from its current position
            $selectedColumns = array_diff($selectedColumns, ['employeePhoto']);
            // Prepend it to the beginning
            array_unshift($selectedColumns, 'employeePhoto');
        }

        // Create new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Style the header row
        $headerRow = 1;
        $columnIndex = 1;

        // Add "No." column as the first column
        $noCell = $sheet->getCell([$columnIndex, $headerRow]);
        $noCell->setValue('No.');
        $sheet->getStyle([$columnIndex, $headerRow])->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF2F2F2'],
            ],
        ]);
        $sheet->getColumnDimensionByColumn($columnIndex)->setWidth(6);
        $columnIndex++;

        foreach ($selectedColumns as $col) {
            $label = $columnLabels[$col] ?? $col;
            $cell = $sheet->getCell([$columnIndex, $headerRow]);
            $cell->setValue($label);

            // Header styling
            $sheet->getStyle([$columnIndex, $headerRow])->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF2F2F2'], // Light gray
                ],
            ]);

            // Set default width
            if ($col === 'employeePhoto') {
                $sheet->getColumnDimensionByColumn($columnIndex)->setWidth(18); // Adjusted width for better framing
            } else {
                $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
            }

            $columnIndex++;
        }

        // Add Data Rows
        $currentRow = 2;
        $rowNumber = 1;
        foreach ($employees as $employee) {
            $columnIndex = 1;

            // Set row height if photo column exists to accommodate the image
            if ($hasPhoto) {
                $sheet->getRowDimension($currentRow)->setRowHeight(90); // Approx 120px height
            } else {
                $sheet->getRowDimension($currentRow)->setRowHeight(20);
            }

            // Add row number in the "No." column
            $noCell = $sheet->getCell([$columnIndex, $currentRow]);
            $noCell->setValue($rowNumber);
            $sheet->getStyle($noCell->getCoordinate())->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ]
            ]);
            $columnIndex++;

            foreach ($selectedColumns as $col) {
                $cellAddress = $sheet->getCell([$columnIndex, $currentRow])->getCoordinate();
                $cell = $sheet->getCell([$columnIndex, $currentRow]);

                // Common cell styling
                $sheet->getStyle($cellAddress)->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ]
                ]);

                if ($col === 'employeePhoto') {
                    // Embed Image
                    if ($employee->employeePhoto && Storage::disk('public')->exists($employee->employeePhoto)) {
                        $path = Storage::disk('public')->path($employee->employeePhoto);

                        $drawing = new Drawing();
                        $drawing->setName('Employee Photo');
                        $drawing->setDescription('Employee Photo');
                        $drawing->setPath($path);
                        $drawing->setCoordinates($cellAddress);
                        $drawing->setHeight(100); // 100px height to prevent overflow (row height is ~120px)
                        // Center image in cell roughly
                        $drawing->setOffsetX(28); // Horizontally center (~30-35px based on col width 18)
                        $drawing->setOffsetY(10); // Vertically center (10px padding top/bottom)
                        $drawing->setWorksheet($sheet);
                    } else {
                        $cell->setValue('No Photo');
                    }

                } elseif (in_array($col, ['employeeDob', 'passport_issue_date', 'passportExpiryDate', 'visaEndorsementDate', 'visaExpiryDate', 'startDate', 'workPermitIssueDate', 'workPermitExpiryDate', 'ninetyDayReportDate', 'insurance_expiry_date_hospital', 'insurance_expiry_date_private', 'sso_issue_date', 'sso_expiry_date'])) {
                    // Format Dates
                    $val = $employee->$col ? \Carbon\Carbon::parse($employee->$col)->format('d/m/Y') : '-';
                    $cell->setValue($val);
                } elseif ($col === 'employeeAge') {
                    $cell->setValue($employee->age);
                } elseif ($col === 'employeeGender') {
                    $cell->setValue($employee->gender ?? $employee->employeeGender);
                } elseif ($col === 'employerTaxId') {
                    $cell->setValueExplicit($employee->employer->employerTaxId ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } elseif ($col === 'businessType') {
                    $cell->setValue($employee->employer->businessType ?? '-');
                } elseif ($col === 'job_owner_id') {
                    $cell->setValue($employee->employer->jobOwner->name ?? '-');
                } elseif ($col === 'employerNameTh') {
                    $val = $includeNameSuffix
                        ? ($employee->employer->employerNameTh ?? '-')
                        : ($employee->employer->getRawOriginal('employerNameTh') ?? '-');
                    $cell->setValue($val);
                } elseif ($col === 'employerNameEn') {
                    $cell->setValue($employee->employer->employerNameEn ?? '-');
                } elseif ($col === 'employerAddressTh') {
                    $address = $employee->employer->addresses->first();
                    $fullAddress = '-';
                    if ($address) {
                        $parts = array_filter([
                            $address->addrNo,
                            $address->addrMoo ? "หมู่ " . $address->addrMoo : null,
                            $address->addrSoi ? "ซอย " . $address->addrSoi : null,
                            $address->addrRoad ? "ถนน " . $address->addrRoad : null,
                            $address->addrSubDistrict ? "ต." . $address->addrSubDistrict : null,
                            $address->addrDistrict ? "อ." . $address->addrDistrict : null,
                            $address->addrProvince ? "จ." . $address->addrProvince : null,
                            $address->addrZipCode
                        ]);
                        $fullAddress = implode(' ', $parts);
                    }
                    $cell->setValue($fullAddress);
                } elseif ($col === 'employerAddressEn') {
                    $address = $employee->employer->addresses->first();
                    $fullAddress = '-';
                    if ($address) {
                        $parts = array_filter([
                            $address->addrNoEn,
                            $address->addrMooEn ? "Moo " . $address->addrMooEn : null,
                            $address->addrSoiEn ? "Soi " . $address->addrSoiEn : null,
                            $address->addrRoadEn ? "Road " . $address->addrRoadEn : null,
                            $address->addrSubDistrictEn,
                            $address->addrDistrictEn,
                            $address->addrProvinceEn,
                            $address->addrZipCodeEn
                        ]);
                        $fullAddress = implode(', ', $parts);
                    }
                    $cell->setValue($fullAddress);
                } elseif ($col === 'request_number') {
                    $val = '-';
                    if ($sourceMenu === 'registration') {
                        $val = $employee->registration_request_number ?? '-';
                    } elseif ($sourceMenu === 'renewal') {
                        $val = $employee->renewal_request_number ?? '-';
                    } elseif (in_array($sourceMenu, ['pre_production', 'workflow'])) {
                        $latestItem = $employee->productionItems->first();
                        $val = $latestItem ? ($latestItem->request_number ?? '-') : '-';
                    } else {
                        $val = $employee->request_number ?? '-';
                    }

                    if ($val !== '-') {
                        $cell->setValueExplicit($val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    } else {
                        $cell->setValue($val);
                    }
                } else {
                    // Handle name_suffix toggle for employee name
                    if ($col === 'employeeNameEn' && !$includeNameSuffix) {
                        $val = $employee->getRawOriginal('employeeNameEn') ?? '-';
                    } else {
                        $val = $employee->$col ?? '-';
                    }
                    // List of columns that might contain long numbers (e.g. 13 digits) and should be explicitly set as text
                    $textColumns = [
                        'employeePassport', 'employeeWorkPermit', 'pinkCardNo', 'tax_id_number',
                        'social_security_number', 'employer_employee_id', 'employee_id_number',
                        'name_list_number', 'bank_account_number', 'employeePhone',
                        'outsource_code', 'employee_reference_id', 'visaEndorsementNo'
                    ];

                    if (in_array($col, $textColumns) && $val !== '-') {
                        $cell->setValueExplicit($val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    } else {
                        $cell->setValue($val);
                    }
                }

                $columnIndex++;
            }
            $currentRow++;
            $rowNumber++;
        }

        $fileName = "advanced_employee_export_" . date('Y-m-d_H-i') . ".xlsx";

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
