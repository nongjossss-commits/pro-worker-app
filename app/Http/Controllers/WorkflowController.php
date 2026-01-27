<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\WorkflowStep;
use App\Models\WorkType;
use App\Models\WorkTypeStep;
use App\Models\Employee;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkflowController extends Controller
{
    /**
     * Display the main Workflow Dashboard with Tabs.
     */
    public function index(Request $request)
    {
        // 1. Get Tabs (Work Types)
        $tabs = WorkType::orderBy('order')->get();

        if ($tabs->isEmpty()) {
            $this->seedDefaultWorkTypes();
            $tabs = WorkType::orderBy('order')->get();
        }

        // 2. Determine Active Tab
        $activeTabSlug = $request->query('tab', $tabs->first()?->slug);
        $activeTab = $tabs->where('slug', $activeTabSlug)->first();

        // 3. Query Orders for this Tab
        $query = ProductionOrder::with(['employer', 'workType'])
            ->where('status', '!=', 'pre_production'); // Active workflows

        if ($activeTab) {
            $query->where('work_type_id', $activeTab->id);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhereHas('employer', function($e) use ($search) {
                      $e->where('employerNameTh', 'like', "%{$search}%")
                        ->orWhere('employerNameEn', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->latest('updated_at')->paginate(15);

        // Calculate Stats PER ORDER for the view (Accordion Header)
        // Also load items lightly if needed for stats, or just counts
        // To avoid N+1, we might need to load items or counts.
        // For accurate step stats, we need items + completedWorkTypeSteps.
        // This is heavy. Let's do it like Registration: load items for current page orders.
        $orders->load(['items.completedWorkTypeSteps']);

        $steps = $activeTab ? $activeTab->steps : collect();
        $stepOneId = $steps->sortBy('order')->first()?->id;

        foreach ($orders as $order) {
            $items = $order->items;
            $total = 0;
            $notStarted = 0;
            $cancelled = 0;
            $completed = 0;
            $stepStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();

            foreach ($items as $item) {
                if ($item->status === 'cancelled') {
                    $cancelled++;
                    continue;
                }

                $total++; // Active items (Pending or Completed)

                if ($item->status === 'completed') {
                    $completed++;
                }

                // Not Started Logic
                if ($stepOneId && !$item->completedWorkTypeSteps->contains('id', $stepOneId)) {
                    $notStarted++;
                }

                // Step Stats (Highest Step)
                // We need to sort the relation collection
                $completedSteps = $item->completedWorkTypeSteps; // Collection
                // We need to know the 'order' of these steps.
                // Assuming pivot is loaded, but step order is on WorkTypeStep model.
                // We loaded items.completedWorkTypeSteps, so we have the step models.
                $highestStep = $completedSteps->sortByDesc('order')->first();
                if ($highestStep && isset($stepStats[$highestStep->id])) {
                    $stepStats[$highestStep->id]++;
                }
            }

            $order->computedStats = [
                'total' => $total,
                'not_started' => $notStarted,
                'cancelled' => $cancelled,
                'completed' => $completed,
                'step_stats' => $stepStats
            ];
        }

        // 4. Calculate Scoreboard Stats (For the Active Tab)
        $stats = [
            'total_projects' => $orders->total(),
            'total_employees' => 0,
            'completed_employees' => 0,
        ];

        if ($activeTab) {
            $statsQuery = ProductionOrder::where('work_type_id', $activeTab->id)
                ->where('status', '!=', 'pre_production');

            $stats['total_projects'] = $statsQuery->count();

            // Total Employees
            $itemsQuery = ProductionItem::whereIn('production_order_id', $statsQuery->select('id'));
            $stats['total_employees'] = $itemsQuery->where('status', '!=', 'cancelled')->count();
        }

        return view('workflow.index', compact('orders', 'tabs', 'activeTab', 'stats', 'steps'));
    }

    /**
     * Fetch Items (Employees) for a specific Order (Card).
     * AJAX for Accordion/Drawer content.
     */
    public function fetchOrderItems(Request $request, $orderId)
    {
        $order = ProductionOrder::with(['workType.steps'])->findOrFail($orderId);

        $items = ProductionItem::with(['employee', 'completedWorkTypeSteps'])
            ->where('production_order_id', $orderId)
            ->orderBy('group_name')
            ->orderBy('id')
            ->get();

        // Group the items collection by group_name for easier view rendering
        $groupedItems = $items->groupBy('group_name');

        return view('workflow.partials.order_items', compact('order', 'groupedItems'));
    }

    /**
     * Toggle a WorkTypeStep for a ProductionItem.
     */
    public function toggleStep(Request $request, $itemId)
    {
        $request->validate([
            'step_id' => 'required|exists:work_type_steps,id',
            'completed' => 'required|boolean'
        ]);

        $item = ProductionItem::findOrFail($itemId);

        if ($request->completed) {
            $item->completedWorkTypeSteps()->syncWithoutDetaching([
                $request->step_id => [
                    'completed_at' => now(),
                    'completed_by' => auth()->id()
                ]
            ]);
        } else {
            $item->completedWorkTypeSteps()->detach($request->step_id);
        }

        // Return stats for UI update if needed, but for now just success
        return response()->json(['success' => true]);
    }

    /**
     * Update Group Name (Batch) for an Item.
     */
    public function updateGroup(Request $request, $itemId)
    {
        $request->validate(['group_name' => 'nullable|string|max:255']);

        $item = ProductionItem::findOrFail($itemId);
        $item->update(['group_name' => $request->group_name]);

        return response()->json(['success' => true]);
    }

    /**
     * API: Search Employees for "Notify In" (Resigned Status / Terminated).
     */
    public function searchResignedEmployees(Request $request)
    {
        $search = $request->query('q');

        // "Notify Out" usually results in termination (terminated_at != null).
        // So we search for employees who are effectively "out" of the system (history).
        $query = Employee::query()
             ->whereNotNull('terminated_at') // Filter for terminated/resigned employees
             ->with('employer');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('employeeNameTh', 'like', "%{$search}%")
                  ->orWhere('employeeNameEn', 'like', "%{$search}%")
                  ->orWhere('employeePassport', 'like', "%{$search}%");
            });
        }

        $employees = $query->limit(20)->get();

        return response()->json($employees);
    }

    /**
     * API: Fetch Active Employees for an Employer (Notify Out).
     */
    public function fetchEmployerActiveEmployees($employerId)
    {
        $employees = Employee::where('employer_id', $employerId)
            ->whereNull('terminated_at') // Active
            ->limit(100)
            ->get(['id', 'employeeNameTh', 'employeeNameEn', 'employeePassport']);

        return response()->json($employees);
    }

    /**
     * Store (Create) a new Workflow Job / Add Employees.
     */
    public function store(Request $request)
    {
        $order = null;

        if ($request->filled('production_order_id')) {
            $order = ProductionOrder::findOrFail($request->production_order_id);
        } else {
            $request->validate([
                'work_type_id' => 'required|exists:work_types,id',
                'employer_id' => 'required|exists:employers,id',
                // 'items' => array of employee IDs or new data
            ]);

            $workType = WorkType::findOrFail($request->work_type_id);

            // Logic: Find or Create Order
            if (in_array($workType->slug, ['notify_in', 'notify_out'])) {
                // Single Card per Employer
                $order = ProductionOrder::firstOrCreate(
                    [
                        'employer_id' => $request->employer_id,
                        'work_type_id' => $workType->id,
                        'status' => 'active' // Or whatever active status is
                    ],
                    [
                        'type' => 'employer',
                        'project_name' => $workType->name . ' - ' . Employer::find($request->employer_id)->employerNameTh,
                        'created_by' => auth()->id()
                    ]
                );
            } else {
                // MOU / Other: Always Create New
                $order = ProductionOrder::create([
                    'employer_id' => $request->employer_id,
                    'work_type_id' => $workType->id,
                    'type' => 'employer',
                    'project_name' => $request->project_name ?? ($workType->name . ' - ' . now()->format('d/m/Y')),
                    'status' => 'active',
                    'created_by' => auth()->id()
                ]);
            }
        }

        // Add Items
        if ($request->has('employee_ids')) {
            $ids = $request->employee_ids; // Expecting array
            $groupName = $request->group_name ?? null;

            foreach ($ids as $empId) {
                // Check if already in this order?
                $exists = ProductionItem::where('production_order_id', $order->id)
                            ->where('employee_id', $empId)
                            ->exists();

                if (!$exists) {
                    ProductionItem::create([
                        'production_order_id' => $order->id,
                        'employee_id' => $empId,
                        'group_name' => $groupName,
                        'status' => 'pending'
                    ]);
                }
            }
        }

        // Handle Manual New Employee (MOU/Draft)
        if ($request->filled('new_employee.name_en') || $request->filled('new_employee.name_th')) {
            ProductionItem::create([
                'production_order_id' => $order->id,
                'employee_id' => null,
                'new_employee_data' => $request->new_employee,
                'group_name' => $request->group_name ?? null,
                'status' => 'pending'
            ]);
        }

        $slug = $order->workType->slug ?? ($request->work_type_id ? WorkType::find($request->work_type_id)->slug : 'notify_in');

        return redirect()->route('workflow.index', ['tab' => $slug])
                         ->with('success', 'Job updated successfully.');
    }

    /**
     * Finalize/Complete an Item (Logic depends on WorkType).
     */
    public function finalizeItem(Request $request, $itemId)
    {
        $item = ProductionItem::with(['order.workType', 'employee'])->findOrFail($itemId);
        $slug = $item->order->workType->slug ?? '';

        DB::transaction(function () use ($item, $slug) {
            // Logic Execution
            if ($slug === 'notify_in') {
                // Transfer to new Employer and Activate
                if ($item->employee) {
                    $item->employee->update([
                        'employer_id' => $item->order->employer_id,
                        'status' => null, // Active
                        'terminated_at' => null,
                        'termination_reason' => null
                    ]);
                }
            } elseif ($slug === 'notify_out') {
                // Terminate / Resign
                if ($item->employee) {
                    $item->employee->update([
                        'terminated_at' => now(),
                        'status' => 'resigned'
                    ]);
                }
            }

            // Mark Item Completed
            $item->update(['status' => 'completed']);
        });

        return response()->json(['success' => true]);
    }

    /**
     * Cancel an Item.
     */
    public function cancelItem(Request $request, $itemId)
    {
        $item = ProductionItem::findOrFail($itemId);
        $item->update(['status' => 'cancelled']);
        return response()->json(['success' => true]);
    }

    /**
     * Restore an Item (Pending).
     */
    public function restoreItem(Request $request, $itemId)
    {
        $item = ProductionItem::findOrFail($itemId);
        $item->update(['status' => 'pending']);
        return response()->json(['success' => true]);
    }

    /**
     * Soft Delete an Item.
     */
    public function destroyItem(Request $request, $itemId)
    {
        $item = ProductionItem::findOrFail($itemId);
        $item->delete();
        return response()->json(['success' => true]);
    }

    private function seedDefaultWorkTypes()
    {
        $types = [
            [
                'name' => 'แจ้งเข้า / เปลี่ยนนายจ้าง',
                'slug' => 'notify_in',
                'is_system' => true,
                'order' => 1,
                'steps' => ['รับเอกสาร', 'ยื่นเรื่อง', 'รออนุมัติ', 'รับเล่มคืน', 'แจ้งผล']
            ],
            [
                'name' => 'แจ้งออก',
                'slug' => 'notify_out',
                'is_system' => true,
                'order' => 2,
                'steps' => ['รับเอกสาร', 'แจ้งออกระบบ', 'คืนนายจ้าง']
            ],
            [
                'name' => 'MOU นำเข้า',
                'slug' => 'mou_import',
                'is_system' => true,
                'order' => 3,
                'steps' => ['Name List', 'Calling Visa', 'Stamp Visa', 'Work Permit', 'Card']
            ]
        ];

        foreach ($types as $typeData) {
            $steps = $typeData['steps'];
            unset($typeData['steps']);

            $workType = WorkType::create($typeData);

            foreach ($steps as $index => $stepName) {
                WorkTypeStep::create([
                    'work_type_id' => $workType->id,
                    'name' => $stepName,
                    'order' => $index + 1
                ]);
            }
        }
    }

    public function show($id)
    {
        $order = ProductionOrder::with('workType')->findOrFail($id);
        return redirect()->route('workflow.index', ['tab' => $order->workType->slug]);
    }

    // --- Step Configuration Methods ---

    public function storeStep(Request $request)
    {
        $request->validate([
            'work_type_id' => 'required|exists:work_types,id',
            'name' => 'required|string|max:255',
        ]);

        $maxOrder = WorkTypeStep::where('work_type_id', $request->work_type_id)->max('order') ?? 0;

        WorkTypeStep::create([
            'work_type_id' => $request->work_type_id,
            'name' => $request->name,
            'order' => $maxOrder + 1
        ]);

        return response()->json(['success' => true]);
    }

    public function updateStep(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        WorkTypeStep::findOrFail($id)->update(['name' => $request->name]);
        return response()->json(['success' => true]);
    }

    public function destroyStep($id)
    {
        WorkTypeStep::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function reorderSteps(Request $request)
    {
        $request->validate(['order' => 'required|array']);

        foreach ($request->order as $index => $id) {
            WorkTypeStep::where('id', $id)->update(['order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }
}
