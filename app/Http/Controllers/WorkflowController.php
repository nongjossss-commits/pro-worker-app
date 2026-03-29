<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\WorkflowStep;
use App\Models\WorkType;
use App\Models\WorkTypeStep;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\SystemSetting;
use App\Models\User;
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
        $query = ProductionOrder::with(['employer.jobOwner', 'workType', 'creator', 'updater'])
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
                      $itemQuery->where(function($q) use ($search, $cleanedSearch) {
                          $q->where('request_number', 'like', "%{$search}%")
                            ->orWhereHas('employee', function($emp) use ($search, $cleanedSearch) {
                                $emp->where('employeeNameTh', 'like', "%{$search}%")
                                    ->orWhere('employeeNameEn', 'like', "%{$search}%")
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
                    $q->where('status', 'pending')
                      ->doesntHave('completedWorkTypeSteps');
                } elseif ($filter === 'cancelled') {
                    $q->where('status', 'cancelled');
                } elseif ($filter === 'completed') {
                    $q->where('status', 'completed');
                } elseif ($filter === 'pending_daily_check') {
                    $q->where(function($sub) {
                        $sub->whereNull('last_checked_at')
                            ->orWhereDate('last_checked_at', '<', Carbon::today());
                    })->whereNotIn('status', ['cancelled', 'completed']);
                } elseif (is_numeric($filter)) {
                    // Highest Step ID match (approx for SQL: has this step)
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

        // SORTING: Active items (not cancelled/completed) first, then updated_at
        $query->withCount([
            'items as active_items_count' => function ($q) {
                $q->whereNotIn('status', ['cancelled', 'completed']);
            },
            'items as cancelled_items_count' => function ($q) {
                $q->where('status', 'cancelled');
            }
        ]);

        $perPage = $request->input('per_page', 20);
        if (!in_array($perPage, [20, 50, 100])) {
            $perPage = 20;
        }

        $orders = $query->orderByRaw("CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END")
                        ->orderByDesc('active_items_count')
                        ->orderBy('cancelled_items_count')
                        ->latest('updated_at')
                        ->paginate($perPage)
                        ->withQueryString();

        // Calculate Stats PER ORDER for the view (Accordion Header)
        // We defer loading items directly and calculate minimal per-order stats instead to save memory
        $orders->load(['employer.addresses']);

        // Employers for Dropdown
        $employers = Employer::orderBy('employerNameTh')->get();

        // Users for Operator Filter
        $users = User::orderBy('name')->get(['id', 'name', 'status']); // Assuming status exists from recent migrations

        $steps = $activeTab ? $activeTab->workflowSteps : collect();
        $stepOneId = $steps->sortBy('order')->first()?->id;

        foreach ($orders as $order) {
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

        // 4. Calculate Scoreboard Stats (For the Active Tab)
        $stats = [
            'total_projects' => $orders->total(),
            'total_employees' => 0,
            'not_started' => 0,
            'cancelled' => 0,
            'completed' => 0,
            'step_stats' => $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray(),
            'pending_daily_check' => 0,
        ];

        if ($activeTab) {
            // Stats should reflect search but NOT the state filter
            $statsQuery = ProductionOrder::where('status', '!=', 'pre_production')
                ->where('work_type_id', $activeTab->id)
                ->whereHas('employer', function ($q) {
                    $q->whereNull('deleted_at');
                });

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
                          $itemQuery->where(function($q) use ($search, $cleanedSearch) {
                              $q->where('request_number', 'like', "%{$search}%")
                                ->orWhereHas('employee', function($emp) use ($search, $cleanedSearch) {
                                    $emp->where('employeeNameTh', 'like', "%{$search}%")
                                        ->orWhere('employeeNameEn', 'like', "%{$search}%")
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
                      ->orWhereHas('employer.jobOwner', function($owner) use ($search) {
                          $owner->where('name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->has('operator_filter') && $request->operator_filter) {
                $opFilter = $request->operator_filter;
                $statsQuery->whereHas('items', fn($q) => $q->where('operator_id', $opFilter));
            }

            $stats['total_projects'] = $statsQuery->count();
            $matchingOrderIds = $statsQuery->pluck('id');

            if ($matchingOrderIds->isNotEmpty()) {
                $baseItemQuery = ProductionItem::whereIn('production_order_id', $matchingOrderIds);

                $stats['total_employees'] = (clone $baseItemQuery)->count();

                // If the order is cancelled, we need to count all its items as cancelled
                $stats['cancelled'] = (clone $baseItemQuery)
                    ->where(function($q) {
                        $q->where('status', 'cancelled')
                          ->orWhereHas('order', fn($o) => $o->where('status', 'cancelled'));
                    })->count();

                $stats['completed'] = (clone $baseItemQuery)->where('status', 'completed')->count();
                $stats['not_started'] = (clone $baseItemQuery)
                    ->whereIn('status', ['pending', 'completed'])
                    ->whereHas('order', fn($o) => $o->where('status', '!=', 'cancelled'))
                    ->whereDoesntHave('completedWorkTypeSteps', function($q) {
                        $q->where('order', 1);
                    })->count();
                $stats['pending_daily_check'] = (clone $baseItemQuery)->whereNotIn('status', ['cancelled', 'completed'])
                    ->where(function($q) {
                        $q->whereNull('last_checked_at')
                          ->orWhereDate('last_checked_at', '<', now()->today());
                    })->count();

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
        }

        return view('workflow.index', compact('orders', 'tabs', 'activeTab', 'stats', 'steps', 'addressOptions', 'employers', 'users'));
    }

    /**
     * Update Remarks for a ProductionItem via AJAX.
     */
    public function updateOrderRemarks(Request $request, ProductionOrder $order)
    {
        if (!auth()->user()->can('manage-own-workflow')) {
            abort(403);
        }

        $validated = $request->validate([
            'remarks' => 'nullable|string',
        ]);

        $order->update(['remarks' => $validated['remarks']]);

        return response()->json(['success' => true]);
    }

    public function updateRemarks(Request $request, $itemId)
    {
        $request->validate(['remarks' => 'nullable|string']);

        $item = ProductionItem::findOrFail($itemId);
        $item->update(['remarks' => $request->remarks]);

        return response()->json(['success' => true]);
    }

    /**
     * Dashboard Landing Page Logic
     */
    private function dashboard($tabs)
    {
        // 1. Get Valid WorkType IDs (to match the displayed tabs)
        $validWorkTypeIds = $tabs->pluck('id');

        // 2. Base Query for Items that should be counted
        // Must belong to a valid WorkType (Tab) AND have a valid Employer (same as index query)
        $itemsQuery = ProductionItem::whereHas('order', function($q) use ($validWorkTypeIds) {
            $q->where('status', '!=', 'pre_production')
              ->whereIn('work_type_id', $validWorkTypeIds)
              ->whereHas('employer');
        });

        // 3. Global Scoreboard Stats
        $stats = [
            'total_projects' => ProductionOrder::where('status', '!=', 'pre_production')
                                    ->whereIn('work_type_id', $validWorkTypeIds)
                                    ->whereHas('employer')
                                    ->count(),
            'total_employees' => (clone $itemsQuery)->count(),
            'not_started' => (clone $itemsQuery)->whereIn('status', ['pending', 'completed'])
                                           ->whereHas('order', fn($q) => $q->where('status', '!=', 'cancelled'))
                                           ->whereDoesntHave('completedWorkTypeSteps', function($q) {
                                               $q->where('order', 1);
                                           })
                                           ->count(),
            'cancelled' => (clone $itemsQuery)
                                ->where(function($q) {
                                    $q->where('status', 'cancelled')
                                      ->orWhereHas('order', fn($o) => $o->where('status', 'cancelled'));
                                })->count(),
            'completed' => (clone $itemsQuery)->where('status', 'completed')->count(),
            'pending_daily_check' => (clone $itemsQuery)
                ->where('status', 'pending')
                ->whereHas('order', fn($q) => $q->where('status', '!=', 'cancelled'))
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
        $isUpdated = false;

        if ($request->has('appointment_date')) {
            $data['appointment_date'] = $request->appointment_date;
            if ($item->appointment_date != $request->appointment_date) {
                $isUpdated = true;
            }
        }
        if ($request->has('appointment_location')) {
            $data['appointment_location'] = $request->appointment_location;
            if ($item->appointment_location != $request->appointment_location) {
                $isUpdated = true;
            }
        }

        if ($isUpdated) {
            $data['appointment_updated_by'] = auth()->id();
            $data['appointment_updated_at'] = now();
        }

        $item->update($data);

        return response()->json([
            'success' => true,
            'appointment_updated_by_name' => auth()->user()->name,
            'appointment_updated_at_human' => now()->diffForHumans()
        ]);
    }

    /**
     * Update Employee Credentials (Email/Password) from Item Card.
     */
    public function updateCredentials(Request $request, $itemId)
    {
        $item = ProductionItem::with('employee')->findOrFail($itemId);
        $employeeId = $item->employee->id ?? null;

        $request->validate([
            'email' => [
                'nullable',
                'string',
                'max:255',
                $employeeId ? \Illuminate\Validation\Rule::unique('employees', 'email')->ignore($employeeId) : 'unique:employees,email'
            ],
            'password' => 'nullable|string|max:255',
            'outsource_code' => 'nullable|string|max:255',
        ]);

        if ($item->employee) {
            try {
                $data = [];
                if ($request->has('email')) $data['email'] = $request->email;
                if ($request->has('password')) $data['password'] = $request->password;
                if ($request->has('outsource_code')) $data['outsource_code'] = $request->outsource_code;

                $item->employee->update($data);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save: ' . $e->getMessage()
                ], 500);
            }
        }

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
        $order = ProductionOrder::with('workType')->findOrFail($orderId);

        if ($order->status === 'pre_production') {
            $steps = $order->workType->preparationSteps;
        } else {
            $steps = $order->workType->workflowSteps;
        }

        // Query Items
        $query = ProductionItem::with(['employee', 'completedWorkTypeSteps'])
            ->where('production_order_id', $orderId);

        // We fetch all items including cancelled ones, and use CSS classes (e.g. .status-cancelled)
        // to hide them on the frontend unless toggled.

        // 1. History Filter (Default: Hide completed > 24h)
        // Unless we are filtering specifically for "Completed", we keep this rule.
        // But if user clicks "Completed" pill, they might expect ALL completed?
        // Usually history is separate. Let's stick to standard rule: Active List shows recent completions only.
        $query->where(function($q) {
            $q->where('status', '!=', 'completed')
              ->orWhere(function($sub) {
                  $sub->where('status', 'completed')
                      ->where('completed_at', '>=', now()->subHours(24));
              });
        });

        // 2. Apply Dashboard Filters (from Request)
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
            } elseif ($filter === 'pending_daily_check') {
                 $query->where(function($q) {
                     $q->whereNull('last_checked_at')
                       ->orWhereDate('last_checked_at', '<', Carbon::today());
                 })->whereNotIn('status', ['cancelled', 'completed']);
            } elseif (is_numeric($filter)) {
                 // We will filter by exact highest step in PHP collection
                 // But apply rough SQL filter first
                 $query->where('status', '!=', 'cancelled');
            }
        }

        // 2.1 Apply Operator Filter
        if ($request->has('operator_filter') && $request->operator_filter) {
            $query->where('operator_id', $request->operator_filter);
        }

        // 3. Apply Search Filter
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            $cleanedSearch = str_replace(' ', '', $search);

            // Check if Order matches the search criteria (Project, Employer, etc.)
            // If it DOES match, we show ALL items (user found the order).
            // If it DOES NOT match, we assume the user found the order via a specific item/employee, so we show ONLY matching items.
            $orderMatches = ProductionOrder::where('id', $orderId)
                ->where(function($q) use ($search, $cleanedSearch) {
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
                      ->orWhereHas('employer.jobOwner', function($owner) use ($search) {
                          $owner->where('name', 'like', "%{$search}%");
                      });
                })
                ->exists();

            if (!$orderMatches) {
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
                               ->orWhere('request_number', 'like', "%{$search}%")
                               ->orWhere('employer_employee_id', 'like', "%{$search}%")
                               ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                               ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
                       });
                 });
            }
        }

        $items = $query->orderBy('group_name')
                       ->orderBy('id')
                       ->get();

        // 3. Precise Step Filtering (PHP)
        if ($request->has('filter') && is_numeric($request->filter)) {
            $stepId = $request->filter;
            $items = $items->filter(function($item) use ($stepId) {
                if ($item->status === 'cancelled') return false;
                $highest = $item->completedWorkTypeSteps->sortByDesc('order')->first();
                return $highest && $highest->id == $stepId;
            });
        }

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

        return view('workflow.partials.order_items', compact('order', 'groupedItems', 'hasHistory', 'steps'));
    }

    /**
     * Fetch Historic Items (Completed > 24h).
     */
    public function fetchOrderHistory(Request $request, $orderId)
    {
        $order = ProductionOrder::with('workType')->findOrFail($orderId);

        if ($order->status === 'pre_production') {
            $steps = $order->workType->preparationSteps;
        } else {
            $steps = $order->workType->workflowSteps;
        }

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
        return view('workflow.partials.order_items', compact('order', 'groupedItems', 'steps'))->with('isHistory', true);
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

        $item = ProductionItem::with('order.workType')->findOrFail($itemId);

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

        // Calculate Global/Tab Stats
        $isPreProduction = $order->status === 'pre_production';
        $tabStats = $this->calculateTabStats($order->workType, $isPreProduction);

        return response()->json([
            'success' => true,
            'order_stats' => $orderStats,
            'tab_stats' => $tabStats
        ]);
    }

    /**
     * Helper to calculate Global Stats for a Tab context.
     */
    private function calculateTabStats($workType, $isPreProduction)
    {
        $query = ProductionOrder::where('work_type_id', $workType->id)
            ->whereHas('employer', function ($q) {
                // Ensure employer is not deleted
                $q->whereNull('deleted_at');
            });

        if ($isPreProduction) {
            $query->where('status', 'pre_production');
            $steps = WorkTypeStep::where('work_type_id', $workType->id)
                        ->where('stage', 'preparation')
                        ->orderBy('order')
                        ->get();
        } else {
            $query->where('status', '!=', 'pre_production');
            $steps = $workType->workflowSteps;
        }

        // Optimize query by only selecting needed fields
        // We need items and their completed steps
        // This could be heavy if many orders, but we rely on eager loading.
        $allOrders = $query->with(['items.completedWorkTypeSteps'])->get();

        $stats = [
            'total_projects' => $allOrders->count(),
            'total_employees' => 0,
            'not_started' => 0,
            'cancelled' => 0,
            'completed' => 0,
            'step_stats' => $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray(),
            'pending_daily_check' => 0,
        ];

        $stepOneId = $steps->sortBy('order')->first()?->id;

        foreach ($allOrders as $order) {
            if ($order->status === 'cancelled') {
                foreach ($order->items as $item) {
                    $stats['total_employees']++;
                    $stats['cancelled']++;
                }
                continue;
            }

            foreach ($order->items as $item) {
                $stats['total_employees']++;

                if ($item->status === 'cancelled') {
                    $stats['cancelled']++;
                    continue;
                }

                if ($item->status === 'completed') {
                    $stats['completed']++;
                }

                // Not Started: Pending + No Step 1
                if (in_array($item->status, ['pending', 'completed']) && $stepOneId && !$item->completedWorkTypeSteps->contains('id', $stepOneId)) {
                    $stats['not_started']++;
                }

                // Daily Check
                if (!$item->is_checked_today && $item->status !== 'completed' && $item->status !== 'cancelled') {
                    $stats['pending_daily_check']++;
                }

                // Step Stats
                $highestStep = $item->completedWorkTypeSteps->sortByDesc('order')->first();
                if ($highestStep && isset($stats['step_stats'][$highestStep->id])) {
                    $stats['step_stats'][$highestStep->id]++;
                }
            }
        }

        return $stats;
    }

    /**
     * Helper to calculate stats for a single order.
     */
    private function calculateOrderStats(ProductionOrder $order)
    {
        // Ensure relations are loaded
        // Use filtered relationship if available, or load it
        $order->load(['items.completedWorkTypeSteps', 'workType.workflowSteps']);
        $items = $order->items;

        // Determine Steps based on Stage
        if ($order->status === 'pre_production') {
             $steps = WorkTypeStep::where('work_type_id', $order->work_type_id)
                        ->where('stage', 'preparation')
                        ->orderBy('order')
                        ->get();
        } else {
             // Use filtered steps (Main Workflow)
             $steps = $order->workType->workflowSteps ?? collect();
        }

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

            if (in_array($item->status, ['pending', 'completed']) && $stepOneId && !$item->completedWorkTypeSteps->contains('id', $stepOneId)) {
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
        $employerId = $request->query('employer_id');

        $query = Employee::query()
             ->where(function ($q) use ($employerId) {
                 // Condition 1: Employees who have been terminated (resigned) from any employer
                 $q->whereNotNull('terminated_at');

                 // Condition 2: Active employees belonging to the target employer
                 if ($employerId) {
                     $q->orWhere(function ($subQ) use ($employerId) {
                         $subQ->whereNull('terminated_at')
                              ->where('employer_id', $employerId);
                     });
                 }
             })
             ->with('employer');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('employeeNameTh', 'like', "%{$search}%")
                  ->orWhere('employeeNameEn', 'like', "%{$search}%")
                  ->orWhere('employeePassport', 'like', "%{$search}%")
                  ->orWhere('employeeWorkPermit', 'like', "%{$search}%")
                  ->orWhere('employee_id_number', 'like', "%{$search}%")
                  ->orWhere('name_list_number', 'like', "%{$search}%")
                  ->orWhere('pinkCardNo', 'like', "%{$search}%")
                  ->orWhere('request_number', 'like', "%{$search}%")
                  ->orWhere('employer_employee_id', 'like', "%{$search}%")
                  ->orWhereHas('employer', function($e) use ($search) {
                      $e->filterByAddress($search);
                  });
            });
        }

        $employees = $query->limit(20)->get();
        $employees->append('photo_url');

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
                  ->orWhere('employeeWorkPermit', 'like', "%{$search}%")
                  ->orWhere('employee_id_number', 'like', "%{$search}%")
                  ->orWhere('name_list_number', 'like', "%{$search}%")
                  ->orWhere('pinkCardNo', 'like', "%{$search}%")
                  ->orWhere('request_number', 'like', "%{$search}%")
                  ->orWhere('employer_employee_id', 'like', "%{$search}%")
                  ->orWhereHas('employer', function($e) use ($search) {
                      $e->filterByAddress($search);
                  });
            });
        }

        $employees = $query->limit(20)->get();
        $employees->append('photo_url');

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

                $duplicateMessages = [];

                // Pre-validate all employees for duplicates BEFORE making any database changes
                foreach ($employees as $emp) {
                     $hasActiveWorkflow = ProductionItem::where('employee_id', $emp->id)
                        ->whereHas('order', function($q) use ($workTypeId) {
                             $q->where('work_type_id', $workTypeId)
                               ->where('status', '!=', 'cancelled');
                        })
                        ->whereNotIn('status', ['completed', 'cancelled'])
                        ->with('order')
                        ->first();

                     if ($hasActiveWorkflow) {
                         $name = $emp->employeeNameEn ?? $emp->employeeNameTh ?? 'Unknown';
                         $statusStr = $hasActiveWorkflow->order->status === 'pre_production' ? 'Pre-Production' : 'Workflow';
                         $duplicateMessages[] = "$name is already in $statusStr";
                     }
                }

                if (!empty($duplicateMessages)) {
                    $errorMsg = implode(', ', $duplicateMessages) . ". Please complete or cancel the existing process first.";

                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $errorMsg
                        ]);
                    }
                    return back()->with('error', $errorMsg);
                }

                DB::beginTransaction();
                try {
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
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }

                if ($request->ajax() || $request->wantsJson()) {
                    $redirectRoute = $isPreProduction
                        ? route('production.index', ['tab' => 'notify_out'])
                        : route('workflow.index', ['tab' => 'notify_out']);

                    return response()->json([
                        'success' => true,
                        'message' => 'Employees processed into resignation lists.',
                        'order_ids' => $updatedOrderIds,
                        'redirect_url' => $redirectRoute
                    ]);
                }

                if ($isPreProduction) {
                    return redirect()->route('production.index', ['tab' => 'notify_out'])->with('success', 'Employees processed.');
                }
                return redirect()->route('workflow.index', ['tab' => 'notify_out'])->with('success', 'Employees processed.');
            }
        }

        // --- Original Logic for Other Types ---

        // 1. Validation: Check for duplicates (Existing Employees)
        // Ensure an employee cannot be in the same WorkType workflow (Active or Pre-Production) twice.
        // User Requirement: Strict One-to-One. Employee cannot be in Pre-Prod OR Active Workflow for the same Process.
        if ($request->has('employee_ids') && is_array($request->employee_ids)) {
            if ($workTypeId) {
                // Find any items for these employees in this WorkType that are NOT cancelled or completed.
                $duplicates = ProductionItem::whereIn('employee_id', $request->employee_ids)
                    ->whereHas('order', function($q) use ($workTypeId) {
                        $q->where('work_type_id', $workTypeId)
                          ->where('status', '!=', 'cancelled'); // Ensure order is not cancelled
                    })
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->with(['employee', 'order'])
                    ->get();

                if ($duplicates->isNotEmpty()) {
                    // Generate specific error messages
                    $messages = [];
                    foreach ($duplicates as $item) {
                        $name = $item->employee->employeeNameEn ?? $item->employee->employeeNameTh ?? 'Unknown';
                        $status = $item->order->status === 'pre_production' ? 'Pre-Production' : 'Workflow';
                        $messages[] = "$name is already in $status";
                    }
                    $errorMsg = implode(', ', $messages) . ". Please complete or cancel the existing process first.";

                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $errorMsg
                        ]);
                    }

                    return back()->with('duplicate_error', $errorMsg);
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
                // Locking: Check if employee is already in an active workflow (Scoped to WorkType)
                // We allow employees to be in different workflows (e.g. Visa Renewal AND Change Employer) concurrently if needed,
                // but strictly ONE per WorkType.
                $hasActiveWorkflow = ProductionItem::where('employee_id', $empId)
                    ->whereHas('order', function($q) use ($workTypeId) {
                         if ($workTypeId) $q->where('work_type_id', $workTypeId);
                         $q->where('status', '!=', 'cancelled');
                    })
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
                'passport_issue_date', 'passportExpiryDate', 'pinkCardNo', 'visaExpiryDate',
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
            // For 'notify_in' (Change Employer), we DELAY the update by 24 hours.
            // So we DO NOT update the employee record here. The Scheduled Job will handle it.
            if (in_array($slug, ['mou_import', 'mou_renewal'])) {
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
     * Send an item back to Pre-Production (Preparation).
     */
    public function sendBackToPreProduction(Request $request, $itemId)
    {
        $item = ProductionItem::with(['order', 'employee'])->findOrFail($itemId);
        $currentOrder = $item->order;

        if ($currentOrder->status === 'pre_production') {
            return response()->json(['success' => false, 'message' => 'Item is already in Pre-Production.'], 400);
        }

        // Check if employee is already in Pre-Production for this process
        $hasDuplicate = ProductionItem::where('employee_id', $item->employee_id)
            ->whereHas('order', function($q) use ($currentOrder) {
                $q->where('work_type_id', $currentOrder->work_type_id)
                  ->where('status', 'pre_production');
            })
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->exists();

        if ($hasDuplicate) {
             return response()->json([
                 'success' => false,
                 'message' => 'This employee is already in Pre-Production for this process.'
             ], 400);
        }

        DB::beginTransaction();
        try {
            // Find a Pre-Production Order for this Employer + WorkType
            $preProdOrder = ProductionOrder::where('employer_id', $currentOrder->employer_id)
                                ->where('work_type_id', $currentOrder->work_type_id)
                                ->where('status', 'pre_production')
                                ->latest()
                                ->first();

            if (!$preProdOrder) {
                // Create new Pre-Production Order
                $workTypeName = $currentOrder->workType->name ?? 'Job';
                $employerName = $currentOrder->employer->employerNameTh ?? 'Unknown';

                $preProdOrder = ProductionOrder::create([
                    'employer_id' => $currentOrder->employer_id,
                    'work_type_id' => $currentOrder->work_type_id,
                    'type' => $currentOrder->type,
                    'project_name' => "$workTypeName - $employerName (Prep)",
                    'description' => $currentOrder->description,
                    'status' => 'pre_production',
                    'created_by' => auth()->id(),
                ]);
            }

            // Move Item
            $item->update([
                'production_order_id' => $preProdOrder->id,
                'status' => 'pending',
                'last_checked_at' => null,
                'appointment_date' => null,
                'appointment_location' => null,
                'appointment_completed_at' => null,
            ]);

            // Clear Completed Steps (Workflow steps do not map to Preparation steps 1:1)
            $item->completedWorkTypeSteps()->detach();

            DB::commit();

            // Calculate Stats for the OLD order (Workflow) to update UI
            $orderStats = $this->calculateOrderStats($currentOrder);

            return response()->json(['success' => true, 'order_stats' => $orderStats]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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
        // Load filtered workflowSteps
        $item = ProductionItem::with(['employee', 'order.employer', 'order.workType', 'completedWorkTypeSteps'])
            ->findOrFail($itemId);

        $order = $item->order;

        if ($order->status === 'pre_production') {
            $steps = $order->workType->preparationSteps ?? collect();
        } else {
            $steps = $order->workType->workflowSteps ?? collect();
        }

        // Render just the card partial
        $html = view('workflow.partials._item_card', compact('item', 'steps', 'order'))->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Fetch Trashed Items (Soft Deleted)
     */
    public function fetchTrash(Request $request)
    {
        $isPreProduction = $request->boolean('is_pre_production');

        $query = ProductionItem::onlyTrashed()
            ->with(['order' => fn($q) => $q->withTrashed(), 'order.employer' => fn($q) => $q->withTrashed(), 'employee' => fn($q) => $q->withTrashed()])
            ->whereHas('order', function($q) use ($isPreProduction) {
                // We must use withTrashed() for the whereHas query if the order itself is deleted
                $q->withTrashed();
                if ($isPreProduction) {
                    $q->where('status', 'pre_production');
                } else {
                    $q->where('status', '!=', 'pre_production');
                }
            })
            ->latest('deleted_at');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('employee', function($e) use ($search) {
                    $e->withTrashed()
                      ->where('employeeNameTh', 'like', "%{$search}%")
                      ->orWhere('employeeNameEn', 'like', "%{$search}%")
                      ->orWhere('employeePassport', 'like', "%{$search}%")
                      ->orWhere('employeeWorkPermit', 'like', "%{$search}%")
                      ->orWhere('employee_id_number', 'like', "%{$search}%")
                      ->orWhere('name_list_number', 'like', "%{$search}%")
                      ->orWhere('pinkCardNo', 'like', "%{$search}%")
                      ->orWhere('request_number', 'like', "%{$search}%")
                      ->orWhere('employer_employee_id', 'like', "%{$search}%");
                })
                ->orWhereHas('order', function($o) use ($search) {
                    $o->withTrashed()
                      ->where('project_name', 'like', "%{$search}%")
                      ->orWhereHas('employer', function($emp) use ($search) {
                          $emp->withTrashed()->where('employerNameTh', 'like', "%{$search}%");
                      });
                });
            });
        }

        $items = $query->paginate(10);

        // Get Retention Setting
        $retentionSetting = SystemSetting::where('key', 'trash_retention_days')->first();
        $retentionDays = $retentionSetting ? $retentionSetting->value : '30'; // Default 30

        return view('workflow.partials.trash_list', compact('items', 'retentionDays'));
    }

    /**
     * Restore Trashed Item
     */
    public function restoreTrash(Request $request, $id)
    {
        // Permission check
        // Assuming 'edit-employees' or 'manage-own-workflow' is appropriate
        if (!auth()->user()->can('edit-employees') && !auth()->user()->can('manage-own-workflow')) {
            abort(403);
        }

        $item = ProductionItem::onlyTrashed()->with(['employee' => fn($q) => $q->withTrashed(), 'order' => fn($q) => $q->withTrashed()])->findOrFail($id);

        $item->restore();

        if ($item->employee && $item->employee->trashed()) {
            $item->employee->restore();
        }

        if ($item->order && $item->order->trashed()) {
            $item->order->restore();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Permanently Delete Item (Force Delete)
     */
    public function forceDeleteTrash(Request $request, $id)
    {
        if (!auth()->user()->can('edit-employees') && !auth()->user()->can('manage-own-workflow')) {
            abort(403);
        }

        $item = ProductionItem::onlyTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['success' => true]);
    }

    /**
     * Update Trash Retention Settings
     */
    public function updateTrashSettings(Request $request)
    {
        if (!auth()->user()->can('manage-own-workflow')) { // Or admin check
             abort(403);
        }

        $request->validate([
            'retention_days' => 'required|string', // 'forever' or numeric
        ]);

        SystemSetting::updateOrCreate(
            ['key' => 'trash_retention_days'],
            ['value' => $request->retention_days, 'group' => 'workflow']
        );

        return response()->json(['success' => true]);
    }

    /**
     * Toggle Operator Assignment for a Production Item.
     */
    public function toggleOperator(Request $request, $itemId)
    {
        $item = ProductionItem::findOrFail($itemId);

        // Handle explicit operator assignment via dropdown
        if ($request->has('operator_id')) {
            $userId = $request->input('operator_id');
            if (empty($userId)) {
                $item->update(['operator_id' => null]);
                $message = 'Operator unassigned.';
            } else {
                $user = User::find($userId);
                if ($user) {
                    $item->update(['operator_id' => $user->id]);
                    $message = 'Operator assigned to ' . $user->name;
                } else {
                     return response()->json(['success' => false, 'message' => 'User not found'], 404);
                }
            }
        } else {
            // Legacy Toggle Behavior
            $userId = auth()->id();
            if ($item->operator_id === $userId) {
                $item->update(['operator_id' => null]);
                $message = 'Operator unassigned.';
            } else {
                $item->update(['operator_id' => $userId]);
                $message = 'Operator assigned to ' . auth()->user()->name;
            }
        }

        return response()->json(['success' => true, 'message' => $message]);
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
            } elseif ($filter === 'pending_daily_check') {
                $query->where(function($sub) {
                    $sub->whereNull('last_checked_at')
                        ->orWhereDate('last_checked_at', '<', Carbon::today());
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
                          ->orWhere('request_number', 'like', "%{$search}%")
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

        $steps = $activeTab ? $activeTab->workflowSteps : collect();
        $users = User::orderBy('name')->get(['id', 'name']);

        // Since items are `ProductionItem` models but the expected blade uses `$employees`, we map them.
        $employees = $items->map(function ($item) {
            $employee = $item->employee;
            if ($employee) {
                $employee->production_item = $item;
            }
            return $employee;
        })->filter();

        if (view()->exists('workflow._employee_list_content')) {
            return view('workflow._employee_list_content', [
                'employees' => $employees,
                'employer' => $order->employer,
                'steps' => $steps,
                'order' => $order,
                'users' => $users,
                'activeTab' => $activeTab
            ]);
        }

        // Fallback to the production registration view since they share the same base employee card partial
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

        $orders = ProductionOrder::whereIn('id', $orderIds)->with('workType.workflowSteps')->get();
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
                              ->orWhere('request_number', 'like', "%{$search}%")
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
                $stepOneId = $steps->sortBy('order')->first()?->id;

            $notStarted = 0;
            $cancelled = 0;
            $completed = 0;
            $stepStats = [];

            $steps = $order->workType ? $order->workType->workflowSteps : collect();
            $stepOneId = $steps->sortBy('order')->first()?->id;

            foreach ($steps as $step) {
                $stepStats[$step->id] = 0;
            }

            foreach ($items as $item) {
                if ($item->status === 'cancelled') {
                    $cancelled++;
                    continue;
                }

                $total++;

                if ($item->status === 'completed') {
                    $completed++;
                }

                if (in_array($item->status, ['pending', 'completed']) && $stepOneId && !$item->completedWorkTypeSteps->contains('id', $stepOneId)) {
                    $notStarted++;
                }

                $highestStep = $item->completedWorkTypeSteps->first(); // Ordered by desc
                if ($highestStep && isset($stepStats[$highestStep->id])) {
                    $stepStats[$highestStep->id]++;
                }
            }

            $results[$order->id] = [
                'total' => $total,
                'not_started' => $notStarted,
                'cancelled' => $cancelled,
                'completed' => $completed,
                'step_stats' => $stepStats,
                'active_items_count' => $total
            ];
        }

        return response()->json([
            'success' => true,
            'stats' => $results
        ]);
    }
}
