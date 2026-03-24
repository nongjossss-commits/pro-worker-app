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
        }

        $validated['status'] = 'quoted';
        $validated['created_by'] = auth()->id();

        SalesLead::create($validated);

        return redirect()->route('sales.index')->with('success', 'สร้างรายการเสนอราคาสำเร็จ');
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
            'document' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
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
        if ($request->hasFile('document')) {
            $validated['document_path'] = $request->file('document')->store('sales_lead_employees/documents', 'public');
        }

        SalesLeadEmployee::create($validated);

        return redirect()->back()->with('success', 'เพิ่มลูกจ้างสำเร็จ');
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
                    // Add other defaults as necessary
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
                    ]);

                    // Handle transferring files to the real employee record
                    // using Spatie Media Library or simple path updates depending on the system implementation.
                    // For now, we update the path references on the real employee model if it supports it,
                    // or just keep them in storage. The main system usually uses Media Library for Employees.
                    if ($slEmp->photo_path) {
                        // $newEmp->addMedia(storage_path('app/public/' . $slEmp->photo_path))->toMediaCollection('employee_photo'); // Example
                        $newEmp->photo_path = $slEmp->photo_path; // Simple fallback
                    }

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
