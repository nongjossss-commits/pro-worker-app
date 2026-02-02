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
use Carbon\Carbon;

class WorkflowController extends Controller
{
    /**
     * Display the main Workflow Dashboard with Tabs.
     */
    public function index(Request $request)
    {
        // 1. Get Tabs (Work Types)
        $tabs = WorkType::withCount(['orders' => function($q){
             $q->where('status', '!=', 'pre_production');
        }])->orderBy('order')->get();

        if ($tabs->isEmpty()) {
            $this->seedDefaultWorkTypes();
            $tabs = WorkType::orderBy('order')->get();
        }

        // 2. Determine Active Tab
        // If no tab is specified, show the Dashboard Landing Page
        $activeTabSlug = $request->query('tab');

        if (!$activeTabSlug) {
             return $this->dashboard($tabs);
        }

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
                        ->orWhere('employerNameEn', 'like', "%{$search}%")
                        ->orWhere(function($addrQ) use ($search) {
                            $addrQ->filterByAddress($search);
                        });
                  });
            });
        }

        $orders = $query->latest('updated_at')->paginate(15);

        // Calculate Stats PER ORDER for the view (Accordion Header)
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
                $highestStep = $item->completedWorkTypeSteps->sortByDesc('order')->first();
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
            'not_started' => 0,
            'cancelled' => 0,
            'completed' => 0,
            'step_stats' => []
        ];

        if ($activeTab) {
            $statsQuery = ProductionOrder::where('work_type_id', $activeTab->id)
                ->where('status', '!=', 'pre_production');

            $stats['total_projects'] = $statsQuery->count();

            // Get all items for these orders to calculate step stats
            $allTabItems = ProductionItem::whereIn('production_order_id', $statsQuery->select('id'))
                ->with(['completedWorkTypeSteps' => function($q) {
                    $q->select('work_type_steps.id', 'work_type_steps.order', 'production_item_step.production_item_id');
                }])
                ->select('id', 'status', 'production_order_id')
                ->get();

            $globalStepStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
            $stepOneId = $steps->sortBy('order')->first()?->id;

            foreach ($allTabItems as $item) {
                if ($item->status === 'cancelled') {
                    $stats['cancelled']++;
                    continue; // Cancelled items don't count towards step stats usually
                }

                $stats['total_employees']++;

                if ($item->status === 'completed') {
                    $stats['completed']++;
                }

                // Not Started
                if ($stepOneId && !$item->completedWorkTypeSteps->contains('id', $stepOneId)) {
                    $stats['not_started']++;
                }

                // Highest Step
                $highestStep = $item->completedWorkTypeSteps->sortByDesc('order')->first();
                if ($highestStep && isset($globalStepStats[$highestStep->id])) {
                    $globalStepStats[$highestStep->id]++;
                }
            }
            $stats['step_stats'] = $globalStepStats;
        }

        return view('workflow.index', compact('orders', 'tabs', 'activeTab', 'stats', 'steps'));
    }

    /**
     * Dashboard Landing Page Logic
     */
    private function dashboard($tabs)
    {
        // 1. Global Scoreboard Stats
        $stats = [
            'total_projects' => ProductionOrder::where('status', '!=', 'pre_production')->count(),
            'total_employees' => ProductionItem::whereHas('order', fn($q) => $q->where('status', '!=', 'pre_production'))
                                               ->count(),
            'not_started' => ProductionItem::whereHas('order', fn($q) => $q->where('status', '!=', 'pre_production'))
                                           ->where('status', 'pending')
                                           ->doesntHave('completedWorkTypeSteps')
                                           ->count(),
            'cancelled' => ProductionItem::whereHas('order', fn($q) => $q->where('status', '!=', 'pre_production'))
                                         ->where('status', 'cancelled')->count(),
            'completed' => ProductionItem::whereHas('order', fn($q) => $q->where('status', '!=', 'pre_production'))
                                         ->where('status', 'completed')->count(),
        ];

        // 2. Upcoming Appointments
        // Filter by each work type's notification setting
        $upcomingAppointments = collect();
        $workTypes = $tabs;

        foreach ($workTypes as $wt) {
            $days = $wt->notify_days_advance ?? 3;
            // Range: Today 00:00 to Today+Days 23:59
            $start = Carbon::now()->startOfDay();
            $end = Carbon::now()->addDays($days)->endOfDay();

            $items = ProductionItem::whereHas('order', fn($q) => $q->where('work_type_id', $wt->id))
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'completed')
                ->whereNotNull('appointment_date')
                ->whereBetween('appointment_date', [$start, $end])
                ->with(['employee', 'order.employer', 'order.workType'])
                ->get();

            $upcomingAppointments = $upcomingAppointments->merge($items);
        }

        // Sort by date soonest
        $upcomingAppointments = $upcomingAppointments->sortBy('appointment_date');

        return view('workflow.dashboard', compact('tabs', 'stats', 'upcomingAppointments'));
    }

    /**
     * API: Update Appointment Date
     */
    public function updateAppointmentDate(Request $request, $itemId)
    {
        $request->validate([
            'appointment_date' => 'nullable|date',
        ]);

        $item = ProductionItem::findOrFail($itemId);
        $item->update(['appointment_date' => $request->appointment_date]);

        return response()->json(['success' => true]);
    }

    /**
     * API: Update Notification Settings for WorkType
     */
    public function updateNotificationSettings(Request $request, $workTypeId)
    {
        $request->validate([
            'notify_days_advance' => 'required|integer|min:0|max:365'
        ]);

        $wt = WorkType::findOrFail($workTypeId);
        $wt->update(['notify_days_advance' => $request->notify_days_advance]);

        return response()->json(['success' => true]);
    }

    /**
     * Fetch Employer Teams for "Manage Team" Modal.
     */
    public function getEmployerTeams($employerId)
    {
        $groups = \App\Models\EmployeeGroup::where('employer_id', $employerId)
            ->with('teams')
            ->get();

        return response()->json($groups);
    }

    /**
     * Update/Assign Team for an Item (Employee).
     */
    public function updateItemTeam(Request $request, $itemId)
    {
        $request->validate([
            'team_ids' => 'array', // Allow multiple teams or empty (to clear)
            'team_ids.*' => 'exists:employee_teams,id'
        ]);

        $item = ProductionItem::with('employee')->findOrFail($itemId);

        if (!$item->employee) {
            return response()->json(['success' => false, 'message' => 'Cannot assign team to draft employee.']);
        }

        $item->employee->teams()->sync($request->input('team_ids', []));

        return response()->json(['success' => true]);
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

        // Return stats for UI update (Recalculate Order Stats)
        $order = $item->order;
        $orderStats = $this->calculateOrderStats($order);

        return response()->json([
            'success' => true,
            'order_stats' => $orderStats
        ]);
    }

    /**
     * Helper to calculate stats for a single order.
     */
    private function calculateOrderStats(ProductionOrder $order)
    {
        // Ensure relations are loaded
        $order->load(['items.completedWorkTypeSteps', 'workType.steps']);
        $items = $order->items;
        $steps = $order->workType->steps ?? collect();
        $stepOneId = $steps->sortBy('order')->first()?->id;

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

            $total++;

            if ($item->status === 'completed') {
                $completed++;
            }

            if ($stepOneId && !$item->completedWorkTypeSteps->contains('id', $stepOneId)) {
                $notStarted++;
            }

            $highestStep = $item->completedWorkTypeSteps->sortByDesc('order')->first();
            if ($highestStep && isset($stepStats[$highestStep->id])) {
                $stepStats[$highestStep->id]++;
            }
        }

        return [
            'total' => $total,
            'not_started' => $notStarted,
            'cancelled' => $cancelled,
            'completed' => $completed,
            'step_stats' => $stepStats
        ];
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
        $query = Employee::query()
             ->whereNotNull('terminated_at')
             ->with('employer');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('employeeNameTh', 'like', "%{$search}%")
                  ->orWhere('employeeNameEn', 'like', "%{$search}%")
                  ->orWhere('employeePassport', 'like', "%{$search}%")
                  ->orWhereHas('employer', function($e) use ($search) {
                      $e->filterByAddress($search);
                  });
            });
        }

        $employees = $query->limit(20)->get();

        return response()->json($employees);
    }

    /**
     * API: Search Global Active Employees (Any Employer).
     */
    public function searchGlobalEmployees(Request $request)
    {
        $search = $request->query('q');
        $query = Employee::query()
             ->whereNull('terminated_at')
             ->with('employer');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('employeeNameTh', 'like', "%{$search}%")
                  ->orWhere('employeeNameEn', 'like', "%{$search}%")
                  ->orWhere('employeePassport', 'like', "%{$search}%")
                  ->orWhereHas('employer', function($e) use ($search) {
                      $e->filterByAddress($search);
                  });
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
            ->whereNull('terminated_at')
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
            ]);

            $workType = WorkType::findOrFail($request->work_type_id);

            if (in_array($workType->slug, ['notify_in', 'notify_out'])) {
                $order = ProductionOrder::firstOrCreate(
                    [
                        'employer_id' => $request->employer_id,
                        'work_type_id' => $workType->id,
                        'status' => 'active'
                    ],
                    [
                        'type' => 'employer',
                        'project_name' => $workType->name . ' - ' . Employer::find($request->employer_id)->employerNameTh,
                        'created_by' => auth()->id()
                    ]
                );
            } else {
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

        if ($request->has('employee_ids')) {
            $ids = $request->employee_ids;
            $groupName = $request->group_name ?? null;

            foreach ($ids as $empId) {
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
            if ($slug === 'notify_in') {
                if ($item->employee) {
                    $item->employee->update([
                        'employer_id' => $item->order->employer_id,
                        'status' => null,
                        'terminated_at' => null,
                        'termination_reason' => null
                    ]);
                }
            } elseif ($slug === 'notify_out') {
                if ($item->employee) {
                    $item->employee->update([
                        'terminated_at' => now(),
                        'status' => 'resigned'
                    ]);
                }
            }

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
