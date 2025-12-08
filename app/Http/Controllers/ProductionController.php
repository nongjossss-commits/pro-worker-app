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
     * Display a listing of Production Orders (Preparation / Pre-Production).
     */
    public function index()
    {
        // Only show Pre-Production here. Active jobs go to WorkflowController.
        $orders = ProductionOrder::with('employer')
                    ->where('status', 'pre_production')
                    ->withCount('items')
                    ->latest()
                    ->paginate(15);

        return view('production.index', compact('orders'));
    }

    /**
     * Show the form for creating a new Production Order (Pre-Production).
     */
    public function create(Request $request)
    {
        $employerId = $request->query('employer_id');
        $ticketId = $request->query('ticket_id');

        // Handle pre-selected employees (sent from bulk actions)
        $selectedEmployeeIds = $request->query('employee_ids'); // Expecting array or comma-separated if GET
        if (!$selectedEmployeeIds && $request->has('employee_ids_json')) {
            $selectedEmployeeIds = json_decode($request->query('employee_ids_json'), true);
        }

        if (session()->has('bulk_employee_ids')) {
            $selectedEmployeeIds = session('bulk_employee_ids');
        }

        // Fetch Employee Models if IDs exist
        $preSelectedEmployees = collect();
        $isIndependent = false;
        $detectedEmployerId = null;

        if ($selectedEmployeeIds) {
            if (is_string($selectedEmployeeIds)) {
                $selectedEmployeeIds = explode(',', $selectedEmployeeIds);
            }
            $preSelectedEmployees = Employee::with('employer')->whereIn('id', $selectedEmployeeIds)->get();

            if ($preSelectedEmployees->isNotEmpty()) {
                // Check if all belong to same employer
                $employerIds = $preSelectedEmployees->pluck('employer_id')->unique();

                if ($employerIds->count() > 1) {
                    // Mixed employers -> Force Independent
                    $isIndependent = true;
                } else {
                    // Single employer -> Default to Employer mode
                    $detectedEmployerId = $employerIds->first();
                }
            }
        }

        // Use detected employer if not explicitly overridden in URL
        if (!$employerId && $detectedEmployerId) {
            $employerId = $detectedEmployerId;
        }

        // FIX: Provide list of employers for manual selection if starting from scratch
        // Fetch lighter list for performance
        $employers = collect();
        if ($preSelectedEmployees->isEmpty()) {
            $employers = Employer::select('id', 'employerNameTh', 'employerNameEn', 'employer_id')
                                ->orderBy('employerNameTh')
                                ->limit(200) // Safety limit
                                ->get();
        }

        return view('production.create', compact('employerId', 'ticketId', 'preSelectedEmployees', 'isIndependent', 'employers'));
    }

    /**
     * Store a newly created Production Order in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_name' => 'nullable|string|max:255',
            'type' => 'required|in:employer,independent',
            // employer_id required only if type is employer
            'employer_id' => 'required_if:type,employer|nullable|exists:employers,id',
            'selected_employees' => 'nullable|array',
            'selected_employees.*' => 'exists:employees,id',
            'new_employees' => 'nullable|array', // Array of new employee data
        ]);

        DB::beginTransaction();
        try {
            // Check for independent mismatch
            if ($request->type === 'employer' && $request->has('selected_employees')) {
                $employees = Employee::whereIn('id', $request->selected_employees)->get();
                $diffEmployers = $employees->pluck('employer_id')->unique();
                if ($diffEmployers->count() > 1 || ($diffEmployers->isNotEmpty() && $diffEmployers->first() != $request->employer_id)) {
                    // This creates a conflict: Employer mode but employees from other employers.
                    // Ideally, UI prevents this, but backend should guard or auto-switch.
                    // For now, strict validation:
                    throw new \Exception('Selected employees do not belong to the selected employer. Please use "Independent" mode.');
                }
            }

            $order = ProductionOrder::create([
                'employer_id' => $request->type === 'employer' ? $request->employer_id : null,
                'type' => $request->type,
                'project_name' => $request->project_name,
                'description' => $request->description,
                'status' => 'pre_production',
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

            // 2. Create New Employees (Temp Data or Real DB?)
            // User requested "New employees might not be in DB yet but show card".
            // So we store in 'new_employee_data' JSON column on ProductionItem.
            if ($request->has('new_employees')) {
                foreach ($request->new_employees as $newEmpData) {
                    ProductionItem::create([
                        'production_order_id' => $order->id,
                        'employee_id' => null, // Not in DB yet
                        'new_employee_data' => $newEmpData // Store raw JSON
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('production.edit', $order->id)->with('success', 'Project created in Pre-Production.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     * Redirects based on status.
     */
    public function show($id)
    {
        $production = ProductionOrder::findOrFail($id);

        if ($production->status === 'pre_production') {
            return redirect()->route('production.edit', $production->id);
        }

        // If active, redirect to Workflow controller
        return redirect()->route('workflow.show', $production->id);
    }

    /**
     * Show the form for editing (The Pre-Production Preparation Page).
     */
    public function edit($id)
    {
        $production = ProductionOrder::with(['items.employee.employer', 'employer'])->findOrFail($id);

        if ($production->status !== 'pre_production') {
            return redirect()->route('workflow.show', $production->id);
        }

        return view('production.prepare', compact('production'));
    }

    /**
     * Toggle readiness status flags via AJAX.
     */
    public function toggleStatus(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);

        $request->validate([
            'type' => 'required|in:document_ready,financial_approved',
            'status' => 'required|boolean'
        ]);

        $type = $request->type;
        $status = $request->status;

        if ($type === 'financial_approved') {
            // Check admin permission
            if (!auth()->user()->can('approve-production')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized. Admin permission required.'], 403);
            }

            $production->update([
                'financial_approved_at' => $status ? now() : null,
                'financial_approved_by' => $status ? auth()->id() : null
            ]);
        }
        else if ($type === 'document_ready') {
            // Assume any staff with access to this page can toggle this?
            // Or specific permission? User said "Staff or Caretaker".
            // Since they are on this page, they likely have permission to edit.

            $production->update([
                'document_ready_at' => $status ? now() : null,
                'document_ready_by' => $status ? auth()->id() : null
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);

        // Check if we are "Starting Workflow"
        if ($request->has('start_workflow') && $request->start_workflow == 1) {
            // Server-side validation of flags
            if (!$production->document_ready_at || !$production->financial_approved_at) {
                 return back()->with('error', 'Cannot start workflow. Both "Documents Ready" and "Financial/Admin Approved" flags must be set.');
            }

            DB::beginTransaction();
            try {
                // Activate Production Order
                $production->update(['status' => 'active']);

                // Confirm all "pending_confirmation" employees in this order
                $pendingItems = $production->items()->with('employee')->get();
                $pendingEmployees = collect();

                foreach($pendingItems as $item) {
                    if ($item->employee && $item->employee->status === 'pending_confirmation') {
                        $pendingEmployees->push($item->employee->id);
                    }
                }

                if ($pendingEmployees->isNotEmpty()) {
                    Employee::whereIn('id', $pendingEmployees)->update(['status' => 'active']);
                }

                DB::commit();
                return redirect()->route('workflow.show', $production->id)->with('success', 'Project sent to Workflow. Employees confirmed.');

            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('error', 'Failed to start workflow: ' . $e->getMessage());
            }
        }

        $production->update([
            'project_name' => $request->project_name,
            'description' => $request->description,
            'financial_data' => $request->financial,
        ]);

        return back()->with('success', 'Details updated.');
    }

    /**
     * Add an employee to an existing order.
     */
    public function addEmployee(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);
        $request->validate(['employee_id' => 'required|exists:employees,id']);

        // Check duplicate in THIS order
        $exists = ProductionItem::where('production_order_id', $id)
                    ->where('employee_id', $request->employee_id)
                    ->exists();

        // Independent check logic?
        // User said: "Employees can be in different cards in workflow, but NOT in the same card."
        // We handle that with the $exists check.
        // Also "Independent jobs can have mixed employers".
        // "Employer jobs must be single employer".

        if ($production->type === 'employer') {
            $employee = Employee::find($request->employee_id);
            if ($employee->employer_id !== $production->employer_id) {
                return back()->with('error', 'Cannot add employee from different employer to this Standard Project.');
            }
        }

        if (!$exists) {
            ProductionItem::create([
                'production_order_id' => $id,
                'employee_id' => $request->employee_id
            ]);
        }

        return back()->with('success', 'Employee added.');
    }

    /**
     * Add a NEW (Temp) employee to an existing order.
     */
    public function addNewEmployee(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);

        // Validate basic inputs
        $data = $request->validate([
            'name_th' => 'required|string',
            'passport_no' => 'nullable|string',
            'nationality' => 'nullable|string',
            // Add more as needed
        ]);

        ProductionItem::create([
            'production_order_id' => $id,
            'employee_id' => null,
            'new_employee_data' => $data
        ]);

        return back()->with('success', 'New employee added to card.');
    }

    public function destroy($id)
    {
        $production = ProductionOrder::findOrFail($id);
        $production->delete();
        return redirect()->route('production.index')->with('success', 'Project deleted.');
    }
}
