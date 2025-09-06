<?php

namespace App\Http\Controllers;

use App\Models\Employer;
use App\Models\Employee;
use App\Models\User;
use App\Models\Counter;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;

class EmployerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Employer::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('employerNameTh', 'like', "%{$search}%")
                  ->orWhere('employerNameEn', 'like', "%{$search}%")
                  ->orWhere('employerId', 'like', "%{$search}%");
            });
        }

        $employers = $query->get();
        $jobOwners = User::pluck('name', 'id');

        return view('employers.index', compact('employers', 'jobOwners'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $counter = Counter::firstOrCreate(['name' => 'employer'], ['value' => 0]);
        $nextId = $counter->value + 1;
        $newEmployerId = 'MC-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        return view('employers.create', compact('newEmployerId'));
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

        DB::transaction(function () use ($request) {
            $data = $request->except(['document_company_registration', 'document_vat_registration', 'document_map', 'registered_addresses', 'workplace_addresses']);

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

            // Handle Addresses from JSON
            if ($request->filled('registered_addresses')) {
                $addresses = json_decode($request->registered_addresses, true);
                if (is_array($addresses)) {
                    foreach ($addresses as $addressData) {
                        $employer->addresses()->create($addressData);
                    }
                }
            }

            if ($request->filled('workplace_addresses')) {
                $addresses = json_decode($request->workplace_addresses, true);
                if (is_array($addresses)) {
                    foreach ($addresses as $addressData) {
                        $employer->addresses()->create($addressData);
                    }
                }
            }

            Counter::where('name', 'employer')->increment('value');
        });

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

    public function restoreEmployee(Employee $employee)
    {
        $employee->update([
            'terminated_at' => null,
            'termination_reason' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Employee restored successfully.']);
    }

    public function forceDeleteEmployee(Employee $employee)
    {
        // Delete photo from storage if it exists
        if ($employee->employeePhoto && Storage::disk('public')->exists($employee->employeePhoto)) {
            Storage::disk('public')->delete($employee->employeePhoto);
        }

        $employee->forceDelete();

        return response()->json(['success' => true, 'message' => 'Employee permanently deleted.']);
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

    public function export()
    {
        $employers = Employer::all();
        $csvHeader = ['ID', 'Employer ID', 'Thai Name', 'English Name', 'Business Type', 'Signer Name TH', 'Signer Name EN', 'Tax ID', 'Reg Capital', 'Reg Date'];

        $response = new StreamedResponse(function() use ($employers, $csvHeader) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $csvHeader);

            foreach ($employers as $employer) {
                $data = [
                    $employer->id,
                    $employer->employerId,
                    $employer->employerNameTh,
                    $employer->employerNameEn,
                    $employer->businessType,
                    $employer->signerNameTh,
                    $employer->signerNameEn,
                    $employer->employerTaxId,
                    $employer->regCapital,
                    $employer->regDate,
                ];
                fputcsv($handle, $data);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employers.csv"',
        ]);

        return $response;
    }

    public function exportEmployees(Employer $employer)
    {
        $employees = $employer->employees()->whereNull('terminated_at')->get();
        $csvHeader = [
            'ID', 'English Name', 'Thai Name', 'Position', 'Nationality', 'Passport No', 'Passport Expiry',
            'Work Permit No', 'Work Permit Expiry', 'MOU Group', 'Visa Expiry', '90-Day Report'
        ];
        $fileName = "{$employer->employerId}_employees.csv";

        $response = new StreamedResponse(function() use ($employees, $csvHeader) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $csvHeader);

            foreach ($employees as $employee) {
                $data = [
                    $employee->id,
                    $employee->employeeNameEn,
                    $employee->employeeNameTh,
                    $employee->employeePosition,
                    $employee->employeeNationality,
                    $employee->employeePassport,
                    $employee->passportExpiryDate,
                    $employee->employeeWorkPermit,
                    $employee->workPermitExpiryDate,
                    $employee->workPermitMOUGroup,
                    $employee->visaExpiryDate,
                    $employee->ninetyDayReportDate,
                ];
                fputcsv($handle, $data);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);

        return $response;
    }

    public function exportHistory(Employer $employer)
    {
        $employees = $employer->employees()->whereNotNull('terminated_at')->get();
        $csvHeader = [
            'ID', 'English Name', 'Thai Name', 'Position', 'Nationality', 'Passport No',
            'Terminated Date', 'Termination Reason'
        ];
        $fileName = "{$employer->employerId}_history.csv";

        $response = new StreamedResponse(function() use ($employees, $csvHeader) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $csvHeader);

            foreach ($employees as $employee) {
                $data = [
                    $employee->id,
                    $employee->employeeNameEn,
                    $employee->employeeNameTh,
                    $employee->employeePosition,
                    $employee->employeeNationality,
                    $employee->employeePassport,
                    $employee->terminated_at,
                    $employee->termination_reason,
                ];
                fputcsv($handle, $data);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);

        return $response;
    }
}
