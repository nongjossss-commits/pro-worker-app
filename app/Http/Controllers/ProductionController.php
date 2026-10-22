<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\ProductionFinancialGroup;
use App\Models\ProductionCustomField;
use App\Models\Employer;
use App\Models\Employee;
use App\Models\WorkType;
use App\Models\WorkTypeStep;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Traits\AddressFilterTrait;

class ProductionController extends Controller
{
    use AddressFilterTrait;

    public function __construct()
    {
        // Matches WorkflowController's role gate — employer/labor-* accounts
        // have no business in this menu; caretaker keeps access for their
        // routine step-tick/employee work.
        $this->middleware('role:admin|super-admin|staff|caretaker');

        // Preparation-stage step CONFIGURATION (add/rename/delete/reorder
        // steps) — distinct from ticking a step, which stays under
        // 'update-progress-steps'. Matches how step-config is gated in
        // WorkflowController.
        $this->middleware('permission:manage-own-workflow', ['only' => [
            'storeStep', 'updateStep', 'destroyStep', 'reorderSteps',
        ]]);

        // Financial/billing data — matches the rest of the Finance module's
        // 'manage-finance' permission (admin+staff only, caretaker excluded).
        $this->middleware('permission:manage-finance', ['only' => [
            'storeFinancialGroup', 'updateFinancialGroup', 'destroyFinancialGroup',
            'storeDraftEmployee',
        ]]);
    }

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
             // Use global step logic: wait, without active tab, we don't have a specific stepOneId.
             // Actually, 'pre_production' is not started globally if they don't have their respective step 1.
             // But usually global stats are for total.
             $stats['not_started'] = (clone $baseItemsQuery)->whereIn('status', ['pending', 'completed'])
                ->whereDoesntHave('completedWorkTypeSteps', function($q) {
                    $q->where('order', 1);
                })->count();
             $stats['cancelled'] = (clone $baseItemsQuery)->where('status', 'cancelled')->count();
             $stats['completed'] = (clone $baseItemsQuery)->where('status', 'completed')->count();
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

            // Two-stage filter — box 1 locks one exact employer; box 2's
            // free-text search then only needs to match everything EXCEPT
            // the employer's own name (already locked to one exact row).
            $employerAlreadyFiltered = $request->filled('employer_filter_id');
            if ($employerAlreadyFiltered) {
                $query->where('employer_id', $request->input('employer_filter_id'));
            }
            // Exact picks from box 1 / box 2 suggestions (TwoStageSearch).
            if ($request->filled('employee_pick_id')) {
                $pickedEmployee = (int) $request->input('employee_pick_id');
                $query->whereHas('items', fn ($i) => $i->where('employee_id', $pickedEmployee));
            }
            if ($request->filled('job_owner_pick_id')) {
                $pickedOwner = (int) $request->input('job_owner_pick_id');
                $query->whereHas('employer', fn ($e) => $e->where('job_owner_id', $pickedOwner));
            }

            // Search — box 1's typed text (when it isn't a pick) and box 2's text;
            // every term must match (TwoStageSearch::terms).
            foreach (\App\Services\TwoStageSearch::terms($request) as $search) {

                // Support ID:123 format for direct employee ID lookup
                if (preg_match('/^ID:\s*(\d+)$/i', $search, $matches)) {
                    $targetId = (int) $matches[1];
                    $query->where(function($q) use ($targetId) {
                        $q->whereHas('items', function($itemQuery) use ($targetId) {
                            $itemQuery->whereHas('employee', function($emp) use ($targetId) {
                                $emp->where('id', $targetId);
                            });
                        })
                        ->orWhereHas('employer', function($e) use ($targetId) {
                            $e->where('id', $targetId);
                        });
                    });
                } else {
                    $cleanedSearch = str_replace(' ', '', $search);
                    $query->where(function($q) use ($search, $cleanedSearch, $employerAlreadyFiltered) {
                        $q->where('project_name', 'like', "%{$search}%")
                          ->orWhereRaw("REPLACE(project_name, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
                        if (!$employerAlreadyFiltered) {
                            $q->orWhereHas('employer', function($e) use ($search, $cleanedSearch) {
                                $e->where('employerNameTh', 'like', "%{$search}%")
                                  ->orWhere('employerNameEn', 'like', "%{$search}%")
                                  ->orWhere('name_suffix', 'like', "%{$search}%")
                                  ->orWhere('employerId', 'like', "%{$search}%")
                                  ->orWhere('employerTaxId', 'like', "%{$search}%")
                                  ->orWhere('outsource_re_code', 'like', "%{$search}%")
                                  ->orWhereRaw("REPLACE(employerNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                                  ->orWhereRaw("REPLACE(employerNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                                  ->orWhere(function($addrQ) use ($search) {
                                      $addrQ->filterByAddress($search);
                                  });
                            })->orWhereHas('employer.jobOwner', function($owner) use ($search) {
                                $owner->where('name', 'like', "%{$search}%");
                            });
                        }
                        $q->orWhereHas('items', function($itemQuery) use ($search, $cleanedSearch) {
                              $itemQuery->where(function($q) use ($search, $cleanedSearch) {
                                  $q->where('request_number', 'like', "%{$search}%")
                                    ->orWhereHas('employee', function($emp) use ($search, $cleanedSearch) {
                                        $emp->where('employeeNameTh', 'like', "%{$search}%")
                                            ->orWhere('employeeNameEn', 'like', "%{$search}%")
                                            ->orWhere('name_suffix', 'like', "%{$search}%")
                                            ->orWhere('employeePassport', 'like', "%{$search}%")
                                            ->orWhere('employeeWorkPermit', 'like', "%{$search}%")
                                            ->orWhere('employee_id_number', 'like', "%{$search}%")
                                            ->orWhere('name_list_number', 'like', "%{$search}%")
                                            ->orWhere('pinkCardNo', 'like', "%{$search}%")
                                            ->orWhere('request_number', 'like', "%{$search}%")
                                            ->orWhere('employer_employee_id', 'like', "%{$search}%")
                                            ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                                            ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
                                    });
                              });
                          })
                          ->orWhereHas('creator', function($creator) use ($search) {
                              $creator->where('name', 'like', "%{$search}%");
                          })
                          ->orWhereHas('updater', function($updater) use ($search) {
                              $updater->where('name', 'like', "%{$search}%");
                          });
                    });
                }
            }

            // Filter (Status/Step)
            if ($request->has('filter') && $request->filter) {
                $filter = $request->filter;
                $query->whereHas('items', function($q) use ($filter) {
                    if ($filter === 'not_started') {
                        $q->whereIn('status', ['pending', 'completed'])
                          ->whereDoesntHave('completedWorkTypeSteps', function($subQ) {
                              $subQ->where('order', 1);
                          });
                    } elseif ($filter === 'cancelled') {
                        $q->where('status', 'cancelled');
                    } elseif ($filter === 'completed') {
                        $q->where('status', 'completed');
                    } elseif (is_numeric($filter)) {
                        // Match the same semantics as the step pill: the
                        // item's HIGHEST completed step must equal $filter.
                        // A plain whereHas would also match items that
                        // passed this step and moved on to a later one,
                        // which is why empty employer cards were leaking
                        // into the result before.
                        $q->whereNotIn('status', ['cancelled', 'completed'])
                          ->whereRaw("(
                              SELECT work_type_step_id
                              FROM production_item_step
                              JOIN work_type_steps ON production_item_step.work_type_step_id = work_type_steps.id
                              WHERE production_item_step.production_item_id = production_items.id
                              ORDER BY work_type_steps.`order` DESC
                              LIMIT 1
                          ) = ?", [$filter]);
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

            // Sort priority:
            //   1. Orders with zero active employees → bottom (empty cards go last)
            //   2. Cancelled orders → bottom
            //   3. Most recently touched orders → top. ProductionItem::$touches
            //      bumps the parent order's updated_at on every item change, and
            //      EmployeeObserver bumps related items when the employee record
            //      is edited, so any activity surfaces the employer card on the
            //      next page load.
            $orders = $query->orderByRaw("CASE WHEN active_items_count = 0 THEN 1 ELSE 0 END ASC")
                            ->orderByRaw("CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END ASC")
                            ->latest('updated_at')
                            ->paginate($perPage)
                            ->withQueryString();

            // Load Relations - Do not eager load ALL items to save memory, they will be lazy loaded!
            $orders->load([
                'employer.addresses',
                'customFields',
            ]);

            foreach ($orders as $order) {
                // Determine shared groups for proper financial status calculation if work_type_id is set
                $sharedGroups = $order->financialGroups;
                if ($order->work_type_id !== null) {
                    $sharedGroups = \App\Models\ProductionFinancialGroup::where('employer_id', $order->employer_id)
                        ->where('work_type_id', $order->work_type_id)
                        ->with(['transactions.items', 'transactions.payments', 'advanceItems'])
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

            if ($employerAlreadyFiltered) {
                $statsQuery->where('employer_id', $request->input('employer_filter_id'));
            }
            if ($request->filled('employee_pick_id')) {
                $statsQuery->whereHas('items', fn ($i) => $i->where('employee_id', (int) $request->input('employee_pick_id')));
            }
            if ($request->filled('job_owner_pick_id')) {
                $statsQuery->whereHas('employer', fn ($e) => $e->where('job_owner_id', (int) $request->input('job_owner_pick_id')));
            }

            foreach (\App\Services\TwoStageSearch::terms($request) as $search) {
                $cleanedSearch = str_replace(' ', '', $search);
                $statsQuery->where(function($q) use ($search, $cleanedSearch, $employerAlreadyFiltered) {
                    $q->where('project_name', 'like', "%{$search}%")
                      ->orWhereRaw("REPLACE(project_name, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
                    if (!$employerAlreadyFiltered) {
                        $q->orWhereHas('employer', function($e) use ($search, $cleanedSearch) {
                            $e->where('employerNameTh', 'like', "%{$search}%")
                              ->orWhere('employerNameEn', 'like', "%{$search}%")
                              ->orWhere('name_suffix', 'like', "%{$search}%")
                              ->orWhere('employerId', 'like', "%{$search}%")
                              ->orWhere('employerTaxId', 'like', "%{$search}%")
                              ->orWhere('outsource_re_code', 'like', "%{$search}%")
                              ->orWhereRaw("REPLACE(employerNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                              ->orWhereRaw("REPLACE(employerNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                              ->orWhere(function($addrQ) use ($search) { $addrQ->filterByAddress($search); });
                        })->orWhereHas('employer.jobOwner', function($owner) use ($search) {
                            $owner->where('name', 'like', "%{$search}%");
                        });
                    }
                    $q->orWhereHas('items', function($itemQuery) use ($search, $cleanedSearch) {
                          $itemQuery->where(function($q) use ($search, $cleanedSearch) {
                              $q->where('request_number', 'like', "%{$search}%")
                                ->orWhereHas('employee', function($emp) use ($search, $cleanedSearch) {
                                    $emp->where('employeeNameTh', 'like', "%{$search}%")
                                        ->orWhere('employeeNameEn', 'like', "%{$search}%")
                                        ->orWhere('name_suffix', 'like', "%{$search}%")
                                        ->orWhere('employeePassport', 'like', "%{$search}%")
                                        ->orWhere('employeeWorkPermit', 'like', "%{$search}%")
                                        ->orWhere('employee_id_number', 'like', "%{$search}%")
                                        ->orWhere('name_list_number', 'like', "%{$search}%")
                                        ->orWhere('pinkCardNo', 'like', "%{$search}%")
                                        ->orWhere('request_number', 'like', "%{$search}%")
                                        ->orWhere('employer_employee_id', 'like', "%{$search}%")
                                        ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                                        ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
                                });
                          });
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
                // $matchingOrderIds (above) already reflects search —
                // matched at whichever level applies (project/employer/
                // job-owner/item), so every item within those orders
                // belongs in the count. Re-applying the search as an
                // item-level condition here used to require the ITEM
                // itself to also match (request_number/employee fields) —
                // an order correctly matched by employer name (e.g.
                // searching an office/branch) has no items whose own
                // fields match that text, so this silently zeroed out
                // every stat below despite the order list showing correct
                // results. Operator filter is different: it's a genuine
                // per-item drill-down (narrow to items an operator
                // actually handles), not a re-check of criteria already
                // used to pick the orders, so it stays.
                $baseItemQuery = ProductionItem::whereIn('production_order_id', $matchingOrderIds);

                // Apply operator filter to items
                if ($request->has('operator_filter') && $request->operator_filter) {
                    $baseItemQuery->where('operator_id', $request->operator_filter);
                }

                // total_employees must stay the TRUE total (reflecting only
                // search/operator, same as every other stat here) — it must
                // NOT also apply the active state/step filter, or it just
                // ends up equal to whichever pill is selected instead of the
                // real total. See $baseItemQuery above, already scoped to
                // search+operator only.
                $stats['total_employees'] = (clone $baseItemQuery)->count();
                $stats['cancelled'] = (clone $baseItemQuery)->where('status', 'cancelled')->count();
                $stats['completed'] = (clone $baseItemQuery)->where('status', 'completed')->count();
                $stats['not_started'] = (clone $baseItemQuery)->whereIn('status', ['pending', 'completed'])
                    ->whereDoesntHave('completedWorkTypeSteps', function($q) {
                        $q->where('order', 1);
                    })->count();

                // Step Stats (Highest Step) — Global. 24h grace rule:
                // cancelled always drops, completed items keep counting for
                // 24h after Finish so the top-of-page badges match the
                // visible list (item cards stay for 24h before archival).
                $itemsWithSteps = (clone $baseItemQuery)
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($q) {
                        $q->where('status', '!=', 'completed')
                          ->orWhereNull('completed_at')
                          ->orWhere('completed_at', '>=', now()->subHours(24));
                    })
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

            // Team names (group_name) per order (for the team-filter
            // "flaps" hanging off the bottom of each order's card) — one
            // batched query for the whole page instead of one per order.
            // (finished employees keep their team — not offered for rename here)
            $orderTeamNamesMap = ProductionItem::whereIn('production_order_id', $orders->pluck('id'))
                ->where(fn ($q) => $q->where('status', '!=', 'completed')->orWhere('completed_at', '>=', now()->subHours(24)))
                ->whereNotNull('group_name')
                ->where('group_name', '!=', '')
                ->select('production_order_id', 'group_name')
                ->distinct()
                ->orderBy('group_name')
                ->get()
                ->groupBy('production_order_id')
                ->map(fn($rows) => $rows->pluck('group_name')->all());

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
                $order->teamNames = $orderTeamNamesMap->get($order->id, []);
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
    public function updateOutsourceLogin(Request $request, Employee $employee)
    {
        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $request->validate([
            'email' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'outsource_code' => 'nullable|string|max:255',
        ]);

        $employee->update([
            'email' => $request->email,
            'password' => $request->password,
            'outsource_code' => $request->outsource_code,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login info updated successfully.'
        ]);
    }

    public function updateItemFields(Request $request, ProductionItem $item)
    {
        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $request->validate([
            'request_number' => 'nullable|string|max:255',
        ]);

        $item->update([
            'request_number' => $request->request_number,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * ส่ง ProductionOrder ทั้งใบไป Workflow (เปลี่ยน status: pre_production → active)
     * ใช้กับ MOU demand card ที่ส่งทั้งใบเป็น case-based
     */
    public function sendOrderToWorkflow(Request $request, ProductionOrder $order)
    {
        if ($order->status !== 'pre_production') {
            return response()->json(['success' => false, 'message' => 'Order is not in Pre-Production stage.'], 400);
        }

        try {
            \DB::transaction(function () use ($order) {
                $order->status = 'active';
                $order->save();

                // ปรับ items: ที่ยังเป็น pending ให้คงเดิม (พร้อมให้ workflow ดำเนินการ)
                // ที่ completed/cancelled คงไว้
            });

            return response()->json(['success' => true, 'message' => 'Order sent to Workflow successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * เพิ่ม custom field ให้ ProductionOrder (ใช้กับปุ่ม Fields บนการ์ด MOU)
     * รูปแบบเหมือน storeEmployerCustomField ในเมนูมติลงทะเบียน
     */
    public function storeOrderCustomField(Request $request, ProductionOrder $order)
    {
        $validated = $request->validate([
            'field_name' => 'required|string|max:255',
            'field_type' => 'required|in:text,date,file',
            'field_value' => 'nullable|string',
            'field_file' => 'nullable|file|max:102400', // 100MB
        ]);

        $data = [
            'field_name' => $validated['field_name'],
            'field_type' => $validated['field_type'],
        ];

        if ($validated['field_type'] === 'file' && $request->hasFile('field_file')) {
            $file = $request->file('field_file');
            $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("order_files/{$order->id}/custom", $filename, 'public');
            $data['file_path'] = $path;
            if ($request->filled('field_value_desc')) {
                $data['field_value'] = $request->field_value_desc;
            }
        } else {
            $data['field_value'] = $validated['field_value'] ?? null;
            if ($validated['field_type'] === 'date' && $request->has('field_date_value')) {
                $data['field_value'] = $request->field_date_value;
            }
        }

        $field = $order->customFields()->create($data);

        if ($request->ajax()) {
            $order->load('customFields');
            return response()->json([
                'success' => true,
                'message' => 'Field added successfully.',
                'order' => [
                    'id' => $order->id,
                    'custom_fields' => $order->customFields,
                ],
                'newField' => $field,
            ]);
        }

        return back()->with('success', 'Field added successfully.');
    }

    /**
     * แก้ไข custom field ของ ProductionOrder
     */
    public function updateOrderCustomField(Request $request, ProductionCustomField $field)
    {
        if ($field->model_type !== ProductionOrder::class) {
            abort(404);
        }

        $rules = ['field_name' => 'required|string|max:255'];

        if ($field->field_type === 'file') {
            $rules['field_file'] = 'nullable|file|max:102400';
        } else {
            $rules['field_value'] = 'nullable|string';
            if ($field->field_type === 'date') {
                $rules['field_date_value'] = 'nullable|date';
            }
        }

        $validated = $request->validate($rules);
        $data = ['field_name' => $validated['field_name']];

        if ($field->field_type === 'file') {
            if ($request->hasFile('field_file')) {
                if ($field->file_path) {
                    Storage::disk('public')->delete($field->file_path);
                }
                $file = $request->file('field_file');
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $orderId = $field->model_id;
                $path = $file->storeAs("order_files/{$orderId}/custom", $filename, 'public');
                $data['file_path'] = $path;
                if ($request->filled('field_value')) {
                    $data['field_value'] = $request->field_value;
                }
            } elseif ($request->filled('field_value')) {
                $data['field_value'] = $request->field_value;
            }
        } else {
            $data['field_value'] = $validated['field_value'] ?? null;
            if ($field->field_type === 'date' && $request->has('field_date_value')) {
                $data['field_value'] = $request->field_date_value;
            }
        }

        $field->update($data);

        return back()->with('success', 'Field updated successfully.');
    }

    /**
     * ลบ custom field ของ ProductionOrder
     */
    public function destroyOrderCustomField(ProductionCustomField $field)
    {
        if ($field->model_type !== ProductionOrder::class) {
            abort(404);
        }

        if ($field->field_type === 'file' && $field->file_path) {
            Storage::disk('public')->delete($field->file_path);
        }
        $field->delete();
        return back()->with('success', 'Field removed.');
    }

    public function bulkSendToWorkflow(Request $request)
    {
        $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:production_items,id'
        ]);

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($request->item_ids as $itemId) {
            // visibleToUser() scopes this to the caller's own employer(s)
            // for employer/caretaker roles — mirrors the same fix already
            // applied to WorkflowController (see
            // ProductionItem::scopeVisibleToUser()). An out-of-scope id
            // simply comes back null and is skipped below, same as an
            // already-nonexistent id.
            $item = ProductionItem::with(['order', 'employee'])->visibleToUser()->find($itemId);
            if (!$item) continue;

            $currentOrder = $item->order;

            if ($currentOrder->status !== 'pre_production') {
                $failedCount++;
                $errors[] = "Item ID {$itemId} is not in Pre-Production.";
                continue;
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
                 $failedCount++;
                 $errors[] = "Employee {$item->employee->employeeNameTh} is already active in the Workflow.";
                 continue;
            }

            DB::beginTransaction();
            try {
                // Find an Active Order for this Employer + WorkType — but only
                // reuse/merge into one if this tab keeps a single ongoing card
                // per employer. A tab with allow_multiple_orders=true (MOU
                // Import, or any custom tab a Super Admin sets to "multiple
                // cards") must NOT get silently merged into whatever the
                // "latest" active order happens to be — each Pre-Production
                // card becomes its own Active card, same as WorkflowController::store().
                $allowMultipleOrders = (bool) ($currentOrder->workType->allow_multiple_orders ?? false);

                $activeOrder = $allowMultipleOrders ? null : ProductionOrder::where('employer_id', $currentOrder->employer_id)
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

                // Move Item: Update the production_order_id to the new active order
                $item->update([
                    'production_order_id' => $activeOrder->id,
                    'status' => 'pending', // Reset status to pending in workflow
                    'appointment_date' => null, // Reset appointment date as it is stage-specific
                    'appointment_location' => null,
                    'appointment_completed_at' => null,
                    'operator_id' => null, // Reset operator as requested
                ]);

                // Clear Completed Steps (Preparation steps do not map to Workflow steps 1:1)
                $item->completedWorkTypeSteps()->detach();

                DB::commit();
                $successCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                $failedCount++;
                $errors[] = "Error processing employee {$item->employee->employeeNameTh}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully sent $successCount items to Workflow. Failed: $failedCount.",
            'errors' => $errors
        ]);
    }

    public function sendToWorkflow(Request $request, $itemId)
    {
        // See bulkSendToWorkflow()'s comment above — same scoping.
        $item = ProductionItem::with(['order', 'employee'])->visibleToUser()->findOrFail($itemId);
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
            // Find an Active Order for this Employer + WorkType — see the same
            // allow_multiple_orders guard in bulkSendToWorkflow() above.
            $allowMultipleOrders = (bool) ($currentOrder->workType->allow_multiple_orders ?? false);

            $activeOrder = $allowMultipleOrders ? null : ProductionOrder::where('employer_id', $currentOrder->employer_id)
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

        $step = WorkTypeStep::create([
            'work_type_id' => $request->work_type_id,
            'name' => $request->name,
            'order' => $maxOrder + 1,
            'stage' => 'preparation' // Distinct from workflow steps
        ]);

        return response()->json(['success' => true, 'step' => ['id' => $step->id, 'name' => $step->name, 'order' => $step->order]]);
    }

    /**
     * Update a Preparation-stage step's name. Own endpoint (not shared with
     * Workflow's) so a Pre-Production id can never touch a Workflow step.
     */
    public function updateStep(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        WorkTypeStep::where('id', $id)->where('stage', 'preparation')->firstOrFail()->update(['name' => $request->name]);
        return response()->json(['success' => true]);
    }

    /**
     * Delete a Preparation-stage step. Replaces the old approach of piggy-
     * backing on Workflow's /workflow/steps/{id} route.
     */
    public function destroyStep($id)
    {
        WorkTypeStep::where('id', $id)->where('stage', 'preparation')->firstOrFail()->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Reorder Preparation-stage steps.
     */
    public function reorderSteps(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:work_type_steps,id',
        ]);

        foreach ($request->order as $index => $id) {
            WorkTypeStep::where('id', $id)->where('stage', 'preparation')->update(['order' => $index + 1]);
        }

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

    /**
     * Add a "draft" person to this order's employee pool — someone a client
     * has sent identity details for but who isn't a registered Employee
     * yet (e.g. quoting for workers before they're formally onboarded).
     * Creates a real ProductionItem (employee_id = null) so it slots into
     * the existing pricing-tier / document-generation machinery exactly
     * like a real employee would, keyed on new_employee_data instead.
     *
     * Manual bills only — never lets a placeholder person slip into a real
     * Workflow/Pre-Production/Registration order, where every worker must
     * be a genuine Employee for the actual government paperwork.
     */
    public function storeDraftEmployee(Request $request, $id, $groupId)
    {
        $order = ProductionOrder::findOrFail($id);

        if (!is_null($order->work_type_id)) {
            abort(403, 'This is only available for manual bills (Quotations/Invoices from the Finance Hub).');
        }

        $group = ProductionFinancialGroup::findOrFail($groupId);
        if ($group->production_order_id !== $order->id) {
            abort(403, 'Unauthorized access to financial group.');
        }

        $validated = $request->validate([
            'name_en' => 'required_without:name_th|nullable|string|max:255',
            'name_th' => 'required_without:name_en|nullable|string|max:255',
            'title_en' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:100',
            'passport' => 'nullable|string|max:50',
            'id_number' => 'nullable|string|max:50',
            'work_permit' => 'nullable|string|max:50',
            'photo' => 'nullable|image|max:2048',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('production_items/draft_photos', 'public');
        }

        $item = ProductionItem::create([
            'production_order_id' => $order->id,
            'employee_id' => null,
            'status' => 'pending',
            'new_employee_data' => [
                'name_en' => $validated['name_en'] ?? '',
                'name_th' => $validated['name_th'] ?? '',
                'title_en' => $validated['title_en'] ?? '',
                'nationality' => $validated['nationality'] ?? '',
                'passport' => $validated['passport'] ?? '',
                'id_number' => $validated['id_number'] ?? '',
                'work_permit' => $validated['work_permit'] ?? '',
                'photo' => $photoPath,
            ],
        ]);

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'name_en' => $validated['name_en'] ?? '',
                'name_th' => $validated['name_th'] ?? '',
                'title_en' => $validated['title_en'] ?? '',
                'nationality' => $validated['nationality'] ?? '',
                'passport' => $validated['passport'] ?? '',
                'id_number' => $validated['id_number'] ?? '',
                'work_permit' => $validated['work_permit'] ?? '',
                'photo_url' => $photoPath ? Storage::disk('public')->url($photoPath) : null,
            ],
        ]);
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

        // Block deletion while the tab still holds transactions (installments,
        // down payments, full payments). Previously we soft-deleted the tab
        // outright, which left every transaction and payment orphaned: the
        // rows remained in `financial_transactions` but the tab that owned
        // them was gone, so operators had no UI path to edit or clear them.
        $txCount = $group->transactions()->count();
        if ($txCount > 0) {
            $paidCount = $group->transactions()
                ->whereIn('status', ['paid', 'partial'])
                ->count();
            $pendingCount = $txCount - $paidCount;

            return response()->json([
                'success' => false,
                'message' => "ลบแท็บนี้ไม่ได้ — ยังมีรายการเรียกเก็บเงินค้างอยู่ {$txCount} รายการ กรุณาลบรายการทั้งหมดในแท็บนี้ก่อน แล้วจึงลบแท็บได้",
                'transaction_count' => $txCount,
                'paid_count' => $paidCount,
                'pending_count' => $pendingCount,
            ], 422);
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
             $sharedGroups = $production->financialGroups()->with(['transactions.items', 'transactions.payments', 'advanceItems'])->get();
        } else {
             $sharedGroups = ProductionFinancialGroup::where('employer_id', $production->employer_id)
                ->where('work_type_id', $production->work_type_id)
                ->with(['transactions.items', 'transactions.payments', 'advanceItems'])
                ->get();

             // If no shared groups exist, but the order has old groups (migration fallback), fetch them
             if ($sharedGroups->isEmpty()) {
                 $sharedGroups = $production->financialGroups()->with(['transactions.items', 'transactions.payments', 'advanceItems'])->get();
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
        $stepOneId = $steps->sortBy('order')->first()?->id;
        $graceCutoff = now()->subHours(24);

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
            if (in_array($item->status, ['pending', 'completed']) && $stepOneId && !in_array($stepOneId, $completedSteps)) {
                $notStarted++;
            }

            // 24h grace on step badge — completed items keep counting until
            // 24h after Finish so the badge matches the visible card list.
            $completedAt = $item->completed_at;
            $countTowardsSteps = !($item->status === 'completed'
                && $completedAt
                && $completedAt->lt($graceCutoff));

            if ($countTowardsSteps) {
                $highestStep = $item->completedWorkTypeSteps->sortByDesc('order')->first();
                if ($highestStep && isset($stepStats[$highestStep->id])) {
                    $stepStats[$highestStep->id]++;
                }
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
     * Live-search preview for the index() search box — same employer/employee
     * suggestions a user would expect from typing into the main search field,
     * scoped to pre_production orders (and the active tab, if any), limited
     * to a small set so it's cheap to call on every keystroke.
     */
    public function previewSearch(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $activeTabSlug = $request->query('tab');
        $workTypeId = $activeTabSlug ? WorkType::where('slug', $activeTabSlug)->value('id') : null;

        // Box 1: employers / job owners / employees; box 2: employees inside box 1's
        // result (App\Services\TwoStageSearch). Employees = those with a
        // pre-production item in this tab.
        return response()->json(\App\Services\TwoStageSearch::preview($request, $q, function () use ($workTypeId) {
            return Employee::whereHas('productionItems', function ($iq) use ($workTypeId) {
                $iq->whereHas('order', function ($oq) use ($workTypeId) {
                    $oq->where('status', 'pre_production');
                    if ($workTypeId) {
                        $oq->where('work_type_id', $workTypeId);
                    }
                });
            });
        }));
    }

    /**
     * Live-search preview for box 1 (pick one exact employer) — scoped to
     * employers who actually have a pre_production order (and the active
     * tab, if any), so a selection never yields an empty employer list.
     */
    public function previewSearchEmployer(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $activeTabSlug = $request->query('tab');
        $workTypeId = $activeTabSlug ? WorkType::where('slug', $activeTabSlug)->value('id') : null;
        $like = "%{$q}%";

        $employers = Employer::whereHas('productionOrders', function ($oq) use ($workTypeId) {
                $oq->where('status', 'pre_production');
                if ($workTypeId) {
                    $oq->where('work_type_id', $workTypeId);
                }
            })
            ->where(function ($eq) use ($like) {
                $eq->where('employerNameTh', 'like', $like)
                   ->orWhere('employerNameEn', 'like', $like)
                   ->orWhere('name_suffix', 'like', $like);
            })
            ->limit(10)
            ->get(['id', 'employerNameTh', 'employerNameEn']);

        $results = [];
        foreach ($employers as $er) {
            $results[] = [
                'id' => $er->id,
                'label' => $er->employerNameTh ?: $er->employerNameEn ?: '-',
                'sub_label' => ($er->employerNameEn && $er->employerNameEn !== $er->employerNameTh) ? $er->employerNameEn : '',
                'group' => __('นายจ้าง'),
            ];
        }

        return response()->json($results);
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

        // We fetch all items including cancelled ones, and use CSS classes (e.g. .status-cancelled)
        // to hide them on the frontend unless toggled.

        // Status/Step Filter
        if ($request->has('filter') && $request->filter) {
            $filter = $request->filter;
            if ($filter === 'not_started') {
                $query->whereIn('status', ['pending', 'completed'])
                      ->whereDoesntHave('completedWorkTypeSteps', function($q) {
                          $q->where('order', 1);
                      });
            } elseif ($filter === 'cancelled') {
                $query->where('status', 'cancelled');
            } elseif ($filter === 'completed') {
                $query->where('status', 'completed');
            } elseif (is_numeric($filter)) {
                $query->whereHas('completedWorkTypeSteps', function($s) use ($filter) {
                    $s->where('work_type_steps.id', $filter);
                });
            }
        }

        // Two-stage search (box 1 + box 2 — TwoStageSearch): a term matching the
        // order's project / employer / job owner keeps all items, otherwise only
        // the items whose employee matches.
        \App\Services\TwoStageSearch::applyInsideEmployer($query, ProductionOrder::whereKey($orderId)->value('employer_id'), $request, 'employee', function ($q, $term) use ($orderId) {
            $q->where('request_number', 'like', "%{$term}%")
              ->orWhereExists(fn ($o) => $o->selectRaw('1')->from('production_orders')->where('production_orders.id', $orderId)
                  ->where('project_name', 'like', "%{$term}%"));
        });

        // Operator Filter
        if ($request->has('operator_filter') && $request->operator_filter) {
            $query->where('operator_id', $request->operator_filter);
        }

        // Team filter (the "flaps" hanging off the order card) —
        // multi-select OR: show items in ANY of the selected teams.
        // group_name is a plain column on this same table, unlike
        // Registration/Renewal's separate employee_team_assignments table.
        if ($request->has('team_filter') && is_array($request->team_filter) && count($request->team_filter)) {
            $query->whereIn('group_name', $request->team_filter);
        }

        $items = $query->get();

        // Setup financial status locally
        $empIds = $items->pluck('employee_id')->filter()->unique();

        // Determine shared groups
        $sharedGroups = $order->financialGroups;
        if ($order->work_type_id !== null) {
            $sharedGroups = \App\Models\ProductionFinancialGroup::where('employer_id', $order->employer_id)
                ->where('work_type_id', $order->work_type_id)
                ->with(['transactions.items', 'transactions.payments', 'advanceItems'])
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
     * Lightweight JSON list of every employee under this order that is
     * eligible for the "Select All" bulk-selection checkbox — same
     * rationale as WorkflowController::selectAllOrderEmployeeIds(), mirrored
     * here for Pre-Production.
     */
    public function selectAllOrderEmployeeIds(Request $request, $orderId)
    {
        $order = ProductionOrder::findOrFail($orderId);

        $query = ProductionItem::where('production_order_id', $orderId)
            ->where('status', 'pending')
            ->whereHas('employee', function ($q) {
                $q->whereNull('terminated_at');
            });

        if ($request->has('filter') && $request->filter) {
            $filter = $request->filter;
            if ($filter === 'not_started') {
                $query->whereDoesntHave('completedWorkTypeSteps', function ($q) {
                    $q->where('order', 1);
                });
            } elseif (is_numeric($filter)) {
                $query->whereHas('completedWorkTypeSteps', function ($s) use ($filter) {
                    $s->where('work_type_steps.id', $filter);
                });
            }
            // 'cancelled' / 'completed' filters naturally yield nothing here
            // since this query is always scoped to status = 'pending'.
        }

        // Two-stage search (box 1 + box 2 — TwoStageSearch): a term matching the
        // order's project / employer / job owner keeps all items, otherwise only
        // the items whose employee matches.
        \App\Services\TwoStageSearch::applyInsideEmployer($query, ProductionOrder::whereKey($orderId)->value('employer_id'), $request, 'employee', function ($q, $term) use ($orderId) {
            $q->where('request_number', 'like', "%{$term}%")
              ->orWhereExists(fn ($o) => $o->selectRaw('1')->from('production_orders')->where('production_orders.id', $orderId)
                  ->where('project_name', 'like', "%{$term}%"));
        });

        if ($request->has('operator_filter') && $request->operator_filter) {
            $query->where('operator_id', $request->operator_filter);
        }

        $items = $query->with(['employee:id,employer_id,employeeNameTh,employeeNameEn,employeeTitleTh,employeeTitleEn,employeeNationality,employeePhoto,insurance_type,employeePassport'])
            ->get(['id', 'employee_id']);

        $employerName = $order->employer->employerNameTh ?? $order->employer->employerNameEn ?? 'N/A';

        $result = $items->filter(fn($item) => $item->employee)->map(function ($item) use ($employerName) {
            $emp = $item->employee;
            return [
                'id' => $emp->id,
                'employer_id' => $emp->employer_id,
                'name_th' => $emp->employeeNameTh,
                'name_en' => $emp->employeeNameEn,
                'title_th' => $emp->employeeTitleTh,
                'title_en' => $emp->employeeTitleEn,
                'nationality' => $emp->employeeNationality,
                'photo' => $emp->employeePhoto ? \Illuminate\Support\Facades\Storage::disk('public')->url($emp->employeePhoto) : '',
                'employer_name' => $employerName,
                'insurance_type' => $emp->insurance_type,
                'passport' => $emp->employeePassport,
                'production_item_id' => $item->id,
                'locked_completed' => false,
            ];
        })->values();

        return response()->json(['success' => true, 'items' => $result]);
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

            // Same two-stage rule as the order's employee list (fetchEmployees).
            \App\Services\TwoStageSearch::applyInsideEmployer($query, $order->employer_id, $request, 'employee', function ($q, $term) use ($order) {
                $q->where('request_number', 'like', "%{$term}%")
                  ->orWhereExists(fn ($o) => $o->selectRaw('1')->from('production_orders')->where('production_orders.id', $order->id)
                      ->where('project_name', 'like', "%{$term}%"));
            });

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

            $stepOneId = $steps->sortBy('order')->first()?->id;
            foreach ($steps as $step) {
                $stepStats[$step->id] = 0;
            }
            $graceCutoff = now()->subHours(24);

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
                if (in_array($item->status, ['pending', 'completed']) && $stepOneId && !$completedSteps->contains('id', $stepOneId)) {
                    $notStarted++;
                }

                // 24h grace on step badge — completed items keep counting
                // until 24h after Finish (matches the visible card list).
                $completedAt = $item->completed_at;
                $countTowardsSteps = !($item->status === 'completed'
                    && $completedAt
                    && $completedAt->lt($graceCutoff));

                if ($countTowardsSteps) {
                    $highestStep = $completedSteps->first(); // Since we ordered by desc above
                    if ($highestStep && isset($stepStats[$highestStep->id])) {
                        $stepStats[$highestStep->id]++;
                    }
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

    /**
     * โหลด Finance Tab สำหรับ order ที่ระบุ (ใช้กับ AJAX lazy loading)
     * เพื่อลดภาระการโหลดหน้า Production Index
     */
    public function fetchFinanceTab(Request $request, $id)
    {
        $order = ProductionOrder::with([
            'employer',
            'items.employee.registrationSteps',
            'items.completedWorkTypeSteps',
            'financialGroups.transactions.items',
            'financialGroups.advanceItems',
            'workType',
        ])->findOrFail($id);

        $employees = $order->items->pluck('employee')->filter()->values();

        return view('production.partials.financial-tab', [
            'production'    => $order,
            'employeeCount' => $order->items->count(),
            'employees'     => $employees,
        ]);
    }
}
