<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\Employer;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\WorkflowBarrier;

class ProductionController extends Controller
{
    /**
     * Display a listing of Production Orders (Dashboard).
     */
    public function index()
    {
        $orders = ProductionOrder::with('employer')
                    ->withCount('items')
                    ->latest()
                    ->paginate(15);

        return view('production.index', compact('orders'));
    }

    /**
     * Show the form for creating a new Production Order (Pre-Production).
     */
    public function create()
    {
        // For the employer selector
        // We might want to limit this or use an AJAX search for performance if many employers exist.
        // For now, passing all employers or using the existing API endpoint logic in view.
        return view('production.create');
    }

    /**
     * Store a newly created Production Order in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'project_name' => 'nullable|string|max:255',
            'selected_employees' => 'nullable|array', // IDs of existing employees
            'selected_employees.*' => 'exists:employees,id',
            'new_employees' => 'nullable|array', // Array of new employee data
            'financial.quotation_no' => 'nullable|string',
            'financial.invoice_no' => 'nullable|string',
            'financial.total_amount' => 'nullable|numeric',
            'financial.paid_amount' => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        try {
            $order = ProductionOrder::create([
                'employer_id' => $request->employer_id,
                'project_name' => $request->project_name,
                'description' => $request->description,
                'status' => 'pre_production', // Starts in Pre-Production
                'financial_data' => $request->financial,
                'created_by' => auth()->id(),
            ]);

            // 1. Attach Existing Employees
            if ($request->has('selected_employees')) {
                foreach ($request->selected_employees as $empId) {
                    ProductionItem::create([
                        'production_order_id' => $order->id,
                        'employee_id' => $empId,
                    ]);
                }
            }

            // 2. Create & Attach New Employees
            if ($request->has('new_employees')) {
                foreach ($request->new_employees as $newEmpData) {
                    // Minimal creation - logic can be expanded to match full EmployeeController validation
                    // Assuming basic fields: name, passport, etc.
                    // Important: These are linked to the Employer

                    // We need to handle potential 'title' mapping if needed, or just save raw
                    $employee = Employee::create([
                        'employer_id' => $request->employer_id,
                        'employeeTitleTh' => $newEmpData['title'] ?? null,
                        'employeeFirstNameTh' => $newEmpData['name_th'] ?? null,
                        'employeeLastNameTh' => $newEmpData['surname_th'] ?? null,
                        'employeeFirstNameEn' => $newEmpData['name_en'] ?? null,
                        'employeeLastNameEn' => $newEmpData['surname_en'] ?? null,
                        'employeePassport' => $newEmpData['passport_no'] ?? null,
                        'employeeNationality' => $newEmpData['nationality'] ?? null,
                        // Add defaults for required fields if any
                    ]);

                    ProductionItem::create([
                        'production_order_id' => $order->id,
                        'employee_id' => $employee->id,
                    ]);
                }
            }

            DB::commit();

            // Redirect to the "Preparation" view (Edit mode of Pre-Production)
            return redirect()->route('production.edit', $order->id)->with('success', 'Project created in Pre-Production.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating project: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     * If status is 'active', show the Workflow tracking view.
     * If 'pre_production', show the Preparation view.
     */
    public function show(ProductionOrder $production) // Route param name mismatch fix: route uses {production}
    {
        $production->load(['items.employee', 'items.currentBarrier', 'employer']);

        if ($production->status === 'pre_production') {
            return redirect()->route('production.edit', $production->id);
        }

        $barriers = WorkflowBarrier::orderBy('sequence')->get();
        return view('production.workflow_dashboard', compact('production', 'barriers'));
    }

    /**
     * Show the form for editing (The Pre-Production Preparation Page).
     */
    public function edit($id)
    {
        $production = ProductionOrder::with(['items.employee', 'employer'])->findOrFail($id);

        if ($production->status !== 'pre_production') {
            return redirect()->route('production.show', $production->id);
        }

        return view('production.prepare', compact('production'));
    }

    /**
     * Update the specified resource in storage.
     * Used to save changes during Preparation or to "Send to Workflow".
     */
    public function update(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);

        // Check if we are "Starting Workflow"
        if ($request->has('start_workflow') && $request->start_workflow == 1) {
            $production->update(['status' => 'active']);
            return redirect()->route('production.show', $production->id)->with('success', 'Project sent to Workflow.');
        }

        // Otherwise, standard update of details/finance
        $production->update([
            'project_name' => $request->project_name,
            'description' => $request->description,
            'financial_data' => $request->financial,
        ]);

        return back()->with('success', 'Details updated.');
    }

    /**
     * Add an employee to an existing order (Pre-production or Active).
     */
    public function addEmployee(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);
        $request->validate(['employee_id' => 'required|exists:employees,id']);

        // Check duplicate
        $exists = ProductionItem::where('production_order_id', $id)
                    ->where('employee_id', $request->employee_id)
                    ->exists();

        if (!$exists) {
            ProductionItem::create([
                'production_order_id' => $id,
                'employee_id' => $request->employee_id
            ]);
        }

        return back()->with('success', 'Employee added.');
    }

    /**
     * Add a NEW employee to an existing order.
     */
    public function addNewEmployee(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);

        // Similar logic to store()
        $employee = Employee::create([
            'employer_id' => $production->employer_id,
            'employeeTitleTh' => $request->title,
            'employeeFirstNameTh' => $request->name_th,
            'employeeLastNameTh' => $request->surname_th,
            'employeePassport' => $request->passport_no,
            'employeeNationality' => $request->nationality,
        ]);

        ProductionItem::create([
            'production_order_id' => $production->id,
            'employee_id' => $employee->id
        ]);

        return back()->with('success', 'New employee created and added.');
    }

    public function destroy($id)
    {
        $production = ProductionOrder::findOrFail($id);
        $production->delete();
        return redirect()->route('production.index')->with('success', 'Project deleted.');
    }
}
