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

    // --- เพิ่ม Logic ส่วนนี้เข้าไปทั้งหมด ---
    $perPageOptions = [10, 25, 50];
    $currentPerPage = $request->input('per_page', 10);
    $currentView = $request->input('view', 'card');

    $employeesQuery = $employer->employees()->whereNull('termination_date');

    if ($request->has('nationality') && $request->nationality != '') {
        $employeesQuery->where('employeeNationality', $request->nationality);
    }
    if ($request->has('mou_type') && $request->mou_type != '') {
        $employeesQuery->where('workPermitMOUGroup', $request->mou_type);
    }
    if ($request->has('pink_card_status') && $request->pink_card_status != '') {
        if ($request->pink_card_status == 'yes') {
            $employeesQuery->whereNotNull('pinkCardNo')->where('pinkCardNo', '!=', '');
        } else {
            $employeesQuery->where(fn($q) => $q->whereNull('pinkCardNo')->orWhere('pinkCardNo', ''));
        }
    }
    if ($request->has('search') && $request->search != '') {
        $searchTerm = $request->search;
        $employeesQuery->where(function ($q) use ($searchTerm) {
            $q->where('employeeNameTh', 'like', "%{$searchTerm}%")
              ->orWhere('employeeNameEn', 'like', "%{$searchTerm}%")
              ->orWhere('employeePassport', 'like', "%{$searchTerm}%");
        });
    }

    $employees = $employeesQuery->paginate($currentPerPage, ['*'], 'employees_page')->withQueryString();

    $terminatedEmployees = $employer->employees()->whereNotNull('terminated_at')->get();

    return view('employers.edit', compact(
        'employer',
        'jobOwners',
        'employees', // ส่งตัวแปรที่ถูกต้อง
        'terminatedEmployees',
        'perPageOptions', // ส่งตัวแปรที่ขาดไป
        'currentView'     // ส่งตัวแปรที่ขาดไป
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