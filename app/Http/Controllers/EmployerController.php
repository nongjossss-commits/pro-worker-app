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

public function edit(Request $request, Employer $employer) // เพิ่ม Request $request
{
    $jobOwners = JobOwner::orderBy('name')->get();

    $jobOwners = JobOwner::orderBy('name')->get();

    // Find the line that starts with "$employeesQuery = ..." and replace the entire filtering block

$employeesQuery = $employer->employees()->whereNull('termination_date');

// Search functionality
$employeesQuery->when($request->filled('search'), function ($q) use ($request) {
    $searchTerm = $request->search;
    $q->where(function ($subQuery) use ($searchTerm) {
        $subQuery->where('employeeNameTh', 'like', "%{$searchTerm}%")
                 ->orWhere('employeeNameEn', 'like', "%{$searchTerm}%")
                 ->orWhere('employeePassport', 'like', "%{$searchTerm}%");
    });
});

// Filter functionality
$employeesQuery->when($request->filled('nationality'), fn($q) => $q->where('employeeNationality', $request->nationality));
$employeesQuery->when($request->filled('mou_type'), fn($q) => $q->where('workPermitMOUGroup', $request->mou_type));
$employeesQuery->when($request->filled('pink_card_status'), function ($q) use ($request) {
    if ($request->pink_card_status === 'yes') {
        $q->whereNotNull('pinkCardNo')->where('pinkCardNo', '!=', '');
    } elseif ($request->pink_card_status === 'no') {
        $q->where(fn($sub) => $sub->whereNull('pinkCardNo')->orWhere('pinkCardNo', ''));
    }
});

$employees = $employeesQuery->paginate(10, ['*'], 'employees_page')->withQueryString();

    $terminatedEmployees = $employer->employees()->whereNotNull('termination_date')->get(); // Assuming you still need this for a modal or separate list

    return view('employers.edit', compact(
        'employer',
        'jobOwners',
        'employees',
        'terminatedEmployees'
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

    // --- เพิ่ม Method ที่ขาดหายไป ---
    public function terminate(Request $request, Employee $employee)
    {
        $this->authorize('terminate-employees'); // ป้องกันด้วย Permission โดยตรง

        $validated = $request->validate([
            'terminated_at' => 'required|date',
            'termination_reason' => 'nullable|string',
        ]);

        $isSuccess = $employee->update([
            'terminated_at' => $validated['terminated_at'],
            'termination_reason' => $validated['termination_reason'],
        ]);

        if ($isSuccess) {
            return response()->json(['success' => true, 'message' => 'Employee terminated successfully.']);
        } else {
            return response()->json(['success' => false, 'message' => 'Failed to terminate employee. Please try again.'], 422);
        }
    }

    public function restoreEmployee(Employee $employee)
    {
        $this->authorize('restore-employees');
        $employee->update(['terminated_at' => null, 'termination_reason' => null]);
        return response()->json(['success' => true, 'message' => 'Employee restored successfully.']);
    }

    public function forceDeleteEmployee(Employee $employee)
    {
        $this->authorize('force-delete-employees');
        $employee->delete();
        return response()->json(['success' => true, 'message' => 'Employee permanently deleted.']);
    }

    public function filterHistory(Employer $employer)
    {
        // This logic will be more complex later, for now just fetch all
        $terminatedEmployees = $employer->employees()->whereNotNull('terminated_at')->get()->map(function($employee) {
            // Add authorization checks to each employee object for the frontend
            $employee->can_restore = auth()->user()->can('restore-employees');
            $employee->can_force_delete = auth()->user()->can('force-delete-employees');
            return $employee;
        });
        return response()->json($terminatedEmployees);
    }
}