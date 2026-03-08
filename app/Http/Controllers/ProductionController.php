<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\ProductionFinancialGroup;
use App\Models\Employer;
use App\Models\Employee;
use App\Models\WorkType;
use App\Models\WorkTypeStep;
use App\Models\User;
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
        // 1. Get Tabs (Work Types) - Same as Workflow
        $tabs = WorkType::withCount(['orders' => function($q){
             $q->where('status', 'pre_production');
        }])->orderBy('order')->get();

        if ($tabs->isEmpty()) {
            $tabs = WorkType::orderBy('order')->get();
        }

        // 2. Determine Active Tab
        $activeTabSlug = $request->query('tab');
        $activeTab = null;
        if ($activeTabSlug) {
            $activeTab = $tabs->where('slug', $activeTabSlug)->first();
        }

        // Initialize empty Orders and Steps
        $perPage = $request->input('per_page', 20);
        if (!in_array($perPage, [20, 50, 100])) {
            $perPage = 20;
        }

        $orders = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        $steps = collect();

        // 3. Global Stats Calculation (Default)
        $stats = [
            'total_projects' => 0,
            'total_employees' => 0,
            'not_started' => 0,
            'cancelled' => 0,
            'completed' => 0,
            'pending_daily_check' => 0,
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
             $stats['pending_daily_check'] = (clone $baseItemsQuery)->whereNotIn('status', ['cancelled', 'completed'])
                ->where(function($q) {
                    $q->whereNull('last_checked_at')
                      ->orWhereDate('last_checked_at', '<', now()->today());
                })->count();
        } else {
            // 4. Active Tab Logic: Query Orders and Calculate Specific Stats

            // Query Orders for this Tab
            $query = ProductionOrder::with(['employer.jobOwner', 'workType', 'creator', 'updater'])
                        ->whereHas('employer')
                        ->where('status', 'pre_production')
                        ->where('work_type_id', $activeTab->id);

            // Address Filtering
            $addressOptions = $this->getAddressOptions($query, 'employer_id');
            $query = $this->applyAddressFilters($query, $request, 'employer');

            // Search
            if ($request->has('search') && $request->search) {
                $search = trim($request->search);
                $cleanedSearch = str_replace(' ', '', $search);
                $query->where(function($q) use ($search, $cleanedSearch) {
                    $q->where('project_name', 'like', "%{$search}%")
                      ->orWhereRaw("REPLACE(project_name, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                      ->orWhereHas('employer', function($e) use ($search, $cleanedSearch) {
                          $e->where('employerNameTh', 'like', "%{$search}%")
                            ->orWhere('employerNameEn', 'like', "%{$search}%")
                            ->orWhereRaw("REPLACE(employerNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                            ->orWhereRaw("REPLACE(employerNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                            ->orWhere(function($addrQ) use ($search) {
                                $addrQ->filterByAddress($search);
                            });
                      })
                      ->orWhereHas('items', function($itemQuery) use ($search, $cleanedSearch) {
                          $itemQuery->where('request_number', 'like', "%{$search}%")
                              ->orWhereHas('employee', function($emp) use ($search, $cleanedSearch) {
                                  $emp->where('employeeNameTh', 'like', "%{$search}%")
                                      ->orWhere('employeeNameEn', 'like', "%{$search}%")
                                      ->orWhere('employeePassport', 'like', "%{$search}%")
                                      ->orWhere('employeeWorkPermit', 'like', "%{$search}%")
                                      ->orWhere('employee_id_number', 'like', "%{$search}%")
                                      ->orWhere('name_list_number', 'like', "%{$search}%")
                                      ->orWhere('pinkCardNo', 'like', "%{$search}%")
                                      ->orWhere('employer_employee_id', 'like', "%{$search}%")
                                      ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                                      ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
                              });
                      })
                      ->orWhereHas('creator', function($creator) use ($search) {
                          $creator->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('updater', function($updater) use ($search) {
                          $updater->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('employer.jobOwner', function($owner) use ($search) {
                          $owner->where('name', 'like', "%{$search}%");
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
                    } elseif ($filter === 'pending_daily_check') {
                        $q->where(function($sub) {
                            $sub->whereNull('last_checked_at')
                                ->orWhereDate('last_checked_at', '<', now()->today());
                        })->whereNotIn('status', ['cancelled', 'completed']);
                    } elseif (is_numeric($filter)) {
                        $q->whereHas('completedWorkTypeSteps', function($s) use ($filter) {
                            $s->where('work_type_steps.id', $filter);
                        });
                    }
                });
            }

            // Operator Filter
            if ($request->has('operator_filter') && $request->operator_filter) {
                $opFilter = $request->operator_filter;
                $query->whereHas('items', function($q) use ($opFilter) {
                    $q->where('operator_id', $opFilter);
                });
            }

            // SORTING
            $query->withCount([
                'items as active_items_count' => function ($q) {
                    $q->whereNotIn('status', ['cancelled', 'completed']);
                },
                'items as cancelled_items_count' => function ($q) {
                    $q->where('status', 'cancelled');
                }
            ]);

            $orders = $query->orderByDesc('active_items_count')
                            ->orderBy('cancelled_items_count')
                            ->latest('updated_at')
                            ->paginate($perPage)
                            ->withQueryString();

            // Load Relations - Do not eager load ALL items to save memory, they will be lazy loaded!
            $orders->load([
                'employer.addresses'
            ]);

            foreach ($orders as $order) {
                // Determine shared groups for proper financial status calculation if work_type_id is set
                $sharedGroups = $order->financialGroups;
                if ($order->work_type_id !== null) {
                    $sharedGroups = \App\Models\ProductionFinancialGroup::where('employer_id', $order->employer_id)
                        ->where('work_type_id', $order->work_type_id)
                        ->with(['transactions.items', 'advanceItems'])
                        ->get();
                    if ($sharedGroups->isEmpty()) {
                        $sharedGroups = $order->financialGroups;
                    }
                }
                $order->setRelation('financialGroups', $sharedGroups);

                $empIds = $order->items->pluck('employee_id')->filter()->unique();
                $employeeFinancialStatus = \App\Services\FinancialStatusService::calculateStatusForEmployees($order, $empIds);

                foreach ($order->items as $item) {
                    if ($item->employee) {
                        $item->employee->financialStatus = $employeeFinancialStatus[$item->employee->id] ?? null;
                    }
                }
            }

            // Get Steps
            $steps = WorkTypeStep::where('work_type_id', $activeTab->id)
                        ->where('stage', 'preparation')
                        ->orderBy('order')
                        ->get();

            // Init Step Stats
            $stats['step_stats'] = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();

            // Stats should reflect search but NOT the state filter
            $statsQuery = ProductionOrder::where('status', 'pre_production')
                ->where('work_type_id', $activeTab->id);

            if ($request->has('search') && $request->search) {
                $search = trim($request->search);
                $cleanedSearch = str_replace(' ', '', $search);
                $statsQuery->where(function($q) use ($search, $cleanedSearch) {
                    $q->where('project_name', 'like', "%{$search}%")
                      ->orWhereRaw("REPLACE(project_name, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                      ->orWhereHas('employer', function($e) use ($search, $cleanedSearch) {
                          $e->where('employerNameTh', 'like', "%{$search}%")
                            ->orWhere('employerNameEn', 'like', "%{$search}%")
                            ->orWhereRaw("REPLACE(employerNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                            ->orWhereRaw("REPLACE(employerNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                            ->orWhere(function($addrQ) use ($search) { $addrQ->filterByAddress($search); });
                      })
                      ->orWhereHas('items', function($itemQuery) use ($search, $cleanedSearch) {
                          $itemQuery->where('request_number', 'like', "%{$search}%")
                              ->orWhereHas('employee', function($emp) use ($search, $cleanedSearch) {
                                  $emp->where('employeeNameTh', 'like', "%{$search}%")
                                      ->orWhere('employeeNameEn', 'like', "%{$search}%")
                                      ->orWhere('employeePassport', 'like', "%{$search}%")
                                      ->orWhere('employeeWorkPermit', 'like', "%{$search}%")
                                      ->orWhere('employee_id_number', 'like', "%{$search}%")
                                      ->orWhere('name_list_number', 'like', "%{$search}%")
                                      ->orWhere('pinkCardNo', 'like', "%{$search}%")
                                      ->orWhere('employer_employee_id', 'like', "%{$search}%")
                                      ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                                      ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
                              });
                      })
                      ->orWhereHas('employer.jobOwner', function($owner) use ($search) {
                          $owner->where('name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->has('operator_filter') && $request->operator_filter) {
                $opFilter = $request->operator_filter;
                $statsQuery->whereHas('items', fn($q) => $q->where('operator_id', $opFilter));
            }

            // Efficient Stats Calculation via SQL rather than PHP loops
            $stats['total_projects'] = $statsQuery->count();

            // Get all order IDs that match the query
            $matchingOrderIds = $statsQuery->pluck('id');

            if ($matchingOrderIds->isNotEmpty()) {
                $baseItemQuery = ProductionItem::whereIn('production_order_id', $matchingOrderIds);

                $stats['total_employees'] = (clone $baseItemQuery)->count();
                $stats['cancelled'] = (clone $baseItemQuery)->where('status', 'cancelled')->count();
                $stats['completed'] = (clone $baseItemQuery)->where('status', 'completed')->count();
                $stats['not_started'] = (clone $baseItemQuery)->where('status', 'pending')->doesntHave('completedWorkTypeSteps')->count();
                $stats['pending_daily_check'] = (clone $baseItemQuery)->whereNotIn('status', ['cancelled', 'completed'])
                    ->where(function($q) {
                        $q->whereNull('last_checked_at')
                          ->orWhereDate('last_checked_at', '<', now()->today());
                    })->count();

                // Step Stats (Highest Step) - Global
                $itemsWithSteps = (clone $baseItemQuery)->whereNotIn('status', ['cancelled', 'completed'])
                    ->with(['completedWorkTypeSteps' => function($q) {
                        $q->orderByDesc('order');
                    }])
                    ->get();

                foreach ($itemsWithSteps as $item) {
                    $highestStep = $item->completedWorkTypeSteps->first();
                    if ($highestStep && isset($stats['step_stats'][$highestStep->id])) {
                        $stats['step_stats'][$highestStep->id]++;
                    }
                }
            }

            // Remove eager loading of items for ALL orders to save memory.
            // Items for specific orders will be lazy loaded in the view or batchStats.

            // Per Order Stats will now be empty and populated via AJAX in batchStats
            foreach ($orders as $order) {
                // Determine active items count (already passed from withCount above)
                $activeCount = $order->active_items_count ?? 0;

                $order->computedStats = [
                    'total' => $activeCount, // Temporary placeholder until AJAX
                    'not_started' => 0,
                    'cancelled' => $order->cancelled_items_count ?? 0,
                    'completed' => 0,
                    'step_stats' => $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray(),
                    'active_items_count' => $activeCount
                ];
            }
        }

        // Employers for Dropdown (Global Add Employee)
        if (!isset($addressOptions)) {
            $addressOptions = ['provinces' => [], 'districts' => [], 'subDistricts' => []];
        }
        $employers = Employer::orderBy('employerNameTh')->get();

        // Users for Operator Filter
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('production.index', compact('orders', 'tabs', 'activeTab', 'steps', 'addressOptions', 'employers', 'stats', 'users'));
    }

    /**
     * Send an item to the Workflow (Active Status).
     */
    public function updateItemFields(Request $request, ProductionItem $item)
    {
        $request->validate([
            'request_number' => 'nullable|string|max:255',
        ]);

        $item->update([
            'request_number' => $request->request_number,
        ]);

        return response()->json(['success' => true]);
    }

    public function sendToWorkflow(Request $request, $itemId)
    {
        $item = ProductionItem::with(['order', 'employee'])->findOrFail($itemId);
        $currentOrder = $item->order;

        if ($currentOrder->status !== 'pre_production') {
            return response()->json(['success' => false, 'message' => 'Item is already in Workflow.'], 400);
        }

        // Strict Validation: Check if employee already exists in any Active Workflow for this WorkType
        $hasDuplicate = ProductionItem::where('employee_id', $item->employee_id)
            ->whereHas('order', function($q) use ($currentOrder) {
                $q->where('work_type_id', $currentOrder->work_type_id)
                  ->where('status', '!=', 'pre_production') // Active Workflow Order
                  ->where('status', '!=', 'cancelled');
            })
            ->whereNotIn('status', ['cancelled', 'completed'])
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
                $workTypeName = $currentOrder->workType->name ?? 'Job';
                $employerName = $currentOrder->employer->employerNameTh ?? 'Unknown';

                $activeOrder = ProductionOrder::create([
                    'employer_id' => $currentOrder->employer_id,
                    'work_type_id' => $currentOrder->work_type_id,
                    'type' => $currentOrder->type,
                    'project_name' => "$workTypeName - $employerName",
                    'description' => $currentOrder->description,
                    'status' => 'active',
                    'created_by' => auth()->id(),
                ]);
            }

            // --- LINK FINANCIAL GROUPS Logic ---
            // Ensure both orders share the same Financial Group(s).
            // Since we are moving from Pre-Prod to Active, we check if Pre-Prod has groups.
            // If so, we ensure they are tagged with employer/workType.

            // Note: The logic has shifted to 'Shared Retrieval' based on Employer+WorkType.
            // But we should ensure consistency if groups were created before the migration or without tags.
            // (Migration handled existing groups, so we are good).

            // Move Item: Update the production_order_id to the new active order
            $item->update([
                'production_order_id' => $activeOrder->id,
                'status' => 'pending', // Reset status to pending in workflow
                'last_checked_at' => null, // Reset checks
                'appointment_date' => null, // Reset appointment date as it is stage-specific
                'appointment_location' => null,
                'appointment_completed_at' => null,
                'operator_id' => null, // Reset operator as requested
            ]);

            // Clear Completed Steps (Preparation steps do not map to Workflow steps 1:1)
            $item->completedWorkTypeSteps()->detach();

            DB::commit();

            // Calculate Stats for the OLD order (Preparation) to update UI
            $orderStats = $this->calculateOrderStats($currentOrder);

            return response()->json(['success' => true, 'order_stats' => $orderStats]);

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

    // --- Financial Group Methods (Updated for Shared Logic) ---

    public function storeFinancialGroup(Request $request, $id)
    {
        $production = ProductionOrder::findOrFail($id);
        $request->validate(['name' => 'required|string']);

        // Create Shared Group
        $group = ProductionFinancialGroup::create([
            'production_order_id' => $production->id, // Primary link
            'employer_id' => $production->employer_id, // Shared link
            'work_type_id' => $production->work_type_id, // Shared link
            'name' => $request->name,
            'financial_data' => $production->financial_data ?? []
        ]);

        return response()->json(['success' => true, 'group' => $group]);
    }

    public function updateFinancialGroup(Request $request, $id, $groupId)
    {
        // Allow updating via Shared Context
        // We find the group by ID, but ensure it belongs to the same context (Employer/WorkType) OR the specific order.
        // Simple finding by ID is safe if we assume ID is unique globally.
        $group = ProductionFinancialGroup::findOrFail($groupId);

        // Security check: Ensure the group belongs to the order OR shares context
        $order = ProductionOrder::findOrFail($id);
        if ($group->production_order_id !== $order->id &&
           ($group->employer_id !== $order->employer_id || $group->work_type_id !== $order->work_type_id)) {
            abort(403, 'Unauthorized access to financial group.');
        }

        $jsonPayload = $request->json()->all();

        // 1. Handle Rename
        if ($request->has('name') && !isset($jsonPayload['pricing_tiers'])) {
             $group->update(['name' => $request->name]);
             return response()->json(['success' => true, 'group' => $group]);
        }

        // 2. Handle Full Settings Save
        if (isset($jsonPayload['pricing_tiers']) || isset($jsonPayload['fixed_base_amount'])) {
             $createdItemIds = [];

             // Handle Price Tier Item Creation
             if (isset($jsonPayload['pricing_tiers']) && is_array($jsonPayload['pricing_tiers'])) {
                 foreach ($jsonPayload['pricing_tiers'] as &$tier) {
                     if (isset($tier['item_ids']) && is_array($tier['item_ids'])) {
                         $newItemIds = [];
                         foreach ($tier['item_ids'] as $itemId) {
                             if (is_string($itemId) && str_starts_with($itemId, 'emp_')) {
                                 $empId = str_replace('emp_', '', $itemId);
                                 // Create Item if not exists
                                 // Note: This creates item in the CURRENT order ($id)
                                 $item = ProductionItem::firstOrCreate(
                                     [
                                         'production_order_id' => $id,
                                         'employee_id' => $empId
                                     ],
                                     [
                                         'status' => 'pending',
                                         'group_name' => null
                                     ]
                                 );
                                 $newItemIds[] = $item->id;
                                 $createdItemIds[] = $item->id;
                             } else {
                                 $newItemIds[] = $itemId;
                             }
                         }
                         $tier['item_ids'] = $newItemIds;
                         $tier['count'] = count($newItemIds);
                     }
                 }
             }

             $group->financial_data = $jsonPayload;
             $group->save();

             // Sync Advance Items
             if (isset($jsonPayload['advance_items']) && is_array($jsonPayload['advance_items'])) {
                 $group->advanceItems()->delete();
                 foreach ($jsonPayload['advance_items'] as $advItem) {
                     $group->advanceItems()->create([
                         'description' => $advItem['description'] ?? '',
                         'quantity' => $advItem['quantity'] ?? 1,
                         'unit_price' => $advItem['unit_price'] ?? 0,
                         'total' => ($advItem['quantity'] ?? 1) * ($advItem['unit_price'] ?? 0),
                     ]);
                 }
             }

             // Fetch newly created items
             $newItems = [];
             if (!empty($createdItemIds)) {
                 $rawItems = ProductionItem::with('employee')
                     ->whereIn('id', $createdItemIds)
                     ->get();

                 $newItems = $rawItems->map(function($item) {
                     $emp = $item->employee;
                     return [
                         'id' => $item->id,
                         'name' => $emp ? ($emp->employeeNameTh ?? $emp->employeeNameEn ?? 'New Employee') : 'New Item',
                         'name_en' => $emp->employeeNameEn ?? '',
                         'title_en' => $emp->employeeTitleEn ?? '',
                         'photo' => $emp->photo_url ?? '',
                         'nationality' => $emp->employeeNationality ?? '',
                         'employee_id' => $item->employee_id,
                     ];
                 });
             }

             return response()->json([
                 'success' => true,
                 'group' => $group->fresh(['advanceItems']),
                 'new_items' => $newItems
             ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid payload'], 400);
    }

    public function destroyFinancialGroup(Request $request, $id, $groupId)
    {
        // Use findOrFail directly on model, then check permission
        $group = ProductionFinancialGroup::findOrFail($groupId);
        $order = ProductionOrder::findOrFail($id);

         if ($group->production_order_id !== $order->id &&
           ($group->employer_id !== $order->employer_id || $group->work_type_id !== $order->work_type_id)) {
            abort(403);
        }

        $group->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Show the form for editing the specified Production Order (or specific tabs).
     */
    public function edit(Request $request, $id)
    {
        // 1. Find Order (without Financial Groups strictly linked to it)
        $production = ProductionOrder::with([
            'employer',
            'items.employee'
        ])->findOrFail($id);

        // 2. Fetch Shared Financial Groups
        // Look for groups belonging to this Employer + WorkType
        // IMPORTANT: Manual bills have work_type_id = null. We do NOT want to share groups
        // across all manual bills for the same employer. If null, fetch ONLY its own groups.
        if ($production->work_type_id === null) {
             $sharedGroups = $production->financialGroups()->with(['transactions.items', 'advanceItems'])->get();
        } else {
             $sharedGroups = ProductionFinancialGroup::where('employer_id', $production->employer_id)
                ->where('work_type_id', $production->work_type_id)
                ->with(['transactions.items', 'advanceItems'])
                ->get();

             // If no shared groups exist, but the order has old groups (migration fallback), fetch them
             if ($sharedGroups->isEmpty()) {
                 $sharedGroups = $production->financialGroups()->with(['transactions.items', 'advanceItems'])->get();
             }
        }

        // Attach shared groups to the production object for the view
        $production->setRelation('financialGroups', $sharedGroups);

        // 3. Prepare Data for Partial
        $employeeCount = $production->items->count();
        // Get employees collection for dropdowns/management (Only Current Stage)
        $employees = $production->items->map(function($item) {
            return $item->employee;
        })->filter()->values();

        $employeeFinancialStatus = \App\Services\FinancialStatusService::calculateStatusForEmployees($production, $employees->pluck('id'));

        foreach ($employees as $emp) {
            $emp->financialStatus = $employeeFinancialStatus[$emp->id] ?? null;
        }

        // 4. Return View
        return view('production.edit', compact('production', 'employeeCount', 'employees'));
    }

    public function create(Request $request)
    {
        $employeeIdsJson = $request->query('employee_ids_json');
        $preSelectedEmployees = collect();
        $employerId = $request->query('employer_id');
        $isIndependent = false;

        if ($employeeIdsJson) {
            $employeeIds = json_decode($employeeIdsJson, true);
            if (is_array($employeeIds)) {
                $preSelectedEmployees = \App\Models\Employee::whereIn('id', $employeeIds)->get();
                $employerIds = $preSelectedEmployees->pluck('employer_id')->unique();
                if ($employerIds->count() > 1 || ($employerIds->count() == 1 && $employerIds->first() == null)) {
                    $isIndependent = true;
                }
            }
        }

        $workTypes = WorkType::orderBy('order')->get();
        return view('production.create', compact('workTypes', 'preSelectedEmployees', 'employerId', 'isIndependent'));
    }

    public function store(Request $request)
    {
         return parent::callAction('store', [$request]);
    }

    /**
     * Helper to calculate stats for a single order (Production/Preparation Context).
     */
    private function calculateOrderStats(ProductionOrder $order)
    {
        $order->load(['items.completedWorkTypeSteps']);

        $steps = WorkTypeStep::where('work_type_id', $order->work_type_id)
                    ->where('stage', 'preparation')
                    ->orderBy('order')
                    ->get();

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

        return [
            'total' => $total,
            'not_started' => $notStarted,
            'cancelled' => $cancelled,
            'completed' => $completed,
            'step_stats' => $stepStats
        ];
    }

    /**
     * Fetch Employees for a specific Order (Lazy Load)
     */
    public function fetchEmployees(Request $request, $orderId)
    {
        $order = ProductionOrder::with('workType')->findOrFail($orderId);
        $activeTab = $order->workType;

        $query = ProductionItem::with(['employee', 'completedWorkTypeSteps'])
            ->where('production_order_id', $orderId);

        // Status/Step Filter
        if ($request->has('filter') && $request->filter) {
            $filter = $request->filter;
            if ($filter === 'not_started') {
                $query->where('status', 'pending')->doesntHave('completedWorkTypeSteps');
            } elseif ($filter === 'cancelled') {
                $query->where('status', 'cancelled');
            } elseif ($filter === 'completed') {
                $query->where('status', 'completed');
            } elseif ($filter === 'pending_daily_check') {
                $query->where(function($sub) {
                    $sub->whereNull('last_checked_at')
                        ->orWhereDate('last_checked_at', '<', now()->today());
                })->whereNotIn('status', ['cancelled', 'completed']);
            } elseif (is_numeric($filter)) {
                $query->whereHas('completedWorkTypeSteps', function($s) use ($filter) {
                    $s->where('work_type_steps.id', $filter);
                });
            }
        }

        // Search Filter
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            $cleanedSearch = str_replace(' ', '', $search);

            $query->where(function($q) use ($search, $cleanedSearch) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhereHas('employee', function($emp) use ($search, $cleanedSearch) {
                      $emp->where('employeeNameTh', 'like', "%{$search}%")
                          ->orWhere('employeeNameEn', 'like', "%{$search}%")
                          ->orWhere('employeePassport', 'like', "%{$search}%")
                          ->orWhere('employeeWorkPermit', 'like', "%{$search}%")
                          ->orWhere('employee_id_number', 'like', "%{$search}%")
                          ->orWhere('name_list_number', 'like', "%{$search}%")
                          ->orWhere('pinkCardNo', 'like', "%{$search}%")
                          ->orWhere('employer_employee_id', 'like', "%{$search}%")
                          ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                          ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
                  });
            });
        }

        // Operator Filter
        if ($request->has('operator_filter') && $request->operator_filter) {
            $query->where('operator_id', $request->operator_filter);
        }

        $items = $query->get();

        // Setup financial status locally
        $empIds = $items->pluck('employee_id')->filter()->unique();

        // Determine shared groups
        $sharedGroups = $order->financialGroups;
        if ($order->work_type_id !== null) {
            $sharedGroups = \App\Models\ProductionFinancialGroup::where('employer_id', $order->employer_id)
                ->where('work_type_id', $order->work_type_id)
                ->with(['transactions.items', 'advanceItems'])
                ->get();
            if ($sharedGroups->isEmpty()) {
                $sharedGroups = $order->financialGroups;
            }
        }
        $order->setRelation('financialGroups', $sharedGroups);

        $employeeFinancialStatus = \App\Services\FinancialStatusService::calculateStatusForEmployees($order, $empIds);

        foreach ($items as $item) {
            if ($item->employee) {
                $item->employee->financialStatus = $employeeFinancialStatus[$item->employee->id] ?? null;
            }
        }

        $steps = WorkTypeStep::where('work_type_id', $order->work_type_id)
            ->where('stage', 'preparation')
            ->orderBy('order')
            ->get();

        $users = User::orderBy('name')->get(['id', 'name']);

        // Since items are `ProductionItem` models but the expected blade uses `$employees`, we map them.
        // We also pass the order's employer since the `_employee_list_content` expects `$employer`.
        $employees = $items->map(function ($item) {
            $employee = $item->employee;
            if ($employee) {
                $employee->production_item = $item;
            }
            return $employee;
        })->filter();

        // If the view for general production/prepare employees list exists, use it.
        // Otherwise, fallback to the generic registration employee list content view.
        if (view()->exists('production._employee_list_content')) {
            return view('production._employee_list_content', [
                'employees' => $employees,
                'employer' => $order->employer,
                'steps' => $steps,
                'order' => $order,
                'users' => $users,
                'activeTab' => $activeTab
            ]);
        }

        // This relies on the _employee_list_content that expects an employer and steps
        return view('production.registration._employee_list_content', [
            'employees' => $employees,
            'employer' => $order->employer,
            'steps' => $steps,
            'order' => $order,
            'users' => $users,
            'activeTab' => $activeTab
        ]);
    }

    /**
     * Calculate Stats per Order via AJAX
     */
    public function updateRemarks(Request $request, ProductionOrder $order)
    {
        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $validated = $request->validate([
            'remarks' => 'nullable|string',
        ]);

        $order->update(['remarks' => $validated['remarks']]);

        return response()->json(['success' => true]);
    }

    public function batchStats(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:production_orders,id',
            'search' => 'nullable|string',
            'filter' => 'nullable|string',
            'operator_filter' => 'nullable|integer'
        ]);

        $orderIds = $request->input('order_ids');
        $search = $request->input('search');
        $filter = $request->input('filter');
        $operatorFilter = $request->input('operator_filter');

        $orders = ProductionOrder::whereIn('id', $orderIds)->get();
        $results = [];

        foreach ($orders as $order) {
            $query = ProductionItem::with(['completedWorkTypeSteps' => function($q) {
                $q->orderByDesc('order');
            }])->where('production_order_id', $order->id);

            // Apply Search Filter Logic for items
            if ($search) {
                $cleanedSearch = str_replace(' ', '', $search);
                $query->where(function($q) use ($search, $cleanedSearch) {
                    $q->where('request_number', 'like', "%{$search}%")
                      ->orWhereHas('employee', function($emp) use ($search, $cleanedSearch) {
                          $emp->where('employeeNameTh', 'like', "%{$search}%")
                              ->orWhere('employeeNameEn', 'like', "%{$search}%")
                              ->orWhere('employeePassport', 'like', "%{$search}%")
                              ->orWhere('employeeWorkPermit', 'like', "%{$search}%")
                              ->orWhere('employee_id_number', 'like', "%{$search}%")
                              ->orWhere('name_list_number', 'like', "%{$search}%")
                              ->orWhere('pinkCardNo', 'like', "%{$search}%")
                              ->orWhere('employer_employee_id', 'like', "%{$search}%")
                              ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                              ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
                      });
                });
            }

            if ($operatorFilter) {
                $query->where('operator_id', $operatorFilter);
            }

            $items = $query->get();

            $total = 0;
            $notStarted = 0;
            $cancelled = 0;
            $completed = 0;
            $stepStats = [];

            // Initialize step stats based on work type steps
            $steps = WorkTypeStep::where('work_type_id', $order->work_type_id)
                ->where('stage', 'preparation')
                ->get();

            foreach ($steps as $step) {
                $stepStats[$step->id] = 0;
            }

            foreach ($items as $item) {
                if ($item->status === 'cancelled') {
                    $cancelled++;
                    continue; // Cancelled items don't count towards active/completed/step stats usually, but they are in the items list
                }

                $total++;

                if ($item->status === 'completed') {
                    $completed++;
                }

                $completedSteps = $item->completedWorkTypeSteps;
                if ($completedSteps->isEmpty()) {
                    $notStarted++;
                }

                $highestStep = $completedSteps->first(); // Since we ordered by desc above
                if ($highestStep && isset($stepStats[$highestStep->id])) {
                    $stepStats[$highestStep->id]++;
                }
            }

            $results[$order->id] = [
                'activeCount' => $total,
                'notStartedCount' => $notStarted,
                'cancelledCount' => $cancelled,
                'completedCount' => $completed,
                'stepStats' => $stepStats
            ];
        }

        return response()->json($results);
    }
}
