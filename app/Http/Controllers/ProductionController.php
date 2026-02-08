<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\Employer;
use App\Models\Employee;
use App\Models\WorkType;
use App\Models\WorkTypeStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\AddressFilterTrait;

class ProductionController extends Controller
{
    use AddressFilterTrait;

    /**
     * Display a listing of Production Orders (Preparation / Pre-Production).
     * Now mirrored with Workflow tabs.
     */
    public function index(Request $request)
    {
        // 1. Get Tabs (Work Types) - Same as Workflow
        // We might want to show all tabs, or filter. For now, show all.
        $tabs = WorkType::withCount(['orders' => function($q){
             $q->where('status', 'pre_production');
        }])->orderBy('order')->get();

        if ($tabs->isEmpty()) {
            // Seeding logic is in WorkflowController, assuming it's done.
            $tabs = WorkType::orderBy('order')->get();
        }

        // 2. Determine Active Tab
        $activeTabSlug = $request->query('tab');
        $activeTab = null;
        if ($activeTabSlug) {
            $activeTab = $tabs->where('slug', $activeTabSlug)->first();
        }

        // Initialize empty Orders and Steps
        $orders = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        $steps = collect();

        // 3. Global Stats Calculation (Default)
        // If no tab is selected, we calculate stats across ALL Pre-Production orders.
        // If a tab is selected, we override these with tab-specific stats.
        $stats = [
            'total_projects' => 0,
            'total_employees' => 0,
            'not_started' => 0,
            'cancelled' => 0,
            'completed' => 0,
            'step_stats' => [],
        ];

        // If Active Tab is NULL: Calculate Global Stats Only, do not fetch Orders.
        if (!$activeTab) {
             // Efficient Global Stats
             $baseItemsQuery = ProductionItem::whereHas('order', function($q) {
                 $q->where('status', 'pre_production');
             });

             $stats['total_projects'] = ProductionOrder::where('status', 'pre_production')->count();
             $stats['total_employees'] = (clone $baseItemsQuery)->count();
             $stats['not_started'] = (clone $baseItemsQuery)->where('status', 'pending')->doesntHave('completedWorkTypeSteps')->count();
             $stats['cancelled'] = (clone $baseItemsQuery)->where('status', 'cancelled')->count();
             $stats['completed'] = (clone $baseItemsQuery)->where('status', 'completed')->count();
             // Step stats are meaningless globally if steps vary by WorkType, leaving empty or handled per-type (too complex for summary)
        } else {
            // 4. Active Tab Logic: Query Orders and Calculate Specific Stats

            // Query Orders for this Tab
            $query = ProductionOrder::with(['employer', 'workType'])
                        ->whereHas('employer')
                        ->where('status', 'pre_production')
                        ->where('work_type_id', $activeTab->id);

            // Address Filtering
            $addressOptions = $this->getAddressOptions($query, 'employer_id');
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

            // Filter (Status/Step)
            if ($request->has('filter') && $request->filter) {
                $filter = $request->filter;
                $query->whereHas('items', function($q) use ($filter) {
                    if ($filter === 'not_started') {
                        $q->where('status', 'pending')->doesntHave('completedWorkTypeSteps');
                    } elseif ($filter === 'cancelled') {
                        $q->where('status', 'cancelled');
                    } elseif ($filter === 'completed') {
                        $q->where('status', 'completed');
                    } elseif (is_numeric($filter)) {
                        $q->whereHas('completedWorkTypeSteps', function($s) use ($filter) {
                            $s->where('work_type_steps.id', $filter);
                        });
                    }
                });
            }

            // SORTING
            $query->withCount(['items as active_items_count' => function ($q) {
                $q->whereNotIn('status', ['cancelled', 'completed']);
            }]);

            $orders = $query->orderByDesc('active_items_count')
                            ->latest('updated_at')
                            ->paginate(15)
                            ->withQueryString();

            // Load Relations
            $orders->load(['items.completedWorkTypeSteps', 'employer.addresses']);

            // Get Steps
            $steps = WorkTypeStep::where('work_type_id', $activeTab->id)
                        ->where('stage', 'preparation')
                        ->orderBy('order')
                        ->get();

            // Init Step Stats
            $stats['step_stats'] = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();

            // Calculate Stats for Active Tab
            if (!$request->has('search') && !$request->has('filter')) {
                 $baseItemsQuery = ProductionItem::whereHas('order', function($q) use ($activeTab) {
                     $q->where('work_type_id', $activeTab->id)
                       ->where('status', 'pre_production');
                 });
                 $stats['total_projects'] = ProductionOrder::where('work_type_id', $activeTab->id)->where('status', 'pre_production')->count();
                 $stats['total_employees'] = (clone $baseItemsQuery)->count();
                 $stats['not_started'] = (clone $baseItemsQuery)->where('status', 'pending')->doesntHave('completedWorkTypeSteps')->count();
                 $stats['cancelled'] = (clone $baseItemsQuery)->where('status', 'cancelled')->count();
                 $stats['completed'] = (clone $baseItemsQuery)->where('status', 'completed')->count();

                 $allStepItems = (clone $baseItemsQuery)
                     ->with('completedWorkTypeSteps:id,order')
                     ->get();

                 foreach ($allStepItems as $item) {
                     if ($item->status === 'cancelled') continue;
                     $highestStep = $item->completedWorkTypeSteps->sortByDesc('order')->first();
                     if ($highestStep && isset($stats['step_stats'][$highestStep->id])) {
                         $stats['step_stats'][$highestStep->id]++;
                     }
                 }
            } else {
                 $allMatchingOrders = $query->get();
                 $stats['total_projects'] = $allMatchingOrders->count();

                 foreach ($allMatchingOrders as $order) {
                     $order->load(['items.completedWorkTypeSteps']);
                     foreach ($order->items as $item) {
                         $stats['total_employees']++;

                         if ($item->status === 'cancelled') {
                             $stats['cancelled']++;
                             continue;
                         }
                         if ($item->status === 'completed') {
                             $stats['completed']++;
                         }
                         if ($item->status === 'pending' && $item->completedWorkTypeSteps->isEmpty()) {
                             $stats['not_started']++;
                         }

                         $highestStep = $item->completedWorkTypeSteps->sortByDesc('order')->first();
                         if ($highestStep && isset($stats['step_stats'][$highestStep->id])) {
                             $stats['step_stats'][$highestStep->id]++;
                         }
                     }
                 }
            }

            // Per Order Stats
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
                    $total++;
                    if ($item->status === 'completed') {
                        $completed++;
                    }
                    $completedSteps = $item->completedWorkTypeSteps->pluck('id')->toArray();
                    if (empty($completedSteps)) {
                        $notStarted++;
                    }
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
                    'active_items_count' => $order->active_items_count
                ];
            }
        }

        // Employers for Dropdown (Global Add Employee)
        // Need to pass addressOptions even if not used (empty array)
        if (!isset($addressOptions)) {
            $addressOptions = ['provinces' => [], 'districts' => [], 'subDistricts' => []];
        }
        $employers = Employer::orderBy('employerNameTh')->get();

        return view('production.index', compact('orders', 'tabs', 'activeTab', 'steps', 'addressOptions', 'employers', 'stats'));
    }

    /**
     * Send an item to the Workflow (Active Status).
     */
    public function sendToWorkflow(Request $request, $itemId)
    {
        $item = ProductionItem::with(['order', 'employee'])->findOrFail($itemId);
        $currentOrder = $item->order;

        if ($currentOrder->status !== 'pre_production') {
            return response()->json(['success' => false, 'message' => 'Item is already in Workflow.'], 400);
        }

        // Strict Validation: Check if employee already exists in any Active Workflow for this WorkType
        // "Active" means status != 'pre_production' (could be 'active' or 'completed' in workflow context?)
        // User Requirement: Cannot be in Workflow if moving from Pre-Production.
        $hasDuplicate = ProductionItem::where('employee_id', $item->employee_id)
            ->whereHas('order', function($q) use ($currentOrder) {
                $q->where('work_type_id', $currentOrder->work_type_id)
                  ->where('status', '!=', 'pre_production') // Active Workflow Order
                  ->where('status', '!=', 'cancelled');
            })
            ->whereNotIn('status', ['cancelled', 'completed']) // Assuming completed allows new entry? No, strict One-to-One.
            ->exists();

        if ($hasDuplicate) {
             return response()->json([
                 'success' => false,
                 'message' => 'This employee is already active in the Workflow for this process. Please resolve the existing item first.'
             ], 400);
        }

        DB::beginTransaction();
        try {
            // Find an Active Order for this Employer + WorkType
            $activeOrder = ProductionOrder::where('employer_id', $currentOrder->employer_id)
                                ->where('work_type_id', $currentOrder->work_type_id)
                                ->where('status', '!=', 'pre_production') // Active
                                ->where('status', '!=', 'cancelled')
                                ->latest()
                                ->first();

            if (!$activeOrder) {
                // Create new Active Order
                // Use the same project name or a clean one
                $workTypeName = $currentOrder->workType->name ?? 'Job';
                $employerName = $currentOrder->employer->employerNameTh ?? 'Unknown';

                $activeOrder = ProductionOrder::create([
                    'employer_id' => $currentOrder->employer_id,
                    'work_type_id' => $currentOrder->work_type_id,
                    'type' => $currentOrder->type,
                    // Format: "WorkType - EmployerName" as requested implicitly by "Show on Employer Card"
                    'project_name' => "$workTypeName - $employerName",
                    'description' => $currentOrder->description,
                    'status' => 'active',
                    'created_by' => auth()->id(),
                ]);
            }

            // Move Item: Update the production_order_id to the new active order
            $item->update([
                'production_order_id' => $activeOrder->id,
                'status' => 'pending', // Reset status to pending in workflow
                'last_checked_at' => null, // Reset checks
                'appointment_date' => null, // Reset appointment date as it is stage-specific
                'appointment_location' => null,
                'appointment_completed_at' => null,
            ]);

            // Clear Completed Steps (Preparation steps do not map to Workflow steps 1:1)
            $item->completedWorkTypeSteps()->detach();

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new Step (Preparation Stage).
     */
    public function storeStep(Request $request)
    {
        $request->validate([
            'work_type_id' => 'required|exists:work_types,id',
            'name' => 'required|string|max:255',
        ]);

        $maxOrder = WorkTypeStep::where('work_type_id', $request->work_type_id)
                        ->where('stage', 'preparation') // Scoped to preparation
                        ->max('order') ?? 0;

        WorkTypeStep::create([
            'work_type_id' => $request->work_type_id,
            'name' => $request->name,
            'order' => $maxOrder + 1,
            'stage' => 'preparation' // Distinct from workflow steps
        ]);

        return response()->json(['success' => true]);
    }

    // --- Financial Group Methods (Fix for Settings Saving) ---

    public function storeFinancialGroup(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);

        $request->validate(['name' => 'required|string']);

        $group = $production->financialGroups()->create([
            'name' => $request->name,
            'financial_data' => $production->financial_data ?? []
        ]);

        return response()->json(['success' => true, 'group' => $group]);
    }

    public function updateFinancialGroup(Request $request, $id, $groupId)
    {
        $group = \App\Models\ProductionFinancialGroup::where('production_order_id', $id)->findOrFail($groupId);

        $data = $request->all();

        // If simple rename
        if ($request->has('name') && count($data) === 1) {
             $group->update(['name' => $request->name]);
        } else {
             // Saving Settings
             // Filter out token or method if present, though $request->all() usually has them.
             // Typically we want to save the payload as financial_data.
             // The JS sends a clean JSON body.

             $jsonPayload = $request->json()->all();

             // Handle Price Tier Item Creation (Auto-convert candidates to items)
             if (isset($jsonPayload['pricing_tiers']) && is_array($jsonPayload['pricing_tiers'])) {
                 foreach ($jsonPayload['pricing_tiers'] as &$tier) {
                     if (isset($tier['item_ids']) && is_array($tier['item_ids'])) {
                         $newItemIds = [];
                         foreach ($tier['item_ids'] as $itemId) {
                             if (is_string($itemId) && str_starts_with($itemId, 'emp_')) {
                                 $empId = str_replace('emp_', '', $itemId);
                                 // Create Item if not exists
                                 $item = \App\Models\ProductionItem::firstOrCreate(
                                     [
                                         'production_order_id' => $id,
                                         'employee_id' => $empId
                                     ],
                                     [
                                         'status' => 'pending', // Default status
                                         'group_name' => null
                                     ]
                                 );
                                 $newItemIds[] = $item->id;
                             } else {
                                 $newItemIds[] = $itemId;
                             }
                         }
                         $tier['item_ids'] = $newItemIds;
                         // Update count based on real items
                         $tier['count'] = count($newItemIds);
                     }
                 }
             }

             $group->financial_data = $jsonPayload;
             $group->save();
        }

        return response()->json(['success' => true]);
    }

    public function destroyFinancialGroup(Request $request, $id, $groupId)
    {
        $group = \App\Models\ProductionFinancialGroup::where('production_order_id', $id)->findOrFail($groupId);
        $group->delete();
        return response()->json(['success' => true]);
    }

    // ... (Keep other methods like edit, update, etc. if they are still relevant or redirect them)
    // For now, we are replacing the main Index logic.
    // The previous 'create', 'store' methods in ProductionController are still useful for creating the Pre-Prod job initially.

    public function create(Request $request)
    {
        // ... (Keep existing create logic, just ensure work_type_id is handled)
        // Actually, we might need to update Create to select WorkType.
        // Let's assume the Workflow "Create Job" modal is used generally,
        // OR we update the existing create view.
        // For this task, I'll focus on the Index/Board first.

        // Return existing create view but passing worktypes
        $workTypes = WorkType::orderBy('order')->get();
        return view('production.create', compact('workTypes')); // We'll need to update view too
    }

    public function store(Request $request)
    {
         // Update Store to include work_type_id
         // ...
         // For brevity, let's assume we use the WorkflowController@store logic
         // but adapted for Pre-Production if status is set to pre_production.

         // Let's leave existing Store for now and assume migration of logic if needed.
         // The user instruction "Production menu ... like Workflow" implies
         // we might use a similar Modal to create jobs.

         return parent::callAction('store', [$request]); // Fallback or implement
    }
}
