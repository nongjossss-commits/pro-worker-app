<?php

namespace App\Http\Controllers;

use App\Models\SalesLead;
use App\Models\SalesLeadEmployee;
use App\Models\Employer;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\FinancialProfile;

class SalesLeadController extends Controller
{
    public function index()
    {
        $leads = SalesLead::with(['employees', 'employer', 'quotation'])->orderBy('updated_at', 'desc')->get();
        $employers = Employer::all(['id', 'employerNameTh', 'employerNameEn', 'employerTaxId']);

        $quoted = $leads->where('status', 'quoted');
        $deciding = $leads->where('status', 'deciding');
        $confirmed = $leads->where('status', 'confirmed');
        $history = SalesLead::onlyTrashed()->with(['employees', 'employer'])->orderBy('deleted_at', 'desc')->get()
                            ->merge($leads->whereIn('status', ['transitioned', 'cancelled']));

        // Get profiles for quotation
        $financialProfiles = FinancialProfile::where('is_active', true)->get();

        return view('sales.index', compact('quoted', 'deciding', 'confirmed', 'history', 'employers', 'financialProfiles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employer_id' => 'nullable|exists:employers,id',
            'employerNameTh' => 'required_without:employer_id|string|max:255|nullable',
            'employerNameEn' => 'nullable|string|max:255',
            'employerTaxId' => 'nullable|string|max:255',
            'employerPhone' => 'nullable|string|max:255',
            'jobOwner' => 'nullable|string|max:255',
            'employerEmail' => 'nullable|string|max:255',
            'employerPassword' => 'nullable|string|max:255',
            'outsource_re_code' => 'nullable|string|max:255',
            'outsource_password' => 'nullable|string|max:255',
            'socialSecurityHospital' => 'nullable|string|max:255',
            'businessType' => 'nullable|string|max:255',
            'businessTypeEn' => 'nullable|string|max:255',
            'signerNameTh' => 'nullable|string|max:255',
            'signerNameEn' => 'nullable|string|max:255',
            'signer_2_name_th' => 'nullable|string|max:255',
            'signer_2_name_en' => 'nullable|string|max:255',
            'employer_doc_company' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employer_doc_company_expiry' => 'nullable|date',
            'employer_doc_lease' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employer_doc_construction' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employer_doc_other_1' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employer_doc_other_1_desc' => 'nullable|string|max:255',
            'employer_doc_other_2' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employer_doc_other_2_desc' => 'nullable|string|max:255',
            'employer_doc_other_3' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employer_doc_other_3_desc' => 'nullable|string|max:255',
        ]);

        if ($request->filled('employer_id')) {
            $employer = Employer::find($request->employer_id);
            $validated['employerNameTh'] = $employer->employerNameTh;
            $validated['employerNameEn'] = $employer->employerNameEn;
            $validated['employerTaxId'] = $employer->employerTaxId;
            $validated['employerPhone'] = $employer->employerPhone;
            $validated['jobOwner'] = $employer->jobOwner ? $employer->jobOwner->name_th : null;
        } else {
            // Temporary ID logic
            $validated['employerId'] = 'SL-EMP-' . strtoupper(uniqid());
            $validated['employerEmail'] = $request->employerEmail;
            $validated['employerPassword'] = $request->employerPassword;
            $validated['outsource_re_code'] = $request->outsource_re_code;
            $validated['outsource_password'] = $request->outsource_password;
            $validated['socialSecurityHospital'] = $request->socialSecurityHospital;
            $validated['businessType'] = $request->businessType;
            $validated['businessTypeEn'] = $request->businessTypeEn;
            $validated['signerNameTh'] = $request->signerNameTh;
            $validated['signerNameEn'] = $request->signerNameEn;
            $validated['signer_2_name_th'] = $request->signer_2_name_th;
            $validated['signer_2_name_en'] = $request->signer_2_name_en;

            // Handle file uploads
            $fileFields = [
                'employer_doc_company',
                'employer_doc_lease',
                'employer_doc_construction',
                'employer_doc_other_1',
                'employer_doc_other_2',
                'employer_doc_other_3'
            ];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $validated[$field] = $request->file($field)->store('sales_leads/employers', 'public');
                }
            }
        }

        $validated['status'] = 'quoted';
        $validated['created_by'] = auth()->id();

        SalesLead::create($validated);

        return redirect()->route('sales.index')->with('success', 'สร้างรายการเสนอราคาสำเร็จ');
    }

    public function update(Request $request, SalesLead $sales)
    {
        $validated = $request->validate([
            'employerNameTh' => 'required|string|max:255',
            'employerNameEn' => 'nullable|string|max:255',
            'employerTaxId' => 'nullable|string|max:255',
            'employerPhone' => 'nullable|string|max:255',
            'jobOwner' => 'nullable|string|max:255',
            'employerEmail' => 'nullable|string|max:255',
            'employerPassword' => 'nullable|string|max:255',
            'outsource_re_code' => 'nullable|string|max:255',
            'outsource_password' => 'nullable|string|max:255',
            'socialSecurityHospital' => 'nullable|string|max:255',
            'businessType' => 'nullable|string|max:255',
            'businessTypeEn' => 'nullable|string|max:255',
            'signerNameTh' => 'nullable|string|max:255',
            'signerNameEn' => 'nullable|string|max:255',
            'signer_2_name_th' => 'nullable|string|max:255',
            'signer_2_name_en' => 'nullable|string|max:255',
            'employer_doc_company' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employer_doc_company_expiry' => 'nullable|date',
            'employer_doc_lease' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employer_doc_construction' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employer_doc_other_1' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employer_doc_other_1_desc' => 'nullable|string|max:255',
            'employer_doc_other_2' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employer_doc_other_2_desc' => 'nullable|string|max:255',
            'employer_doc_other_3' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employer_doc_other_3_desc' => 'nullable|string|max:255',
        ]);

        // Handle file uploads
        $fileFields = [
            'employer_doc_company',
            'employer_doc_lease',
            'employer_doc_construction',
            'employer_doc_other_1',
            'employer_doc_other_2',
            'employer_doc_other_3'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = $request->file($field)->store('sales_leads/employers', 'public');
            }
        }

        $sales->update($validated);

        return redirect()->back()->with('success', 'อัปเดตข้อมูลลูกค้าสำเร็จ');
    }

    public function updateStatus(Request $request, SalesLead $sales)
    {
        $request->validate([
            'status' => 'required|in:quoted,deciding,confirmed,transitioned,cancelled'
        ]);

        $sales->status = $request->status;
        if ($request->status === 'confirmed') {
            $sales->confirmed_by = auth()->id();
        }
        $sales->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'อัปเดตสถานะสำเร็จ');
    }

    public function destroy(SalesLead $sales)
    {
        $sales->status = 'cancelled';
        $sales->save();
        $sales->delete(); // Soft delete for history
        return redirect()->back()->with('success', 'ย้ายไปประวัติแล้ว');
    }

    public function restore($id)
    {
        $sales = SalesLead::withTrashed()->findOrFail($id);
        $sales->restore();
        $sales->status = 'quoted'; // Reset to initial state
        $sales->save();
        return redirect()->back()->with('success', 'กู้คืนรายการสำเร็จ');
    }

    // --- Employee Management within Sales Lead ---

    public function storeEmployee(Request $request, SalesLead $sales)
    {
        $validated = $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'employeeNameEn' => 'required_without:employee_id|string|max:255|nullable',
            'employeeNameTh' => 'nullable|string|max:255',
            'employeeGender' => 'nullable|string',
            'employeePassport' => 'nullable|string',
            'employeeWorkPermit' => 'nullable|string',
            'photo' => 'nullable|image|max:5120',
            'employee_doc_1' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employee_doc_2' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employee_doc_visa' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employee_doc_3' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employee_doc_4' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employee_doc_other_1' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'other_doc_1_desc' => 'nullable|string|max:255',
            'employee_doc_other_2' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'other_doc_2_desc' => 'nullable|string|max:255',
            'employee_doc_other_3' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'other_doc_3_desc' => 'nullable|string|max:255',
        ]);

        $validated['sales_lead_id'] = $sales->id;

        if ($request->filled('employee_id')) {
            $employee = Employee::find($request->employee_id);
            $validated['employeeNameEn'] = $employee->employeeNameEn;
            $validated['employeeNameTh'] = $employee->employeeNameTh;
            $validated['employeeGender'] = $employee->employeeGender;
            $validated['employeePassport'] = $employee->employeePassport;
            $validated['employeeWorkPermit'] = $employee->employeeWorkPermit;
        }

        // Handle file uploads
        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('sales_lead_employees/photos', 'public');
        }

        $employeeDocs = [
            'employee_doc_1',
            'employee_doc_2',
            'employee_doc_visa',
            'employee_doc_3',
            'employee_doc_4',
            'employee_doc_other_1',
            'employee_doc_other_2',
            'employee_doc_other_3'
        ];

        foreach ($employeeDocs as $doc) {
            if ($request->hasFile($doc)) {
                $validated[$doc] = $request->file($doc)->store('sales_lead_employees/documents', 'public');
            }
        }

        SalesLeadEmployee::create($validated);

        return redirect()->back()->with('success', 'เพิ่มลูกจ้างสำเร็จ');
    }

    public function updateEmployee(Request $request, SalesLead $sales, SalesLeadEmployee $employee)
    {
        $validated = $request->validate([
            'employeeNameEn' => 'required|string|max:255',
            'employeeNameTh' => 'nullable|string|max:255',
            'employeeGender' => 'nullable|string',
            'employeePassport' => 'nullable|string',
            'employeeWorkPermit' => 'nullable|string',
            'photo' => 'nullable|image|max:5120',
            'employee_doc_1' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employee_doc_2' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employee_doc_visa' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employee_doc_3' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employee_doc_4' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'employee_doc_other_1' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'other_doc_1_desc' => 'nullable|string|max:255',
            'employee_doc_other_2' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'other_doc_2_desc' => 'nullable|string|max:255',
            'employee_doc_other_3' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'other_doc_3_desc' => 'nullable|string|max:255',
        ]);

        // Handle file uploads
        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('sales_lead_employees/photos', 'public');
        }

        $employeeDocs = [
            'employee_doc_1',
            'employee_doc_2',
            'employee_doc_visa',
            'employee_doc_3',
            'employee_doc_4',
            'employee_doc_other_1',
            'employee_doc_other_2',
            'employee_doc_other_3'
        ];

        foreach ($employeeDocs as $doc) {
            if ($request->hasFile($doc)) {
                $validated[$doc] = $request->file($doc)->store('sales_lead_employees/documents', 'public');
            }
        }

        $employee->update($validated);

        return redirect()->back()->with('success', 'อัปเดตข้อมูลลูกจ้างสำเร็จ');
    }

    public function destroyEmployee(SalesLead $sales, SalesLeadEmployee $employee)
    {
        $employee->delete();
        return redirect()->back()->with('success', 'ลบลูกจ้างสำเร็จ');
    }

    // --- Quotation ---
    public function storeQuotation(Request $request, SalesLead $sales)
    {
        $validated = $request->validate([
            'financial_profile_id' => 'required|exists:financial_profiles,id',
            'quotation_date' => 'required|date',
            'payment_terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $grandTotal = 0;
        $itemsData = [];
        foreach ($request->items as $item) {
            $qty = $item['qty'] ?? 1;
            $price = $item['price'] ?? 0;
            $total = $qty * $price;
            $grandTotal += $total;

            $itemsData[] = [
                'description' => $item['description'],
                'qty' => $qty,
                'price' => $price,
                'total' => $total,
            ];
        }

        $sales->quotation()->updateOrCreate(
            ['sales_lead_id' => $sales->id],
            [
                'financial_profile_id' => $validated['financial_profile_id'],
                'quotation_date' => $validated['quotation_date'],
                'payment_terms' => $validated['payment_terms'] ?? null,
                'grand_total' => $grandTotal,
                'items_data' => $itemsData,
            ]
        );

        return redirect()->back()->with('success', 'บันทึกใบเสนอราคาสำเร็จ');
    }

    // --- Transition to Production/Workflow ---

    public function transition(Request $request, SalesLead $sales)
    {
        $request->validate([
            'destination' => 'required|string', // e.g. 'production.registration', 'production.renewal', 'workflow.notify_out'
        ]);

        DB::beginTransaction();
        try {
            // 1. Create Real Employer if it doesn't exist
            $realEmployer = null;
            if ($sales->employer_id) {
                $realEmployer = Employer::find($sales->employer_id);
            } else {
                // Must generate a real employerId that satisfies constraints (EMP-REG-XXX etc.)
                $lastEmployer = Employer::orderBy('id', 'desc')->first();
                $nextId = $lastEmployer ? $lastEmployer->id + 1 : 1;
                $employerId = 'EMP-SL-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

                $realEmployer = Employer::create([
                    'employerId' => $employerId,
                    'employerNameTh' => $sales->employerNameTh,
                    'employerNameEn' => $sales->employerNameEn,
                    'employerTaxId' => $sales->employerTaxId,
                    'employerPhone' => $sales->employerPhone,
                    'employerEmail' => $sales->employerEmail,
                    'employerPassword' => $sales->employerPassword,
                    'outsource_re_code' => $sales->outsource_re_code,
                    'outsource_password' => $sales->outsource_password,
                    'socialSecurityHospital' => $sales->socialSecurityHospital,
                    'businessType' => $sales->businessType,
                    'businessTypeEn' => $sales->businessTypeEn,
                    'signerNameTh' => $sales->signerNameTh,
                    'signerNameEn' => $sales->signerNameEn,
                    'signer_2_name_th' => $sales->signer_2_name_th,
                    'signer_2_name_en' => $sales->signer_2_name_en,
                    'employer_doc_company' => $sales->employer_doc_company,
                    'employer_doc_company_expiry' => $sales->employer_doc_company_expiry,
                    'employer_doc_lease' => $sales->employer_doc_lease,
                    'employer_doc_construction' => $sales->employer_doc_construction,
                    'employer_doc_other_1' => $sales->employer_doc_other_1,
                    'employer_doc_other_1_desc' => $sales->employer_doc_other_1_desc,
                    'employer_doc_other_2' => $sales->employer_doc_other_2,
                    'employer_doc_other_2_desc' => $sales->employer_doc_other_2_desc,
                    'employer_doc_other_3' => $sales->employer_doc_other_3,
                    'employer_doc_other_3_desc' => $sales->employer_doc_other_3_desc,
                ]);
                $sales->employer_id = $realEmployer->id;
                $sales->save();
            }

            // 2. Create Real Employees
            $realEmployeeIds = [];
            foreach ($sales->employees as $slEmp) {
                if ($slEmp->employee_id) {
                    $realEmployeeIds[] = $slEmp->employee_id;
                } else {
                    $newEmp = Employee::create([
                        'employer_id' => $realEmployer->id,
                        'employeeNameEn' => $slEmp->employeeNameEn,
                        'employeeNameTh' => $slEmp->employeeNameTh,
                        'employeeGender' => $slEmp->employeeGender ?? 'Not Specified',
                        'employeePassport' => $slEmp->employeePassport,
                        'employeeWorkPermit' => $slEmp->employeeWorkPermit,
                        'status' => 'active', // Default status
                        'employee_doc_1' => $slEmp->employee_doc_1,
                        'employee_doc_2' => $slEmp->employee_doc_2,
                        'employee_doc_visa' => $slEmp->employee_doc_visa,
                        'employee_doc_3' => $slEmp->employee_doc_3,
                        'employee_doc_4' => $slEmp->employee_doc_4,
                        'employee_doc_other_1' => $slEmp->employee_doc_other_1,
                        'other_doc_1_desc' => $slEmp->other_doc_1_desc,
                        'employee_doc_other_2' => $slEmp->employee_doc_other_2,
                        'other_doc_2_desc' => $slEmp->other_doc_2_desc,
                        'employee_doc_other_3' => $slEmp->employee_doc_other_3,
                        'other_doc_3_desc' => $slEmp->other_doc_3_desc,
                        'photo_path' => $slEmp->photo_path,
                    ]);

                    $newEmp->save();

                    $slEmp->employee_id = $newEmp->id;
                    $slEmp->save();
                    $realEmployeeIds[] = $newEmp->id;
                }
            }

            // 3. Mark as transitioned
            $sales->status = 'transitioned';
            $sales->workflow_destination = $request->destination;
            $sales->save();

            // 4. Generate actual Finance Transaction from Quotation if exists
            if ($sales->quotation) {
                // We create a generic FinancialTransaction linked to the employer.
                // The main system's finance module uses `FinancialTransaction` Model.
                if (class_exists(\App\Models\FinancialTransaction::class)) {
                    $transaction = \App\Models\FinancialTransaction::create([
                        'employer_id' => $realEmployer->id,
                        'financial_profile_id' => $sales->quotation->financial_profile_id,
                        'type' => 'income',
                        'status' => 'pending',
                        'amount' => $sales->quotation->grand_total,
                        'transaction_date' => now(),
                        'reference_number' => 'SL-' . str_pad($sales->id, 5, '0', STR_PAD_LEFT),
                        'remarks' => 'Generated from Read and Sale Quotation',
                        'created_by' => auth()->id(),
                    ]);

                    // Iterate items to create transaction items
                    if (class_exists(\App\Models\FinancialTransactionItem::class) && is_array($sales->quotation->items_data)) {
                        foreach ($sales->quotation->items_data as $item) {
                            \App\Models\FinancialTransactionItem::create([
                                'transaction_id' => $transaction->id,
                                'description' => $item['description'],
                                'quantity' => $item['qty'],
                                'unit_price' => $item['price'],
                                'total_amount' => $item['total'],
                            ]);
                        }
                    }
                }
            }

            // 5. Redirect the user to the respective page
            DB::commit();

            $destRoute = 'dashboard';
            if ($request->destination === 'production.registration') $destRoute = 'production.registration.index';
            if ($request->destination === 'production.renewal') $destRoute = 'production.renewal.index';
            if (str_starts_with($request->destination, 'workflow.')) $destRoute = 'workflow.index';

            return redirect()->route($destRoute, [
                'highlight_employer_id' => $realEmployer->id,
            ])->with('success', 'โอนข้อมูลสำเร็จ กรุณาสร้างรายการงานต่อในหน้านี้');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
