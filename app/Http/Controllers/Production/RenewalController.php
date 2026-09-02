<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Helpers\ActivityLogHelper;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionOrder;
use App\Models\SystemConfig;
use App\Models\RegistrationStep;
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

class RenewalController extends Controller
{
    use AddressFilterTrait, HasResolutionTab;

    public function __construct()
    {
        $this->middleware('auth');
        // Matches WorkflowController/ProductionController's role gate —
        // employer/labor-* accounts have no business in this menu; caretaker
        // keeps access for their routine step-tick/employee work.
        $this->middleware('role:admin|super-admin|staff|caretaker');
    }

    /**
     * Display the main dashboard for Renewal Resolution.
     */
    /**
     * Update employer renewal resolution note (inline).
     */
    public function updateResolutionNote(Request $request, $resolutionTab, Employer $employer)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $validated = $request->validate([
            'note' => 'nullable|string',
        ]);

        $employer->update(['renewal_resolution_note' => $validated['note']]);

        return response()->json(['success' => true]);
    }

    public function dashboard(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        $days = \App\Models\NotificationSetting::where('notification_type', 'renewal_appointment')->first()->days_before_expiry ?? 3;
        $start = \Carbon\Carbon::now()->startOfDay();
        $end = \Carbon\Carbon::now()->addDays($days)->endOfDay();

        $query = Employee::query()
            ->where('resolution_tab_id', $this->currentTab->id)
            ->whereIn('status', ['renewal_pending', 'renewal_completed'])
            ->whereNotNull('appointment_date')
            ->whereBetween('appointment_date', [$start, $end])
            ->whereNull('appointment_completed_at')
            ->with(['employer']);

        if (auth()->user()->can('manage-tickets')) {
            $query->withoutGlobalScope('employerTenancy');
        }

        $upcomingAppointments = $query->orderBy('appointment_date', 'asc')->get();

        return view('production.renewal.dashboard', array_merge(compact('upcomingAppointments'), $this->getTabViewData('renewal')));
    }

    public function index(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        $steps = RegistrationStep::renewal()->where('resolution_tab_id', $this->currentTab->id)->orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;
        $lastStepId = $steps->sortByDesc('order')->first()?->id;

        // --- 1. Global Stats Query (No Fetching All Models) ---
        $statsQuery = Employee::query()
            ->where('resolution_tab_id', $this->currentTab->id)
            ->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled']);

        if (auth()->user()->can('manage-tickets')) {
            $statsQuery->withoutGlobalScope('employerTenancy');
        }

        if ($request->has('search') && $request->search) {
            $this->applySearchToQuery($statsQuery, $request->search);
        }

        // Apply operator filter
        if ($request->has('operator_filter') && $request->operator_filter) {
            $opFilter = $request->operator_filter;
            if ($opFilter === 'external') {
                $statsQuery->whereNotNull('custom_operator_name')->where('custom_operator_name', '!=', '');
            } else {
                $statsQuery->where('operator_id', $opFilter);
            }
        }

        // Apply insurance filter
        if ($request->has('insurance_filter') && $request->insurance_filter) {
            if ($request->insurance_filter === 'none') {
                 $statsQuery->where(function($q) {
                     $q->whereNull('insurance_type')->orWhere('insurance_type', '');
                 });
            } else {
                 $statsQuery->where('insurance_type', $request->insurance_filter);
            }
        }

        // Dual-listed employees (Registration-Resolution employees also
        // usable in this tab via EmployeeRenewalLink) — folded into every
        // top-of-page count below so the badges aren't undercounted. Same
        // search/operator/insurance filters as $statsQuery; status-based
        // logic uses the LINK's own status, never the employee's real
        // (registration_*) status.
        $dualTabId = $this->currentTab->id;
        $dualLinkedQuery = Employee::whereHas('renewalLinks', function ($q) use ($dualTabId) {
            $q->where('resolution_tab_id', $dualTabId);
        })->with(['renewalLinks' => function ($q) use ($dualTabId) {
            $q->where('resolution_tab_id', $dualTabId);
        }, 'registrationSteps']);
        if (auth()->user()->can('manage-tickets')) {
            $dualLinkedQuery->withoutGlobalScope('employerTenancy');
        }
        if ($request->has('search') && $request->search) {
            $this->applySearchToQuery($dualLinkedQuery, $request->search);
        }
        if ($request->has('operator_filter') && $request->operator_filter) {
            if ($request->operator_filter === 'external') {
                $dualLinkedQuery->whereNotNull('custom_operator_name')->where('custom_operator_name', '!=', '');
            } else {
                $dualLinkedQuery->where('operator_id', $request->operator_filter);
            }
        }
        if ($request->has('insurance_filter') && $request->insurance_filter) {
            if ($request->insurance_filter === 'none') {
                $dualLinkedQuery->where(function ($q) {
                    $q->whereNull('insurance_type')->orWhere('insurance_type', '');
                });
            } else {
                $dualLinkedQuery->where('insurance_type', $request->insurance_filter);
            }
        }
        $dualLinked = $dualLinkedQuery->get()
            ->filter(fn($e) => $e->renewalLinks->first() !== null)
            ->each(fn($e) => $e->setRelation('activeRenewalLink', $e->renewalLinks->first()));

        $dualActiveOnly = $dualLinked->filter(fn($e) => in_array($e->activeRenewalLink->status, ['renewal_pending', 'renewal_completed'], true));
        $dualTotalCancelled = $dualLinked->filter(fn($e) => $e->activeRenewalLink->status === 'renewal_cancelled')->count();
        $dualTotalSaved = $dualLinked->filter(fn($e) => $e->activeRenewalLink->status === 'renewal_completed')->count();
        $dualNotStarted = $stepOneId ? $dualActiveOnly->filter(fn($e) => !$e->registrationSteps->contains('id', $stepOneId))->count() : 0;
        $dualTotalNotScheduled = $dualActiveOnly->filter(fn($e) => is_null($e->appointment_date))->count();
        $dualAppointmentsPending = $dualLinked->filter(fn($e) => !is_null($e->appointment_date) && is_null($e->appointment_completed_at))->count();
        $dualAppointmentsCompleted = $dualLinked->filter(fn($e) => !is_null($e->appointment_date) && !is_null($e->appointment_completed_at))->count();
        $dualEmployerIds = $dualLinked->pluck('employer_id')->unique();

        $dualFilteredForTotal = $dualLinked;
        if ($request->has('filter') && $request->filter) {
            $filterVal = $request->filter;
            $dualFilteredForTotal = $dualLinked->filter(function ($e) use ($filterVal, $stepOneId) {
                $link = $e->activeRenewalLink;
                if ($filterVal === 'not_started') {
                    if (!in_array($link->status, ['renewal_pending', 'renewal_completed'], true)) return false;
                    return !$e->registrationSteps->contains('id', $stepOneId);
                }
                if ($filterVal === 'saved') return $link->status === 'renewal_completed';
                if ($filterVal === 'cancelled') return $link->status === 'renewal_cancelled';
                if ($filterVal === 'total_appointments') return !is_null($e->appointment_date);
                if ($filterVal === 'appointment_not_scheduled') {
                    return in_array($link->status, ['renewal_pending', 'renewal_completed'], true) && is_null($e->appointment_date);
                }
                if ($filterVal === 'appointment_pending') return !is_null($e->appointment_date) && is_null($e->appointment_completed_at);
                if ($filterVal === 'appointment_completed') return !is_null($e->appointment_date) && !is_null($e->appointment_completed_at);
                if (is_numeric($filterVal)) {
                    if ($link->status === 'renewal_cancelled') return false;
                    return $e->registrationSteps->contains('id', (int) $filterVal);
                }
                return true;
            });
        }

        // Load resolution settings + targets up front so the filter cases
        // below (and the count subqueries / view) can reuse them.
        // Per-tab keys ({key}__tab_{id}) take precedence over legacy global keys.
        $resolutionSettingsRaw = SystemSetting::where('group', 'renewal')->get();
        $resolutionSettings = $this->resolvePerTabSettings($resolutionSettingsRaw, 'renewal', $this->currentTab->id);
        $renewalTargets = [
            'visa' => $resolutionSettings['renewal_auto_visa_expiry'] ?? null,
            'wp'   => $resolutionSettings['renewal_auto_work_permit_expiry'] ?? null,
        ];
        $renewalStatuses = [
            'pending'   => 'renewal_pending',
            'completed' => 'renewal_completed',
            'cancelled' => 'renewal_cancelled',
        ];

        // Apply additional filter for total employees if present
        $totalEmployeesQuery = clone $statsQuery;
        if ($request->has('filter') && $request->filter) {
            $filter = $request->filter;
            if ($filter === 'not_started') {
                 $totalEmployeesQuery->whereIn('status', ['renewal_pending', 'renewal_completed'])
                   ->whereDoesntHave('registrationSteps', function($sq) use ($stepOneId) {
                       $sq->where('registration_steps.id', $stepOneId);
                   });
            } elseif ($filter === 'saved') {
                 $totalEmployeesQuery->where('status', 'renewal_completed');
            } elseif ($filter === 'cancelled') {
                 $totalEmployeesQuery->where('status', 'renewal_cancelled');
            } elseif ($filter === 'total_appointments') {
                 $totalEmployeesQuery->whereNotNull('appointment_date');
            } elseif ($filter === 'appointment_not_scheduled') {
                 $totalEmployeesQuery->whereIn('status', ['renewal_pending', 'renewal_completed'])
                       ->whereNull('appointment_date');
            } elseif ($filter === 'appointment_pending') {
                 $totalEmployeesQuery->whereNotNull('appointment_date')
                       ->whereNull('appointment_completed_at');
            } elseif ($filter === 'appointment_completed') {
                 $totalEmployeesQuery->whereNotNull('appointment_date')
                       ->whereNotNull('appointment_completed_at');
            } elseif (is_numeric($filter)) {
                 $totalEmployeesQuery->where('status', '!=', 'renewal_cancelled')
                    ->whereHas('registrationSteps', function($s) use ($filter) {
                        $s->where('registration_steps.id', $filter);
                    });
            }
        }

        // Clone for different counts — each folds in the dual-listed
        // contribution computed above (see $dualLinked and friends).
        $totalEmployees = $totalEmployeesQuery->count() + $dualFilteredForTotal->count();
        $totalCancelled = (clone $statsQuery)->where('status', 'renewal_cancelled')->count() + $dualTotalCancelled;
        $totalSaved = (clone $statsQuery)->where('status', 'renewal_completed')->count() + $dualTotalSaved;

        $notStartedCount = 0;
        if ($stepOneId) {
            $notStartedCount = (clone $statsQuery)
                ->whereIn('status', ['renewal_pending', 'renewal_completed'])
                ->whereDoesntHave('registrationSteps', function ($q) use ($stepOneId) {
                    $q->where('registration_steps.id', $stepOneId);
                })->count() + $dualNotStarted;
        }

        // Step Stats (Optimized via SQL) — 24h grace rule: drop cancelled
        // outright, drop renewal_completed rows whose finalize is older
        // than 24h so the top-of-page step badges decrement once the
        // countdown ends. Green cards stay in the list by design; only
        // the aggregate counters trim. Same grace rule applied to the
        // dual-listed contribution below, using the LINK's own status/
        // resolution_completed_at.
        $stepStatsQuery = (clone $statsQuery)
            ->where('status', '!=', 'renewal_cancelled')
            ->where(function ($q) {
                $q->where('status', '!=', 'renewal_completed')
                  ->orWhereNull('resolution_completed_at')
                  ->orWhere('resolution_completed_at', '>=', now()->subHours(24));
            });
        $stepStats = $this->getGlobalStepStats($stepStatsQuery, $steps);

        $dualStepStatsBase = $dualLinked->filter(function ($e) {
            $link = $e->activeRenewalLink;
            if ($link->status === 'renewal_cancelled') return false;
            if ($link->status === 'renewal_completed' && $link->resolution_completed_at && $link->resolution_completed_at->lt(now()->subHours(24))) return false;
            return true;
        });
        foreach ($dualStepStatsBase as $dualEmp) {
            $highest = $dualEmp->registrationSteps->sortByDesc('order')->first();
            if ($highest && isset($stepStats[$highest->id])) {
                $stepStats[$highest->id]++;
            }
        }

        // Total Appointments (use same base query with search/operator filters)
        $appointmentsBaseQuery = clone $statsQuery;

        $totalNotScheduled = (clone $appointmentsBaseQuery)
            ->whereIn('status', ['renewal_pending', 'renewal_completed'])
            ->whereNull('appointment_date')
            ->count() + $dualTotalNotScheduled;

        $totalAppointmentsPending = (clone $appointmentsBaseQuery)
            ->whereNotNull('appointment_date')
            ->whereNull('appointment_completed_at')
            ->count() + $dualAppointmentsPending;

        $totalAppointmentsCompleted = (clone $appointmentsBaseQuery)
            ->whereNotNull('appointment_date')
            ->whereNotNull('appointment_completed_at')
            ->count() + $dualAppointmentsCompleted;

        // Total Employers (Global, relevant to search) — union of employers
        // with a primary-scope employee and employers whose only presence
        // here is a dual-listed employee, deduped so an employer with both
        // isn't counted twice.
        $primaryEmployerIds = (clone $statsQuery)
            ->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled'])
            ->pluck('employer_id');
        $totalEmployers = $primaryEmployerIds->merge($dualEmployerIds)->unique()->count();

        // Resolution settings already loaded above — reused here (per-tab resolved).
        $resolutionSettingsRaw = $resolutionSettingsRaw ?? SystemSetting::where('group', 'renewal')->get();
        $resolutionSettings = $this->resolvePerTabSettings($resolutionSettingsRaw, 'renewal', $this->currentTab->id);

        // --- 2. Employer List Query (Pagination) ---
        $employerQuery = Employer::withTrashed()->with(['jobOwner', 'customFields', 'addresses']);
        if (auth()->user()->can('manage-tickets')) {
            $employerQuery->withoutGlobalScope('employerTenancy');
        }

        // Always scope to employers who have relevant employees for this menu
        // — including employers whose ONLY presence here is a Registration
        // employee dual-listed via EmployeeRenewalLink (see that model's
        // docblock). The employer's "active employees" count/sort below is
        // NOT extended to count dual-linked employees yet (documented,
        // deferred scope) — such an employer's card may render slightly
        // greyed-out even though it's fully clickable and functional.
        $tabIdForScope = $this->currentTab->id;
        $employerQuery->where(function ($q) use ($tabIdForScope) {
            $q->whereHas('employees', function ($qq) use ($tabIdForScope) {
                $qq->where('resolution_tab_id', $tabIdForScope)
                   ->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled']);
            })->orWhereHas('employees.renewalLinks', function ($qq) use ($tabIdForScope) {
                $qq->where('resolution_tab_id', $tabIdForScope);
            });
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

        // Renewal-progress multi-select filter (independent of the primary
        // filter pill so users can combine renewal status with any other
        // filter). Scope to the current tab so we don't pull in employers
        // who only match because of an employee under a different tab.
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
                if ($opFilter === 'external') {
                    $q->whereNotNull('custom_operator_name')->where('custom_operator_name', '!=', '');
                } else {
                    $q->where('operator_id', $opFilter);
                }
            });
        }

        // Insurance Filter (Server-Side)
        if ($request->has('insurance_filter') && $request->insurance_filter) {
            $insFilter = $request->insurance_filter;
            $employerQuery->whereHas('employees', function($q) use ($insFilter) {
                $q;
                if ($insFilter === 'none') {
                    $q->where(function($sub) {
                        $sub->whereNull('insurance_type')->orWhere('insurance_type', '');
                    });
                } else {
                    $q->where('insurance_type', $insFilter);
                }
            });
        }

        // Eager load Production Orders to avoid N+1 (scoped to current tab)
        $tabId = $this->currentTab->id;
        $employerQuery->with(['productionOrders' => function($q) use ($tabId) {
             $q->whereIn('status', ['renewal_resolution', 'renewal_resolution_cancelled'])
               ->where('resolution_tab_id', $tabId);
        }]);

        // Counts the employees that are still actively under this tab —
        // excludes terminated and only counts the renewal statuses. Used by
        // the view to grey out the card when this drops to zero. Combines
        // real resolution_tab_id-scoped employees WITH dual-listed ones
        // (EmployeeRenewalLink, pending/completed) — without the second half,
        // an employer whose only presence here is dual-linked employees
        // shows "0 active" and greys out even though it has real, actionable
        // people inside (this was reported as "employees not pulled in
        // correctly" — they WERE linked, this count just didn't see them).
        $employerQuery->selectRaw('employers.*, (
            (SELECT COUNT(*) FROM employees
                WHERE employees.employer_id = employers.id
                AND employees.resolution_tab_id = ?
                AND employees.status IN (\'renewal_pending\', \'renewal_completed\')
                AND employees.terminated_at IS NULL)
            +
            (SELECT COUNT(*) FROM employee_renewal_links
                INNER JOIN employees ON employees.id = employee_renewal_links.employee_id
                WHERE employees.employer_id = employers.id
                AND employee_renewal_links.resolution_tab_id = ?
                AND employee_renewal_links.status IN (\'renewal_pending\', \'renewal_completed\')
                AND employees.terminated_at IS NULL)
        ) as active_employees_in_tab', [$tabId, $tabId]);

        // Sort and Paginate — applied in priority order:
        //   1. Employer cards with zero active employees go to the bottom.
        //   2. Cancelled production orders go below active ones.
        //   3. Most recently touched orders come first. ProductionItem::$touches
        //      bumps the parent ProductionOrder's updated_at on every change.
        $employerQuery->orderByRaw("(
            SELECT CASE WHEN (
                (SELECT COUNT(*) FROM employees
                    WHERE employees.employer_id = employers.id
                    AND employees.resolution_tab_id = ?
                    AND employees.status IN ('renewal_pending', 'renewal_completed')
                    AND employees.terminated_at IS NULL)
                +
                (SELECT COUNT(*) FROM employee_renewal_links
                    INNER JOIN employees ON employees.id = employee_renewal_links.employee_id
                    WHERE employees.employer_id = employers.id
                    AND employee_renewal_links.resolution_tab_id = ?
                    AND employee_renewal_links.status IN ('renewal_pending', 'renewal_completed')
                    AND employees.terminated_at IS NULL)
            ) = 0 THEN 1 ELSE 0 END
        ) ASC", [$tabId, $tabId]);

        $employerQuery->orderByRaw("(
            SELECT CASE WHEN status = 'renewal_resolution_cancelled' THEN 1 ELSE 0 END
            FROM production_orders
            WHERE production_orders.employer_id = employers.id
            AND production_orders.status IN ('renewal_resolution', 'renewal_resolution_cancelled')
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

        $cancelledEmployersQuery = Employer::whereHas('productionOrders', function($q) {
                $q->where('status', 'renewal_resolution_cancelled')
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

        foreach ($employers as $employer) {
            $financeOrder = $employer->productionOrders->first();
            $employer->financeOrder = $financeOrder;

            // Placeholders for view (will be updated via AJAX)
            $employer->stepStats = [];
            $employer->notStartedCount = 0;
            $employer->activeEmployeesCount = 0;
            $employer->cancelledCount = 0;
            $employer->savedCount = 0;
        }

        // Per-tab target expiry config (fallback to legacy global key)
        $currentExpiryConfig = SystemConfig::where('key', 'renewal_target_expiry_date_' . $this->currentTab->id)->value('value')
            ?? SystemConfig::where('key', 'renewal_target_expiry_date')->value('value');

        // Renewal-progress pill counts
        $progressCounts = [];
        foreach (['none', 'visa_only', 'work_permit_only', 'both', 'completed'] as $state) {
            $progressCounts[$state] = (clone $statsQuery)
                ->tap(fn($q) => $this->applyRenewalProgressScope($q, $state, $renewalTargets['visa'], $renewalTargets['wp'], $renewalStatuses))
                ->count();
        }

        return view('production.renewal.index', array_merge(compact(
            'totalEmployees',
            'totalCancelled',
            'totalSaved',
            'totalEmployers',
            'cancelledEmployersCount',
            'notStartedCount',
            'totalNotScheduled',
            'totalAppointmentsPending',
            'totalAppointmentsCompleted',
            'steps',
            'stepStats',
            'employers',
            'lastStepId',
            'addressOptions',
            'currentExpiryConfig',
            'resolutionSettings',
            'renewalTargets',
            'progressCounts',
            'activeOperators',
            'allUsers'
        ), $this->getTabViewData('renewal')));
    }

    private function getGlobalStepStats($baseQuery, $steps)
    {
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
                  $qEmp->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled'])
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
                               ->orWhere('renewal_request_number', 'like', "%{$search}%")
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
    protected function getRenewalTargets(string $group = 'renewal'): array
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
     * Multi-select variant — combine several renewal-progress states with OR.
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

    /**
     * Scope an employee-table query down to one of the renewal-progress states.
     * Mirrors RegistrationController for the renewal status set.
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
            if ($wpTarget) { $q->where($wpNotRenewed); }
        } elseif ($progress === 'work_permit_only') {
            if (!$wpTarget) { $q->whereRaw('1=0'); return; }
            $q->whereNotNull('workPermitExpiryDate')->where('workPermitExpiryDate', '>=', $wpTarget);
            if ($visaTarget) { $q->where($visaNotRenewed); }
        } elseif ($progress === 'both') {
            if (!$visaTarget || !$wpTarget) { $q->whereRaw('1=0'); return; }
            $q->whereNotNull('visaExpiryDate')->where('visaExpiryDate', '>=', $visaTarget)
              ->whereNotNull('workPermitExpiryDate')->where('workPermitExpiryDate', '>=', $wpTarget);
        } elseif ($progress === 'none') {
            if ($visaTarget) { $q->where($visaNotRenewed); }
            if ($wpTarget)   { $q->where($wpNotRenewed); }
        }
    }

    private function applyFilterToEmployerQuery($query, $filter, $stepOneId, ?array $renewalTargets = null, ?array $renewalStatuses = null)
    {
        // Legacy single-select renewal filter — superseded by `renewal_filters`.
        // See RegistrationController for the matching comment.
        if (is_string($filter) && str_starts_with($filter, 'renewal_')) {
            return;
        }

        if ($filter === 'cancelled_employer') {
            $tabId = $this->currentTab->id;
            $query->whereHas('productionOrders', function($q) use ($tabId) {
                $q->where('status', 'renewal_resolution_cancelled')
                  ->where('resolution_tab_id', $tabId);
            });
            return;
        }

        $query->whereHas('employees', function($q) use ($filter, $stepOneId) {
            if ($filter === 'not_started') {
                 $q->whereIn('status', ['renewal_pending', 'renewal_completed'])
                   ->whereDoesntHave('registrationSteps', function($sq) use ($stepOneId) {
                       $sq->where('registration_steps.id', $stepOneId);
                   });
            } elseif ($filter === 'saved') {
                 $q->where('status', 'renewal_completed');
            } elseif ($filter === 'cancelled') {
                 $q->where('status', 'renewal_cancelled');
            } elseif ($filter === 'total_appointments') {
                 $q->whereNotNull('appointment_date');
            } elseif ($filter === 'appointment_not_scheduled') {
                 $q->whereIn('status', ['renewal_pending', 'renewal_completed'])
                   ->whereNull('appointment_date');
            } elseif ($filter === 'appointment_pending') {
                 $q->whereNotNull('appointment_date')
                   ->whereNull('appointment_completed_at');
            } elseif ($filter === 'appointment_completed') {
                 $q->whereNotNull('appointment_date')
                   ->whereNotNull('appointment_completed_at');
            } elseif (is_numeric($filter)) { // Step ID (Highest Step Logic approximation for filter)
                 $q->where('status', '!=', 'renewal_cancelled')
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
        $this->resolveTab($resolutionTab, 'renewal');

        $request->validate([
            'employer_ids' => 'required|array',
            'employer_ids.*' => 'exists:employers,id',
            'search' => 'nullable|string'
        ]);

        $employerIds = $request->input('employer_ids');
        $search = $request->input('search');

        // Fetch visible employers to apply logic
        $employers = Employer::with(['jobOwner', 'addresses'])->whereIn('id', $employerIds)->get();
        $steps = RegistrationStep::renewal()->where('resolution_tab_id', $this->currentTab->id)->orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;

        $results = [];

        foreach ($employers as $employer) {
            // Apply Search Filter Logic
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
                $employerMatches = true;
            }

            // Build Query for this employer
            $query = $employer->employees();

            if (auth()->user()->can('manage-tickets')) {
                $query->withoutGlobalScope('employerTenancy');
            }

            // Base status filter
            $query->where('resolution_tab_id', $this->currentTab->id)
                  ->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled']);

            // Operator Filter
            if ($request->has('operator_filter') && $request->operator_filter) {
                if ($request->operator_filter === 'external') {
                    $query->whereNotNull('custom_operator_name')->where('custom_operator_name', '!=', '');
                } else {
                    $query->where('operator_id', $request->operator_filter);
                }
            }

            // Insurance Filter
            if ($request->has('insurance_filter') && $request->insurance_filter) {
                if ($request->insurance_filter === 'none') {
                     $query->where(function($q) {
                         $q->whereNull('insurance_type')->orWhere('insurance_type', '');
                     });
                } else {
                     $query->where('insurance_type', $request->insurance_filter);
                }
            }

            if (!$employerMatches && $search) {
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
                               ->orWhere('renewal_request_number', 'like', "%{$trimmedSearch}%")
                               ->orWhere('employer_employee_id', 'like', "%{$trimmedSearch}%")
                               ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                               ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
                 });
            }

            $employees = $query->with('registrationSteps')->select('id', 'status', 'resolution_completed_at')->get();

            // Calculate in PHP
            $empStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
            $empNotStarted = 0;
            $empActiveCount = 0;
            $empCancelledCount = 0;
            $empSavedCount = 0;
            $graceCutoff = now()->subHours(24);

            foreach ($employees as $emp) {
                if ($emp->status === 'renewal_cancelled') {
                    $empCancelledCount++;
                    continue;
                }

                $empActiveCount++;

                if ($emp->status === 'renewal_completed') {
                    $empSavedCount++;
                }

                if ($stepOneId && in_array($emp->status, ['renewal_pending', 'renewal_completed']) && !$emp->registrationSteps->contains('id', $stepOneId)) {
                    $empNotStarted++;
                }

                // Step badge — same 24h grace as the global stats: completed
                // employees whose 24h countdown finished no longer count
                // toward the per-employer step badges either.
                $countTowardsSteps = !($emp->status === 'renewal_completed'
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
            ];
        }

        return response()->json($results);
    }

    /**
     * AJAX Method to fetch employee list for an employer.
     */
    public function fetchEmployees(Request $request, $resolutionTab, $employerId)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        $employerQuery = Employer::query();
        if (auth()->user()->can('manage-tickets')) {
            $employerQuery->withoutGlobalScope('employerTenancy');
        }
        $employer = $employerQuery->withTrashed()->findOrFail($employerId);

        $steps = RegistrationStep::renewal()->where('resolution_tab_id', $this->currentTab->id)->orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;

        $query = $employer->employees()
            ->where('resolution_tab_id', $this->currentTab->id);

        if (auth()->user()->can('manage-tickets')) {
            $query->withoutGlobalScope('employerTenancy');
        }

        $query->where(function($q) use ($request) {
                if ($request->boolean('hide_cancelled', true)) {
                    // Completed employees now stay in the main list (green card)
                    // instead of being hidden after 24 hours.
                    $q->whereIn('status', ['renewal_pending', 'renewal_completed']);
                } else {
                    $q->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled']);
                }
            })
            ->with(['registrationSteps', 'customFields']);

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

        // Insurance Filter
        if ($request->has('insurance_filter') && $request->insurance_filter) {
            if ($request->insurance_filter === 'none') {
                 $query->where(function($q) {
                     $q->whereNull('insurance_type')->orWhere('insurance_type', '');
                 });
            } else {
                 $query->where('insurance_type', $request->insurance_filter);
            }
        }

        if ($request->has('filter') && $request->filter) {
            $filter = $request->filter;
            if ($filter === 'saved') $query->where('status', 'renewal_completed');
            elseif ($filter === 'cancelled') $query->where('status', 'renewal_cancelled');
            elseif ($filter === 'not_started') {
                 $query->whereIn('status', ['renewal_pending', 'renewal_completed'])
                       ->whereDoesntHave('registrationSteps', function($q) use ($stepOneId) {
                           $q->where('registration_steps.id', $stepOneId);
                       });
            } elseif (is_numeric($filter)) { // Step ID
                 $query->where('status', '!=', 'renewal_cancelled');
                 // We filter by highest step in PHP below
            }
        }

        // Renewal-progress multi-select filter (employee level — so opening
        // an employer drawer only shows employees matching the selected states).
        $renewalFilters = $this->parseRenewalFilters($request);
        if (!empty($renewalFilters)) {
            $targets = $this->getRenewalTargets('renewal');
            $this->applyRenewalProgressMultiScope($query, $renewalFilters, $targets['visa'], $targets['wp'], [
                'pending'   => 'renewal_pending',
                'completed' => 'renewal_completed',
                'cancelled' => 'renewal_cancelled',
            ]);
        }

        $primaryEmployees = $query->get();

        // Dual-listed employees — Registration-Resolution employees also
        // usable in this tab via EmployeeRenewalLink (see that model's
        // docblock). Fetched separately since their real `status` column
        // stays registration_* — every status-driven filter below is
        // re-applied here against the LINK's own status instead. Search /
        // operator / insurance filters are plain Employee-column filters,
        // so they apply identically via the same query builder.
        $linkedQuery = $employer->employees()
            ->whereHas('renewalLinks', function ($q) {
                $q->where('resolution_tab_id', $this->currentTab->id);
            })
            ->with(['registrationSteps', 'customFields', 'renewalLinks' => function ($q) {
                $q->where('resolution_tab_id', $this->currentTab->id);
            }]);
        if (auth()->user()->can('manage-tickets')) {
            $linkedQuery->withoutGlobalScope('employerTenancy');
        }
        if ($request->has('search') && $request->search) {
            $this->applyEmployerSearchToQuery($linkedQuery, $employer, $request->search);
        }
        if ($request->has('operator_filter') && $request->operator_filter) {
            if ($request->operator_filter === 'external') {
                $linkedQuery->whereNotNull('custom_operator_name')->where('custom_operator_name', '!=', '');
            } else {
                $linkedQuery->where('operator_id', $request->operator_filter);
            }
        }
        if ($request->has('insurance_filter') && $request->insurance_filter) {
            if ($request->insurance_filter === 'none') {
                $linkedQuery->where(function ($q) {
                    $q->whereNull('insurance_type')->orWhere('insurance_type', '');
                });
            } else {
                $linkedQuery->where('insurance_type', $request->insurance_filter);
            }
        }
        // The renewal-progress multi-select filter is status-column SQL —
        // deferred for dual rows (Phase 2, same as the aggregate counters);
        // when that filter is active, dual rows are simply left out rather
        // than risk misreading their link status as their real status.
        $linkedEmployees = empty($this->parseRenewalFilters($request)) ? $linkedQuery->get() : collect();

        $hideCancelled = $request->boolean('hide_cancelled', true);
        $filter = $request->input('filter');
        $linkedEmployees = $linkedEmployees->filter(function ($emp) use ($hideCancelled, $filter, $stepOneId) {
            $link = $emp->renewalLinks->first();
            if (!$link) return false;
            $emp->setRelation('activeRenewalLink', $link);

            if ($hideCancelled && $link->status === 'renewal_cancelled') return false;

            if ($filter) {
                if ($filter === 'saved') return $link->status === 'renewal_completed';
                if ($filter === 'cancelled') return $link->status === 'renewal_cancelled';
                if ($filter === 'not_started') {
                    if (!in_array($link->status, ['renewal_pending', 'renewal_completed'], true)) return false;
                    return !$emp->registrationSteps->contains('id', $stepOneId);
                }
                if (is_numeric($filter)) {
                    if ($link->status === 'renewal_cancelled') return false;
                    $highest = $emp->registrationSteps->sortByDesc('order')->first();
                    return $highest && $highest->id == $filter;
                }
            }
            return true;
        })->values();

        $merged = $primaryEmployees->concat($linkedEmployees);

        // If filtering by step in PHP, the primary set also needs the same
        // "highest step" re-check (unchanged from before — merged set just
        // adds the already-step-filtered linked employees on top).
        if ($request->has('filter') && is_numeric($request->filter)) {
            $filterStepId = $request->filter;
            $merged = $merged->filter(function($emp) use ($filterStepId) {
                if ($emp->relationLoaded('activeRenewalLink')) return true; // already filtered above
                if ($emp->status === 'renewal_cancelled') return false;
                $highest = $emp->registrationSteps->sortByDesc('order')->first();
                return $highest && $highest->id == $filterStepId;
            })->values();
        }

        $perPage = $request->input('per_page', 100);
        $page = $request->input('page', 1);
        $employees = new \Illuminate\Pagination\LengthAwarePaginator(
            $merged->forPage($page, $perPage)->values(),
            $merged->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $financeOrder = ProductionOrder::with('financialGroups.transactions.items', 'financialGroups.transactions.payments')
            ->where('employer_id', $employerId)
            ->whereIn('status', ['renewal_resolution', 'renewal_resolution_cancelled'])
            ->first();

        $employeeFinancialStatus = \App\Services\FinancialStatusService::calculateStatusForEmployees($financeOrder, $employees->pluck('id'));

        foreach ($employees as $emp) {
            $emp->financialStatus = $employeeFinancialStatus[$emp->id] ?? null;
        }

        return view('production.renewal._employee_list_content', array_merge([
            'employees' => $employees,
            'employer' => $employer,
            'steps' => $steps,
            'renewalTargets' => $this->getRenewalTargets('renewal'),
        ], $this->getTabViewData('renewal')));
    }

    /**
     * Lightweight JSON list of every employee under this employer that is
     * eligible for the "Select All" bulk-selection checkbox — see
     * RegistrationController::selectAllEmployerEmployeeIds() for the full
     * rationale (same pattern, mirrored here for Renewal).
     */
    public function selectAllEmployerEmployeeIds(Request $request, $resolutionTab, $employerId)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        $employerQuery = Employer::query();
        if (auth()->user()->can('manage-tickets')) {
            $employerQuery->withoutGlobalScope('employerTenancy');
        }
        $employer = $employerQuery->withTrashed()->findOrFail($employerId);

        $steps = RegistrationStep::renewal()->where('resolution_tab_id', $this->currentTab->id)->orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;

        $query = $employer->employees()
            ->where('resolution_tab_id', $this->currentTab->id);

        if (auth()->user()->can('manage-tickets')) {
            $query->withoutGlobalScope('employerTenancy');
        }

        // Select-all never offers cancelled employees, and only offers
        // completed ones once their 24h Undo window has locked.
        $query->where(function ($q) {
            $q->where('status', 'renewal_pending')
              ->orWhere(function ($q2) {
                  $q2->where('status', 'renewal_completed')
                     ->whereNotNull('resolution_completed_at')
                     ->where('resolution_completed_at', '<=', now()->subHours(24));
              });
        });

        if ($request->has('search') && $request->search) {
            $this->applyEmployerSearchToQuery($query, $employer, $request->search);
        }

        if ($request->has('operator_filter') && $request->operator_filter) {
            if ($request->operator_filter === 'external') {
                $query->whereNotNull('custom_operator_name')->where('custom_operator_name', '!=', '');
            } else {
                $query->where('operator_id', $request->operator_filter);
            }
        }

        if ($request->has('insurance_filter') && $request->insurance_filter) {
            if ($request->insurance_filter === 'none') {
                $query->where(function ($q) {
                    $q->whereNull('insurance_type')->orWhere('insurance_type', '');
                });
            } else {
                $query->where('insurance_type', $request->insurance_filter);
            }
        }

        if ($request->has('filter') && $request->filter) {
            $filter = $request->filter;
            if ($filter === 'saved') {
                $query->where('status', 'renewal_completed');
            } elseif ($filter === 'not_started') {
                $query->whereDoesntHave('registrationSteps', function ($q) use ($stepOneId) {
                    $q->where('registration_steps.id', $stepOneId);
                });
            }
            // 'cancelled' is intentionally not handled — cancelled employees
            // are never select-all eligible. Numeric (step-id) handled below.
        }

        $renewalFilters = $this->parseRenewalFilters($request);
        if (!empty($renewalFilters)) {
            $targets = $this->getRenewalTargets('renewal');
            $this->applyRenewalProgressMultiScope($query, $renewalFilters, $targets['visa'], $targets['wp'], [
                'pending'   => 'renewal_pending',
                'completed' => 'renewal_completed',
                'cancelled' => 'renewal_cancelled',
            ]);
        }

        $isStepFilter = $request->has('filter') && is_numeric($request->filter);

        $employees = $query->select([
            'id', 'employer_id', 'employeeNameTh', 'employeeNameEn', 'employeeTitleTh', 'employeeTitleEn',
            'employeeNationality', 'employeePhoto', 'insurance_type', 'employeePassport', 'status', 'resolution_completed_at',
        ])->when($isStepFilter, fn($q) => $q->with('registrationSteps:id,order'))->get();

        if ($isStepFilter) {
            $filterStepId = $request->filter;
            $employees = $employees->filter(function ($emp) use ($filterStepId) {
                $highest = $emp->registrationSteps->sortByDesc('order')->first();
                return $highest && $highest->id == $filterStepId;
            })->values();
        }

        $items = $employees->map(function ($emp) use ($employer) {
            return [
                'id' => $emp->id,
                'employer_id' => $emp->employer_id,
                'name_th' => $emp->employeeNameTh,
                'name_en' => $emp->employeeNameEn,
                'title_th' => $emp->employeeTitleTh,
                'title_en' => $emp->employeeTitleEn,
                'nationality' => $emp->employeeNationality,
                'photo' => $emp->employeePhoto ? Storage::disk('public')->url($emp->employeePhoto) : '',
                'employer_name' => $employer->employerNameTh ?? 'N/A',
                'insurance_type' => $emp->insurance_type,
                'passport' => $emp->employeePassport,
                'production_item_id' => '',
                'locked_completed' => $emp->status === 'renewal_completed',
            ];
        })->values();

        // Dual-listed employees — same "pending, or completed-and-locked"
        // eligibility rule, but evaluated against the LINK's own status/
        // resolution_completed_at (their real Employee columns stay
        // registration_*, so they can't be used here). Renewal-progress
        // multi-filter deferred for these rows, same as fetchEmployees().
        $linkedItems = collect();
        if (empty($renewalFilters)) {
            $linkedQuery = $employer->employees()
                ->whereHas('renewalLinks', function ($q) {
                    $q->where('resolution_tab_id', $this->currentTab->id);
                })
                ->with(['renewalLinks' => function ($q) {
                    $q->where('resolution_tab_id', $this->currentTab->id);
                }, 'registrationSteps:id,order']);
            if (auth()->user()->can('manage-tickets')) {
                $linkedQuery->withoutGlobalScope('employerTenancy');
            }
            if ($request->has('search') && $request->search) {
                $this->applyEmployerSearchToQuery($linkedQuery, $employer, $request->search);
            }
            if ($request->has('operator_filter') && $request->operator_filter) {
                if ($request->operator_filter === 'external') {
                    $linkedQuery->whereNotNull('custom_operator_name')->where('custom_operator_name', '!=', '');
                } else {
                    $linkedQuery->where('operator_id', $request->operator_filter);
                }
            }
            if ($request->has('insurance_filter') && $request->insurance_filter) {
                if ($request->insurance_filter === 'none') {
                    $linkedQuery->where(function ($q) {
                        $q->whereNull('insurance_type')->orWhere('insurance_type', '');
                    });
                } else {
                    $linkedQuery->where('insurance_type', $request->insurance_filter);
                }
            }

            $filterVal = $request->input('filter');
            $linkedItems = $linkedQuery->get()->filter(function ($emp) use ($filterVal, $stepOneId) {
                $link = $emp->renewalLinks->first();
                if (!$link) return false;
                $isEligible = $link->status === 'renewal_pending'
                    || ($link->status === 'renewal_completed' && $link->resolution_completed_at && $link->resolution_completed_at->lte(now()->subHours(24)));
                if (!$isEligible) return false;

                if ($filterVal) {
                    if ($filterVal === 'saved' && $link->status !== 'renewal_completed') return false;
                    if ($filterVal === 'not_started' && $emp->registrationSteps->contains('id', $stepOneId)) return false;
                    if (is_numeric($filterVal)) {
                        $highest = $emp->registrationSteps->sortByDesc('order')->first();
                        if (!$highest || $highest->id != $filterVal) return false;
                    }
                }
                return true;
            })->map(function ($emp) use ($employer) {
                $link = $emp->renewalLinks->first();
                return [
                    'id' => $emp->id,
                    'employer_id' => $emp->employer_id,
                    'name_th' => $emp->employeeNameTh,
                    'name_en' => $emp->employeeNameEn,
                    'title_th' => $emp->employeeTitleTh,
                    'title_en' => $emp->employeeTitleEn,
                    'nationality' => $emp->employeeNationality,
                    'photo' => $emp->employeePhoto ? Storage::disk('public')->url($emp->employeePhoto) : '',
                    'employer_name' => $employer->employerNameTh ?? 'N/A',
                    'insurance_type' => $emp->insurance_type,
                    'passport' => $emp->employeePassport,
                    'production_item_id' => '',
                    'locked_completed' => $link->status === 'renewal_completed',
                ];
            })->values();
        }

        $items = $items->concat($linkedItems)->values();

        return response()->json(['success' => true, 'items' => $items]);
    }

    /**
     * Import View
     */
    public function importView(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        $employers = collect();
        if (auth()->user()->can('view-employers')) {
             $employers = Employer::orderBy('employerNameTh')->get(['id', 'employerNameTh', 'employerNameEn']);
        } else {
             $user = auth()->user();
             if ($user->employer) {
                 $employers = collect([$user->employer]);
             }
        }

        $request->merge(['target_status' => 'renewal_pending']);
        session()->flash('finish_route', route('production.renewal.index', ['resolutionTab' => $this->currentTab->id]));

        // Hydrate imported employees from session IDs if available (Restoring Preview Feature)
        $sessionImportedEmployees = collect();
        if (session()->has('imported_employee_ids')) {
            $sessionImportedEmployees = Employee::whereIn('id', session('imported_employee_ids'))->get();
        }

        return view('employees.import', [
            'employers' => $employers,
            'production' => null,
            'back_route' => route('production.renewal.index', ['resolutionTab' => $this->currentTab->id]),
            'sessionImportedEmployees' => $sessionImportedEmployees,
        ]);
    }

    /**
     * Create View
     */
    public function create(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $employers = \App\Models\Employer::orderBy('employerNameTh')->get();
        $selectedEmployer = null;

        if ($request->has('employer_id')) {
            $selectedEmployer = \App\Models\Employer::find($request->employer_id);
        }

        return view('production.renewal.create', array_merge([
            'employers' => $employers,
            'employer' => $selectedEmployer,
            'formAction' => route('production.renewal.store', ['resolutionTab' => $this->currentTab->id])
        ], $this->getTabViewData('renewal')));
    }

    /**
     * Store a new renewal step.
     */
    public function storeStep(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $maxOrder = RegistrationStep::renewal()->where('resolution_tab_id', $this->currentTab->id)->max('order') ?? 0;

        $step = RegistrationStep::create([
            'name' => $validated['name'],
            'order' => $maxOrder + 1,
            'type' => 'renewal',
            'resolution_tab_id' => $this->currentTab->id,
        ]);

        return response()->json(['success' => true, 'step' => ['id' => $step->id, 'name' => $step->name, 'order' => $step->order]]);
    }

    /**
     * Update a step (rename/color).
     */
    public function updateStep(Request $request, $resolutionTab, RegistrationStep $step)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $step->update([
            'name' => $validated['name'],
        ]);

        return response()->json(['success' => true]);
    }


    /**
     * Reorder steps.
     */


    public function loadFinancialTab(Request $request, $resolutionTab, Employer $employer)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        // Permission Check
        if (!auth()->user()->can('view-finance') && !auth()->user()->can('edit-employees')) {
             abort(403);
        }

        // Finance Order Logic (scoped per-tab)
        $financeOrder = $employer->productionOrders()
            ->whereIn('status', ['renewal_resolution', 'renewal_resolution_cancelled'])
            ->where('resolution_tab_id', $this->currentTab->id)
            ->first();

        if (!$financeOrder) {
            $financeOrder = ProductionOrder::create([
                'employer_id' => $employer->id,
                'resolution_tab_id' => $this->currentTab->id,
                'status'      => 'renewal_resolution',
                'type'         => 'employer',
                'project_name' => 'Renewal Resolution (' . $this->currentTab->name . ') - ' . $employer->employerNameTh,
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
            ->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled']);
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
    public function reorderSteps(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'renewal');

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
                $oldStepOne = RegistrationStep::renewal()->orderBy('order')->first();
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

            // Update Orders
            foreach ($order as $index => $id) {
                RegistrationStep::where('id', $id)->update(['order' => $index + 1]);
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * Delete a step.
     */
    public function destroyStep($resolutionTab, RegistrationStep $step)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        $step->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Store a newly created renewal employee.
     */
    public function store(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'renewal');

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
        $validated['status'] = 'renewal_pending';
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

        return redirect()->route('production.renewal.operations', ['resolutionTab' => $this->currentTab->id, 'highlight_employer_id' => $validated['employer_id']])
                         ->with('success', 'Renewal Employee created successfully.');
    }

    /**
     * Save Configuration & Auto-Import by Expiry
     */
    public function configureExpiry(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        $request->validate([
            'target_expiry_date' => 'required|date',
        ]);

        $date = $request->target_expiry_date;
        $tabId = $this->currentTab->id;

        // 1. Save Config (per-tab)
        SystemConfig::updateOrCreate(
            ['key' => 'renewal_target_expiry_date_' . $tabId],
            ['value' => $date]
        );

        // 2. Sync Employees — assign/move to this tab
        $newlyAdded = 0;
        $reassigned = 0;
        $linked = 0;

        DB::transaction(function() use ($date, $tabId, &$newlyAdded, &$reassigned, &$linked) {
            // Find all employees with matching expiry, excluding MOU types
            $baseQuery = Employee::where(function($q) use ($date) {
                $q->whereDate('workPermitExpiryDate', $date)
                  ->orWhereDate('visaExpiryDate', $date);
            })
            ->where(function($q) {
                $q->whereNull('workPermitMOUGroup')
                  ->orWhere('workPermitMOUGroup', 'not like', '%MOU%');
            });

            // Group A: Newly added (not currently in any resolution process)
            $newEmployees = (clone $baseQuery)
                ->whereNotIn('status', [
                    'renewal_pending', 'renewal_completed', 'renewal_cancelled',
                    'registration_pending', 'registration_completed', 'registration_cancelled'
                ])->pluck('id');
            $newlyAdded = $newEmployees->count();

            if ($newlyAdded > 0) {
                Employee::whereIn('id', $newEmployees)->update([
                    'status' => 'renewal_pending',
                    'resolution_tab_id' => $tabId,
                ]);
            }

            // Group B: Reassigned (currently renewal_pending in OTHER tabs — move them here)
            // Skip completed/cancelled to preserve finalized work
            $reassignEmployees = (clone $baseQuery)
                ->where('status', 'renewal_pending')
                ->where(function($q) use ($tabId) {
                    $q->whereNull('resolution_tab_id')
                      ->orWhere('resolution_tab_id', '!=', $tabId);
                })
                ->pluck('id');
            $reassigned = $reassignEmployees->count();

            if ($reassigned > 0) {
                Employee::whereIn('id', $reassignEmployees)->update([
                    'resolution_tab_id' => $tabId,
                ]);
            }

            // Group C: Registration-Resolution employees whose dates match too —
            // dual-list them via a link instead of touching their real
            // status/resolution_tab_id (they must stay fully intact in
            // Registration Resolution). See EmployeeRenewalLink's docblock.
            $registrationMatches = (clone $baseQuery)
                ->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled'])
                ->pluck('id');

            foreach ($registrationMatches as $empId) {
                $link = \App\Models\EmployeeRenewalLink::firstOrCreate(
                    ['employee_id' => $empId, 'resolution_tab_id' => $tabId],
                    ['status' => 'renewal_pending']
                );
                if ($link->wasRecentlyCreated) {
                    $linked++;
                }
            }
        });

        $total = $newlyAdded + $reassigned + $linked;
        $msg = "บันทึกการตั้งค่าเรียบร้อย — นำเข้าลูกจ้าง {$total} ราย (ใหม่ {$newlyAdded}, ย้ายจาก tab อื่น {$reassigned}, เชื่อมจากมติลงทะเบียน {$linked})";

        return redirect()->route('production.renewal.index', ['resolutionTab' => $this->currentTab->id])
            ->with('success', $msg);
    }

    /**
     * API: Update Appointment Date & Location
     */
    public function updateAppointment(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'renewal');

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
        $this->resolveTab($resolutionTab, 'renewal');

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
     * API: Update Notification Settings
     */
    public function updateNotificationSettings(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('manage-settings')) {
            abort(403);
        }

        $request->validate([
            'notify_days_advance' => 'required|integer|min:0|max:365'
        ]);

        NotificationSetting::updateOrCreate(
            ['notification_type' => 'renewal_appointment'],
            ['days_before_expiry' => $request->notify_days_advance, 'is_enabled' => true]
        );

        return response()->json(['success' => true]);
    }

    /**
     * API: Get Calendar Data (Counts per day)
     */
    public function getCalendarData(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $query = Employee::query()
            ->where('resolution_tab_id', $this->currentTab->id)
            ->whereIn('status', ['renewal_pending', 'renewal_completed']);
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
        $this->resolveTab($resolutionTab, 'renewal');

        $request->validate(['date' => 'required|date']);
        $date = Carbon::parse($request->date);

        $query = Employee::query()
            ->where('resolution_tab_id', $this->currentTab->id)
            ->whereIn('status', ['renewal_pending', 'renewal_completed']);
        if (auth()->user()->can('manage-tickets')) {
            $query->withoutGlobalScope('employerTenancy');
        }

        $employees = $query->whereDate('appointment_date', $date)
            ->whereNull('appointment_completed_at')
            ->with(['employer'])
            ->orderBy('appointment_date')
            ->get();

        $steps = RegistrationStep::renewal()->orderBy('order')->get();

        $html = view('production.renewal.partials.day_appointments_list', compact('employees', 'steps'))->render();

        return response()->json(['html' => $html]);
    }

    /**
     * API: Update Resolution Auto-Settings
     */
    /**
     * Build a {key => value} map for a resolution group's auto-settings, with per-tab keys
     * ({key}__tab_{id}) taking precedence over the legacy global key.
     *
     * The view templates index by the global key name (e.g. 'renewal_auto_visa_expiry'),
     * so this helper exposes the per-tab value under the global key name — no view changes.
     */
    protected function resolvePerTabSettings($settingsCollection, string $group, int $tabId): array
    {
        $byKey = $settingsCollection->pluck('value', 'key')->toArray();
        $suffix = "__tab_{$tabId}";

        $resolved = [];
        foreach (['visa_expiry', 'work_permit_expiry', 'mou_group'] as $field) {
            $globalKey = "{$group}_auto_{$field}";
            $perTabKey = $globalKey . $suffix;
            // per-tab wins; fall back to legacy global
            $resolved[$globalKey] = $byKey[$perTabKey] ?? ($byKey[$globalKey] ?? null);
        }

        // Also expose any other keys (e.g. unknown fields the view might use) using legacy values
        foreach ($byKey as $k => $v) {
            if (!array_key_exists($k, $resolved) && !str_contains($k, '__tab_')) {
                $resolved[$k] = $v;
            }
        }

        return $resolved;
    }

    public function updateResolutionSettings(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('manage-settings')) {
            abort(403);
        }

        $request->validate([
            'auto_work_permit_expiry' => 'nullable|date',
            'auto_visa_expiry' => 'nullable|date',
            'auto_mou_group' => 'nullable|string|max:255',
        ]);

        $group = 'renewal';
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

    // --- Actions (Finalize, Cancel, etc.) ---

    /**
     * Update progress (Toggle a step for an employee).
     */
    public function updateProgress(Request $request, $resolutionTab, $employeeId)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('update-progress-steps')) {
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

            $steps = RegistrationStep::renewal()->orderBy('order')->get();
            $stepOneId = $steps->sortBy('order')->first()?->id;

            $globalStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
            $globalNotStarted = 0;

            foreach ($allEmployees as $emp) {
                if ($emp->status === 'renewal_cancelled') {
                    continue;
                }
                if ($stepOneId && in_array($emp->status, ['renewal_pending', 'renewal_completed']) && !$emp->registrationSteps->contains('id', $stepOneId)) {
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
            $activeStatuses = ['renewal_pending', 'renewal_completed'];

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

            $empQuery = Employee::query();
            if (auth()->user()->can('manage-tickets')) {
                $empQuery->withoutGlobalScope('employerTenancy');
            }
            $empQuery->whereNull('deleted_at');

            $employerEmployeesQuery = $empQuery->where('employer_id', $employee->employer_id)

                                        ->with('registrationSteps');

            if ($request->has('search') && $request->search) {
                 $employer = $employee->employer;
                 if (!$employer) {
                     $employerQuery = Employer::query();
                     if (auth()->user()->can('manage-tickets')) {
                         $employerQuery->withoutGlobalScope('employerTenancy');
                     }
                     $employer = $employerQuery->find($employee->employer_id);
                 }
                 if ($employer) $this->applyEmployerSearchToQuery($employerEmployeesQuery, $employer, $request->search);
            }

            $employerEmployees = $employerEmployeesQuery->get();
            $employerStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
            $employerNotStarted = 0;

            foreach ($employerEmployees as $emp) {
                 if ($emp->status === 'renewal_cancelled') {
                     continue;
                 }
                 if ($stepOneId && in_array($emp->status, ['renewal_pending', 'renewal_completed']) && !$emp->registrationSteps->contains('id', $stepOneId)) {
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
            Log::error("Error updating renewal progress: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Step-tick / finalize / cancel / restore for a DUAL-listed employee
     * (Registration-Resolution employee also usable in this Renewal tab via
     * EmployeeRenewalLink — see that model's docblock). These mutate ONLY
     * the link's own status/resolution_completed_at/resolution_settings_applied
     * — the real Employee row (status, resolution_tab_id, resolution_completed_at)
     * is never touched, so Registration Resolution stays completely unaffected.
     */
    public function updateLinkProgress(Request $request, $resolutionTab, \App\Models\EmployeeRenewalLink $link)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('update-progress-steps')) {
            abort(403);
        }
        if ($link->resolution_tab_id !== $this->currentTab->id) {
            abort(403, 'This link does not belong to the current tab.');
        }

        $validated = $request->validate([
            'step_id' => 'required|exists:registration_steps,id',
            'completed' => 'required|boolean',
        ]);

        $employee = $link->employee;
        $stepName = \App\Models\RegistrationStep::find($validated['step_id'])?->name ?? 'ขั้นตอน #' . $validated['step_id'];

        if ($validated['completed']) {
            $employee->registrationSteps()->syncWithoutDetaching([
                $validated['step_id'] => ['completed_at' => now()]
            ]);
        } else {
            $employee->registrationSteps()->detach($validated['step_id']);
        }

        \App\Models\ProductionOrder::where('employer_id', $employee->employer_id)
            ->where('resolution_tab_id', $this->currentTab->id)
            ->update(['updated_at' => now()]);

        ActivityLogHelper::logAction('update', ($validated['completed'] ? 'ติ๊กขั้นตอน' : 'ยกเลิกติ๊กขั้นตอน') . ' "' . $stepName . '" ลูกจ้าง (มติต่ออายุ, เชื่อมจากมติลงทะเบียน) ' . ($employee->employeeNameTh ?: $employee->employeeNameEn), Employee::class, $employee->id, [
            'step_id' => $validated['step_id'],
            'step_name' => $stepName,
            'completed' => $validated['completed'],
            'via_renewal_link' => $link->id,
        ]);

        $employee->load('registrationSteps');

        return response()->json([
            'success' => true,
            'html' => $this->getEmployeeCardHtml($employee, $link),
        ]);
    }

    public function finalizeLink(Request $request, $resolutionTab, \App\Models\EmployeeRenewalLink $link)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('edit-employees')) abort(403);
        if ($link->resolution_tab_id !== $this->currentTab->id) abort(403);

        $link->update([
            'status' => 'renewal_completed',
            'resolution_completed_at' => now(),
            'resolution_settings_applied' => false,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'html' => $this->getEmployeeCardHtml($link->employee, $link)]);
        }
        return back()->with('success', 'Employee saved.');
    }

    public function cancelLink(Request $request, $resolutionTab, \App\Models\EmployeeRenewalLink $link)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('edit-employees')) abort(403);
        if ($link->resolution_tab_id !== $this->currentTab->id) abort(403);

        $link->update(['status' => 'renewal_cancelled']);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'html' => $this->getEmployeeCardHtml($link->employee, $link)]);
        }
        return back()->with('success', 'Employee cancelled.');
    }

    public function restoreLink(Request $request, $resolutionTab, \App\Models\EmployeeRenewalLink $link)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('edit-employees')) abort(403);
        if ($link->resolution_tab_id !== $this->currentTab->id) abort(403);

        $link->update([
            'status' => 'renewal_pending',
            'resolution_completed_at' => null,
            'resolution_settings_applied' => false,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'html' => $this->getEmployeeCardHtml($link->employee, $link)]);
        }
        return back()->with('success', 'Employee restored.');
    }

    public function finalize(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('edit-employees')) abort(403);
        // Reset resolution_settings_applied so the 24h auto-apply (UpdateResolutionData)
        // will run again on this fresh finalize cycle even if it was applied before
        // (i.e. after a previous finalize → restore → finalize sequence).
        $employee->update([
            'status' => 'renewal_completed',
            'resolution_completed_at' => now(),
            'resolution_settings_applied' => false,
        ]);
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => $this->getEmployeeCardHtml($employee)
            ]);
        }
        return back()->with('success', 'Employee saved.');
    }

    public function cancel(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('edit-employees')) abort(403);
        $employee->update(['status' => 'renewal_cancelled']);
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => $this->getEmployeeCardHtml($employee)
            ]);
        }
        return back()->with('success', 'Employee cancelled.');
    }

    public function restore(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('edit-employees')) abort(403);
        $employee->update([
            'status' => 'renewal_pending',
            'resolution_tab_id' => $this->currentTab->id,
            'resolution_completed_at' => null,
            'resolution_settings_applied' => false,
        ]);
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => $this->getEmployeeCardHtml($employee)
            ]);
        }
        return back()->with('success', 'Employee restored.');
    }

    /**
     * Fetch Historic Items (Completed > 24h).
     */
    public function fetchHistory(Request $request, $resolutionTab, $employerId)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        $employer = Employer::findOrFail($employerId);

        $employees = $employer->employees()
            ->where('resolution_tab_id', $this->currentTab->id)
            ->where('status', 'renewal_completed')
            ->where(function($q) {
                 $q->whereNotNull('resolution_completed_at')
                   ->where('resolution_completed_at', '<', now()->subHours(24));
            })
            ->with(['registrationSteps'])
            ->get();

        $steps = RegistrationStep::renewal()->orderBy('order')->get();

        return view('production.renewal._employee_list_content', array_merge([
            'employees' => $employees,
            'employer' => $employer,
            'steps' => $steps,
            'renewalTargets' => $this->getRenewalTargets('renewal'),
        ], $this->getTabViewData('renewal')))->with('isHistory', true);
    }

    public function destroy(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('edit-employees')) abort(403);
        $employee->delete();
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Employee deleted.');
    }

    public function cancelEmployer(Request $request, $resolutionTab, Employer $employer)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('edit-employers')) abort(403);
        $tabId = $this->currentTab->id;
        DB::transaction(function () use ($employer, $tabId) {
            // Scope order update to current tab
            ProductionOrder::where('employer_id', $employer->id)
                ->whereIn('status', ['renewal_resolution', 'renewal_resolution_cancelled'])
                ->where('resolution_tab_id', $tabId)
                ->update(['status' => 'renewal_resolution_cancelled']);

            // Scope employee updates to current tab
            $employer->employees()
                ->where('resolution_tab_id', $tabId)
                ->where('status', 'renewal_pending')
                ->update(['status' => 'renewal_cancelled']);
        });
        if ($request->ajax()) return response()->json(['success' => true]);
        return back()->with('success', 'Employer renewal cancelled.');
    }

    public function restoreEmployer(Request $request, $resolutionTab, Employer $employer)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('edit-employers')) abort(403);
        $tabId = $this->currentTab->id;
        DB::transaction(function () use ($employer, $tabId) {
            // Scope order update to current tab
            ProductionOrder::where('employer_id', $employer->id)
                ->whereIn('status', ['renewal_resolution', 'renewal_resolution_cancelled'])
                ->where('resolution_tab_id', $tabId)
                ->update(['status' => 'renewal_resolution']);

            // Scope employee updates to current tab
            $employer->employees()
                ->where('resolution_tab_id', $tabId)
                ->where('status', 'renewal_cancelled')
                ->update(['status' => 'renewal_pending']);
        });
        if ($request->ajax()) return response()->json(['success' => true]);
        return back()->with('success', 'Employer renewal restored.');
    }

    // --- Helpers ---
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
              ->orWhere('renewal_request_number', 'like', "%{$search}%")
              ->orWhere('employer_employee_id', 'like', "%{$search}%")
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

    private function applyEmployerSearchToQuery($query, $employer, $search)
    {
        $search = trim($search);
        $cleanedSearch = str_replace(' ', '', $search);

        $employerMatches = false;
        if (stripos($employer->employerNameTh, $search) !== false ||
            stripos($employer->employerNameEn, $search) !== false ||
            stripos(str_replace(' ', '', $employer->employerNameTh ?? ''), $cleanedSearch) !== false ||
            stripos(str_replace(' ', '', $employer->employerNameEn ?? ''), $cleanedSearch) !== false) {
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
            return $query;
        } else {
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
                  ->orWhere('renewal_request_number', 'like', "%{$search}%")
                  ->orWhere('employer_employee_id', 'like', "%{$search}%")
                  ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                  ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
            });
        }
    }

    /**
     * Fetch Trash
     */
    public function fetchTrash(Request $request, $resolutionTab)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        $query = Employee::onlyTrashed()
            ->with(['employer' => fn($q) => $q->withTrashed(), 'registrationSteps'])
            ->where('resolution_tab_id', $this->currentTab->id)
            ->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled'])
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
                   ->orWhere('employer_employee_id', 'like', "%{$search}%")
                   ->orWhereHas('employer', function($q2) use ($search) {
                       $q2->withTrashed()
                          ->where('employerNameTh', 'like', "%{$search}%")
                          ->orWhere('employerNameEn', 'like', "%{$search}%");
                   });
             });
        }

        $items = $query->paginate(10);

        return view('production.renewal.partials.trash_list', compact('items'));
    }

    /**
     * Restore Trash
     */
    public function restoreTrash(Request $request, $resolutionTab, $id)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $employee = Employee::onlyTrashed()->with(['employer' => fn($q) => $q->withTrashed()])->findOrFail($id);

        if ($employee->employer && $employee->employer->trashed()) {
             $employee->employer->restore();
        }

        $employee->restore();

        return response()->json(['success' => true]);
    }

    /**
     * Helper to render employee card HTML.
     */
    private function getEmployeeCardHtml(Employee $employee, ?\App\Models\EmployeeRenewalLink $renewalLink = null)
    {
        $steps = RegistrationStep::renewal()
            ->where('resolution_tab_id', $this->currentTab->id)
            ->orderBy('order')->get();
        // Uses the shared partial but with renewal steps
        return view('production.registration._employee_card', array_merge([
            'employee' => $employee,
            'steps' => $steps,
            'isHistory' => false,
            'renewalLink' => $renewalLink,
            'renewalTargets' => $this->getRenewalTargets('renewal'),
        ], $this->getTabViewData('renewal')))->render();
    }

    public function updateRemarks(Request $request, $resolutionTab, Employee $employee)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $validated = $request->validate([
            'remarks' => 'nullable|string'
        ]);

        $employee->update(['renewal_remarks' => $validated['remarks']]);

        return response()->json([
            'success' => true,
            'remarks' => $employee->renewal_remarks,
            'message' => 'Remarks updated successfully.'
        ]);
    }

    public function toggleOperator(Request $request, $resolutionTab, $employeeId)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        $employee = Employee::findOrFail($employeeId);

        if ($request->has('operator_id') || $request->has('custom_operator_name')) {
            $userId = $request->input('operator_id');
            $customOperatorName = $request->input('custom_operator_name');

            if (!empty($customOperatorName)) {
                $employee->update([
                    'operator_id' => null,
                    'custom_operator_name' => $customOperatorName
                ]);
                $message = 'Operator assigned to external: ' . $customOperatorName;
            } else if (empty($userId)) {
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

    public function updateInsurance(Request $request, $resolutionTab, $employeeId)
    {
        $this->resolveTab($resolutionTab, 'renewal');

        $employee = Employee::findOrFail($employeeId);

        if (!auth()->user()->can('edit-employees')) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'insurance_type' => 'nullable|string',
            'insurance_detail_social' => 'nullable|string',
            'insurance_detail_private' => 'nullable|string',
            'insurance_detail_hospital' => 'nullable|string',
        ]);

        $updateData = ['insurance_type' => $validated['insurance_type']];

        // Conditional Logic based on Type (Clear others)
        if ($validated['insurance_type'] === 'ประกันสังคม') {
            $updateData['insurance_detail'] = $validated['insurance_detail_social'] ?? null; // Map to main detail column if needed or keep separate
            $updateData['insurance_detail_social'] = $validated['insurance_detail_social'] ?? null;
            // Clear others
            $updateData['insurance_detail_private'] = null;
            $updateData['insurance_detail_hospital'] = null;
        } elseif ($validated['insurance_type'] === 'ประกันเอกชน') {
             $updateData['insurance_detail_private'] = $validated['insurance_detail_private'] ?? null;
             // Clear others
             $updateData['insurance_detail_social'] = null;
             $updateData['insurance_detail_hospital'] = null;
        } elseif ($validated['insurance_type'] === 'ประกันโรงพยาบาล') {
             $updateData['insurance_detail_hospital'] = $validated['insurance_detail_hospital'] ?? null;
             // Clear others
             $updateData['insurance_detail_social'] = null;
             $updateData['insurance_detail_private'] = null;
        } else {
            // None or cleared
            $updateData['insurance_detail_social'] = null;
            $updateData['insurance_detail_private'] = null;
            $updateData['insurance_detail_hospital'] = null;
        }

        $employee->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Insurance updated.',
            'html' => $this->getEmployeeCardHtml($employee)
        ]);
    }

}
