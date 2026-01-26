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
        } else {
            // "All" or "General" view - maybe show uncategorized?
            // For now, if no tab matches, show nothing or all.
            // Let's assume we always have a tab if seeded.
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

        // 4. Calculate Scoreboard Stats (For the Active Tab)
        // Total Projects
        $stats = [
            'total_projects' => $orders->total(),
            'total_employees' => 0,
            'completed_employees' => 0,
        ];

        // We need a separate query for totals across all pages
        if ($activeTab) {
            $statsQuery = ProductionOrder::where('work_type_id', $activeTab->id)
                ->where('status', '!=', 'pre_production');

            $stats['total_projects'] = $statsQuery->count();

            // Join items to get employee counts
            // This might be heavy, optimise later if needed
            $itemsQuery = ProductionItem::whereIn('production_order_id', $statsQuery->select('id'));
            $stats['total_employees'] = $itemsQuery->count();
            // Assuming we have a way to know if item is "completed" - for now just count items
        }

        // Steps for the active tab (to display columns/settings)
        $steps = $activeTab ? $activeTab->steps : collect();

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
            // Grouping logic: Order by Group Name then ID
            ->orderBy('group_name') // Nulls first usually
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
                        'group_name' => $groupName
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
                'group_name' => $request->group_name ?? null
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
            // For MOU, maybe just mark as imported/done?

            // Mark the item itself as completed (if we had a status column on ProductionItem)
            // For now, we assume the action on the Employee model is the "Result".
        });

        return response()->json(['success' => true]);
    }

    // ... keep existing show/showItem methods if needed, or remove if fully replaced.
    // I will keep show() but redirect it or repurpose it.
    // Actually show() was the board view. User wants new UI. I should probably remove the old Board view reference in index.

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
        // Redirect to the tab that contains this order
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
