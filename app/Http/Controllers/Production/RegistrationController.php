<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Helpers\ActivityLogHelper;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionOrder;
use App\Models\RegistrationStep;
use App\Models\EmployeeCustomField;
use App\Models\ProductionCustomField;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\AddressFilterTrait;
use App\Traits\HasResolutionTab;

class RegistrationController extends Controller
{
    use AddressFilterTrait, HasResolutionTab;

    public function __construct()
    {
        // Permissions can be refined later.
        // For now, assume 'admin' or 'staff' access.
        $this->middleware('auth');
    }

    /**
     * Update employer registration resolution note (inline).
     */
    public function updateResolutionNote(Request $request, $resolutionTab, Employer $employer)
    {
        $this->resolveTab($resolutionTab, 'registration');
        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $validated = $request->validate([
            'note' => 'nullable|string',
        ]);

        $employer->update(['registration_resolution_note' => $validated['note']]);

        return response()->json(['success' => true]);
    }

    /**
     * Display the new calendar dashboard for Registration Resolution.
     */
    public function dashboard(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'registration');

        $days = \App\Models\NotificationSetting::where('notification_type', 'registration_appointment')->first()->days_before_expiry ?? 3;
        $start = \Carbon\Carbon::now()->startOfDay();
        $end = \Carbon\Carbon::now()->addDays($days)->endOfDay();

        $query = Employee::query()
            ->where('resolution_tab_id', $this->currentTab->id)
            ->whereIn('status', ['registration_pending', 'registration_completed'])
            ->whereNotNull('appointment_date')
            ->whereBetween('appointment_date', [$start, $end])
            ->whereNull('appointment_completed_at')
            ->with(['employer']);

        if (auth()->user()->can('manage-tickets')) {
            $query->withoutGlobalScope('employerTenancy');
        }

        $upcomingAppointments = $query->orderBy('appointment_date', 'asc')->get();

        return view('production.registration.dashboard', array_merge(compact('upcomingAppointments'), $this->getTabViewData('registration')));
    }

    /**
     * Display the main operations view for Registration Resolution.
     */
    public function index(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'registration');

        $steps = RegistrationStep::registration()->where('resolution_tab_id', $this->currentTab->id)->orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;
        $lastStepId = $steps->sortByDesc('order')->first()?->id;

        // --- 1. Global Stats Query (No Fetching All Models) ---
        $statsQuery = Employee::query()
            ->where('resolution_tab_id', $this->currentTab->id);

        if (auth()->user()->can('manage-tickets')) {
            $statsQuery->withoutGlobalScope('employerTenancy');
        }

        if ($request->has('search') && $request->search) {
            $this->applySearchToQuery($statsQuery, $request->search);
        }

        // Apply operator filter if present
        if ($request->has('operator_filter') && $request->operator_filter) {
            $opFilter = $request->operator_filter;
            if ($opFilter === 'external') {
                $statsQuery->whereNotNull('custom_operator_name')->where('custom_operator_name', '!=', '');
            } else {
                $statsQuery->where('operator_id', $opFilter);
            }
        }

        // Filter by relevant statuses for this menu
        $statsQuery->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled']);

        // Load resolution settings + targets up front so the filter cases
        // below (and the count subqueries / view) can reuse them.
        // Per-tab keys ({key}__tab_{id}) take precedence over legacy global keys.
        $resolutionSettingsRaw = SystemSetting::where('group', 'registration')->get();
        $resolutionSettings = $this->resolvePerTabSettings($resolutionSettingsRaw, 'registration', $this->currentTab->id);
        $renewalTargets = [
            'visa' => $resolutionSettings['registration_auto_visa_expiry'] ?? null,
            'wp'   => $resolutionSettings['registration_auto_work_permit_expiry'] ?? null,
        ];
        $renewalStatuses = [
            'pending'   => 'registration_pending',
            'completed' => 'registration_completed',
            'cancelled' => 'registration_cancelled',
        ];

        // Apply additional filter for total employees if present
        $totalEmployeesQuery = clone $statsQuery;
        if ($request->has('filter') && $request->filter) {
            $filter = $request->filter;
            if ($filter === 'not_started') {
                 $totalEmployeesQuery->whereIn('status', ['registration_pending', 'registration_completed'])
                       ->whereDoesntHave('registrationSteps', function($q) use ($stepOneId) {
                           $q->where('registration_steps.id', $stepOneId);
                       });
            } elseif ($filter === 'saved') {
                 $totalEmployeesQuery->where('status', 'registration_completed');
            } elseif ($filter === 'cancelled') {
                 $totalEmployeesQuery->where('status', 'registration_cancelled');
            } elseif ($filter === 'biometrics_collected') {
                 $totalEmployeesQuery->where('status', '!=', 'registration_cancelled')
                       ->whereNotNull('biometrics_collected_at');
            } elseif ($filter === 'biometrics_not_collected') {
                 $totalEmployeesQuery->where('status', '!=', 'registration_cancelled')
                       ->whereNull('biometrics_collected_at');
            } elseif ($filter === 'total_appointments') {
                 $totalEmployeesQuery->whereNotNull('appointment_date');
            } elseif ($filter === 'appointment_not_scheduled') {
                 $totalEmployeesQuery->whereIn('status', ['registration_pending', 'registration_completed'])
                       ->whereNull('appointment_date');
            } elseif ($filter === 'appointment_pending') {
                 $totalEmployeesQuery->whereNotNull('appointment_date')
                       ->whereNull('appointment_completed_at');
            } elseif ($filter === 'appointment_completed') {
                 $totalEmployeesQuery->whereNotNull('appointment_date')
                       ->whereNotNull('appointment_completed_at');
            } elseif (is_numeric($filter)) {
                // Approximate filtering for count: must have this step
                 $totalEmployeesQuery->where('status', '!=', 'registration_cancelled')
                    ->whereHas('registrationSteps', function($s) use ($filter) {
                        $s->where('registration_steps.id', $filter);
                    });
            }
        }

        // Clone for different counts
        $totalEmployees = $totalEmployeesQuery->count();
        $totalCancelled = (clone $statsQuery)->where('status', 'registration_cancelled')->count();
        $totalSaved = (clone $statsQuery)->where('status', 'registration_completed')->count();
        $totalBiometricsCollected = (clone $statsQuery)
                                        ->whereNotNull('biometrics_collected_at')->count();

        $notStartedCount = 0;
        if ($stepOneId) {
            $notStartedCount = (clone $statsQuery)
                ->whereIn('status', ['registration_pending', 'registration_completed'])
                ->whereDoesntHave('registrationSteps', function ($q) use ($stepOneId) {
                    $q->where('registration_steps.id', $stepOneId);
                })->count();
        }

        // Step Stats (Optimized via SQL) — 24h grace rule:
        // - Always drop `registration_cancelled`.
        // - Drop `registration_completed` rows whose finalize is older than
        //   24h so the top-of-page step badges decrement once the countdown
        //   ends. Cards themselves remain in the list (green cards stay put
        //   by design) — this only trims the aggregate counters.
        $stepStatsQuery = (clone $statsQuery)
            ->where('status', '!=', 'registration_cancelled')
            ->where(function ($q) {
                $q->where('status', '!=', 'registration_completed')
                  ->orWhereNull('resolution_completed_at')
                  ->orWhere('resolution_completed_at', '>=', now()->subHours(24));
            });
        $stepStats = $this->getGlobalStepStats($stepStatsQuery, $steps);

        // Total Appointments (use same base query with search/operator filters)
        $appointmentsBaseQuery = clone $statsQuery;

        $totalNotScheduled = (clone $appointmentsBaseQuery)
            ->whereIn('status', ['registration_pending', 'registration_completed'])
            ->whereNull('appointment_date')
            ->count();

        $totalAppointmentsPending = (clone $appointmentsBaseQuery)
            ->whereNotNull('appointment_date')
            ->whereNull('appointment_completed_at')
            ->count();

        $totalAppointmentsCompleted = (clone $appointmentsBaseQuery)
            ->whereNotNull('appointment_date')
            ->whereNotNull('appointment_completed_at')
            ->count();

        // Total Employers (Global, relevant to search)
        $totalEmployers = (clone $statsQuery)
            ->distinct('employer_id')
            ->count('employer_id');

        // Notification Setting
        $notificationSetting = NotificationSetting::firstOrNew(
            ['notification_type' => 'registration_appointment'],
            ['days_before_expiry' => 3, 'is_enabled' => true]
        );

        // Resolution settings already loaded earlier (top of method) — reused here.

        // --- 2. Employer List Query (Pagination) ---
        $employerQuery = Employer::withTrashed()->with(['jobOwner', 'customFields', 'addresses']);
        if (auth()->user()->can('manage-tickets')) {
            $employerQuery->withoutGlobalScope('employerTenancy');
        }

        // Always scope to employers who have relevant employees for this menu
        $employerQuery->whereHas('employees', function($q) {
             $q->where('resolution_tab_id', $this->currentTab->id)
               ->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled']);
        });

        // Apply Search to Employer Query
        if ($request->has('search') && $request->search) {
            $this->applyEmployerLevelSearch($employerQuery, $request->search);
        }

        // Apply Address Filters
        $addressOptions = $this->getAddressOptions($employerQuery);
        $employerQuery = $this->applyAddressFilters($employerQuery, $request);

        // Apply "Filter" pills (Server-Side)
        if ($request->has('filter') && $request->filter) {
            $this->applyFilterToEmployerQuery($employerQuery, $request->filter, $stepOneId, $renewalTargets, $renewalStatuses);
        }

        // Apply renewal-progress multi-select filter (independent of the
        // primary "filter" pill so users can combine renewal status with
        // any other filter above). Scope to the current tab so we don't
        // pull in employers who only match because of an employee living
        // under a different resolution tab.
        $renewalFilters = $this->parseRenewalFilters($request);
        if (!empty($renewalFilters)) {
            $tabId = $this->currentTab->id;
            $employerQuery->whereHas('employees', function ($q) use ($renewalFilters, $renewalTargets, $renewalStatuses, $tabId) {
                $q->where('resolution_tab_id', $tabId);
                $this->applyRenewalProgressMultiScope($q, $renewalFilters, $renewalTargets['visa'], $renewalTargets['wp'], $renewalStatuses);
            });
        }

        // Operator Filter (Server-Side)
        if ($request->has('operator_filter') && $request->operator_filter) {
            $opFilter = $request->operator_filter;
            $employerQuery->whereHas('employees', function($q) use ($opFilter) {
                // Should only check relevant status?
                if ($opFilter === 'external') {
                    $q->whereNotNull('custom_operator_name')->where('custom_operator_name', '!=', '');
                } else {
                    $q->where('operator_id', $opFilter);
                }
            });
        }

        // Eager load Production Orders to avoid N+1 (scoped to current tab)
        $tabId = $this->currentTab->id;
        $employerQuery->with(['productionOrders' => function($q) use ($tabId) {
             $q->whereIn('status', ['registration_resolution', 'registration_resolution_cancelled'])
               ->where('resolution_tab_id', $tabId);
        }]);

        // Counts the employees that are still actively under this tab —
        // excludes terminated and only counts the registration statuses.
        // Used by the view to grey out the card when this drops to zero.
        $employerQuery->withCount(['employees as active_employees_in_tab' => function($q) use ($tabId) {
            $q->where('resolution_tab_id', $tabId)
              ->whereIn('status', ['registration_pending', 'registration_completed'])
              ->whereNull('terminated_at');
        }]);

        // Sort and Paginate — applied in priority order:
        //   1. Employer cards with zero active employees go to the bottom
        //      (subquery returns 1 when empty, 0 when not).
        //   2. Cancelled production orders go below active ones.
        //   3. Most recently touched orders come first. ProductionItem::$touches
        //      bumps the parent ProductionOrder's updated_at on every change,
        //      so editing any employee under an employer pushes that card to
        //      the top on the next page load.
        $employerQuery->orderByRaw("(
            SELECT CASE WHEN COUNT(*) = 0 THEN 1 ELSE 0 END
            FROM employees
            WHERE employees.employer_id = employers.id
            AND employees.resolution_tab_id = ?
            AND employees.status IN ('registration_pending', 'registration_completed')
            AND employees.terminated_at IS NULL
        ) ASC", [$tabId]);

        $employerQuery->orderByRaw("(
            SELECT CASE WHEN status = 'registration_resolution_cancelled' THEN 1 ELSE 0 END
            FROM production_orders
            WHERE production_orders.employer_id = employers.id
            AND production_orders.status IN ('registration_resolution', 'registration_resolution_cancelled')
            AND production_orders.resolution_tab_id = ?
            LIMIT 1
        ) ASC", [$tabId]);

        $employerQuery->orderByRaw("(
            SELECT MAX(production_orders.updated_at)
            FROM production_orders
            WHERE production_orders.employer_id = employers.id
            AND production_orders.resolution_tab_id = ?
        ) DESC", [$tabId]);

        $perPage = $request->input('per_page', 20);
        // Ensure per_page is handled correctly as integer for pagination
        $perPage = in_array((int)$perPage, [20, 25, 50, 100]) ? (int)$perPage : 20;

        // Auto-navigate: redirect ไปยังหน้าที่ถูกต้องเมื่อ highlight_employer_id อยู่คนละหน้า
        if ($request->filled('highlight_employer_id') && !$request->filled('page')) {
            $highlightId = (int) $request->input('highlight_employer_id');
            // ใช้ get()->pluck() แทน pluck() เพื่อให้ลำดับตรงกับ paginate()
            $allIds = (clone $employerQuery)->get()->pluck('id')->toArray();
            $position = array_search($highlightId, $allIds);
            if ($position !== false) {
                $targetPage = (int) ceil(($position + 1) / $perPage);
                if ($targetPage > 1) {
                    return redirect()->to($request->fullUrlWithQuery(['page' => $targetPage]));
                }
            }
        }

        $employers = $employerQuery->paginate($perPage)->withQueryString();

        // Calculate Cancelled Employers Count (Approximate Global or based on current filter context)
        // Ideally we clone employerQuery before filters? But UI usually expects "Total Cancelled Employers" in the system or matching search.
        // Let's count filtered employers who have cancelled status order.
        // But $employerQuery has pagination. We need a separate query with same filters.
        // To be safe and performant, let's just count global cancelled employers matching search (if any).
        $cancelledEmployersQuery = Employer::whereHas('productionOrders', function($q) {
                $q->where('status', 'registration_resolution_cancelled')
                  ->where('resolution_tab_id', $this->currentTab->id);
            })->whereHas('employees', function($q) {
                $q->where('resolution_tab_id', $this->currentTab->id);
            });
        if (auth()->user()->can('manage-tickets')) {
            $cancelledEmployersQuery->withoutGlobalScope('employerTenancy');
        }
        if ($request->has('search') && $request->search) {
            $this->applyEmployerLevelSearch($cancelledEmployersQuery, $request->search);
        }
        $cancelledEmployersCount = $cancelledEmployersQuery->count();

        // Active Operators for Filter
        $operatorIds = (clone $statsQuery)

            ->whereNotNull('operator_id')
            ->distinct()
            ->pluck('operator_id');

        $activeOperators = User::whereIn('id', $operatorIds)->orderBy('name')->get(['id', 'name']);

        // All Users for Assignment
        $allUsers = User::orderBy('name')->get(['id', 'name']);

        // --- 3. Process Visible Employers ---
        // OPTIMIZATION: Removed heavy hydration and PHP loops.
        // We now load stats via AJAX (batchStats) and financial tab via lazy loading.

        foreach ($employers as $employer) {
            // Eager load just the finance order relation to check status for visual grey-out
            // But we don't create it if missing, nor load items.
            $financeOrder = $employer->productionOrders->first();
            $employer->financeOrder = $financeOrder;

            // Placeholders for view (will be updated via AJAX)
            $employer->stepStats = [];
            $employer->notStartedCount = 0;
            $employer->activeEmployeesCount = 0;
            $employer->cancelledCount = 0;
            $employer->savedCount = 0;
            $employer->biometricsCollectedCount = 0;
            // activeEmployeesList is removed as it's now loaded in the financial tab AJAX call
        }

        // Renewal-progress pill counts — computed against the same status set
        // that drives the legend on the page header. Cheap re-use of the
        // helper so the badges always match what the view will actually show.
        $progressCounts = [];
        foreach (['none', 'visa_only', 'work_permit_only', 'both', 'completed'] as $state) {
            $progressCounts[$state] = (clone $statsQuery)
                ->tap(fn($q) => $this->applyRenewalProgressScope($q, $state, $renewalTargets['visa'], $renewalTargets['wp'], $renewalStatuses))
                ->count();
        }

        return view('production.registration.index', array_merge(compact(
            'totalEmployees',
            'totalCancelled',
            'totalSaved',
            'totalEmployers',
            'cancelledEmployersCount',
            'notStartedCount',
            'totalBiometricsCollected',
            'totalNotScheduled',
            'totalAppointmentsPending',
            'totalAppointmentsCompleted',
            'steps',
            'stepStats',
            'employers',
            'lastStepId',
            'addressOptions',
            'notificationSetting',
            'resolutionSettings',
            'renewalTargets',
            'progressCounts',
            'activeOperators',
            'allUsers'
        ), $this->getTabViewData('registration')));
    }

    private function getGlobalStepStats($baseQuery, $steps)
    {
        // Use a subquery join to filter pivot table efficiently
        $pivotQuery = DB::table('employee_registration_status')
            ->join('registration_steps', 'employee_registration_status.registration_step_id', '=', 'registration_steps.id')
            ->joinSub($baseQuery->select('id'), 'filtered_employees', 'employee_registration_status.employee_id', '=', 'filtered_employees.id')
            ->select('employee_registration_status.employee_id', 'registration_steps.id as step_id', 'registration_steps.order');

        $records = $pivotQuery->get();

        $stats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();

        $grouped = $records->groupBy('employee_id');
        foreach ($grouped as $empId => $stepsData) {
            $highest = $stepsData->sortByDesc('order')->first();
            if ($highest && isset($stats[$highest->step_id])) {
                $stats[$highest->step_id]++;
            }
        }

        return $stats;
    }

    private function applyEmployerLevelSearch($query, $search)
    {
        $search = trim($search);

        // Support ID:123 format for direct employee/employer ID lookup
        if (preg_match('/^ID:\s*(\d+)$/i', $search, $matches)) {
            $targetId = (int) $matches[1];
            $query->where(function($q) use ($targetId) {
                $q->where('id', $targetId)
                  ->orWhereHas('employees', function($qEmp) use ($targetId) {
                      $qEmp->where('id', $targetId);
                  });
            });
            return;
        }

        $cleanedSearch = str_replace(' ', '', $search);

        $query->where(function($q) use ($search, $cleanedSearch) {
            // Employer Fields
            $q->where('employerNameTh', 'like', "%{$search}%")
              ->orWhere('employerNameEn', 'like', "%{$search}%")
              ->orWhereRaw("REPLACE(employerNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
              ->orWhereRaw("REPLACE(employerNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
              // Job Owner
              ->orWhereHas('jobOwner', function($q2) use ($search, $cleanedSearch) {
                  $q2->where('name', 'like', "%{$search}%")
                     ->orWhereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
              })
              // Address
              ->orWhere(function($addrQ) use ($search) {
                  $addrQ->filterByAddress($search);
              })
              // Employees (Robust Search) - Scoped to relevant statuses
              ->orWhereHas('employees', function($qEmp) use ($search, $cleanedSearch) {
                  $qEmp->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled'])
                       ->where(function($sub) use ($search, $cleanedSearch) {
                           $sub->where('employeeNameTh', 'like', "%{$search}%")
                               ->orWhere('employeeNameEn', 'like', "%{$search}%")
                               ->orWhere('name_suffix', 'like', "%{$search}%")
                               ->orWhere('employeePassport', 'like', "%{$search}%")
                               ->orWhere('employeeWorkPermit', 'like', "%{$search}%")
                               ->orWhere('employee_id_number', 'like', "%{$search}%")
                               ->orWhere('name_list_number', 'like', "%{$search}%")
                               ->orWhere('pinkCardNo', 'like', "%{$search}%")
                               ->orWhere('request_number', 'like', "%{$search}%")
                               ->orWhere('registration_request_number', 'like', "%{$search}%")
                               ->orWhere('employer_employee_id', 'like', "%{$search}%")
                               ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                               ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
                       });
              });
        });
    }

    /**
     * Tab-group renewal targets — single-query helper reused by the index()
     * stats path and every AJAX endpoint that re-renders an employee list,
     * so the card partial can short-circuit its per-row SystemSetting lookup.
     */
    protected function getRenewalTargets(string $group = 'registration'): array
    {
        $rows = SystemSetting::where('group', $group)
            ->whereIn('key', ["{$group}_auto_visa_expiry", "{$group}_auto_work_permit_expiry"])
            ->pluck('value', 'key');
        return [
            'visa' => $rows["{$group}_auto_visa_expiry"] ?? null,
            'wp'   => $rows["{$group}_auto_work_permit_expiry"] ?? null,
        ];
    }

    /**
     * Scope an employee-table query down to one of the renewal-progress states.
     * Used by both the stats count query and the whereHas() employer-query path
     * so the pill counts and the visible cards stay in sync.
     *
     * @param string  $progress   none | visa_only | work_permit_only | both | completed
     * @param ?string $visaTarget configured visa expiry target (Y-m-d) — null = unset
     * @param ?string $wpTarget   configured work-permit expiry target
     * @param array   $statuses   ['pending'=>..., 'completed'=>..., 'cancelled'=>...]
     */
    protected function applyRenewalProgressScope($q, string $progress, ?string $visaTarget, ?string $wpTarget, array $statuses): void
    {
        if ($progress === 'completed') {
            $q->where('status', $statuses['completed']);
            return;
        }

        // For not-yet-completed states, exclude both completed and cancelled.
        $q->whereNotIn('status', [$statuses['completed'], $statuses['cancelled']]);

        $visaNotRenewed = function ($v) use ($visaTarget) {
            $v->whereNull('visaExpiryDate')->orWhere('visaExpiryDate', '<', $visaTarget);
        };
        $wpNotRenewed = function ($w) use ($wpTarget) {
            $w->whereNull('workPermitExpiryDate')->orWhere('workPermitExpiryDate', '<', $wpTarget);
        };

        if ($progress === 'visa_only') {
            if (!$visaTarget) { $q->whereRaw('1=0'); return; }
            $q->whereNotNull('visaExpiryDate')->where('visaExpiryDate', '>=', $visaTarget);
            if ($wpTarget) {
                $q->where($wpNotRenewed);
            }
        } elseif ($progress === 'work_permit_only') {
            if (!$wpTarget) { $q->whereRaw('1=0'); return; }
            $q->whereNotNull('workPermitExpiryDate')->where('workPermitExpiryDate', '>=', $wpTarget);
            if ($visaTarget) {
                $q->where($visaNotRenewed);
            }
        } elseif ($progress === 'both') {
            if (!$visaTarget || !$wpTarget) { $q->whereRaw('1=0'); return; }
            $q->whereNotNull('visaExpiryDate')->where('visaExpiryDate', '>=', $visaTarget)
              ->whereNotNull('workPermitExpiryDate')->where('workPermitExpiryDate', '>=', $wpTarget);
        } elseif ($progress === 'none') {
            if ($visaTarget) { $q->where($visaNotRenewed); }
            if ($wpTarget)   { $q->where($wpNotRenewed); }
        }
    }

    /**
     * Multi-select variant — wraps the single-state scope in an OR group so
     * the user can combine, say, "visa renewed only" + "both renewed" in
     * one filter expression. Empty/invalid input is a no-op.
     */
    protected function applyRenewalProgressMultiScope($q, array $progresses, ?string $visaTarget, ?string $wpTarget, array $statuses): void
    {
        $valid = array_values(array_intersect($progresses, ['none', 'visa_only', 'work_permit_only', 'both', 'completed']));
        if (empty($valid)) return;

        $q->where(function ($outer) use ($valid, $visaTarget, $wpTarget, $statuses) {
            foreach ($valid as $state) {
                $outer->orWhere(function ($inner) use ($state, $visaTarget, $wpTarget, $statuses) {
                    $this->applyRenewalProgressScope($inner, $state, $visaTarget, $wpTarget, $statuses);
                });
            }
        });
    }

    /**
     * Pulls the comma-separated renewal_filters query value into a clean array.
     */
    protected function parseRenewalFilters(Request $request): array
    {
        $raw = $request->input('renewal_filters');
        if (!$raw) return [];
        if (is_array($raw)) {
            $items = $raw;
        } else {
            $items = explode(',', (string) $raw);
        }
        $items = array_map('trim', $items);
        $items = array_filter($items, fn($v) => $v !== '');
        return array_values(array_intersect($items, ['none', 'visa_only', 'work_permit_only', 'both', 'completed']));
    }

    private function applyFilterToEmployerQuery($query, $filter, $stepOneId, ?array $renewalTargets = null, ?array $renewalStatuses = null)
    {
        // Legacy single-select renewal filter (filter=renewal_visa_only / renewal_both / ...).
        // The renewal pills now use the multi-select `renewal_filters` query
        // string instead, so an old URL hanging around in someone's tab
        // shouldn't be allowed to fall through to the no-condition whereHas()
        // below — that would silently match every employer with any employee.
        if (is_string($filter) && str_starts_with($filter, 'renewal_')) {
            return;
        }

        if ($filter === 'cancelled_employer') {
            // Scope cancelled employer check to current tab
            $tabId = $this->currentTab->id;
            $query->whereHas('productionOrders', function($q) use ($tabId) {
                $q->where('status', 'registration_resolution_cancelled')
                  ->where('resolution_tab_id', $tabId);
            });
            return;
        }

        // For other filters, we check if the employer has ANY employee matching the criteria
        $query->whereHas('employees', function($q) use ($filter, $stepOneId) {
            if ($filter === 'not_started') {
                 $q->whereIn('status', ['registration_pending', 'registration_completed'])
                   ->whereDoesntHave('registrationSteps', function($sq) use ($stepOneId) {
                       $sq->where('registration_steps.id', $stepOneId);
                   });
            } elseif ($filter === 'saved') {
                 $q->where('status', 'registration_completed');
            } elseif ($filter === 'cancelled') {
                 $q->where('status', 'registration_cancelled');
            } elseif ($filter === 'biometrics_collected') {
                 $q->where('status', '!=', 'registration_cancelled')
                   ->whereNotNull('biometrics_collected_at');
            } elseif ($filter === 'biometrics_not_collected') {
                 $q->where('status', '!=', 'registration_cancelled')
                   ->whereNull('biometrics_collected_at');
            } elseif ($filter === 'total_appointments') {
                 $q->whereNotNull('appointment_date');
            } elseif ($filter === 'appointment_not_scheduled') {
                 $q->whereIn('status', ['registration_pending', 'registration_completed'])
                   ->whereNull('appointment_date');
            } elseif ($filter === 'appointment_pending') {
                 $q->whereNotNull('appointment_date')
                   ->whereNull('appointment_completed_at');
            } elseif ($filter === 'appointment_completed') {
                 $q->whereNotNull('appointment_date')
                   ->whereNotNull('appointment_completed_at');
            } elseif (is_numeric($filter)) { // Step ID (Highest Step Logic approximation for filter)
                 // Strict Highest Step Filtering to match Employee List Logic
                 $q->where('status', '!=', 'registration_cancelled')
                   ->whereRaw("
                        (SELECT registration_step_id
                         FROM employee_registration_status
                         JOIN registration_steps ON employee_registration_status.registration_step_id = registration_steps.id
                         WHERE employee_registration_status.employee_id = employees.id
                         ORDER BY registration_steps.`order` DESC
                         LIMIT 1
                        ) = ?", [$filter]);
            }
        });
    }

    /**
     * AJAX: Fetch stats for a batch of employers (to avoid N+1 on initial load).
     */
    public function batchStats(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'registration');

        $request->validate([
            'employer_ids' => 'required|array',
            'employer_ids.*' => 'exists:employers,id',
            'search' => 'nullable|string'
        ]);

        $employerIds = $request->input('employer_ids');
        $search = $request->input('search');

        // Fetch visible employers to apply logic
        $employers = Employer::with(['jobOwner', 'addresses'])->whereIn('id', $employerIds)->get();
        $steps = RegistrationStep::registration()->where('resolution_tab_id', $this->currentTab->id)->orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;

        $results = [];

        foreach ($employers as $employer) {
            // Apply Search Filter Logic
            // Reuse the exact logic from original index() to determine if we filter employees or take all.
            $employerMatches = false;

            if ($search) {
                $trimmedSearch = trim($search);
                $cleanedSearch = str_replace(' ', '', $trimmedSearch);

                $empNameThClean = str_replace(' ', '', $employer->employerNameTh ?? '');
                $empNameEnClean = str_replace(' ', '', $employer->employerNameEn ?? '');

                if (stripos($employer->employerNameTh, $trimmedSearch) !== false ||
                    stripos($employer->employerNameEn, $trimmedSearch) !== false ||
                    stripos($empNameThClean, $cleanedSearch) !== false ||
                    stripos($empNameEnClean, $cleanedSearch) !== false) {
                    $employerMatches = true;
                }

                if (!$employerMatches && $employer->jobOwner && stripos($employer->jobOwner->name, $trimmedSearch) !== false) {
                    $employerMatches = true;
                }

                if (!$employerMatches) {
                     foreach($employer->addresses as $addr) {
                         if (stripos($addr->addrProvince, $trimmedSearch) !== false || stripos($addr->addrDistrict, $trimmedSearch) !== false) {
                             $employerMatches = true;
                             break;
                         }
                     }
                }
            } else {
                // No search -> "Employer Matches" implicitly means "Show all relevant employees"
                $employerMatches = true;
            }

            // Build Query for this employer
            $query = $employer->employees();

            if (auth()->user()->can('manage-tickets')) {
                $query->withoutGlobalScope('employerTenancy');
            }

            // Base status filter
            $query->where('resolution_tab_id', $this->currentTab->id)
                  ->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled']);

            // Operator Filter
            if ($request->has('operator_filter') && $request->operator_filter) {
                if ($request->operator_filter === 'external') {
                    $query->whereNotNull('custom_operator_name')->where('custom_operator_name', '!=', '');
                } else {
                    $query->where('operator_id', $request->operator_filter);
                }
            }

            if (!$employerMatches && $search) {
                // Filter employees by name/passport
                 $trimmedSearch = trim($search);
                 $cleanedSearch = str_replace(' ', '', $trimmedSearch);
                 $query->where(function($q) use ($trimmedSearch, $cleanedSearch) {
                        $q->where('employeeNameTh', 'like', "%{$trimmedSearch}%")
                               ->orWhere('employeeNameEn', 'like', "%{$trimmedSearch}%")
                               ->orWhere('name_suffix', 'like', "%{$trimmedSearch}%")
                               ->orWhere('employeePassport', 'like', "%{$trimmedSearch}%")
                               ->orWhere('employeeWorkPermit', 'like', "%{$trimmedSearch}%")
                               ->orWhere('employee_id_number', 'like', "%{$trimmedSearch}%")
                               ->orWhere('name_list_number', 'like', "%{$trimmedSearch}%")
                               ->orWhere('pinkCardNo', 'like', "%{$trimmedSearch}%")
                               ->orWhere('request_number', 'like', "%{$trimmedSearch}%")
                               ->orWhere('registration_request_number', 'like', "%{$trimmedSearch}%")
                               ->orWhere('employer_employee_id', 'like', "%{$trimmedSearch}%")
                               ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                               ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
                 });
            }

            // Fetch necessary columns efficiently
            // We include biometrics fields
            // Loading full registrationSteps relation to ensure pivot data and keys are correctly loaded without risk
            $employees = $query->with('registrationSteps')->select('id', 'status', 'biometrics_collected_at', 'resolution_completed_at')->get();

            // Calculate in PHP
            $empStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
            $empNotStarted = 0;
            $empActiveCount = 0;
            $empCancelledCount = 0;
            $empSavedCount = 0;
            $empBiometricsCollected = 0;
            $graceCutoff = now()->subHours(24);

            foreach ($employees as $emp) {
                if ($emp->status === 'registration_cancelled') {
                    $empCancelledCount++;
                    continue;
                }

                $empActiveCount++;

                if ($emp->status === 'registration_completed') {
                    $empSavedCount++;
                }

                if ($stepOneId && in_array($emp->status, ['registration_pending', 'registration_completed']) && !$emp->registrationSteps->contains('id', $stepOneId)) {
                    $empNotStarted++;
                }

                if ($emp->biometrics_collected_at) {
                    $empBiometricsCollected++;
                }

                // Step badge — same 24h grace as the global stats: completed
                // employees whose 24h countdown finished no longer count
                // toward the per-employer step badges either.
                $countTowardsSteps = !($emp->status === 'registration_completed'
                    && $emp->resolution_completed_at
                    && $emp->resolution_completed_at->lt($graceCutoff));

                if ($countTowardsSteps) {
                    $highestStep = $emp->registrationSteps->sortByDesc('order')->first();
                    if ($highestStep && isset($empStats[$highestStep->id])) {
                        $empStats[$highestStep->id]++;
                    }
                }
            }

            $results[$employer->id] = [
                'stepStats' => $empStats,
                'notStartedCount' => $empNotStarted,
                'activeEmployeesCount' => $empActiveCount,
                'cancelledCount' => $empCancelledCount,
                'savedCount' => $empSavedCount,
                'biometricsCollectedCount' => $empBiometricsCollected,
            ];
        }

        return response()->json($results);
    }

    /**
     * AJAX: Load Financial Tab Content (Lazy Load).
     */
    public function loadFinancialTab(Request $request, $resolutionTab, Employer $employer)
    {
        $this->resolveTab($resolutionTab, 'registration');

        // Permission Check
        if (!auth()->user()->can('view-finance') && !auth()->user()->can('edit-employees')) {
             abort(403);
        }

        // Finance Order Logic (scoped per-tab)
        $financeOrder = $employer->productionOrders()
            ->whereIn('status', ['registration_resolution', 'registration_resolution_cancelled'])
            ->where('resolution_tab_id', $this->currentTab->id)
            ->first();

        if (!$financeOrder) {
            $financeOrder = ProductionOrder::create([
                'employer_id' => $employer->id,
                'resolution_tab_id' => $this->currentTab->id,
                'status'      => 'registration_resolution',
                'type'         => 'employer',
                'project_name' => 'Registration Resolution (' . $this->currentTab->name . ') - ' . $employer->employerNameTh,
                'financial_data' => []
            ]);
        }

        if ($financeOrder->financialGroups->isEmpty()) {
            $financeOrder->financialGroups()->create([
                'name' => 'General',
                'financial_data' => $financeOrder->financial_data ?? []
            ]);
        }

        // Load relationships needed for the view
        $financeOrder->load(['financialGroups.transactions.items', 'financialGroups.transactions.payments', 'financialGroups.advanceItems', 'items.employee']);

        // Fetch ALL Active Employees for this employer (ignoring search)
        $query = $employer->employees()
            ->where('resolution_tab_id', $this->currentTab->id)
            ->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled']);
        if (auth()->user()->can('manage-tickets')) {
            $query->withoutGlobalScope('employerTenancy');
        }

        $employees = $query
            ->whereDoesntHave('productionItems', function($q) use ($financeOrder) {
                $q->where('production_order_id', $financeOrder->id);
            })
            ->get();

        return view('production.partials.financial-tab', [
            'production' => $financeOrder,
            'employeeCount' => $employees->count(),
            'employees' => $employees
        ]);
    }

    /**
     * AJAX Method to fetch employee list for an employer.
     */
    public function fetchEmployees(Request $request, $resolutionTab, $employerId)
    {
        $this->resolveTab($resolutionTab, 'registration');

        $employerQuery = Employer::query();
        if (auth()->user()->can('manage-tickets')) {
            $employerQuery->withoutGlobalScope('employerTenancy');
        }
        $employer = $employerQuery->withTrashed()->findOrFail($employerId);

        $steps = RegistrationStep::registration()->where('resolution_tab_id', $this->currentTab->id)->orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;

        // Apply the same base status filter as the index method to ensure consistency
        $query = $employer->employees()
            ->where('resolution_tab_id', $this->currentTab->id);

        if (auth()->user()->can('manage-tickets')) {
            $query->withoutGlobalScope('employerTenancy');
        }

        // Completed employees now stay in the main list (green card) instead
        // of being hidden after 24 hours — the user-driven 4-colour workflow
        // replaced the auto-archive behaviour.
        $query->where(function($q) use ($request) {
                if ($request->boolean('hide_cancelled', true)) {
                    $q->whereIn('status', ['registration_pending', 'registration_completed']);
                } else {
                    $q->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled']);
                }
            })
            ->with(['registrationSteps', 'customFields']); // Load everything needed for the card

        // Apply Search (if global search is active)
        if ($request->has('search') && $request->search) {
            $this->applyEmployerSearchToQuery($query, $employer, $request->search);
        }

        // Operator Filter
        if ($request->has('operator_filter') && $request->operator_filter) {
            if ($request->operator_filter === 'external') {
                $query->whereNotNull('custom_operator_name')->where('custom_operator_name', '!=', '');
            } else {
                $query->where('operator_id', $request->operator_filter);
            }
        }

        // Apply Filter
        if ($request->has('filter') && $request->filter) {
            $filter = $request->filter;
            if ($filter === 'not_started') {
                 $query->whereIn('status', ['registration_pending', 'registration_completed'])
                       ->whereDoesntHave('registrationSteps', function($q) use ($stepOneId) {
                           $q->where('registration_steps.id', $stepOneId);
                       });
            } elseif ($filter === 'saved') {
                 $query->where('status', 'registration_completed');
            } elseif ($filter === 'cancelled') {
                 $query->where('status', 'registration_cancelled');
            } elseif ($filter === 'biometrics_collected') {
                 $query->where('status', '!=', 'registration_cancelled')
                       ->whereNotNull('biometrics_collected_at');
            } elseif ($filter === 'biometrics_not_collected') {
                 $query->where('status', '!=', 'registration_cancelled')
                       ->whereNull('biometrics_collected_at');
            } elseif ($filter === 'total_appointments') {
                 $query
                       ->whereNotNull('appointment_date');
            } elseif (is_numeric($filter)) { // Step ID
                 $query->where('status', '!=', 'registration_cancelled');
                 // We filter by highest step in PHP below
            }
        }

        // Renewal-progress multi-select filter (applied at the employee level
        // so the cards opened inside an employer drawer only show employees
        // matching the selected states).
        $renewalFilters = $this->parseRenewalFilters($request);
        if (!empty($renewalFilters)) {
            $targets = $this->getRenewalTargets('registration');
            $this->applyRenewalProgressMultiScope($query, $renewalFilters, $targets['visa'], $targets['wp'], [
                'pending'   => 'registration_pending',
                'completed' => 'registration_completed',
                'cancelled' => 'registration_cancelled',
            ]);
        }

        $employees = $query->get();

        // If filtering by step in PHP, we must get all first, filter, then manually paginate
        if ($request->has('filter') && is_numeric($request->filter)) {
            $filterStepId = $request->filter;
            $allEmployees = $query->get();
            $filtered = $allEmployees->filter(function($emp) use ($filterStepId) {
                if ($emp->status === 'registration_cancelled') return false;
                $highest = $emp->registrationSteps->sortByDesc('order')->first();
                return $highest && $highest->id == $filterStepId;
            });

            $perPage = $request->input('per_page', 100);
            $page = $request->input('page', 1);
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $filtered->forPage($page, $perPage)->values(),
                $filtered->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            $employees = $paginator;
        } else {
            $perPage = $request->input('per_page', 100);
            $employees = $query->paginate($perPage)->withQueryString();
        }

        // --- Calculate Financial Status ---
        $financeOrder = ProductionOrder::with('financialGroups.transactions.items', 'financialGroups.transactions.payments')
            ->where('employer_id', $employerId)
            ->whereIn('status', ['registration_resolution', 'registration_resolution_cancelled'])
            ->first();

        $employeeFinancialStatus = \App\Services\FinancialStatusService::calculateStatusForEmployees($financeOrder, $employees->pluck('id'));

        foreach ($employees as $emp) {
            $emp->financialStatus = $employeeFinancialStatus[$emp->id] ?? null;
        }

        return view('production.registration._employee_list_content', array_merge([
            'employees' => $employees,
            'steps' => $steps,
            'employer' => $employer,
            'renewalTargets' => $this->getRenewalTargets('registration'),
        ], $this->getTabViewData('registration')));
    }

    /**
     * Cancel an Employer.
     */
    public function cancelEmployer(Request $request, $resolutionTab, Employer $employer)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employers')) {
            abort(403);
        }

        $tabId = $this->currentTab->id;
        DB::transaction(function () use ($employer, $tabId) {
            // 1. Update Order Status (scoped to current tab)
            $order = ProductionOrder::where('employer_id', $employer->id)
                ->whereIn('status', ['registration_resolution', 'registration_resolution_cancelled'])
                ->where('resolution_tab_id', $tabId)
                ->first();

            if ($order) {
                $order->update(['status' => 'registration_resolution_cancelled']);
            }

            // 2. Update Employees (Only Pending ones in this tab get cancelled)
            $employer->employees()
                ->where('resolution_tab_id', $tabId)
                ->where('status', 'registration_pending')
                ->update(['status' => 'registration_cancelled']);
        });

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Employer registration cancelled.');
    }

    /**
     * Restore an Employer.
     */
    public function restoreEmployer(Request $request, $resolutionTab, Employer $employer)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employers')) {
            abort(403);
        }

        $tabId = $this->currentTab->id;
        DB::transaction(function () use ($employer, $tabId) {
            // 1. Update Order Status (scoped to current tab)
            $order = ProductionOrder::where('employer_id', $employer->id)
                ->whereIn('status', ['registration_resolution', 'registration_resolution_cancelled'])
                ->where('resolution_tab_id', $tabId)
                ->first();

            if ($order) {
                $order->update(['status' => 'registration_resolution']);
            }

            // 2. Update Employees (Cancelled -> Pending, scoped to current tab)
            $employer->employees()
                ->where('resolution_tab_id', $tabId)
                ->where('status', 'registration_cancelled')
                ->update(['status' => 'registration_pending']);
        });

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Employer registration restored.');
    }

    /**
     * Finalize an employee (Save to Database).
     */
    public function finalize(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        // Change status to 'registration_completed'
        // Reset resolution_settings_applied so the 24h auto-apply (UpdateResolutionData)
        // will run again on this fresh finalize cycle even if it was applied before
        // (i.e. after a previous finalize → restore → finalize sequence).
        $employee->update([
            'status' => 'registration_completed',
            'resolution_completed_at' => now(),
            'resolution_settings_applied' => false,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => $this->getEmployeeCardHtml($employee),
                'stats' => $this->getStats($employee->employer_id, $request)
            ]);
        }
        return back()->with('success', 'Employee saved to database.');
    }

    /**
     * Cancel an employee (Grey out).
     */
    public function cancel(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $employee->update(['status' => 'registration_cancelled']);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => $this->getEmployeeCardHtml($employee),
                'stats' => $this->getStats($employee->employer_id, $request)
            ]);
        }
        return back()->with('success', 'Employee registration cancelled.');
    }

    /**
     * Restore an employee (Back to pending).
     */
    public function restore(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $employee->update([
            'status' => 'registration_pending',
            'resolution_tab_id' => $this->currentTab->id,
            'resolution_completed_at' => null,
            'resolution_settings_applied' => false,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => $this->getEmployeeCardHtml($employee),
                'stats' => $this->getStats($employee->employer_id, $request)
            ]);
        }
        return back()->with('success', 'Employee restored to pending.');
    }

    /**
     * Fetch Historic Items (Completed > 24h).
     */
    public function fetchHistory(Request $request, $resolutionTab, $employerId)
    {
        $this->resolveTab($resolutionTab, 'registration');

        $employer = Employer::findOrFail($employerId);

        $employees = $employer->employees()
            ->where('resolution_tab_id', $this->currentTab->id)
            ->where('status', 'registration_completed')
            ->where(function($q) {
                 $q->whereNotNull('resolution_completed_at')
                   ->where('resolution_completed_at', '<', now()->subHours(24));
            })
            ->with(['registrationSteps'])
            ->get();

        $steps = RegistrationStep::registration()->orderBy('order')->get();

        return view('production.registration._employee_list_content', array_merge([
            'employees' => $employees,
            'steps' => $steps,
            'employer' => $employer,
            'renewalTargets' => $this->getRenewalTargets('registration'),
        ], $this->getTabViewData('registration')))->with('isHistory', true);
    }

    /**
     * Soft delete an employee.
     */
    public function destroy(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $employerId = $employee->employer_id; // Capture ID before deletion
        $employee->delete(); // Soft delete

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'stats' => $this->getStats($employerId, $request)
            ]);
        }
        return back()->with('success', 'Employee deleted.');
    }

    /**
     * Bulk Finalize employees.
     */
    public function bulkFinalize(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        // Match single-finalize behavior:
        //   - Stamp resolution_completed_at so the 24h auto-apply timer starts
        //   - Reset resolution_settings_applied so UpdateResolutionData will run
        //     on this fresh finalize cycle (previously bulk-finalize never got
        //     auto-applied because resolution_completed_at stayed null)
        Employee::whereIn('id', $request->input('employee_ids'))
            ->update([
                'status' => 'registration_completed',
                'resolution_completed_at' => now(),
                'resolution_settings_applied' => false,
            ]);

        if ($request->ajax()) {
            // Recalculating stats might be heavy here if multiple employers.
            // For now, just return success. If needed, frontend can reload or we calculate stats.
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Selected employees saved to database.');
    }

    /**
     * Restore state (Undo Finalize).
     * @deprecated Use restore() instead for general restore. Kept for backward compat if needed.
     */
    public function restoreState(Request $request, $resolutionTab, Employee $employee)
    {
        return $this->restore($request, $resolutionTab, $employee);
    }

    /**
     * Display the Import View for Registration Resolution.
     */
    public function importView(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'registration');
        $employers = collect();
        if (auth()->user()->can('view-employers')) {
             $employers = Employer::orderBy('employerNameTh')->get(['id', 'employerNameTh', 'employerNameEn']);
        } else {
             $user = auth()->user();
             if ($user->employer) {
                 $employers = collect([$user->employer]);
             }
        }

        $request->merge(['target_status' => 'registration_pending']);

        session()->flash('finish_route', route('production.registration.index', ['resolutionTab' => $this->currentTab->id]));

        // Hydrate imported employees from session IDs if available (Restoring Preview Feature)
        $sessionImportedEmployees = collect();
        if (session()->has('imported_employee_ids')) {
            $sessionImportedEmployees = Employee::whereIn('id', session('imported_employee_ids'))->get();
        }

        return view('employees.import', array_merge([
            'employers' => $employers,
            'production' => null,
            'back_route' => route('production.registration.index', ['resolutionTab' => $this->currentTab->id]),
            'sessionImportedEmployees' => $sessionImportedEmployees,
        ], $this->getTabViewData('registration')));
    }

    public function create(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $employers = \App\Models\Employer::orderBy('employerNameTh')->get();
        $selectedEmployer = null;

        if ($request->has('employer_id')) {
            $selectedEmployer = \App\Models\Employer::find($request->employer_id);
        }

        return view('production.registration.create', array_merge([
            'employers' => $employers,
            'employer' => $selectedEmployer,
            'formAction' => route('production.registration.store', ['resolutionTab' => $this->currentTab->id])
        ], $this->getTabViewData('registration')));
    }

    /**
     * Store a newly created registration employee.
     */
    public function store(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        // Copy of validation from EmployeeController@store
        $validated = $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'passportType' => 'nullable|string|max:255',
            'employeeTitleTh' => 'nullable|string|max:255',
            'employeeNameTh' => 'nullable|string|max:255',
            'employeeTitleEn' => 'nullable|string|max:255',
            'employeeNameEn' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'height' => 'nullable|string|max:255',
            'weight' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'employeeGender' => 'nullable|string|max:255',
            'employeeDob' => 'nullable|date',
            'employeeAge' => 'nullable|integer',
            'employeePhone' => 'nullable|string|max:255',
            'employeeNationality' => 'nullable|string|max:255',
            'passport_type_cambodia' => 'nullable|string|max:255',
            'employeePassport' => 'nullable|string|max:255',
            'passport_issue_place' => 'nullable|string|max:255',
            'passport_issue_date' => 'nullable|date',
            'passportExpiryDate' => 'nullable|date',
            'pinkCardNo' => 'nullable|string|max:255',
            'visaType' => 'nullable|string|max:255',
            'visa_issue_place' => 'nullable|string|max:255',
            'visaExpiryDate' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'job_description' => 'nullable|string',
            'outsource_code' => 'nullable|string|max:255',
            'employee_reference_id' => 'nullable|string|max:255',
            'startDate' => 'nullable|date',
            'employeeWorkPermit' => 'nullable|string|max:255',
            'workPermitExpiryDate' => 'nullable|date',
            'workPermitType' => 'nullable|string|max:255',
            'workPermitMOUGroup' => 'nullable|string|max:255',
            'workPermitMOUGroupOther' => 'nullable|string|max:255',
            'ninetyDayReportDate' => 'nullable|date',
            'name_list_number' => 'nullable|string|max:255',
            'request_number' => 'nullable|string|max:255',
            'employee_id_number' => 'nullable|string|max:255',
            'tax_id_number' => 'nullable|string|max:255',
            'employer_employee_id' => 'nullable|string|max:255',
            'insurance_type' => 'nullable|string|max:255',
            'insurance_detail' => 'nullable|string',
            'insurance_expiry_date' => 'nullable|date',
            'social_security_number' => 'nullable|string|max:255',
            'sso_issue_date' => 'nullable|date',
            'sso_expiry_date' => 'nullable|date',
            'insurance_detail_hospital' => 'nullable|string|max:255',
            'insurance_detail_private' => 'nullable|string|max:255',
            'insurance_expiry_date_private' => 'nullable|string|max:255',
            'insurance_expiry_date_hospital' => 'nullable|string|max:255',
            'insurance_detail_social' => 'nullable|string|max:255',
            'medical_hospital_name' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'employeeEmail' => 'nullable|email|max:255|unique:employees,email',
            'employeePassword' => 'nullable|string|min:8',
            'other_doc_1_desc' => 'nullable|string|max:255',
            'other_doc_2_desc' => 'nullable|string|max:255',
            'other_doc_3_desc' => 'nullable|string|max:255',
            'other_doc_4_desc' => 'nullable|string|max:255',
            'other_doc_5_desc' => 'nullable|string|max:255',
            'other_doc_6_desc' => 'nullable|string|max:255',
            'other_doc_7_desc' => 'nullable|string|max:255',
            'other_doc_8_desc' => 'nullable|string|max:255',
            'other_doc_9_desc' => 'nullable|string|max:255',
            'other_doc_10_desc' => 'nullable|string|max:255',
            'employeePhoto' => 'nullable|image|max:2048',
            'insurance_document_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'insurance_document_path_private' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'medical_certificate_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_4' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_5' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_6' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_7' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_8' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_9' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_10' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_11' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_12' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_13' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_14' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_15' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_16' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_17' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employee_doc_18' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
        ]);

        // Forced Status
        $validated['status'] = 'registration_pending';
        $validated['resolution_tab_id'] = $this->currentTab->id;

        // Insurance Mapping (Same as EmployeeController)
        $validated['insuranceType'] = $validated['insurance_type'] ?? null;

        if ($validated['insuranceType'] === 'ประกันสังคม') {
            $validated['socialSecurityNumber'] = $validated['social_security_number'] ?? null;
            $validated['hospitalName'] = $validated['insurance_detail_social'] ?? null;
            $validated['sso_issue_date'] = $validated['sso_issue_date'] ?? null;
            $validated['sso_expiry_date'] = $validated['sso_expiry_date'] ?? null;
            $validated['insuranceCompany'] = null;
            $validated['insuranceExpiryDate'] = null;
        } elseif ($validated['insuranceType'] === 'ประกันเอกชน') {
            $validated['insuranceCompany'] = $validated['insurance_detail_private'] ?? null;
            $validated['insuranceExpiryDate'] = $validated['insurance_expiry_date_private'] ?? null;
            $validated['socialSecurityNumber'] = null;
            $validated['hospitalName'] = null;
            $validated['sso_issue_date'] = null;
            $validated['sso_expiry_date'] = null;
        } elseif ($validated['insuranceType'] === 'ประกันโรงพยาบาล') {
            $validated['hospitalName'] = $validated['insurance_detail_hospital'] ?? null;
            $validated['insuranceExpiryDate'] = $validated['insurance_expiry_date_hospital'] ?? null;
            $validated['socialSecurityNumber'] = null;
            $validated['insuranceCompany'] = null;
            $validated['sso_issue_date'] = null;
            $validated['sso_expiry_date'] = null;
        } else {
            $validated['socialSecurityNumber'] = null;
            $validated['hospitalName'] = null;
            $validated['insuranceCompany'] = null;
            $validated['insuranceExpiryDate'] = null;
            $validated['sso_issue_date'] = null;
            $validated['sso_expiry_date'] = null;
        }

        // Prevent email from being overwritten with null during partial updates
        if (array_key_exists('employeeEmail', $validated)) {
            $validated['email'] = $validated['employeeEmail'];
            unset($validated['employeeEmail']);
        } else {
            unset($validated['employeeEmail']);
        }

        if (!empty($validated['employeePassword'])) {
            $validated['password'] = $validated['employeePassword'];
        } else if (array_key_exists('employeePassword', $validated) && empty($validated['employeePassword'])) {
            // Do not clear the password if it's explicitly submitted as empty,
            // or if it's missing from the form completely.
            unset($validated['password']);
        }
        unset($validated['employeePassword']);

        // Prevent outsource_code from being cleared out by inline updates
        if (!array_key_exists('outsource_code', $request->all())) {
            unset($validated['outsource_code']);
        }

        // File Uploads
        $fileFields = [
            'employeePhoto', 'insurance_document_path','insurance_document_path_private', 'medical_certificate_path',
            'employee_doc_1', 'employee_doc_2', 'employee_doc_3', 'employee_doc_4',
            'employee_doc_5', 'employee_doc_6', 'employee_doc_7', 'employee_doc_8',
            'employee_doc_9', 'employee_doc_10', 'employee_doc_11', 'employee_doc_12',
            'employee_doc_13', 'employee_doc_14', 'employee_doc_15', 'employee_doc_16',
            'employee_doc_17', 'employee_doc_18'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs("employee_files/{$validated['employer_id']}", $filename, 'public');
                $validated[$field] = $path;
            }
        }

        Employee::create($validated);

        return redirect()->route('production.registration.operations', ['resolutionTab' => $this->currentTab->id, 'highlight_employer_id' => $validated['employer_id']])
                         ->with('success', 'Registration Employee created successfully.');
    }

    /**
     * Store a new registration step.
     */
    public function storeStep(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'registration');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $maxOrder = RegistrationStep::registration()->where('resolution_tab_id', $this->currentTab->id)->max('order') ?? 0;

        RegistrationStep::create([
            'name' => $validated['name'],
            'order' => $maxOrder + 1,
            'type' => 'registration',
            'resolution_tab_id' => $this->currentTab->id,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Update a step (rename/color).
     */
    public function updateStep(Request $request, $resolutionTab, RegistrationStep $step)
    {
        $this->resolveTab($resolutionTab, 'registration');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $step->update([
            'name' => $validated['name'],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a step.
     */
    public function destroyStep($resolutionTab, RegistrationStep $step)
    {
        $this->resolveTab($resolutionTab, 'registration');
        $step->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Reorder steps.
     */
    public function reorderSteps(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'registration');
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:registration_steps,id',
            'handle_step_one_behavior' => 'nullable|string|in:auto_tick,none',
        ]);

        $order = $request->input('order');
        $behavior = $request->input('handle_step_one_behavior', 'none');

        DB::transaction(function () use ($order, $behavior) {
            // Handle Step 1 Change Logic
            if ($behavior === 'auto_tick') {
                $oldStepOne = RegistrationStep::registration()->orderBy('order')->first();
                $newStepOneId = $order[0] ?? null;

                if ($oldStepOne && $newStepOneId && $oldStepOne->id != $newStepOneId) {
                    // Find active employees (those who have the OLD Step 1 completed)
                    $employeesToUpdate = Employee::whereHas('registrationSteps', function($q) use ($oldStepOne) {
                        $q->where('registration_steps.id', $oldStepOne->id);
                    })->get();

                    // Sync the NEW Step 1 for them
                    foreach ($employeesToUpdate as $emp) {
                        $emp->registrationSteps()->syncWithoutDetaching([
                            $newStepOneId => ['completed_at' => now()]
                        ]);
                    }
                }
            }

            foreach ($order as $index => $id) {
                RegistrationStep::where('id', $id)->update(['order' => $index + 1]);
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * Update progress (Toggle a step for an employee).
     */
    public function updateProgress(Request $request, $resolutionTab, $employeeId)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        try {
            $employeeQuery = Employee::query();
            if (auth()->user()->can('manage-tickets')) {
                $employeeQuery->withoutGlobalScope('employerTenancy');
            }
            $employee = $employeeQuery->findOrFail($employeeId);

            $validated = $request->validate([
                'step_id' => 'required|exists:registration_steps,id',
                'completed' => 'required|boolean',
            ]);

            DB::beginTransaction();

            $stepName = \App\Models\RegistrationStep::find($validated['step_id'])?->name ?? 'ขั้นตอน #' . $validated['step_id'];

            if ($validated['completed']) {
                $employee->registrationSteps()->syncWithoutDetaching([
                    $validated['step_id'] => ['completed_at' => now()]
                ]);
            } else {
                $employee->registrationSteps()->detach($validated['step_id']);
            }

            // Bump the employer's production_orders for this resolution tab so the
            // sort in the index view (MAX(production_orders.updated_at) DESC) surfaces
            // this employer card to the top on next page load. Pivot sync alone doesn't
            // touch any order, so we update them directly here.
            \App\Models\ProductionOrder::where('employer_id', $employee->employer_id)
                ->where('resolution_tab_id', $this->currentTab->id)
                ->update(['updated_at' => now()]);

            // Log step change as activity on the employee
            ActivityLogHelper::logAction('update', ($validated['completed'] ? 'ติ๊กขั้นตอน' : 'ยกเลิกติ๊กขั้นตอน') . ' "' . $stepName . '" ลูกจ้าง ' . ($employee->employeeNameTh ?: $employee->employeeNameEn), Employee::class, $employee->id, [
                'step_id' => $validated['step_id'],
                'step_name' => $stepName,
                'completed' => $validated['completed'],
            ]);

            DB::commit();

            // --- Recalculate Stats for Response (Highest Step Logic) ---
            $allQuery = Employee::query();
            if (auth()->user()->can('manage-tickets')) {
                $allQuery->withoutGlobalScope('employerTenancy');
            }
            $allQuery->whereNull('deleted_at');

            if ($request->has('search') && $request->search) {
                $this->applySearchToQuery($allQuery, $request->search);
            }

            $allEmployees = $allQuery
                                    ->with('registrationSteps')
                                    ->get();

            $steps = RegistrationStep::registration()->orderBy('order')->get();
            // Determine step 1 ID for "Not Started" logic
            $stepOneId = $steps->sortBy('order')->first()?->id;

            // Global Stats
            $globalStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
            $globalNotStarted = 0;

            foreach ($allEmployees as $emp) {
                if ($emp->status === 'registration_cancelled') {
                    continue;
                }
                // Count Not Started
                if ($stepOneId && in_array($emp->status, ['registration_pending', 'registration_completed']) && !$emp->registrationSteps->contains('id', $stepOneId)) {
                    $globalNotStarted++;
                }

                $highest = $emp->registrationSteps->sortByDesc('order')->first();
                if ($highest && isset($globalStats[$highest->id])) {
                    $globalStats[$highest->id]++;
                }
            }

            // Also get daily check and appointments logic for toggle step
            $globalQuery = Employee::query();
            if (auth()->user()->can('manage-tickets')) {
                $globalQuery->withoutGlobalScope('employerTenancy');
            }
            $globalQuery->whereNull('deleted_at');
            if ($request->has('search') && $request->search) {
                $this->applySearchToQuery($globalQuery, $request->search);
            }
            $activeStatuses = ['registration_pending', 'registration_completed'];

            $globalAppointmentsPending = (clone $globalQuery)
                ->whereIn('status', $activeStatuses)
                ->whereNotNull('appointment_date')
                ->whereNull('appointment_completed_at')
                ->count();

            $globalAppointmentsCompleted = (clone $globalQuery)
                ->whereIn('status', $activeStatuses)
                ->whereNotNull('appointment_date')
                ->whereNotNull('appointment_completed_at')
                ->count();

            // Employer Stats
            $empQuery = Employee::query();
            if (auth()->user()->can('manage-tickets')) {
                $empQuery->withoutGlobalScope('employerTenancy');
            }
            $empQuery->whereNull('deleted_at');

            $employerEmployeesQuery = $empQuery->where('employer_id', $employee->employer_id)

                                        ->with('registrationSteps');

            if ($request->has('search') && $request->search) {
                 $employer = $employee->employer;
                 // If the employee relation is not loaded or null, fetch it
                 if (!$employer) {
                     $employerQuery = Employer::query();
                     if (auth()->user()->can('manage-tickets')) {
                         $employerQuery->withoutGlobalScope('employerTenancy');
                     }
                     $employer = $employerQuery->find($employee->employer_id);
                 }
                 if ($employer) {
                     $this->applyEmployerSearchToQuery($employerEmployeesQuery, $employer, $request->search);
                 }
            }

            $employerEmployees = $employerEmployeesQuery->get();

            $employerStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
            $employerNotStarted = 0;

            foreach ($employerEmployees as $emp) {
                 if ($emp->status === 'registration_cancelled') {
                     continue;
                 }
                 // Count Not Started
                 if ($stepOneId && in_array($emp->status, ['registration_pending', 'registration_completed']) && !$emp->registrationSteps->contains('id', $stepOneId)) {
                     $employerNotStarted++;
                 }

                 $highest = $emp->registrationSteps->sortByDesc('order')->first();
                 if ($highest && isset($employerStats[$highest->id])) {
                     $employerStats[$highest->id]++;
                 }
            }

            // Reload steps for correct HTML rendering
            $employee->load('registrationSteps');

            return response()->json([
                'success' => true,
                'html' => $this->getEmployeeCardHtml($employee),
                'globalStats' => $globalStats,
                'globalNotStarted' => $globalNotStarted,
                'globalAppointmentsPending' => $globalAppointmentsPending,
                'globalAppointmentsCompleted' => $globalAppointmentsCompleted,
                'employerStats' => $employerStats,
                'employerNotStarted' => $employerNotStarted,
                'employerId' => $employee->employer_id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating progress for employee {$employeeId}: " . $e->getMessage());
            // Return JSON even on fatal error
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        } catch (\Throwable $e) { // Catch fatal errors (PHP 7+)
             DB::rollBack();
             Log::error("Fatal Error updating progress for employee {$employeeId}: " . $e->getMessage());
             return response()->json([
                 'success' => false,
                 'message' => 'Fatal Error: ' . $e->getMessage()
             ], 500);
        }
    }

    /**
     * Store a custom ad-hoc field for an employee.
     */
    public function storeCustomField(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $validated = $request->validate([
            'field_name' => 'required|string|max:255',
            'field_type' => 'required|in:text,date,file',
            'field_value' => 'nullable|string',
            'field_file' => 'nullable|file|max:102400', // 100MB
        ]);

        $data = [
            'employee_id' => $employee->id,
            'field_name' => $validated['field_name'],
            'field_type' => $validated['field_type'],
        ];

        if ($validated['field_type'] === 'file' && $request->hasFile('field_file')) {
             $file = $request->file('field_file');
             $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
             $path = $file->storeAs("employee_files/{$employee->employer_id}/custom", $filename, 'public');
             $data['file_path'] = $path;
        } else {
             $data['field_value'] = $validated['field_value'];
             if ($validated['field_type'] === 'date' && $request->has('field_date_value')) {
                 $data['field_value'] = $request->field_date_value;
             }
        }

        $field = EmployeeCustomField::create($data);

        if ($request->ajax()) {
            // Return the full list of custom fields for this employee to refresh the view
            // OR just the new field. Let's return the updated list logic as per the JS helper.
            $employee->load('customFields');
            return response()->json([
                'success' => true,
                'message' => 'Field added successfully.',
                'employee' => $employee, // Contains updated customFields
                'newField' => $field
            ]);
        }

        return back()->with('success', 'Field added successfully.');
    }

    /**
     * Store a custom field for an EMPLOYER.
     */
    public function storeEmployerCustomField(Request $request, $resolutionTab, Employer $employer)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) { // Assuming same permission
            abort(403);
        }

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
             // Store in employer specific folder
             $path = $file->storeAs("employer_files/{$employer->id}/custom", $filename, 'public');
             $data['file_path'] = $path;
        } else {
             $data['field_value'] = $validated['field_value'];
             if ($validated['field_type'] === 'date' && $request->has('field_date_value')) {
                 $data['field_value'] = $request->field_date_value;
             }
        }

        $field = $employer->customFields()->create($data);

        if ($request->ajax()) {
            $employer->load('customFields');
            return response()->json([
                'success' => true,
                'message' => 'Field added successfully.',
                'employer' => $employer,
                'newField' => $field
            ]);
        }

        return back()->with('success', 'Field added successfully.');
    }

    /**
     * Update employer registration resolution status and note.
     */
    public function updateResolutionStatus(Request $request, $resolutionTab, Employer $employer)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) { // Assuming 'edit-employees' or similar is enough
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'nullable|in:preparing,waiting,ready',
            'note' => 'nullable|string',
        ]);

        $data = [];
        if ($request->has('status')) {
            $data['registration_resolution_status'] = $validated['status'];
        }
        if ($request->has('note')) {
            $data['registration_resolution_note'] = $validated['note'];
        }

        $employer->update($data);

        return response()->json(['success' => true]);
    }

    /**
     * Update employer registration custom field.
     */
    public function updateEmployerCustomField(Request $request, $resolutionTab, ProductionCustomField $field)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        // We only allow updating the field name, or the value/file
        // Type cannot be changed easily without UI complexity, so we assume type stays same.
        // Actually, for file upload, the user might want to re-upload.

        $rules = [
            'field_name' => 'required|string|max:255',
        ];

        if ($field->field_type === 'file') {
            // For file type, we might have a new file
            // Note: The form input name in edit form for file is not standard yet in our JS
            // But let's assume we use 'field_file' again if we add file input to edit form.
            $rules['field_file'] = 'nullable|file|max:102400'; // 100MB
        } else {
             $rules['field_value'] = 'nullable|string';
             if ($field->field_type === 'date') {
                 $rules['field_date_value'] = 'nullable|date';
             }
        }

        $validated = $request->validate($rules);

        $data = [
            'field_name' => $validated['field_name'],
        ];

        if ($field->field_type === 'file') {
             if ($request->hasFile('field_file')) {
                 // Delete old file
                 if ($field->file_path) {
                     Storage::disk('public')->delete($field->file_path);
                 }

                 $file = $request->file('field_file');
                 $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                 // Store in employer specific folder. Getting employer ID via relation.
                 $employerId = $field->model_id; // Assuming morphed to Employer
                 $path = $file->storeAs("employer_files/{$employerId}/custom", $filename, 'public');
                 $data['file_path'] = $path;
                 // We can also update field_value to store original name or description if passed
                 if ($request->filled('field_value')) {
                     $data['field_value'] = $request->field_value;
                 }
             } elseif ($request->filled('field_value')) {
                 // Just updating description
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

    public function destroyCustomField($resolutionTab, EmployeeCustomField $field)
    {
        $this->resolveTab($resolutionTab, 'registration');
        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        if ($field->field_type === 'file' && $field->file_path) {
            Storage::disk('public')->delete($field->file_path);
        }
        $field->delete();
        return back()->with('success', 'Field removed.');
    }

    public function destroyEmployerCustomField($resolutionTab, ProductionCustomField $field)
    {
        $this->resolveTab($resolutionTab, 'registration');
        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        if ($field->field_type === 'file' && $field->file_path) {
            Storage::disk('public')->delete($field->file_path);
        }
        $field->delete();
        return back()->with('success', 'Field removed.');
    }

    /**
     * Calculate global and employer-specific stats.
     *
     * @param int|null $employerId
     * @param Request $request
     * @return array
     */
    private function getStats($employerId = null, Request $request = null)
    {
        // Define relevant statuses
        $activeStatuses = ['registration_pending', 'registration_completed'];
        $allStatuses = ['registration_pending', 'registration_completed', 'registration_cancelled'];

        // Get Steps info
        $steps = RegistrationStep::registration()->orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;

        // --- GLOBAL STATS ---
        // Use standard query to respect any relevant scopes (like tenancy)
        $globalQuery = Employee::query();
        if (auth()->user()->can('manage-tickets')) {
            $globalQuery->withoutGlobalScope('employerTenancy');
        }

        if ($request && $request->has('search') && $request->search) {
            $this->applySearchToQuery($globalQuery, $request->search);
        }

        // 1. Total Employees (Active)
        $globalTotal = (clone $globalQuery)->whereIn('status', $activeStatuses)->count();

        // 2. Not Started
        // Active AND (Does not have step 1)
        $globalNotStarted = 0;
        if ($stepOneId) {
            $globalNotStarted = (clone $globalQuery)
                ->whereIn('status', $activeStatuses)
                ->whereDoesntHave('registrationSteps', function ($q) use ($stepOneId) {
                    $q->where('registration_steps.id', $stepOneId);
                })->count();
        }

        // 3. Cancelled
        $globalCancelled = (clone $globalQuery)->where('status', 'registration_cancelled')->count();

        // 4. Saved (Completed)
        $globalSaved = (clone $globalQuery)->where('status', 'registration_completed')->count();

        // 5. Total Employers (who have any registration employees)
        $globalEmployers = (clone $globalQuery)->whereIn('status', $allStatuses)->distinct('employer_id')->count('employer_id');

        // 6. Total Biometrics Collected
        $globalBiometrics = (clone $globalQuery)->whereIn('status', $activeStatuses)->whereNotNull('biometrics_collected_at')->count();

        // 8. Appointments Pending
        $globalAppointmentsPending = (clone $globalQuery)
            ->whereIn('status', $activeStatuses)
            ->whereNotNull('appointment_date')
            ->whereNull('appointment_completed_at')
            ->count();

        // 9. Appointments Completed
        $globalAppointmentsCompleted = (clone $globalQuery)
            ->whereIn('status', $activeStatuses)
            ->whereNotNull('appointment_date')
            ->whereNotNull('appointment_completed_at')
            ->count();

        $stats = [
            'global' => [
                'total' => $globalTotal,
                'not_started' => $globalNotStarted,
                'cancelled' => $globalCancelled,
                'saved' => $globalSaved,
                'employers_count' => $globalEmployers,
                'biometrics_collected' => $globalBiometrics,
                'appointments_pending' => $globalAppointmentsPending,
                'appointments_completed' => $globalAppointmentsCompleted
            ]
        ];

        // --- EMPLOYER STATS (If requested) ---
        if ($employerId) {
            $empQuery = Employee::query();
            if (auth()->user()->can('manage-tickets')) {
                $empQuery->withoutGlobalScope('employerTenancy');
            }
            $empQuery->where('employer_id', $employerId);

            if ($request && $request->has('search') && $request->search) {
                 $employerQuery = Employer::query();
                 if (auth()->user()->can('manage-tickets')) {
                     $employerQuery->withoutGlobalScope('employerTenancy');
                 }
                 $employer = $employerQuery->find($employerId);
                 if ($employer) {
                     $this->applyEmployerSearchToQuery($empQuery, $employer, $request->search);
                 }
            }

            $empTotal = (clone $empQuery)->whereIn('status', $activeStatuses)->count();

            $empNotStarted = 0;
            if ($stepOneId) {
                $empNotStarted = (clone $empQuery)
                    ->whereIn('status', $activeStatuses)
                    ->whereDoesntHave('registrationSteps', function ($q) use ($stepOneId) {
                        $q->where('registration_steps.id', $stepOneId);
                    })->count();
            }

            $empCancelled = (clone $empQuery)->where('status', 'registration_cancelled')->count();
            $empSaved = (clone $empQuery)->where('status', 'registration_completed')->count();
            $empBiometrics = (clone $empQuery)->whereIn('status', $activeStatuses)->whereNotNull('biometrics_collected_at')->count();

            $stats['employer'] = [
                'id' => $employerId,
                'total' => $empTotal,
                'not_started' => $empNotStarted,
                'cancelled' => $empCancelled,
                'saved' => $empSaved,
                'biometrics_collected' => $empBiometrics,
            ];
        }

        return $stats;
    }

    /**
     * Update Biometrics Collection Status and File.
     */
    public function updateBiometrics(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        // Validate File
        $request->validate([
            'biometrics_file' => 'required|file|max:10240', // 10MB
        ]);

        if ($request->hasFile('biometrics_file')) {
            // Delete old file if exists (optional, but good practice)
            if ($employee->employee_doc_9) {
                Storage::disk('public')->delete($employee->employee_doc_9);
            }

            $file = $request->file('biometrics_file');
            $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("employee_files/{$employee->employer_id}", $filename, 'public');

            // Update Employee: Set Doc 9 AND Biometrics Timestamp
            $employee->update([
                'employee_doc_9' => $path,
                'biometrics_collected_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Biometrics updated successfully.',
                'html' => $this->getEmployeeCardHtml($employee),
                'stats' => $this->getStats($employee->employer_id, $request)
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded.'], 400);
    }

    /**
     * Toggle Biometrics Collected Status (without file).
     */
    public function toggleBiometrics(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $isCollected = $employee->biometrics_collected_at !== null;

        if ($isCollected) {
            $employee->update(['biometrics_collected_at' => null]);
            $message = 'Biometrics marked as not collected.';
        } else {
            $employee->update(['biometrics_collected_at' => now()]);
            $message = 'Biometrics marked as collected.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'collected' => !$isCollected,
            'html' => $this->getEmployeeCardHtml($employee),
            'stats' => $this->getStats($employee->employer_id, $request)
        ]);
    }

    /**
     * Apply Search Filter to Global Query
     */
    private function applySearchToQuery($query, $search)
    {
        $search = trim($search);

        // Support ID:123 format for direct employee ID lookup
        if (preg_match('/^ID:\s*(\d+)$/i', $search, $matches)) {
            return $query->where('id', (int) $matches[1]);
        }

        $cleanedSearch = str_replace(' ', '', $search);

        return $query->where(function($q) use ($search, $cleanedSearch) {
            $q->where('employeeNameTh', 'like', "%{$search}%")
              ->orWhere('employeeNameEn', 'like', "%{$search}%")
              ->orWhere('name_suffix', 'like', "%{$search}%")
              ->orWhere('employeePassport', 'like', "%{$search}%")
              ->orWhere('employeeWorkPermit', 'like', "%{$search}%")
              ->orWhere('employee_id_number', 'like', "%{$search}%")
              ->orWhere('name_list_number', 'like', "%{$search}%")
              ->orWhere('pinkCardNo', 'like', "%{$search}%")
              ->orWhere('request_number', 'like', "%{$search}%")
              ->orWhere('registration_request_number', 'like', "%{$search}%")
              ->orWhere('employer_employee_id', 'like', "%{$search}%")
              // Robust Name Search (Ignore Spaces)
              ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
              ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])

              ->orWhereHas('employer', function($q2) use ($search, $cleanedSearch) {
                  $q2->where('employerNameTh', 'like', "%{$search}%")
                     ->orWhere('employerNameEn', 'like', "%{$search}%")
                     ->orWhereRaw("REPLACE(employerNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                     ->orWhereRaw("REPLACE(employerNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                     ->orWhereHas('jobOwner', function($q3) use ($search, $cleanedSearch) {
                         $q3->where('name', 'like', "%{$search}%")
                            ->orWhereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
                     })
                     ->orWhere(function($addrQ) use ($search) {
                         $addrQ->filterByAddress($search);
                     });
              });
        });
    }

    /**
     * Apply Search Filter to Specific Employer's Employees
     */
    private function applyEmployerSearchToQuery($query, $employer, $search)
    {
        $search = trim($search);
        $cleanedSearch = str_replace(' ', '', $search);

        // Check if Employer matches (including robust space check)
        $employerMatches = false;

        $empNameThClean = str_replace(' ', '', $employer->employerNameTh ?? '');
        $empNameEnClean = str_replace(' ', '', $employer->employerNameEn ?? '');

        if (stripos($employer->employerNameTh, $search) !== false ||
            stripos($employer->employerNameEn, $search) !== false ||
            stripos($empNameThClean, $cleanedSearch) !== false ||
            stripos($empNameEnClean, $cleanedSearch) !== false) {
            $employerMatches = true;
        }

        if (!$employerMatches && $employer->jobOwner && stripos($employer->jobOwner->name, $search) !== false) {
            $employerMatches = true;
        }

        // Check address match
        if (!$employerMatches) {
             $addressMatch = $employer->addresses()->where(function($q) use ($search) {
                 $q->where('addrProvince', 'like', "%{$search}%")
                   ->orWhere('addrDistrict', 'like', "%{$search}%");
             })->exists();

             if ($addressMatch) {
                 $employerMatches = true;
             }
        }

        if ($employerMatches) {
            // If the employer itself matches the search term, we do NOT filter employees by name.
            // We show ALL employees for this employer (subject to other status filters).
            return $query;
        } else {
            // Employer does not match, so user is searching for specific employee
            return $query->where(function($q) use ($search, $cleanedSearch) {
                $q->where('employeeNameTh', 'like', "%{$search}%")
                  ->orWhere('employeeNameEn', 'like', "%{$search}%")
                  ->orWhere('name_suffix', 'like', "%{$search}%")
                  ->orWhere('employeePassport', 'like', "%{$search}%")
                  ->orWhere('employeeWorkPermit', 'like', "%{$search}%")
                  ->orWhere('employee_id_number', 'like', "%{$search}%")
                  ->orWhere('name_list_number', 'like', "%{$search}%")
                  ->orWhere('pinkCardNo', 'like', "%{$search}%")
                  ->orWhere('request_number', 'like', "%{$search}%")
                  ->orWhere('registration_request_number', 'like', "%{$search}%")
                  ->orWhere('employer_employee_id', 'like', "%{$search}%")
                  // Robust Name Search
                  ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                  ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
            });
        }
    }

    /**
     * API: Update Appointment Date & Location
     */
    public function updateAppointment(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $request->validate([
            'appointment_date' => 'nullable|date',
            'appointment_location' => 'nullable|string|max:255',
        ]);

        $data = [];
        $isUpdated = false;

        if ($request->has('appointment_date')) {
            $data['appointment_date'] = $request->appointment_date;
            if ($employee->appointment_date != $request->appointment_date) {
                $isUpdated = true;
            }
        }
        if ($request->has('appointment_location')) {
            $data['appointment_location'] = $request->appointment_location;
            if ($employee->appointment_location != $request->appointment_location) {
                $isUpdated = true;
            }
        }

        if ($isUpdated) {
            $data['appointment_updated_by'] = auth()->id();
            $data['appointment_updated_at'] = now();
        }

        $employee->update($data);

        return response()->json([
            'success' => true,
            'appointment_updated_by_name' => auth()->user()->name,
            'appointment_updated_at_human' => now()->diffForHumans()
        ]);
    }

    /**
     * API: Toggle Appointment Complete
     */
    public function toggleAppointmentComplete(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        if ($employee->appointment_completed_at) {
            $employee->update(['appointment_completed_at' => null]);
        } else {
            $employee->update(['appointment_completed_at' => now()]);
        }

        return response()->json(['success' => true, 'completed_at' => $employee->appointment_completed_at]);
    }

    /**
     * Build a {key => value} map for a resolution group's auto-settings, with per-tab keys
     * ({key}__tab_{id}) taking precedence over the legacy global key.
     */
    protected function resolvePerTabSettings($settingsCollection, string $group, int $tabId): array
    {
        $byKey = $settingsCollection->pluck('value', 'key')->toArray();
        $suffix = "__tab_{$tabId}";

        $resolved = [];
        foreach (['visa_expiry', 'work_permit_expiry', 'mou_group'] as $field) {
            $globalKey = "{$group}_auto_{$field}";
            $perTabKey = $globalKey . $suffix;
            $resolved[$globalKey] = $byKey[$perTabKey] ?? ($byKey[$globalKey] ?? null);
        }

        foreach ($byKey as $k => $v) {
            if (!array_key_exists($k, $resolved) && !str_contains($k, '__tab_')) {
                $resolved[$k] = $v;
            }
        }

        return $resolved;
    }

    /**
     * API: Update Resolution Auto-Settings (per-tab)
     */
    public function updateResolutionSettings(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('manage-settings')) {
            abort(403);
        }

        $request->validate([
            'auto_work_permit_expiry' => 'nullable|date',
            'auto_visa_expiry' => 'nullable|date',
            'auto_mou_group' => 'nullable|string|max:255',
        ]);

        $group = 'registration';
        $tabId = $this->currentTab->id;
        $suffix = "__tab_{$tabId}";

        SystemSetting::updateOrCreate(
            ['key' => "{$group}_auto_work_permit_expiry{$suffix}"],
            ['value' => $request->auto_work_permit_expiry, 'group' => $group]
        );

        SystemSetting::updateOrCreate(
            ['key' => "{$group}_auto_visa_expiry{$suffix}"],
            ['value' => $request->auto_visa_expiry, 'group' => $group]
        );

        SystemSetting::updateOrCreate(
            ['key' => "{$group}_auto_mou_group{$suffix}"],
            ['value' => $request->auto_mou_group, 'group' => $group]
        );

        return response()->json(['success' => true]);
    }

    /**
     * API: Update Notification Settings
     */
    public function updateNotificationSettings(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('manage-settings')) {
            abort(403);
        }

        $request->validate([
            'notify_days_advance' => 'required|integer|min:0|max:365'
        ]);

        NotificationSetting::updateOrCreate(
            ['notification_type' => 'registration_appointment'],
            ['days_before_expiry' => $request->notify_days_advance, 'is_enabled' => true]
        );

        return response()->json(['success' => true]);
    }

    /**
     * API: Get Calendar Data (Counts per day)
     */
    public function getCalendarData(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'registration');

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $query = Employee::query()
            ->where('resolution_tab_id', $this->currentTab->id);
        if (auth()->user()->can('manage-tickets')) {
            $query->withoutGlobalScope('employerTenancy');
        }

        $counts = $query->select(DB::raw('DATE(appointment_date) as date'), DB::raw('count(*) as count'))
            ->whereBetween('appointment_date', [$start, $end])

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
    public function getAppointmentsByDate(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'registration');

        $request->validate(['date' => 'required|date']);
        $date = Carbon::parse($request->date);

        $query = Employee::query()
            ->where('resolution_tab_id', $this->currentTab->id);
        if (auth()->user()->can('manage-tickets')) {
            $query->withoutGlobalScope('employerTenancy');
        }

        $employees = $query->whereDate('appointment_date', $date)
            ->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled'])
            ->whereNull('appointment_completed_at')
            ->with(['employer', 'registrationSteps'])
            ->orderBy('appointment_date')
            ->get();

        $steps = RegistrationStep::registration()->orderBy('order')->get();

        $html = view('production.registration.partials.day_appointments_list', compact('employees', 'steps'))->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Fetch Trash (Deleted Employees with Registration Status)
     */
    public function fetchTrash(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'registration');

        $query = Employee::onlyTrashed()
            ->with(['employer' => fn($q) => $q->withTrashed(), 'registrationSteps'])
            ->where('resolution_tab_id', $this->currentTab->id)
            ->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled'])
            ->latest('deleted_at');

        if ($request->has('search') && $request->search) {
             $search = $request->search;
             // Apply search logic manually to support withTrashed() on relations
             $query->where(function($q) use ($search) {
                 $q->where('employeeNameTh', 'like', "%{$search}%")
                   ->orWhere('employeeNameEn', 'like', "%{$search}%")
                   ->orWhere('name_suffix', 'like', "%{$search}%")
                   ->orWhere('employeePassport', 'like', "%{$search}%")
                   ->orWhere('employeeWorkPermit', 'like', "%{$search}%")
                   ->orWhere('employee_id_number', 'like', "%{$search}%")
                   ->orWhere('name_list_number', 'like', "%{$search}%")
                   ->orWhere('pinkCardNo', 'like', "%{$search}%")
                   ->orWhere('request_number', 'like', "%{$search}%")
                   ->orWhere('registration_request_number', 'like', "%{$search}%")
                   ->orWhere('employer_employee_id', 'like', "%{$search}%")
                   ->orWhereHas('employer', function($q2) use ($search) {
                       $q2->withTrashed()
                          ->where('employerNameTh', 'like', "%{$search}%")
                          ->orWhere('employerNameEn', 'like', "%{$search}%");
                   });
             });
        }

        $items = $query->paginate(10);

        return view('production.registration.partials.trash_list', compact('items'));
    }

    /**
     * Restore Trash
     */
    public function restoreTrash(Request $request, $resolutionTab, $id)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $employee = Employee::onlyTrashed()->with(['employer' => fn($q) => $q->withTrashed()])->findOrFail($id);

        // If employer is trashed, restore it too to avoid orphaned record issues
        if ($employee->employer && $employee->employer->trashed()) {
             $employee->employer->restore();
        }

        $employee->restore();

        return response()->json(['success' => true]);
    }

    /**
     * Helper to render employee card HTML.
     */
    private function getEmployeeCardHtml(Employee $employee)
    {
        $steps = RegistrationStep::registration()
            ->where('resolution_tab_id', $this->currentTab->id)
            ->orderBy('order')->get();
        return view('production.registration._employee_card', array_merge([
            'employee' => $employee,
            'steps' => $steps,
            'isHistory' => false
        ], $this->getTabViewData('registration')))->render();
    }

    public function updateRemarks(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'registration');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $validated = $request->validate([
            'remarks' => 'nullable|string'
        ]);

        $employee->update(['registration_remarks' => $validated['remarks']]);

        return response()->json([
            'success' => true,
            'remarks' => $employee->registration_remarks,
            'message' => 'Remarks updated successfully.'
        ]);
    }

    public function toggleOperator(Request $request, $resolutionTab, $employeeId)
    {
        $this->resolveTab($resolutionTab, 'registration');
        $employee = Employee::findOrFail($employeeId);

        // Check for specific user assignment
        if ($request->has('operator_id') || $request->has('custom_operator_name')) {
            $userId = $request->input('operator_id');
            $customOperatorName = $request->input('custom_operator_name');

            // If custom operator name is provided (external operator)
            if (!empty($customOperatorName)) {
                $employee->update([
                    'operator_id' => null,
                    'custom_operator_name' => $customOperatorName
                ]);
                $message = 'Operator assigned to external: ' . $customOperatorName;
            }
            // If explicit null or empty and no custom name, remove operator
            else if (empty($userId)) {
                $employee->update([
                    'operator_id' => null,
                    'custom_operator_name' => null
                ]);
                $message = 'Operator unassigned.';
            } else {
                $user = User::find($userId);
                if ($user) {
                    $employee->update([
                        'operator_id' => $user->id,
                        'custom_operator_name' => null
                    ]);
                    $message = 'Operator assigned to ' . $user->name;
                } else {
                     return response()->json(['success' => false, 'message' => 'User not found'], 404);
                }
            }
        } else {
            // Legacy Toggle Behavior (Toggle Me)
            $userId = auth()->id();
            if ($employee->operator_id === $userId && empty($employee->custom_operator_name)) {
                $employee->update(['operator_id' => null, 'custom_operator_name' => null]);
                $message = 'Operator unassigned.';
            } else {
                $employee->update(['operator_id' => $userId, 'custom_operator_name' => null]);
                $message = 'Operator assigned to ' . auth()->user()->name;
            }
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'html' => $this->getEmployeeCardHtml($employee)
        ]);
    }

}
