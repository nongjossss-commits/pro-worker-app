<?php

namespace App\Http\Controllers;

use App\Models\Employer;
use App\Models\Employee;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EmployerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employers = Employer::all();
        return view('employers.index', compact('employers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('employers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employerNameTh' => 'required',
            'employerNameEn' => 'nullable',
            'employerId' => 'required|unique:employers',
            'employerTaxId' => 'nullable|string|max:255',
            'businessType' => 'nullable|string|max:255',
            'signerNameTh' => 'nullable',
            'signerNameEn' => 'nullable',
            'businessTypeEn' => 'nullable',
            'regCapital' => 'nullable',
            'regDate' => 'nullable|date',
            'minimum_wage' => 'nullable|string',
            'document_company_registration' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_vat_registration' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_map' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $data = $request->except(['document_company_registration', 'document_vat_registration', 'document_map']);

        $employer = Employer::create($data);

        if ($request->hasFile('document_company_registration')) {
            $path = $request->file('document_company_registration')->store("employer_documents/{$employer->id}", 'public');
            $employer->document_company_registration = $path;
        }
        if ($request->hasFile('document_vat_registration')) {
            $path = $request->file('document_vat_registration')->store("employer_documents/{$employer->id}", 'public');
            $employer->document_vat_registration = $path;
        }
        if ($request->hasFile('document_map')) {
            $path = $request->file('document_map')->store("employer_documents/{$employer->id}", 'public');
            $employer->document_map = $path;
        }
        $employer->save();

        return redirect()->route('employers.index')
            ->with('success', 'เพิ่มข้อมูลนายจ้างเรียบร้อยแล้ว');
    }

    /**
     * Display the specified resource.
     */
    public function show(Employer $employer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employer $employer)
    {
        $employees = $employer->employees()->whereNull('terminated_at')->get();
        $terminated_employees = $employer->employees()->whereNotNull('terminated_at')->get();
        return view('employers.edit', compact('employer', 'employees', 'terminated_employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employer $employer)
    {
        $request->validate([
            'employerNameTh' => 'required',
            'employerNameEn' => 'nullable',
            'employerId' => 'required|unique:employers,employerId,' . $employer->id,
            'employerTaxId' => 'nullable|string|max:255',
            'businessType' => 'nullable|string|max:255',
            'signerNameTh' => 'nullable',
            'signerNameEn' => 'nullable',
            'businessTypeEn' => 'nullable',
            'regCapital' => 'nullable',
            'regDate' => 'nullable|date',
            'minimum_wage' => 'nullable|string',
            'document_company_registration' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_vat_registration' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'document_map' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $data = $request->except(['document_company_registration', 'document_vat_registration', 'document_map']);

        if ($request->hasFile('document_company_registration')) {
            $path = $request->file('document_company_registration')->store("employer_documents/{$employer->id}", 'public');
            $data['document_company_registration'] = $path;
        }
        if ($request->hasFile('document_vat_registration')) {
            $path = $request->file('document_vat_registration')->store("employer_documents/{$employer->id}", 'public');
            $data['document_vat_registration'] = $path;
        }
        if ($request->hasFile('document_map')) {
            $path = $request->file('document_map')->store("employer_documents/{$employer->id}", 'public');
            $data['document_map'] = $path;
        }

        $employer->update($data);

        return redirect()->route('employers.index')
            ->with('success', 'อัปเดตข้อมูลนายจ้างเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employer $employer)
    {
        $employer->delete();

        return redirect()->route('employers.index')
            ->with('success', 'ลบข้อมูลนายจ้างเรียบร้อยแล้ว');
    }

    public function terminate(Request $request, Employee $employee)
    {
        $request->validate([
            'terminateDate' => 'required|date',
            'terminationReason' => 'nullable|string',
        ]);

        $employee->terminated_at = Carbon::parse($request->terminateDate);
        $employee->termination_reason = $request->terminationReason;
        $employee->save();

        return response()->json(['success' => true, 'message' => 'Employee terminated successfully.']);
    }

    public function filterEmployees(Request $request, Employer $employer)
    {
        $query = $employer->employees()->whereNull('terminated_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('employeeNameTh', 'like', "%{$search}%")
                  ->orWhere('employeeNameEn', 'like', "%{$search}%")
                  ->orWhere('employeePassport', 'like', "%{$search}%");
            });
        }

        if ($request->filled('nationality')) {
            $query->where('employeeNationality', $request->nationality);
        }

        if ($request->filled('mouGroup')) {
            $query->where('workPermitMOUGroup', $request->mouGroup);
        }

        if ($request->filled('pinkCard')) {
            if ($request->pinkCard === 'has_card') {
                $query->whereNotNull('pinkCardNo')->where('pinkCardNo', '!=', '');
            } elseif ($request->pinkCard === 'no_card') {
                $query->whereNull('pinkCardNo')->orWhere('pinkCardNo', '=', '');
            }
        }

        $employees = $query->get();

        return response()->json($employees);
    }

    public function filterHistory(Request $request, Employer $employer)
    {
        $query = $employer->employees()->whereNotNull('terminated_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('employeeNameTh', 'like', "%{$search}%")
                  ->orWhere('employeeNameEn', 'like', "%{$search}%")
                  ->orWhere('employeePassport', 'like', "%{$search}%");
            });
        }

        $employees = $query->get();

        return response()->json($employees);
    }
}
