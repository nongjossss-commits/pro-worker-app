<?php

namespace App\Http\Controllers;

use App\Models\SalesLead;
use App\Models\SalesLeadEmployee;
use App\Models\Employer;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\FinancialProfile;
use App\Models\ProductionOrder;

class SalesLeadController extends Controller
{
    public function index()
    {
        $leads = SalesLead::with(['employees', 'employer', 'quotation'])->orderBy('updated_at', 'desc')->get();
        $employers = Employer::all(['id', 'employerNameTh', 'employerNameEn', 'name_suffix', 'employerTaxId']);

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
            'name_suffix' => 'nullable|string|max:255',
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
            $validated['employerNameTh'] = $employer->getRawOriginal('employerNameTh');
            $validated['employerNameEn'] = $employer->employerNameEn;
            $validated['name_suffix'] = $employer->name_suffix;
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
            'name_suffix' => 'nullable|string|max:255',
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
        // Text/date field validation
        $textRules = [
            'employee_id' => 'nullable|exists:employees,id',
            'employeeNameEn' => 'required_without:employee_id|string|max:255|nullable',
            'name_suffix' => 'nullable|string|max:255',
            'employeeNameTh' => 'nullable|string|max:255',
            'employeeTitleTh' => 'nullable|string|max:50',
            'employeeTitleEn' => 'nullable|string|max:50',
            'employeeGender' => 'nullable|string|max:20',
            'employeeDob' => 'nullable|date',
            'height' => 'nullable|string|max:10',
            'weight' => 'nullable|string|max:10',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'employeePhone' => 'nullable|string|max:50',
            'employeeNationality' => 'nullable|string|max:100',
            'passportType' => 'nullable|string|max:50',
            'passport_type_cambodia' => 'nullable|string|max:50',
            'employeePassport' => 'nullable|string|max:100',
            'passport_issue_place' => 'nullable|string|max:255',
            'passport_issue_date' => 'nullable|date',
            'passportExpiryDate' => 'nullable|date',
            'pinkCardNo' => 'nullable|string|max:100',
            'visaType' => 'nullable|string|max:100',
            'visa_issue_place' => 'nullable|string|max:255',
            'visaExpiryDate' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'job_description' => 'nullable|string|max:255',
            'startDate' => 'nullable|date',
            'employeeWorkPermit' => 'nullable|string|max:100',
            'workPermitExpiryDate' => 'nullable|date',
            'ninetyDayReportDate' => 'nullable|date',
            'workPermitMOUGroup' => 'nullable|string|max:100',
            'workPermitMOUGroupOther' => 'nullable|string|max:255',
            'name_list_number' => 'nullable|string|max:100',
            'request_number' => 'nullable|string|max:100',
            'employee_id_number' => 'nullable|string|max:100',
            'tax_id_number' => 'nullable|string|max:100',
            'employer_employee_id' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:255',
            'employee_reference_id' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'insurance_type' => 'nullable|string|max:100',
            'social_security_number' => 'nullable|string|max:100',
            'insurance_detail_social' => 'nullable|string|max:255',
            'sso_issue_date' => 'nullable|date',
            'sso_expiry_date' => 'nullable|date',
            'insurance_detail_hospital' => 'nullable|string|max:255',
            'insurance_expiry_date_hospital' => 'nullable|date',
            'insurance_detail_private' => 'nullable|string|max:255',
            'insurance_expiry_date_private' => 'nullable|date',
            'medical_hospital_name' => 'nullable|string|max:255',
            'employeeEmail' => 'nullable|string|max:255',
            'employeePassword' => 'nullable|string|max:255',
            'outsource_code' => 'nullable|string|max:255',
        ];

        // File validation
        $fileRules = ['photo' => 'nullable|image|max:5120'];
        $fileFields = ['employee_doc_1','employee_doc_2','employee_doc_visa','employee_doc_3','employee_doc_4',
            'employee_doc_5','employee_doc_6','employee_doc_7','employee_doc_8',
            'employee_doc_9','employee_doc_10','employee_doc_11','employee_doc_12','employee_doc_13',
            'employee_doc_14','employee_doc_15','employee_doc_16','employee_doc_17','employee_doc_18',
            'employee_doc_other_1','employee_doc_other_2','employee_doc_other_3',
            'medical_certificate_path','insurance_document_path_social',
            'insurance_document_path_hospital','insurance_document_path_private'];
        foreach ($fileFields as $f) {
            $fileRules[$f] = 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240';
        }

        // Description fields
        $descRules = [];
        for ($i = 1; $i <= 10; $i++) {
            $descRules["other_doc_{$i}_desc"] = 'nullable|string|max:255';
        }

        $validated = $request->validate(array_merge($textRules, $fileRules, $descRules));

        $validated['sales_lead_id'] = $sales->id;

        if ($request->filled('employee_id')) {
            $employee = Employee::find($request->employee_id);
            $validated['employeeNameEn'] = $employee->getRawOriginal('employeeNameEn');
            $validated['name_suffix'] = $employee->name_suffix;
            $validated['employeeNameTh'] = $employee->employeeNameTh;
            $validated['employeeGender'] = $employee->employeeGender;
            $validated['employeePassport'] = $employee->employeePassport;
            $validated['employeeWorkPermit'] = $employee->employeeWorkPermit;
            $validated['employeeNationality'] = $employee->employeeNationality ?? null;
            $validated['employeeDob'] = $employee->employeeDob ?? null;
            $validated['pinkCardNo'] = $employee->pinkCardNo ?? null;
            $validated['insurance_type'] = $employee->insuranceType ?? null;
        }

        // Handle photo
        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('sales_lead_employees/photos', 'public');
        }

        // Handle all document file uploads
        $allFileFields = array_merge($fileFields);
        foreach ($allFileFields as $doc) {
            if ($request->hasFile($doc)) {
                $validated[$doc] = $request->file($doc)->store('sales_lead_employees/documents', 'public');
            }
        }

        SalesLeadEmployee::create($validated);

        return redirect()->back()->with('success', 'เพิ่มลูกจ้างสำเร็จ');
    }

    public function updateEmployee(Request $request, SalesLead $sales, SalesLeadEmployee $employee)
    {
        $textRules = [
            'employeeNameEn' => 'required|string|max:255',
            'name_suffix' => 'nullable|string|max:255',
            'employeeNameTh' => 'nullable|string|max:255',
            'employeeTitleTh' => 'nullable|string|max:50',
            'employeeTitleEn' => 'nullable|string|max:50',
            'employeeGender' => 'nullable|string|max:20',
            'employeeDob' => 'nullable|date',
            'height' => 'nullable|string|max:10',
            'weight' => 'nullable|string|max:10',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'employeePhone' => 'nullable|string|max:50',
            'employeeNationality' => 'nullable|string|max:100',
            'passportType' => 'nullable|string|max:50',
            'passport_type_cambodia' => 'nullable|string|max:50',
            'employeePassport' => 'nullable|string|max:100',
            'passport_issue_place' => 'nullable|string|max:255',
            'passport_issue_date' => 'nullable|date',
            'passportExpiryDate' => 'nullable|date',
            'pinkCardNo' => 'nullable|string|max:100',
            'visaType' => 'nullable|string|max:100',
            'visa_issue_place' => 'nullable|string|max:255',
            'visaExpiryDate' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'job_description' => 'nullable|string|max:255',
            'startDate' => 'nullable|date',
            'employeeWorkPermit' => 'nullable|string|max:100',
            'workPermitExpiryDate' => 'nullable|date',
            'ninetyDayReportDate' => 'nullable|date',
            'workPermitMOUGroup' => 'nullable|string|max:100',
            'workPermitMOUGroupOther' => 'nullable|string|max:255',
            'name_list_number' => 'nullable|string|max:100',
            'request_number' => 'nullable|string|max:100',
            'employee_id_number' => 'nullable|string|max:100',
            'tax_id_number' => 'nullable|string|max:100',
            'employer_employee_id' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:255',
            'employee_reference_id' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'insurance_type' => 'nullable|string|max:100',
            'social_security_number' => 'nullable|string|max:100',
            'insurance_detail_social' => 'nullable|string|max:255',
            'sso_issue_date' => 'nullable|date',
            'sso_expiry_date' => 'nullable|date',
            'insurance_detail_hospital' => 'nullable|string|max:255',
            'insurance_expiry_date_hospital' => 'nullable|date',
            'insurance_detail_private' => 'nullable|string|max:255',
            'insurance_expiry_date_private' => 'nullable|date',
            'medical_hospital_name' => 'nullable|string|max:255',
            'employeeEmail' => 'nullable|string|max:255',
            'employeePassword' => 'nullable|string|max:255',
            'outsource_code' => 'nullable|string|max:255',
        ];

        $fileRules = ['photo' => 'nullable|image|max:5120'];
        $fileFields = ['employee_doc_1','employee_doc_2','employee_doc_visa','employee_doc_3','employee_doc_4',
            'employee_doc_5','employee_doc_6','employee_doc_7','employee_doc_8',
            'employee_doc_9','employee_doc_10','employee_doc_11','employee_doc_12','employee_doc_13',
            'employee_doc_14','employee_doc_15','employee_doc_16','employee_doc_17','employee_doc_18',
            'employee_doc_other_1','employee_doc_other_2','employee_doc_other_3',
            'medical_certificate_path','insurance_document_path_social',
            'insurance_document_path_hospital','insurance_document_path_private'];
        foreach ($fileFields as $f) {
            $fileRules[$f] = 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240';
        }

        $descRules = [];
        for ($i = 1; $i <= 10; $i++) {
            $descRules["other_doc_{$i}_desc"] = 'nullable|string|max:255';
        }

        $validated = $request->validate(array_merge($textRules, $fileRules, $descRules));

        // Handle photo
        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('sales_lead_employees/photos', 'public');
        }

        // Handle all document file uploads
        foreach ($fileFields as $doc) {
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

    // --- Document Generation ---
    public function generateDocument(SalesLead $sales, $type)
    {
        $sales->load(['quotation', 'employees']);

        if (!$sales->quotation) {
            return back()->with('error', 'กรุณาสร้างใบเสนอราคาก่อนพิมพ์เอกสาร');
        }

        $quotation = $sales->quotation;

        // Get CompanyProfile from financial_profile_id
        $profile = null;
        if ($quotation->financial_profile_id) {
            $fp = \App\Models\FinancialProfile::find($quotation->financial_profile_id);
            if ($fp) {
                $profile = \App\Models\CompanyProfile::where('name', 'like', '%' . $fp->name . '%')->first();
            }
        }
        if (!$profile) {
            $profile = \App\Models\CompanyProfile::where('is_default', true)->first();
        }
        if (!$profile) {
            $profile = new \App\Models\CompanyProfile([
                'name' => 'Company Name (Default)',
                'address' => 'Please configure a company profile in settings.',
                'tax_id' => '0000000000000',
            ]);
        }

        // Build fake production object for the document layout
        $employer = $sales->employer;
        $production = (object) [
            'id' => $sales->id,
            'employer' => $employer ?? (object) [
                'employerNameTh' => $sales->employerNameTh,
                'employerNameEn' => $sales->employerNameEn ?? '',
                'employerTaxId' => $sales->employerTaxId ?? '',
                'employerPhone' => $sales->employerPhone ?? '',
                'addresses' => collect(),
            ],
            'items' => collect(),
            'financialGroups' => collect(),
        ];

        // Build financial data from quotation
        $financial = [
            'pricing_mode' => 'fixed',
            'total_amount' => $quotation->grand_total,
            'vat_enabled' => false,
            'vat_rate' => 7,
            'vat_included' => false,
            'wht_enabled' => false,
            'wht_rate' => 3,
            'pricing_tiers' => [],
        ];

        // Build transactions from quotation items
        $transactions = collect();
        $activeGroup = null;

        $viewName = match ($type) {
            'quotation' => 'documents.quotation',
            'invoice' => 'documents.invoice',
            'receipt' => 'documents.receipt',
            'tax_invoice' => 'documents.tax_invoice',
            default => 'documents.quotation',
        };

        return view($viewName, compact('production', 'profile', 'financial', 'transactions', 'type', 'activeGroup'));
    }

    // --- Payments (Pre-Production) ---
    public function storeSalesPayment(Request $request, SalesLead $sales)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $quotation = $sales->quotation;
        if (!$quotation) {
            return redirect()->back()->with('error', 'กรุณาสร้างใบเสนอราคาก่อน');
        }

        $payments = $quotation->payments_data ?? [];
        $payments[] = [
            'id' => count($payments) + 1,
            'amount' => (float) $request->amount,
            'paid_at' => $request->paid_at,
            'notes' => $request->notes,
            'created_at' => now()->toDateTimeString(),
        ];

        $totalPaid = collect($payments)->sum('amount');
        $status = $totalPaid >= $quotation->grand_total ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');

        $quotation->update([
            'payments_data' => $payments,
            'total_paid' => $totalPaid,
            'payment_status' => $status,
        ]);

        return redirect()->back()->with('success', 'บันทึกการชำระเงินสำเร็จ');
    }

    public function destroySalesPayment(SalesLead $sales, $paymentIndex)
    {
        $quotation = $sales->quotation;
        if (!$quotation) {
            return redirect()->back()->with('error', 'ไม่พบข้อมูลใบเสนอราคา');
        }

        $payments = $quotation->payments_data ?? [];
        if (isset($payments[$paymentIndex])) {
            array_splice($payments, $paymentIndex, 1);
        }

        $totalPaid = collect($payments)->sum('amount');
        $status = $totalPaid >= $quotation->grand_total ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');

        $quotation->update([
            'payments_data' => $payments,
            'total_paid' => $totalPaid,
            'payment_status' => $status,
        ]);

        return redirect()->back()->with('success', 'ลบรายการชำระเงินสำเร็จ');
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
                    'employerNameTh' => $sales->getRawOriginal('employerNameTh'),
                    'employerNameEn' => $sales->employerNameEn,
                    'name_suffix' => $sales->name_suffix,
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
                        'employeeNameEn' => $slEmp->getRawOriginal('employeeNameEn'),
                        'name_suffix' => $slEmp->name_suffix,
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

    /**
     * Load the full Financial Tab for a sales lead (same as production/registration).
     */
    public function loadFinancialTab(SalesLead $sales)
    {
        // Find or create a production order for this sales lead
        $financeOrder = ProductionOrder::where('sales_lead_id', $sales->id)->first();

        if (!$financeOrder) {
            $financeOrder = ProductionOrder::create([
                'employer_id'   => $sales->employer_id, // nullable for temp employers
                'sales_lead_id' => $sales->id,
                'status'        => 'sales_quotation',
                'type'          => 'employer',
                'project_name'  => 'Sales Quotation - ' . $sales->employerNameTh,
                'financial_data' => [],
            ]);
        }

        // Create default financial group if empty
        if ($financeOrder->financialGroups->isEmpty()) {
            $financeOrder->financialGroups()->create([
                'name' => 'General',
                'financial_data' => $financeOrder->financial_data ?? [],
            ]);
        }

        // Load relationships needed by the financial-tab partial
        $financeOrder->load([
            'financialGroups.transactions.items',
            'financialGroups.transactions.payments',
            'financialGroups.advanceItems',
            'items.employee',
        ]);

        // Sync sales lead employees → production items (so financial tab can use them)
        $sales->load('employees.employee');

        foreach ($sales->employees as $slemp) {
            if ($slemp->employee_id) {
                // Real employee: create ProductionItem if not exists
                $financeOrder->items()->firstOrCreate(
                    ['employee_id' => $slemp->employee_id],
                    ['status' => 'pending']
                );
            } else {
                // Temp employee: create ProductionItem with new_employee_data
                $existsTempItem = $financeOrder->items()
                    ->whereNull('employee_id')
                    ->whereJsonContains('new_employee_data->sales_lead_employee_id', $slemp->id)
                    ->exists();

                if (!$existsTempItem) {
                    $financeOrder->items()->create([
                        'employee_id' => null,
                        'status' => 'pending',
                        'new_employee_data' => [
                            'sales_lead_employee_id' => $slemp->id,
                            'name_en' => $slemp->employeeNameEn,
                            'name_th' => $slemp->employeeNameTh,
                            'title_en' => $slemp->employeeTitleEn,
                            'nationality' => $slemp->employeeNationality,
                            'passport' => $slemp->employeePassport,
                            'gender' => $slemp->employeeGender,
                        ],
                    ]);
                }
            }
        }

        // Reload items after sync
        $financeOrder->load(['items.employee.registrationSteps']);

        // Employees available to add (real employees NOT yet in production items)
        $existingItemEmployeeIds = $financeOrder->items->pluck('employee_id')->filter()->toArray();
        $availableEmployees = collect();

        $realEmployeeIds = $sales->employees()->whereNotNull('employee_id')->pluck('employee_id');
        if ($realEmployeeIds->isNotEmpty()) {
            $availableEmployees = Employee::whereIn('id', $realEmployeeIds)
                ->whereNotIn('id', $existingItemEmployeeIds)
                ->with(['registrationSteps'])
                ->get();
        }

        return view('production.partials.financial-tab', [
            'production'    => $financeOrder,
            'employeeCount' => $sales->employees->count(),
            'employees'     => $availableEmployees,
        ]);
    }
}
