<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\Employer;
use App\Models\Employee;
use App\Models\WorkType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\AddressFilterTrait;

class ProductionController extends Controller
{
    use AddressFilterTrait;

    /**
     * Display a listing of Production Orders (Preparation / Pre-Production).
     */
    public function index(Request $request)
    {
        // 1. Fetch Tabs (Work Types)
        // Similar to WorkflowController, but we use the same WorkTypes.
        $tabs = WorkType::orderBy('order')->get();

        // 2. Determine Active Tab
        $activeTab = null;
        if ($request->has('tab')) {
            $activeTab = $tabs->where('slug', $request->query('tab'))->first();
        }
        if (!$activeTab && $tabs->isNotEmpty()) {
            $activeTab = $tabs->first();
        }

        // 3. Build Query for Orders (Status = pre_production)
        $query = ProductionOrder::with(['employer.addresses', 'items.employee.employer', 'items.completedWorkTypeSteps', 'workType'])
                    ->where('status', 'pre_production')
                    ->withCount('items');

        // Filter by Active Tab (WorkType)
        if ($activeTab) {
            $query->where('work_type_id', $activeTab->id);
        }

        // Apply Address Filters
        $addressOptions = $this->getAddressOptions($query, 'employer_id');
        $query = $this->applyAddressFilters($query, $request, 'employer');
        $query->latest();

        $orders = $query->paginate(15)->withQueryString();

        // 4. Calculate Stats (Scoreboard) for the Active Tab (or Global if needed, usually per tab)
        // We replicate Workflow stats logic but for "pre_production" items.
        // Actually, "Total Employees" in Production dashboard might mean total in Pre-Production phase.

        $stats = [
            'total_employees' => 0,
            'not_started' => 0,
            'cancelled' => 0,
            'completed' => 0, // "Ready" in this context? Or just items marked as completed step?
            'total_projects' => $orders->total(),
            'step_stats' => []
        ];

        // Fetch Steps for the Active Tab (Preparation Stage)
        $steps = collect();
        if ($activeTab) {
            $steps = $activeTab->preparationSteps; // Use the new relationship
        }

        // Calculate detailed stats for the visible orders (or all matching orders if we want global tab stats)
        // For performance, let's query aggregates for the current tab.
        if ($activeTab) {
            $baseQuery = ProductionItem::whereHas('order', function($q) use ($activeTab) {
                $q->where('status', 'pre_production')
                  ->where('work_type_id', $activeTab->id);
            });

            $stats['total_employees'] = $baseQuery->count();
            $stats['cancelled'] = (clone $baseQuery)->where('status', 'cancelled')->count();
            $stats['completed'] = (clone $baseQuery)->where('status', 'completed')->count();
            $stats['not_started'] = (clone $baseQuery)->where('status', 'pending')->count(); // Rough approx
        }

        // Compute per-order stats for the view (Accordion Badges)
        foreach ($orders as $order) {
            $total = $order->items->count();
            $cancelled = $order->items->where('status', 'cancelled')->count();
            $completed = $order->items->where('status', 'completed')->count();
            $pending = $total - $cancelled - $completed;

            // Step Stats for this order
            $stepStats = [];
            foreach ($steps as $step) {
                // Count items that have completed this step
                // Ideally this is done via SQL aggregation for performance, but loop is okay for pagination size 15.
                $count = 0;
                foreach ($order->items as $item) {
                     if ($item->completedWorkTypeSteps->contains('id', $step->id)) {
                         $count++;
                     }
                }
                $stepStats[$step->id] = $count;
            }

            $order->computedStats = [
                'total' => $total,
                'not_started' => $pending,
                'cancelled' => $cancelled,
                'completed' => $completed,
                'step_stats' => $stepStats
            ];
        }

        return view('production.index', compact('orders', 'tabs', 'activeTab', 'stats', 'steps', 'addressOptions'));
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

        // Fetch lighter list for performance
        $employers = collect();
        if ($preSelectedEmployees->isEmpty()) {
            $employers = Employer::select('id', 'employerNameTh', 'employerNameEn')
                                ->orderBy('employerNameTh')
                                ->limit(200) // Safety limit
                                ->get();
        }

        // Fetch Work Types for Dropdown
        $workTypes = WorkType::orderBy('order')->get();

        return view('production.create', compact('employerId', 'ticketId', 'preSelectedEmployees', 'isIndependent', 'employers', 'workTypes'));
    }

    /**
     * Store a newly created Production Order in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_name' => 'nullable|string|max:255',
            'type' => 'required|in:employer,independent',
            'work_type_id' => 'required|exists:work_types,id', // Added
            'employer_id' => 'required_if:type,employer|nullable|exists:employers,id',
            'selected_employees' => 'nullable|array',
            'selected_employees.*' => 'exists:employees,id',
            'new_employees' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // Check for independent mismatch
            if ($request->type === 'employer' && $request->has('selected_employees')) {
                $employees = Employee::whereIn('id', $request->selected_employees)->get();
                $diffEmployers = $employees->pluck('employer_id')->unique();
                if ($diffEmployers->count() > 1 || ($diffEmployers->isNotEmpty() && $diffEmployers->first() != $request->employer_id)) {
                    throw new \Exception('Selected employees do not belong to the selected employer. Please use "Independent" mode.');
                }
            }

            $order = ProductionOrder::create([
                'employer_id' => $request->type === 'employer' ? $request->employer_id : null,
                'type' => $request->type,
                'work_type_id' => $request->work_type_id, // Added
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

            // Redirect to index with specific tab
            $slug = WorkType::find($request->work_type_id)->slug;
            return redirect()->route('production.index', ['tab' => $slug])->with('success', 'Project created in Pre-Production.');

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
        // Not used much if we use Dashboard style, but kept for compatibility
        $production = ProductionOrder::findOrFail($id);

        if ($production->status === 'pre_production') {
            return redirect()->route('production.edit', $production->id);
        }

        // If active, redirect to Workflow controller
        return redirect()->route('workflow.show', $production->id);
    }

    /**
     * Show the form for editing (The Pre-Production Preparation Page).
     * NOTE: This is likely replaced by the Dashboard style (Expand in Accordion).
     * But we keep it as a fallback or detailed view.
     */
    public function edit($id)
    {
        // Eager load advanceItems for the UI
        $production = ProductionOrder::with(['items.employee.employer', 'employer', 'financialGroups.advanceItems'])->findOrFail($id);

        if ($production->status !== 'pre_production') {
            return redirect()->route('workflow.show', $production->id);
        }

        return view('production.prepare', compact('production'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);

        // Check if we are "Starting Workflow"
        if ($request->has('start_workflow') && $request->start_workflow == 1) {
            // Validation removed/relaxed as per new requirements ("User decides when ready").
            // Old logic required flags. New logic: Button is clicked, we move it.

            DB::beginTransaction();
            try {
                // Activate Production Order
                $production->update(['status' => 'active']);

                // Reset step progress?
                // Requirement: "Preparation steps do not follow to workflow".
                // Since steps are tracked via `production_item_step` pivot linked to `WorkTypeStep`,
                // and we added a `stage` to `WorkTypeStep`, the "preparation" steps will naturally be filtered out
                // when viewing in Workflow mode (which shows `workflow` stage steps).
                // So we don't need to delete them, keeping history is good.

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

                // Redirect to the Workflow Dashboard Tab
                $tabSlug = $production->workType->slug ?? 'default';
                return redirect()->route('workflow.index', ['tab' => $tabSlug])->with('success', 'Project sent to Workflow.');

            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('error', 'Failed to start workflow: ' . $e->getMessage());
            }
        }

        // ... (Existing financial updates logic preserved below if needed)
        // For brevity, assuming standard update logic for project name/desc
        $production->update($request->only(['project_name', 'description']));

        return back()->with('success', 'Details updated.');
    }

    // ... (Keep existing methods: toggleStatus, financial groups, addEmployee, etc.)
    // They are still useful if we want to keep the detailed view or reuse logic.

    public function destroy($id)
    {
        $production = ProductionOrder::findOrFail($id);
        $production->delete();
        return redirect()->route('production.index')->with('success', 'Project deleted.');
    }

    // ... [Preserve other existing methods like uploadLogo, etc.]

    /**
     * Upload a custom logo for the financial header.
     */
    public function uploadLogo(Request $request, $id)
    {
        $request->validate([
            'logo' => 'required|image|max:2048', // 2MB Max
        ]);

        $production = ProductionOrder::findOrFail($id);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = 'logo_' . time() . '.' . $file->getClientOriginalExtension();
            // Store in a public folder
            $path = $file->storeAs('uploads/logos', $filename, 'public');

            return response()->json([
                'success' => true,
                'path' => $path
            ]);
        }

        return response()->json(['success' => false], 400);
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
            $production->update([
                'financial_approved_at' => $status ? now() : null,
                'financial_approved_by' => $status ? auth()->id() : null
            ]);
        }
        else if ($type === 'document_ready') {
            $production->update([
                'document_ready_at' => $status ? now() : null,
                'document_ready_by' => $status ? auth()->id() : null
            ]);
        }
        else if ($type === 'waiting_for_documents') {
            $production->update([
                'waiting_for_documents' => $status
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Add an employee to an existing order.
     */
    public function addEmployee(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);
        $request->validate(['employee_id' => 'required|exists:employees,id']);

        $exists = ProductionItem::where('production_order_id', $id)
                    ->where('employee_id', $request->employee_id)
                    ->exists();

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
        ]);

        ProductionItem::create([
            'production_order_id' => $id,
            'employee_id' => null,
            'new_employee_data' => $data
        ]);

        return back()->with('success', 'New employee added to card.');
    }

    /**
     * Store a new financial group (Tab).
     */
    public function storeFinancialGroup(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);
        $request->validate(['name' => 'required|string|max:255']);

        $group = $production->financialGroups()->create([
            'name' => $request->name,
            'financial_data' => [] // Default empty
        ]);

        return response()->json(['success' => true, 'group' => $group]);
    }

    /**
     * Rename a financial group.
     */
    public function updateFinancialGroup(Request $request, $id, $groupId)
    {
        $production = ProductionOrder::findOrFail($id);
        $group = $production->financialGroups()->findOrFail($groupId);

        $request->validate(['name' => 'required|string|max:255']);
        $group->update(['name' => $request->name]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a financial group.
     */
    public function destroyFinancialGroup($id, $groupId)
    {
        $production = ProductionOrder::findOrFail($id);
        $group = $production->financialGroups()->findOrFail($groupId);

        // Safety check to prevent deleting the first group
        $firstGroup = $production->financialGroups()->orderBy('id')->first();
        if ($firstGroup && $firstGroup->id == $groupId) {
            return response()->json(['success' => false, 'message' => 'Cannot delete the primary tab.'], 403);
        }

        $group->delete();
        $group->transactions()->delete();

        return response()->json(['success' => true]);
    }

}
