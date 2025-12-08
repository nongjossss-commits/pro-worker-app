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
     * Only shows PRE_PRODUCTION items.
     */
    public function index()
    {
        $orders = ProductionOrder::preProduction()
                    ->with('employer')
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
        $jobType = $request->query('type', 'employer'); // Default to 'employer'

        // Handle pre-selected employees (sent from bulk actions)
        $selectedEmployeeIds = $request->query('employee_ids');
        if (!$selectedEmployeeIds && $request->has('employee_ids_json')) {
            $selectedEmployeeIds = json_decode($request->query('employee_ids_json'), true);
        }
        if (session()->has('bulk_employee_ids')) {
            $selectedEmployeeIds = session('bulk_employee_ids');
        }

        $preSelectedEmployees = collect();
        if ($selectedEmployeeIds) {
            if (is_string($selectedEmployeeIds)) {
                $selectedEmployeeIds = explode(',', $selectedEmployeeIds);
            }
            $preSelectedEmployees = Employee::whereIn('id', $selectedEmployeeIds)->get();

            // Smart Detection:
            // If employees belong to DIFFERENT employers -> switch to INDEPENDENT
            // If employees belong to ONE employer -> switch to EMPLOYER (and set ID)
            $uniqueEmployerIds = $preSelectedEmployees->pluck('employer_id')->unique();
            if ($uniqueEmployerIds->count() > 1) {
                $jobType = 'independent';
                $employerId = null;
            } elseif ($uniqueEmployerIds->count() === 1) {
                $jobType = 'employer';
                $employerId = $uniqueEmployerIds->first();
            }
        }

        // Fetch all employers for the dropdown (simple fetch for now, can be optimized)
        $employers = Employer::select('id', 'name_th', 'name_en')->orderBy('name_th')->get();

        return view('production.create', compact('employerId', 'jobType', 'preSelectedEmployees', 'employers'));
    }

    /**
     * Store a newly created Production Order in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:employer,independent',
            'employer_id' => 'nullable|required_if:type,employer|exists:employers,id',
            'project_name' => 'nullable|string|max:255',
            // Arrays of IDs
            'selected_employees' => 'nullable|array',
            'selected_employees.*' => 'exists:employees,id',
            // Arrays of Objects (New Emp Data)
            'new_employees' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $order = ProductionOrder::create([
                'type' => $request->type,
                'employer_id' => $request->type === 'employer' ? $request->employer_id : null,
                'project_name' => $request->project_name,
                'description' => $request->description,
                'status' => 'pre_production',
                'financial_data' => $request->financial,
                'created_by' => auth()->id(),
                'custom_field_definitions' => [], // Initialize empty
            ]);

            // 1. Attach Existing Employees
            if ($request->has('selected_employees')) {
                foreach ($request->selected_employees as $empId) {
                    // Check uniqueness within this order
                    // Since it's new, no need to check, but good practice if duplicates sent
                    ProductionItem::firstOrCreate([
                        'production_order_id' => $order->id,
                        'employee_id' => $empId,
                    ]);
                }
            }

            // 2. Attach New (Ghost) Employees
            if ($request->has('new_employees')) {
                foreach ($request->new_employees as $newEmpData) {
                    ProductionItem::create([
                        'production_order_id' => $order->id,
                        'employee_id' => null, // Ghost
                        'new_employee_data' => $newEmpData, // JSON
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('production.edit', $order->id)->with('success', 'Project created.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating project: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     * Logic: If Pre-Production -> Edit View. If Active -> Workflow View.
     */
    public function show(ProductionOrder $production)
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

        // If trying to access a workflow item via edit route, redirect to show
        if ($production->status !== 'pre_production') {
            return redirect()->route('production.show', $production->id);
        }

        return view('production.prepare', compact('production'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);

        // Transition to Workflow
        if ($request->has('start_workflow') && $request->start_workflow == 1) {
            $production->update(['status' => 'active']);
            // Redirect to index because it disappears from this list
            return redirect()->route('production.index')->with('success', 'Project moved to Workflow.');
        }

        // Standard Update
        $production->update([
            'project_name' => $request->project_name,
            'description' => $request->description,
            'financial_data' => $request->financial,
        ]);

        return back()->with('success', 'Details updated.');
    }

    /**
     * Add an existing employee to the order.
     */
    public function addEmployee(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);
        $request->validate(['employee_id' => 'required|exists:employees,id']);

        // Check if employee already exists in this order
        $exists = ProductionItem::where('production_order_id', $id)
                    ->where('employee_id', $request->employee_id)
                    ->exists();

        if ($exists) {
            return back()->with('error', 'This employee is already in the list.');
        }

        // If employer type, check mismatch (optional enforcement, but user asked for flexibility on Independent)
        if ($production->type === 'employer') {
            $emp = Employee::find($request->employee_id);
            if ($emp->employer_id != $production->employer_id) {
                 return back()->with('error', 'Mismatch: Employee does not belong to the project employer.');
            }
        }

        ProductionItem::create([
            'production_order_id' => $id,
            'employee_id' => $request->employee_id
        ]);

        return back()->with('success', 'Employee added.');
    }

    /**
     * Add a NEW (Ghost) employee.
     */
    public function addNewEmployee(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);

        $data = [
            'title' => $request->title,
            'name_th' => $request->name_th,
            'surname_th' => $request->surname_th,
            'passport_no' => $request->passport_no,
            'nationality' => $request->nationality,
            'photo_url' => null // Could handle file upload here if needed
        ];

        ProductionItem::create([
            'production_order_id' => $id,
            'employee_id' => null,
            'new_employee_data' => $data
        ]);

        return back()->with('success', 'New employee added to list.');
    }

    /**
     * Create a Custom Field (Column) for this Job.
     */
    public function addCustomField(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);
        $request->validate([
            'field_name' => 'required|string|max:50',
            'field_type' => 'required|in:text,date,file',
        ]);

        $defs = $production->custom_field_definitions ?? [];
        // Generate a safe key
        $key = 'field_' . time() . '_' . rand(100,999);

        $defs[] = [
            'key' => $key,
            'label' => $request->field_name,
            'type' => $request->field_type
        ];

        $production->update(['custom_field_definitions' => $defs]);

        // Check if we need to pre-fill for selected items?
        // The user said "Select 30 people... create field... update status".
        // This method just creates the definition. The 'bulkUpdateCustomField' will handle values.

        return back()->with('success', 'New field added.');
    }

    /**
     * Bulk Update Custom Field Values.
     */
    public function bulkUpdateCustomField(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);
        // Expects: field_key, value (or value_date, value_file), item_ids (array)

        $key = $request->field_key;
        $val = $request->value;
        if ($request->has('value_date') && $request->value_date) {
            $val = $request->value_date;
        }

        $isFileUpload = false;
        $filePath = null;

        if ($request->hasFile('value_file')) {
            $isFileUpload = true;
            // Store the single file
            $filePath = $request->file('value_file')->store('production_files', 'public');
            $val = $filePath;
        }

        $ids = $request->item_ids; // ProductionItem IDs

        if (!$ids || !is_array($ids)) {
            return back()->with('error', 'No employees selected.');
        }

        $items = ProductionItem::whereIn('id', $ids)->where('production_order_id', $id)->get();

        foreach ($items as $item) {
            $currentData = $item->custom_field_values ?? [];
            $currentData[$key] = $val;
            $item->update(['custom_field_values' => $currentData]);
        }

        return back()->with('success', 'Updated ' . count($items) . ' employees.');
    }

    public function destroy($id)
    {
        $production = ProductionOrder::findOrFail($id);
        $production->delete();
        return redirect()->route('production.index')->with('success', 'Project deleted.');
    }
}
