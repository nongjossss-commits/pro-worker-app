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
use App\Traits\AddressFilterTrait;

class WorkflowController extends Controller
{
    use AddressFilterTrait;

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
            ->whereHas('employer')
            ->where('status', '!=', 'pre_production'); // Active workflows

        if ($activeTab) {
            $query->where('work_type_id', $activeTab->id);
        }

        // NEW: Address options (before address filtering)
        // ProductionOrder has employer_id
        $addressOptions = $this->getAddressOptions($query, 'employer_id');

        // NEW: Apply address filters
        $query = $this->applyAddressFilters($query, $request, 'employer');

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
                  })
                  ->orWhereHas('items.employee', function($emp) use ($search) {
                      $emp->where('employeeNameTh', 'like', "%{$search}%")
                          ->orWhere('employeeNameEn', 'like', "%{$search}%")
                          ->orWhere('employeePassport', 'like', "%{$search}%");
                  })
                  ->orWhereHas('creator', function($creator) use ($search) {
                      $creator->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // SORTING: Active items (not cancelled/completed) first, then updated_at
        $query->withCount(['items as active_items_count' => function ($q) {
            $q->whereNotIn('status', ['cancelled', 'completed']);
        }]);

        $orders = $query->orderByDesc('active_items_count')
                        ->latest('updated_at')
                        ->paginate(15)
                        ->withQueryString();

        // Calculate Stats PER ORDER for the view (Accordion Header)
        $orders->load(['items.completedWorkTypeSteps', 'employer.addresses']);

        // Employers for Dropdown
        $employers = Employer::orderBy('employerNameTh')->get();

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
                'step_stats' => $stepStats,
                'active_items_count' => $order->active_items_count // Pass to view for Grayscale
            ];
        }

        // 4. Calculate Scoreboard Stats (For the Active Tab)
        $stats = [
            'total_projects' => $orders->total(),
            'total_employees' => 0,
            'not_started' => 0,
            'cancelled' => 0,
            'completed' => 0,
            'step_stats' => [],
            'pending_daily_check' => 0, // NEW
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
                ->select('id', 'status', 'production_order_id', 'last_checked_at', 'created_at')
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

                // Daily Check (Pending if not checked today)
                if (!$item->is_checked_today && $item->status !== 'completed' && $item->status !== 'cancelled') {
                    $stats['pending_daily_check']++;
                }
            }
            $stats['step_stats'] = $globalStepStats;
        }

        return view('workflow.index', compact('orders', 'tabs', 'activeTab', 'stats', 'steps', 'addressOptions', 'employers'));
    }

    /**
     * Dashboard Landing Page Logic
     */
    private function dashboard($tabs)
    {
        // 1. Global Scoreboard Stats
        $itemsQuery = ProductionItem::whereHas('order', fn($q) => $q->where('status', '!=', 'pre_production'));

        $stats = [
            'total_projects' => ProductionOrder::where('status', '!=', 'pre_production')->count(),
            'total_employees' => (clone $itemsQuery)->count(),
            'not_started' => (clone $itemsQuery)->where('status', 'pending')
                                           ->doesntHave('completedWorkTypeSteps')
                                           ->count(),
            'cancelled' => (clone $itemsQuery)->where('status', 'cancelled')->count(),
            'completed' => (clone $itemsQuery)->where('status', 'completed')->count(),
            'pending_daily_check' => (clone $itemsQuery)
                ->where('status', 'pending')
                ->where(function($q) {
                     $q->whereNull('last_checked_at')
                       ->orWhereDate('last_checked_at', '<', Carbon::today());
                })->count(),
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
                ->whereNull('appointment_completed_at') // Exclude completed appointments
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
     * API: Update Appointment Date & Location
     */
    public function updateAppointmentDate(Request $request, $itemId)
    {
        $request->validate([
            'appointment_date' => 'nullable|date',
            'appointment_location' => 'nullable|string|max:255',
        ]);

        $item = ProductionItem::findOrFail($itemId);

        $data = [];
        if ($request->has('appointment_date')) {
            $data['appointment_date'] = $request->appointment_date;
        }
        if ($request->has('appointment_location')) {
            $data['appointment_location'] = $request->appointment_location;
        }

        $item->update($data);

        return response()->json(['success' => true]);
    }

    /**
     * API: Perform Daily Check on Item
     */
    public function checkDaily(Request $request, $itemId)
    {
        $item = ProductionItem::findOrFail($itemId);
        $item->update(['last_checked_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * API: Toggle Appointment Complete
     */
    public function toggleAppointmentComplete(Request $request, $itemId)
    {
        $item = ProductionItem::findOrFail($itemId);

        // If completed, un-complete it (toggle), or user request implies specific action.
        // Usually buttons are "Finish". If already finished, maybe nothing.
        // But for robust toggle:
        if ($item->appointment_completed_at) {
            $item->update(['appointment_completed_at' => null]);
        } else {
            $item->update(['appointment_completed_at' => now()]);
        }

        return response()->json(['success' => true, 'completed_at' => $item->appointment_completed_at]);
    }

    /**
     * API: Get Calendar Data (Counts per day)
     */
    public function getCalendarData(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $counts = ProductionItem::select(DB::raw('DATE(appointment_date) as date'), DB::raw('count(*) as count'))
            ->whereBetween('appointment_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'completed')
            ->whereNull('appointment_completed_at') // Exclude completed appointments
            ->groupBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->date => $item->count];
            });

        return response()->json($counts);
    }

    /**
     * API: Get Appointments for a specific Date (Modal list -> Rendered HTML)
     */
    public function getAppointmentsByDate(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $date = Carbon::parse($request->date);

        $items = ProductionItem::whereDate('appointment_date', $date)
            ->where('status', '!=', 'cancelled')
            ->whereNull('appointment_completed_at') // Exclude completed
            ->with(['employee', 'order.employer', 'order.workType', 'completedWorkTypeSteps'])
            ->get();

        // We need to group them by order for better display, or just list them as cards.
        // Using a partial view for the list of cards.

        $html = view('workflow.partials.day_appointments_list', compact('items'))->render();

        return response()->json(['html' => $html]);
    }

    /**
     * API: Export Selected Appointments
     */
    public function exportAppointments(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:production_items,id'
        ]);

        $ids = $request->ids;
        $items = ProductionItem::whereIn('id', $ids)
            ->with(['employee', 'order.employer', 'order.workType'])
            ->get();

        // Simple CSV Export
        $fileName = 'appointments_export_' . date('Y-m-d_H-i') . '.csv';
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['Date', 'Time', 'Employee Name', 'Passport', 'Employer', 'Project', 'Work Type', 'Location', 'Status'];

        $callback = function() use($items, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($items as $item) {
                $row = [
                    $item->appointment_date ? $item->appointment_date->format('Y-m-d') : '',
                    $item->appointment_date ? $item->appointment_date->format('H:i') : '',
                    $item->employee->employeeNameEn ?? $item->new_employee_data['name_en'] ?? 'New Employee',
                    $item->employee->employeePassport ?? '-',
                    $item->order->employer->employerNameTh ?? '-',
                    $item->order->project_name ?? '-',
                    $item->order->workType->name ?? '-',
                    $item->appointment_location ?? '-',
                    $item->status
                ];
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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

        // Filter out "Historic" items (Completed > 24h ago)
        $items = ProductionItem::with(['employee', 'completedWorkTypeSteps'])
            ->where('production_order_id', $orderId)
            ->where(function($q) {
                $q->where('status', '!=', 'completed')
                  ->orWhere(function($sub) {
                      $sub->where('status', 'completed')
                          ->where('completed_at', '>=', now()->subHours(24));
                  });
            })
            ->orderBy('group_name')
            ->orderBy('id')
            ->get();

        // Group the items collection by group_name for easier view rendering
        $groupedItems = $items->groupBy('group_name');

        // Check if there are history items (for the button)
        $hasHistory = ProductionItem::where('production_order_id', $orderId)
            ->where('status', 'completed')
            ->where(function($q) {
                $q->whereNull('completed_at')
                  ->orWhere('completed_at', '<', now()->subHours(24));
            })
            ->exists();

        return view('workflow.partials.order_items', compact('order', 'groupedItems', 'hasHistory'));
    }

    /**
     * Fetch Historic Items (Completed > 24h).
     */
    public function fetchOrderHistory(Request $request, $orderId)
    {
        $order = ProductionOrder::with(['workType.steps'])->findOrFail($orderId);

        $items = ProductionItem::with(['employee', 'completedWorkTypeSteps'])
            ->where('production_order_id', $orderId)
            ->where('status', 'completed')
            ->where(function($q) {
                $q->whereNull('completed_at')
                  ->orWhere('completed_at', '<', now()->subHours(24));
            })
            ->orderByDesc('completed_at')
            ->get();

        $groupedItems = $items->groupBy('group_name');

        // Reuse the order_items partial but maybe with a flag or different view
        // For simplicity, reusing order_items but passing a flag is good,
        // OR render a simple list. Let's reuse order_items but we need to handle "Restore" button hidden.
        return view('workflow.partials.order_items', compact('order', 'groupedItems'))->with('isHistory', true);
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
        $isPreProduction = $request->boolean('is_pre_production');
        $targetStatus = $isPreProduction ? 'pre_production' : 'active';

        // Resolve WorkType first
        $workTypeId = null;
        if ($request->filled('production_order_id')) {
            $existingOrder = ProductionOrder::findOrFail($request->production_order_id);
            $workTypeId = $existingOrder->work_type_id;
        } elseif ($request->filled('work_type_id')) {
            $workTypeId = $request->work_type_id;
        }

        // --- Special Logic: Notify Out (Resignation) ---
        // If adding existing employees to 'Notify Out', automatically group them by their CURRENT employer.
        // This overrides the selected employer in the form (if any).
        if ($workTypeId && $request->has('employee_ids') && is_array($request->employee_ids)) {
            $workType = WorkType::find($workTypeId);

            if ($workType && $workType->slug === 'notify_out') {
                $employees = Employee::whereIn('id', $request->employee_ids)->with('employer')->get();
                $grouped = $employees->groupBy('employer_id');
                $updatedOrderIds = [];

                foreach ($grouped as $employerId => $emps) {
                    if (!$employerId) continue;

                    // Find or Create Order for this Employer
                    $employer = $emps->first()->employer; // Optimization: use relation from first item

                    $order = ProductionOrder::firstOrCreate(
                        [
                            'employer_id' => $employerId,
                            'work_type_id' => $workTypeId,
                            'status' => $targetStatus
                        ],
                        [
                            'type' => 'employer',
                            'project_name' => $workType->name . ' - ' . ($employer->employerNameTh ?? 'Unknown') . ($isPreProduction ? ' (Prep)' : ''),
                            'created_by' => auth()->id()
                        ]
                    );

                    $updatedOrderIds[] = $order->id;

                    foreach ($emps as $emp) {
                         // Check duplicates (Locking)
                         $hasActive = ProductionItem::where('employee_id', $emp->id)
                            ->whereNotIn('status', ['completed', 'cancelled'])
                            ->exists();

                         if ($hasActive) continue;

                         $exists = ProductionItem::where('production_order_id', $order->id)
                            ->where('employee_id', $emp->id)->exists();

                         if (!$exists) {
                             ProductionItem::create([
                                'production_order_id' => $order->id,
                                'employee_id' => $emp->id,
                                'group_name' => $request->group_name ?? null,
                                'status' => 'pending'
                             ]);
                         }
                    }
                }

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Employees processed into resignation lists.',
                        'redirect_url' => route('workflow.index', ['tab' => 'notify_out']) // Force refresh
                    ]);
                }
                return redirect()->route('workflow.index', ['tab' => 'notify_out'])->with('success', 'Employees processed.');
            }
        }

        // --- Original Logic for Other Types ---

        // 1. Validation: Check for duplicates (Existing Employees)
        // Ensure an employee cannot be in the same WorkType workflow (Active or Pre-Production) twice.
        if ($request->has('employee_ids') && is_array($request->employee_ids)) {
            if ($workTypeId) {
                // Find any items for these employees in this WorkType that are NOT cancelled or completed.
                $duplicates = ProductionItem::whereIn('employee_id', $request->employee_ids)
                    ->whereHas('order', function($q) use ($workTypeId) {
                        $q->where('work_type_id', $workTypeId)
                          ->where('status', '!=', 'cancelled'); // Ensure order is not cancelled
                    })
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->with('employee')
                    ->get();

                if ($duplicates->isNotEmpty()) {
                    $names = $duplicates->map(fn($item) => $item->employee->employeeNameEn ?? $item->employee->employeeNameTh)->unique()->implode(', ');

                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Employees already in this workflow: $names"
                        ]);
                    }

                    return back()->with('duplicate_error', "Employees already in this workflow: $names. Please complete their current process first.");
                }
            }
        }

        $order = null;

        if ($request->filled('production_order_id')) {
            $order = ProductionOrder::findOrFail($request->production_order_id);
        } else {
            $request->validate([
                'work_type_id' => 'required|exists:work_types,id',
                'employer_id' => 'required|exists:employers,id',
            ]);

            $workType = WorkType::findOrFail($request->work_type_id);

            // Bucket Logic (Merge into existing if applicable)
            // Note: notify_out is handled above for existing employees, but if "New Employee" is created,
            // it falls through here. For New Employee, we use the selected employer (request->employer_id).
            if (in_array($workType->slug, ['notify_in', 'notify_out', 'mou_renewal'])) {
                $order = ProductionOrder::firstOrCreate(
                    [
                        'employer_id' => $request->employer_id,
                        'work_type_id' => $workType->id,
                        'status' => $targetStatus // Separate buckets for Active vs Pre-Production
                    ],
                    [
                        'type' => 'employer',
                        'project_name' => $workType->name . ' - ' . Employer::find($request->employer_id)->employerNameTh . ($isPreProduction ? ' (Prep)' : ''),
                        'created_by' => auth()->id()
                    ]
                );
            } else {
                $order = ProductionOrder::create([
                    'employer_id' => $request->employer_id,
                    'work_type_id' => $workType->id,
                    'type' => 'employer',
                    'project_name' => $request->project_name ?? ($workType->name . ' - ' . now()->format('d/m/Y')),
                    'status' => $targetStatus,
                    'created_by' => auth()->id()
                ]);
            }
        }

        if ($request->has('employee_ids')) {
            $ids = $request->employee_ids;
            $groupName = $request->group_name ?? null;

            foreach ($ids as $empId) {
                // Locking: Check if employee is already in an active workflow
                $hasActiveWorkflow = ProductionItem::where('employee_id', $empId)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->exists();

                if ($hasActiveWorkflow) {
                    continue;
                }

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

        // Handle Full Employee Creation (Replaces old Draft logic)
        // Check if we have core fields for a new employee
        if ($request->filled('employeeNameEn') || $request->filled('employeeNameTh')) {
             // Validate
             $validated = $request->validate([
                'employer_id' => 'required|exists:employers,id',
                'employeeNameTh' => 'nullable|string|max:255',
                'employeeNameEn' => 'required|string|max:255',
                'employeePassport' => 'nullable|string|max:255',
                'employeeNationality' => 'nullable|string|max:255',
                // Add strict validation for other fields as needed, mirroring EmployeeController
             ]);

             // Capture all potential fields from request that match Employee model
             $employeeData = $request->only([
                'employer_id', 'employeeTitleTh', 'employeeNameTh', 'employeeTitleEn', 'employeeNameEn',
                'father_name', 'mother_name', 'employeeGender', 'employeeDob', 'employeeAge', 'employeePhone',
                'employeeNationality', 'passportType', 'passport_type_cambodia', 'employeePassport',
                'passport_issue_date', 'passportExpiryDate', 'pinkCardNo', 'visaType', 'visaExpiryDate',
                'job_title', 'job_description', 'startDate', 'employeeWorkPermit', 'workPermitExpiryDate',
                'workPermitType', 'workPermitMOUGroup', 'workPermitMOUGroupOther', 'ninetyDayReportDate',
                'name_list_number', 'request_number', 'employee_id_number', 'tax_id_number',
                'employer_employee_id', 'employee_reference_id', 'insurance_type', 'insurance_detail',
                'insurance_expiry_date', 'social_security_number', 'insurance_detail_hospital',
                'insurance_detail_private', 'insurance_expiry_date_private', 'insurance_expiry_date_hospital',
                'insurance_detail_social', 'medical_hospital_name', 'outsource_code', 'bank_name',
                'bank_account_number', 'other_doc_1_desc', 'other_doc_2_desc', 'other_doc_3_desc',
                'other_doc_4_desc', 'other_doc_5_desc', 'other_doc_6_desc', 'other_doc_7_desc',
                'other_doc_8_desc', 'other_doc_9_desc', 'other_doc_10_desc'
             ]);

             // Insurance Mapping
            $employeeData['insuranceType'] = $request->insurance_type ?? null;
            if ($employeeData['insuranceType'] === 'ประกันสังคม') {
                $employeeData['socialSecurityNumber'] = $request->social_security_number ?? null;
                $employeeData['hospitalName'] = $request->insurance_detail_social ?? null;
            } elseif ($employeeData['insuranceType'] === 'ประกันเอกชน') {
                $employeeData['insuranceCompany'] = $request->insurance_detail_private ?? null;
                $employeeData['insuranceExpiryDate'] = $request->insurance_expiry_date_private ?? null;
            } elseif ($employeeData['insuranceType'] === 'ประกันโรงพยาบาล') {
                $employeeData['hospitalName'] = $request->insurance_detail_hospital ?? null;
                $employeeData['insuranceExpiryDate'] = $request->insurance_expiry_date_hospital ?? null;
            }

            // Email & Password
            $employeeData['email'] = $request->employeeEmail ?? null;
            if ($request->filled('employeePassword')) {
                $employeeData['password'] = $request->employeePassword;
            }

            $employeeData['status'] = 'onboarding';

            // Create Employee
            $employee = Employee::create($employeeData);

            // File Uploads
            $fileFields = [
                'employeePhoto', 'insurance_document_path','insurance_document_path_private', 'medical_certificate_path',
                'employee_doc_1', 'employee_doc_2', 'employee_doc_3', 'employee_doc_4',
                'employee_doc_5', 'employee_doc_6', 'employee_doc_7', 'employee_doc_8',
                'employee_doc_9', 'employee_doc_10', 'employee_doc_11', 'employee_doc_12',
                'employee_doc_13', 'employee_doc_14', 'employee_doc_15', 'employee_doc_16',
                'employee_doc_17', 'employee_doc_18'
            ];

            $filesToUpdate = [];
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $filename = \Illuminate\Support\Str::random(20) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs("employee_files/{$employee->employer_id}", $filename, 'public');
                    $filesToUpdate[$field] = $path;
                }
            }

            if (!empty($filesToUpdate)) {
                $employee->update($filesToUpdate);
            }

            // Add to ProductionItem
            ProductionItem::create([
                'production_order_id' => $order->id,
                'employee_id' => $employee->id,
                'group_name' => $request->group_name ?? null,
                'status' => 'pending'
            ]);
        }

        $slug = $order->workType->slug ?? ($request->work_type_id ? WorkType::find($request->work_type_id)->slug : 'notify_in');

        // Redirect based on status/context
        if ($request->ajax() || $request->wantsJson()) {
            // Calculate stats for the order to update UI
            $orderStats = $this->calculateOrderStats($order);
            return response()->json([
                'success' => true,
                'message' => 'Employee added successfully.',
                'order_id' => $order->id,
                'order_stats' => $orderStats,
                'redirect_url' => $isPreProduction || $order->status === 'pre_production'
                    ? route('production.index', ['tab' => $slug])
                    : route('workflow.index', ['tab' => $slug])
            ]);
        }

        if ($isPreProduction || $order->status === 'pre_production') {
             return redirect()->route('production.index', ['tab' => $slug])
                         ->with('success', 'Preparation Job updated successfully.');
        }

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
            if (in_array($slug, ['notify_in', 'mou_import', 'mou_renewal'])) {
                if ($item->employee) {
                    $item->employee->update([
                        'employer_id' => $item->order->employer_id,
                        'status' => 'active',
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

            $item->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
        });

        // Recalculate Stats
        $orderStats = $this->calculateOrderStats($item->order);

        return response()->json(['success' => true, 'order_stats' => $orderStats]);
    }

    /**
     * Cancel an Item.
     */
    public function cancelItem(Request $request, $itemId)
    {
        $item = ProductionItem::findOrFail($itemId);
        $item->update(['status' => 'cancelled']);

        // Recalculate Stats
        $orderStats = $this->calculateOrderStats($item->order);

        return response()->json(['success' => true, 'order_stats' => $orderStats]);
    }

    /**
     * Restore an Item (Pending).
     */
    public function restoreItem(Request $request, $itemId)
    {
        $item = ProductionItem::findOrFail($itemId);
        // Reset completed_at so if finalized again, timer restarts
        $item->update([
            'status' => 'pending',
            'completed_at' => null
        ]);

        // Recalculate Stats
        $orderStats = $this->calculateOrderStats($item->order);

        return response()->json(['success' => true, 'order_stats' => $orderStats]);
    }

    /**
     * Soft Delete an Item.
     */
    public function destroyItem(Request $request, $itemId)
    {
        $item = ProductionItem::with(['employee', 'order'])->findOrFail($itemId);
        $order = $item->order; // Capture order before delete

        // Capture employee before deleting the item
        $employee = $item->employee;

        $item->delete();

        // Check if employee should also be deleted
        // Logic: If employee was created specifically for this workflow (status 'onboarding')
        if ($employee && $employee->status === 'onboarding') {
            $employee->delete();
        }

        // Recalculate Stats
        $orderStats = $this->calculateOrderStats($order);

        return response()->json(['success' => true, 'order_stats' => $orderStats]);
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
            ],
            [
                'name' => 'ต่ออายุ MOU',
                'slug' => 'mou_renewal',
                'is_system' => true,
                'order' => 4,
                'steps' => ['ยื่นเอกสาร', 'รอผล', 'รับเล่ม']
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

    /**
     * Get HTML for a single Item Card (for AJAX refresh).
     */
    public function getItemHtml($itemId)
    {
        $item = ProductionItem::with(['employee', 'order.employer', 'order.workType.steps', 'completedWorkTypeSteps'])
            ->findOrFail($itemId);

        $order = $item->order;
        $steps = $order->workType->steps ?? collect();

        // Render just the card partial
        $html = view('workflow.partials._item_card', compact('item', 'steps', 'order'))->render();

        return response()->json(['html' => $html]);
    }
}
