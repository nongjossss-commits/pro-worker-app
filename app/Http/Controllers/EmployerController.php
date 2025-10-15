<?php

namespace App\Http\Controllers;

use App\Models\Employer;
use App\Models\JobOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\Employee; // <-- เพิ่มบรรทัดนี้

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

    public function index()
    {
        $employers = Employer::with('jobOwner')->latest()->paginate(10);
        return view('employers.index', compact('employers'));
    }

    public function create()
    {
        $jobOwners = JobOwner::orderBy('name')->get();

        // สร้างรหัสนายจ้างใหม่ที่ไม่ซ้ำใคร
        $lastEmployer = Employer::orderBy('id', 'desc')->first();
        $nextId = $lastEmployer ? $lastEmployer->id + 1 : 1;
        $newEmployerId = 'EMP-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

    return view('employers.create', compact('jobOwners', 'newEmployerId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employerNameTh' => 'required|string|max:255',
            'employerNameEn' => 'nullable|string|max:255',
            'employerId' => 'required|string|unique:employers,employerId|max:255',
            'employerTaxId' => 'nullable|string|max:255',
            'businessType' => 'required|string|max:255',
            'signerNameTh' => 'nullable|string|max:255',
            'signerNameEn' => 'nullable|string|max:255',
            'businessTypeEn' => 'nullable|string|max:255',
            'regCapital' => 'nullable|numeric',
            'regDate' => 'nullable|date',
            'minimum_wage' => 'nullable|numeric',
            'document_company_registration' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_vat_registration' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_map' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'job_owner_id' => 'required|exists:job_owners,id',
        ]);

        if ($request->hasFile('document_company_registration')) {
            $validated['document_company_registration'] = $request->file('document_company_registration')->store('employer_documents', 'public');
        }
        if ($request->hasFile('document_vat_registration')) {
            $validated['document_vat_registration'] = $request->file('document_vat_registration')->store('employer_documents', 'public');
        }
        if ($request->hasFile('document_map')) {
            $validated['document_map'] = $request->file('document_map')->store('employer_documents', 'public');
        }

        Employer::create($validated);
        return redirect()->route('employers.index')->with('success', 'Employer created successfully.');
    }

public function edit(Request $request, Employer $employer)
{
    $jobOwners = JobOwner::orderBy('name')->get();

    $employeeQuery = $employer->employees()->whereNull('terminated_at');

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
            'businessType' => 'required|string|max:255',
            'signerNameTh' => 'nullable|string|max:255',
            'signerNameEn' => 'nullable|string|max:255',
            'businessTypeEn' => 'nullable|string|max:255',
            'regCapital' => 'nullable|numeric',
            'regDate' => 'nullable|date',
            'minimum_wage' => 'nullable|numeric',
            'document_company_registration' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_vat_registration' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'document_map' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'job_owner_id' => 'required|exists:job_owners,id',
        ]);

        if ($request->hasFile('document_company_registration')) {
            if ($employer->document_company_registration) {
                Storage::disk('public')->delete($employer->document_company_registration);
            }
            $validated['document_company_registration'] = $request->file('document_company_registration')->store('employer_documents', 'public');
        }
        if ($request->hasFile('document_vat_registration')) {
            if ($employer->document_vat_registration) {
                Storage::disk('public')->delete($employer->document_vat_registration);
            }
            $validated['document_vat_registration'] = $request->file('document_vat_registration')->store('employer_documents', 'public');
        }
        if ($request->hasFile('document_map')) {
            if ($employer->document_map) {
                Storage::disk('public')->delete($employer->document_map);
            }
            $validated['document_map'] = $request->file('document_map')->store('employer_documents', 'public');
        }

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
        $query = $employer->employees()->onlyTrashed();

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

        // Add authorization data to each employee object within the paginated result
        $terminatedEmployees->through(function($employee) {
            $employee->can_restore = auth()->user()->can('restore-employees');
            $employee->can_force_delete = auth()->user()->can('force-delete-employees');
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
            $query = $employer->employees()->whereNull('terminated_at');
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
            if ($request->filled('pink_card')) {
                if ($request->input('pink_card') === 'yes') {
                    $query->where(fn($q) => $q->whereNotNull('pinkCardNo')->where('pinkCardNo', '!=', ''));
                } elseif ($request->input('pink_card') === 'no') {
                    $query->where(fn($q) => $q->whereNull('pinkCardNo')->orWhere('pinkCardNo', '=', ''));
                }
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
}