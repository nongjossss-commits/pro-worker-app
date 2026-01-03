<?php

namespace App\Http\Controllers;

use App\Models\Employer;
use App\Models\JobOwner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\Employee; // <-- เพิ่มบรรทัดนี้
use Illuminate\Support\Facades\Hash;

class EmployerController extends Controller
{
    /**
     * Apply permission middleware to the controller.
     */
    public function __construct()
    {
        $this->middleware('permission:view-employers', ['only' => ['index', 'show']]);
        $this->middleware('permission:create-employers', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit-employers', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete-employers', ['only' => ['destroy']]);
    }

    public function index(Request $request) // <-- เพิ่ม Request $request
    {
        // $employers = Employer::with('jobOwner')->latest()->paginate(10); // <-- ลบแถวนี้

        // --- START: เพิ่ม Logic การค้นหา ---
        $query = Employer::with(['jobOwner', 'assignedStaff'])->latest();

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';

            $query->where(function($q) use ($searchTerm) {
                $q->where('employerNameTh', 'like', $searchTerm)
                  ->orWhere('employerNameEn', 'like', $searchTerm)
                  ->orWhere('businessType', 'like', $searchTerm)
                  ->orWhereHas('jobOwner', function($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', $searchTerm);
                  })
                  ->orWhereHas('assignedStaff', function($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', $searchTerm);
                  });
            });
        }
        // --- END: เพิ่ม Logic การค้นหา ---

        $perPage = $request->input('per_page', 10);
        // เพิ่ม withQueryString() เพื่อให้ pagination จำค่า search
        $employers = $query->paginate($perPage)->withQueryString();

        return view('employers.index', compact('employers'));
    }

    public function create()
    {
        $jobOwners = JobOwner::orderBy('name')->get();
        $staffUsers = User::role(['admin', 'staff', 'caretaker'])->orderBy('name')->get();

        return view('employers.create', compact('jobOwners', 'staffUsers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employerNameTh' => 'required|string|max:255',
            'employerNameEn' => 'nullable|string|max:255',
            // 'employerId' will be auto-generated
            'employerTaxId' => 'nullable|string|max:255',
            'employerEmail' => 'nullable|email|max:255|unique:employers,employerEmail',
            'employerPassword' => 'nullable|string|max:255',
            'employerPhone' => 'nullable|string|max:255',
            'outsource_re_code' => 'nullable|string|max:255',
            'outsource_password' => 'nullable|string|max:255',
            'socialSecurityHospital' => 'nullable|string|max:255',
            'businessType' => 'required|string|max:255',
            'signerNameTh' => 'nullable|string|max:255',
            'signerNameEn' => 'nullable|string|max:255',
            'businessTypeEn' => 'nullable|string|max:255',
            'regCapital' => 'nullable|numeric',
            'regDate' => 'nullable|date',
            'minimum_wage' => 'nullable|numeric',
            'employer_doc_company' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_company_expiry' => 'nullable|date',
            'employer_doc_lease' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_construction' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_1_desc' => 'nullable|string|max:255',
            'employer_doc_other_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_2_desc' => 'nullable|string|max:255',
            'employer_doc_other_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_3_desc' => 'nullable|string|max:255',
            'job_owner_id' => 'required|exists:job_owners,id',
            'assigned_staff_id' => 'nullable|exists:users,id',
        ]);

        // Generate a unique random Employer ID
        do {
            $randomId = 'EMP-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Employer::where('employerId', $randomId)->exists());

        $validated['employerId'] = $randomId;

        // Handle new document uploads
        $docFields = ['employer_doc_company', 'employer_doc_lease', 'employer_doc_construction', 'employer_doc_other_1', 'employer_doc_other_2', 'employer_doc_other_3'];
        foreach ($docFields as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = $request->file($field)->store('employer_documents', 'public');
            }
        }

        // Password is now stored as plain text per user requirement
        // if (!empty($validated['employerPassword'])) {
        //     $validated['employerPassword'] = Hash::make($validated['employerPassword']);
        // }

        Employer::create($validated);
        return redirect()->route('employers.index')->with('success', 'Employer created successfully.');
    }

public function edit(Request $request, Employer $employer)
{
    $jobOwners = JobOwner::orderBy('name')->get();
    $staffUsers = User::role(['admin', 'staff', 'caretaker'])->orderBy('name')->get();

    $employeeQuery = $employer->employees()
        ->whereNull('terminated_at')
        ->where(function($q) {
            $q->whereNotIn('status', ['registration_pending', 'registration_cancelled'])
              ->orWhereNull('status');
        });

    // --- START: ADDED FILTERING LOGIC ---
    if ($request->filled('search')) {
        $searchTerm = '%' . $request->input('search') . '%';
        $employeeQuery->where(function ($q) use ($searchTerm) {
            $q->where('employeeNameTh', 'like', $searchTerm)
              ->orWhere('employeeNameEn', 'like', $searchTerm)
              ->orWhere('employeePassport', 'like', $searchTerm)
              ->orWhere('pinkCardNo', 'like', $searchTerm);
        });
    }

    if ($request->filled('nationality')) {
        $employeeQuery->where('employeeNationality', $request->input('nationality'));
    }

    if ($request->filled('mou_group')) {
        $employeeQuery->where('workPermitMOUGroup', $request->input('mou_group'));
    }

    if ($request->filled('insurance_type')) {
        $insuranceType = $request->input('insurance_type');
        if ($insuranceType === 'none') {
            $employeeQuery->where(function ($q) {
                $q->whereNull('insurance_type')->orWhere('insurance_type', '=', '');
            });
        } else {
            $employeeQuery->where('insurance_type', $insuranceType);
        }
    }

    if ($request->filled('pink_card')) {
        if ($request->input('pink_card') === 'yes') {
            $employeeQuery->where(function ($q) {
                $q->whereNotNull('pinkCardNo')->where('pinkCardNo', '!=', '');
            });
        } elseif ($request->input('pink_card') === 'no') {
            $employeeQuery->where(function ($q) {
                $q->whereNull('pinkCardNo')->orWhere('pinkCardNo', '=', '');
            });
        }
    }

    if ($request->filled('work_permit_expiry_date')) {
        $employeeQuery->whereDate('workPermitExpiryDate', $request->input('work_permit_expiry_date'));
    }

    if ($request->filled('passport_type_myanmar')) {
        $employeeQuery->where('passportType', $request->input('passport_type_myanmar'));
    }

    if ($request->filled('passport_type_cambodia')) {
        $employeeQuery->where('passport_type_cambodia', $request->input('passport_type_cambodia'));
    }
    // --- END: ADDED FILTERING LOGIC ---

    $perPageOptions = [10, 25, 50];
    $currentPerPage = $request->input('per_page', 10);

    // Added withQueryString() to preserve filters on pagination
    $employees = $employeeQuery->paginate($currentPerPage)->withQueryString();

    $currentView = $request->input('view', 'card');
    $terminatedEmployees = $employer->employees()->whereNotNull('terminated_at')->get();

    return view('employers.edit', compact(
        'employer',
        'jobOwners',
        'staffUsers',
        'employees',
        'terminatedEmployees',
        'perPageOptions',
        'currentView',
        'currentPerPage'
    ));
}

    public function update(Request $request, Employer $employer)
    {
        $validated = $request->validate([
            'employerNameTh' => 'required|string|max:255',
            'employerNameEn' => 'nullable|string|max:255',
            'employerId' => ['required', 'string', Rule::unique('employers')->ignore($employer->id), 'max:255'],
            'employerTaxId' => 'nullable|string|max:255',
            'employerEmail' => ['nullable', 'email', 'max:255', Rule::unique('employers', 'employerEmail')->ignore($employer->id)],
            'employerPassword' => 'nullable|string|max:255',
            'employerPhone' => 'nullable|string|max:255',
            'outsource_re_code' => 'nullable|string|max:255',
            'outsource_password' => 'nullable|string|max:255',
            'socialSecurityHospital' => 'nullable|string|max:255',
            'businessType' => 'required|string|max:255',
            'signerNameTh' => 'nullable|string|max:255',
            'signerNameEn' => 'nullable|string|max:255',
            'businessTypeEn' => 'nullable|string|max:255',
            'regCapital' => 'nullable|numeric',
            'regDate' => 'nullable|date',
            'minimum_wage' => 'nullable|numeric',
            'employer_doc_company' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_company_expiry' => 'nullable|date',
            'employer_doc_lease' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_construction' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_1_desc' => 'nullable|string|max:255',
            'employer_doc_other_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_2_desc' => 'nullable|string|max:255',
            'employer_doc_other_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_3_desc' => 'nullable|string|max:255',
            'job_owner_id' => 'required|exists:job_owners,id',
            'assigned_staff_id' => 'nullable|exists:users,id',
        ]);

        // Handle new document uploads
        $docFields = ['employer_doc_company', 'employer_doc_lease', 'employer_doc_construction', 'employer_doc_other_1', 'employer_doc_other_2', 'employer_doc_other_3'];
        foreach ($docFields as $field) {
            if ($request->hasFile($field)) {
                // Delete old file if it exists
                if ($employer->{$field}) {
                    Storage::disk('public')->delete($employer->{$field});
                }
                // Store new file
                $validated[$field] = $request->file($field)->store('employer_documents', 'public');
            }
        }

        // Password is now stored as plain text per user requirement
        // if (!empty($validated['employerPassword'])) {
        //     $validated['employerPassword'] = Hash::make($validated['employerPassword']);
        // }

        $employer->update($validated);
        return redirect()->route('employers.index')->with('success', 'Employer updated successfully.');
    }

    public function destroy(Employer $employer)
    {
        // Delete associated files from storage
        if ($employer->document_company_registration) {
            Storage::disk('public')->delete($employer->document_company_registration);
        }
        if ($employer->document_vat_registration) {
            Storage::disk('public')->delete($employer->document_vat_registration);
        }
        if ($employer->document_map) {
            Storage::disk('public')->delete($employer->document_map);
        }

        $employer->delete();
        return redirect()->route('employers.index')->with('success', 'Employer deleted successfully.');
    }

    // Other methods like export, filter etc.

    public function filterHistory(Request $request, Employer $employer)
    {
        $query = $employer->employees()->whereNotNull('terminated_at');

        // Implement search
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', $searchTerm)
                  ->orWhere('employeeNameEn', 'like', $searchTerm)
                  ->orWhere('employeePassport', 'like', $searchTerm);
            });
        }

        // Paginate results and preserve query string
        $terminatedEmployees = $query->paginate(10)->withQueryString();

        // Add authorization and computed data to each employee object
        $terminatedEmployees->getCollection()->transform(function ($employee) {
            $employee->can_restore = auth()->user()->can('restore-employees');
            $employee->can_force_delete = auth()->user()->can('force-delete-employees');
            // FIX: Generate a full, correct URL for the employee photo
            $employee->photo_url = $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : asset('images/default-avatar.png');
            // ADD: Calculate days since termination
            $employee->days_since_termination = floor($employee->terminated_at ? \Carbon\Carbon::parse($employee->terminated_at)->diffInDays(\Carbon\Carbon::now()) : 0);
            return $employee;
        });

        return response()->json($terminatedEmployees);
    }

    public function exportHistory(Request $request, Employer $employer)
    {
        // Add a 'history' flag to the request and call the main export method.
        $request->merge(['history' => true]);
        return $this->exportEmployees($request, $employer);
    }

    public function exportEmployees(Request $request, Employer $employer)
    {
        $this->authorize('view-employees'); // Or a more specific permission if available

        // Determine the scope: active or terminated (history) employees
        $isHistoryExport = $request->has('history');

        if ($isHistoryExport) {
            $query = $employer->employees()->whereNotNull('terminated_at');
        } else {
            $query = $employer->employees()
                ->whereNull('terminated_at')
                ->where(function($q) {
                    $q->whereNotIn('status', ['registration_pending', 'registration_cancelled'])
                      ->orWhereNull('status');
                });
        }

        // Reuse the same filtering logic from the edit/history methods
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', $searchTerm)
                  ->orWhere('employeeNameEn', 'like', $searchTerm)
                  ->orWhere('employeePassport', 'like', $searchTerm)
                  ->orWhere('pinkCardNo', 'like', $searchTerm);
            });
        }

        if (!$isHistoryExport) {
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

            if ($request->filled('passport_type_myanmar')) {
                $query->where('passportType', $request->input('passport_type_myanmar'));
            }

            if ($request->filled('passport_type_cambodia')) {
                $query->where('passport_type_cambodia', $request->input('passport_type_cambodia'));
            }
        }

        $employees = $query->get();

        $safeEmployerName = preg_replace('/[^A-Za-z0-9\-]/', '_', $employer->employerNameTh);
        $exportType = $isHistoryExport ? 'history' : 'active';
        $fileName = "{$safeEmployerName}_{$exportType}_employees_" . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Encoding"    => "UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Employee Name (TH)', 'Employee Name (EN)', 'Nationality',
            'Passport No', 'Passport Expiry', 'Work Permit No',
            'Work Permit Expiry', 'Visa Expiry', '90 Day Report', 'Pink Card No'
        ];

        if ($isHistoryExport) {
            $columns[] = 'Terminated At';
            $columns[] = 'Termination Reason';
        }

        $callback = function() use($employees, $columns, $isHistoryExport) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // Add BOM for UTF-8
            fputcsv($file, $columns);

            foreach ($employees as $employee) {
                $row = [
                    'Employee Name (TH)'   => $employee->employeeNameTh,
                    'Employee Name (EN)'   => $employee->employeeNameEn,
                    'Nationality'          => $employee->employeeNationality,
                    'Passport No'          => $employee->employeePassport,
                    'Passport Expiry'      => $employee->passportExpiryDate,
                    'Work Permit No'       => $employee->employeeWorkPermit,
                    'Work Permit Expiry'   => $employee->workPermitExpiryDate,
                    'Visa Expiry'          => $employee->visaExpiryDate,
                    '90 Day Report'        => $employee->ninetyDayReportDate,
                    'Pink Card No'         => $employee->pinkCardNo,
                ];

                if ($isHistoryExport) {
                    $row['Terminated At'] = $employee->terminated_at;
                    $row['Termination Reason'] = $employee->termination_reason;
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Exports the filtered list of employers to a CSV file.
     */
    public function export(Request $request)
    {
        $this->authorize('view-employers');

        $query = Employer::with('jobOwner')->latest();

        // --- START: ใช้ Logic การค้นหาเดียวกับ index ---
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';

            $query->where(function($q) use ($searchTerm) {
                $q->where('employerNameTh', 'like', $searchTerm)
                  ->orWhere('employerNameEn', 'like', $searchTerm)
                  ->orWhereHas('jobOwner', function($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', $searchTerm);
                  });
            });
        }
        // --- END: ใช้ Logic การค้นหาเดียวกับ index ---

        $employers = $query->get();

        $fileName = 'employers_export_' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Encoding"    => "UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Employer ID', 'Employer Name (TH)', 'Employer Name (EN)',
            'Job Owner', 'Business Type', 'Tax ID', 'Email', 'Phone'
        ];

        $callback = function() use($employers, $columns) {
            $file = fopen('php://output', 'w');
            // Add BOM to support UTF-8 in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);

            foreach ($employers as $employer) {
                $row['Employer ID']        = $employer->employerId;
                $row['Employer Name (TH)'] = $employer->employerNameTh;
                $row['Employer Name (EN)'] = $employer->employerNameEn;
                $row['Job Owner']          = $employer->jobOwner->name ?? 'N/A';
                $row['Business Type']      = $employer->businessType;
                $row['Tax ID']             = $employer->employerTaxId;
                $row['Email']              = $employer->employerEmail;
                $row['Phone']              = $employer->employerPhone;

                fputcsv($file, array_values($row));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Provides a JSON list of employers for API calls.
     */
    public function listApi(Request $request)
    {
        // This endpoint is used in contexts where a user needs to select an employer.
        // We can reuse the 'manage-tickets' permission as it's a good proxy
        // for "is an admin or staff member".
        $this->authorize('view-employers');

        $query = Employer::query();

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('employerNameTh', 'like', $searchTerm)
                  ->orWhere('employerNameEn', 'like', $searchTerm)
                  ->orWhere('employerId', 'like', $searchTerm);
            });
        }

        $employers = $query->select(['id', 'employerNameTh', 'employerId'])->take(10)->get();

        return response()->json($employers);
    }

    public function locate(Employer $employer)
    {
        return redirect()->route('employers.index')
                         ->with('highlight_employer', $employer->id);
    }

    /**
     * Download a document as PDF (converting images if necessary).
     */
    public function downloadDocumentAsPdf(Employer $employer, $field)
    {
        $allowedFields = [
            'employer_doc_company',
            'employer_doc_lease',
            'employer_doc_construction',
            'employer_doc_other_1',
            'employer_doc_other_2',
            'employer_doc_other_3'
        ];

        if (!in_array($field, $allowedFields)) {
            abort(404, 'Document type not found.');
        }

        $this->authorize('view-employers');

        $filePath = $employer->{$field};
        $disk = 'public';

        if (!$filePath || !Storage::disk($disk)->exists($filePath)) {
            abort(404, 'File not found.');
        }

        $mimeType = Storage::disk($disk)->mimeType($filePath);

        // If it's already a PDF, download it directly
        if ($mimeType === 'application/pdf') {
            return Storage::disk($disk)->download($filePath);
        }

        return \App\Helpers\PdfHelper::streamFile($disk, $filePath);
    }
}