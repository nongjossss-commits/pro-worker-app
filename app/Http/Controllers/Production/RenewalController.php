<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
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
use App\Traits\DailyCheckTrait;

class RenewalController extends Controller
{
    use AddressFilterTrait, DailyCheckTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the main dashboard for Renewal Resolution.
     */
    /**
     * Update employer renewal resolution note (inline).
     */
    public function updateResolutionNote(Request $request, Employer $employer)
    {
        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $validated = $request->validate([
            'note' => 'nullable|string',
        ]);

        $employer->update(['renewal_resolution_note' => $validated['note']]);

        return response()->json(['success' => true]);
    }

    public function dashboard(Request $request)
    {
        return view('production.renewal.dashboard');
    }

    public function index(Request $request)
    {
        $steps = RegistrationStep::renewal()->orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;
        $lastStepId = $steps->sortByDesc('order')->first()?->id;

        // --- 1. Global Stats Query (No Fetching All Models) ---
        $statsQuery = Employee::query()
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
            } elseif ($filter === 'pending_daily_check') {
                 $totalEmployeesQuery->where('daily_check_enabled', true)
                   ->where(function ($sub) {
                       $sub->whereNull('last_daily_checked_at')
                         ->orWhereDate('last_daily_checked_at', '<', now()->today());
                   });
            } elseif (is_numeric($filter)) {
                 $totalEmployeesQuery->where('status', '!=', 'renewal_cancelled')
                    ->whereHas('registrationSteps', function($s) use ($filter) {
                        $s->where('registration_steps.id', $filter);
                    });
            }
        }

        // Clone for different counts
        $totalEmployees = $totalEmployeesQuery->count();
        $totalCancelled = (clone $statsQuery)->where('status', 'renewal_cancelled')->count();
        $totalSaved = (clone $statsQuery)->where('status', 'renewal_completed')->count();

        $notStartedCount = 0;
        if ($stepOneId) {
            $notStartedCount = (clone $statsQuery)
                ->whereIn('status', ['renewal_pending', 'renewal_completed'])
                ->whereDoesntHave('registrationSteps', function ($q) use ($stepOneId) {
                    $q->where('registration_steps.id', $stepOneId);
                })->count();
        }

        // Step Stats (Optimized via SQL)
        $stepStatsQuery = (clone $statsQuery)->where('status', '!=', 'renewal_cancelled');
        $stepStats = $this->getGlobalStepStats($stepStatsQuery, $steps);

        // Total Appointments
        $appointmentsQuery = Employee::query()
            ->whereIn('status', ['renewal_pending', 'renewal_completed']);
        if (auth()->user()->can('manage-tickets')) {
            $appointmentsQuery->withoutGlobalScope('employerTenancy');
        }

        $totalAppointmentsPending = (clone $appointmentsQuery)

            ->whereNotNull('appointment_date')
            ->whereNull('appointment_completed_at')
            ->count();

        $totalAppointmentsCompleted = (clone $appointmentsQuery)

            ->whereNotNull('appointment_date')
            ->whereNotNull('appointment_completed_at')
            ->count();

        // Total Daily Check Pending (Global)
        $totalDailyCheckPending = (clone $statsQuery)

            ->where('daily_check_enabled', true)
            ->where(function ($q) {
                $q->whereNull('last_daily_checked_at')
                  ->orWhereDate('last_daily_checked_at', '<', now()->today());
            })->count();

        // Total Employers (Global, relevant to search)
        $totalEmployers = (clone $statsQuery)
            ->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled'])
            ->distinct('employer_id')
            ->count('employer_id');

        // Resolution Auto-Settings
        $resolutionSettingsRaw = SystemSetting::where('group', 'renewal')->get();
        $resolutionSettings = $resolutionSettingsRaw->pluck('value', 'key')->toArray();

        // --- 2. Employer List Query (Pagination) ---
        $employerQuery = Employer::withTrashed()->with(['jobOwner', 'customFields', 'addresses']);
        if (auth()->user()->can('manage-tickets')) {
            $employerQuery->withoutGlobalScope('employerTenancy');
        }

        // Always scope to employers who have relevant employees for this menu
        $employerQuery->whereHas('employees', function($q) {
             $q->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled']);
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
            $this->applyFilterToEmployerQuery($employerQuery, $request->filter, $stepOneId);
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

        // Eager load Production Orders to avoid N+1
        $employerQuery->with(['productionOrders' => function($q) {
             $q->whereIn('status', ['renewal_resolution', 'renewal_resolution_cancelled']);
        }]);

        // Sort and Paginate
        $employerQuery->orderByRaw("(
            SELECT CASE WHEN status = 'renewal_resolution_cancelled' THEN 1 ELSE 0 END
            FROM production_orders
            WHERE production_orders.employer_id = employers.id
            AND production_orders.status IN ('renewal_resolution', 'renewal_resolution_cancelled')
            LIMIT 1
        ) ASC");

        $perPage = $request->input('per_page', 20);
        $perPage = in_array((int)$perPage, [20, 25, 50, 100]) ? (int)$perPage : 20;

        $employers = $employerQuery->paginate($perPage)->withQueryString();

        $cancelledEmployersQuery = Employer::whereHas('productionOrders', function($q) {
                $q->where('status', 'renewal_resolution_cancelled');
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
            $employer->dailyCheckPendingCount = 0;
        }

        $currentExpiryConfig = SystemConfig::where('key', 'renewal_target_expiry_date')->value('value');

        return view('production.renewal.index', compact(
            'totalEmployees',
            'totalCancelled',
            'totalSaved',
            'totalEmployers',
            'cancelledEmployersCount',
            'notStartedCount',
            'totalDailyCheckPending',
            'steps',
            'stepStats',
            'employers',
            'lastStepId',
            'addressOptions',
            'currentExpiryConfig',
            'resolutionSettings',
            'activeOperators',
            'allUsers'
        ));
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

    private function applyFilterToEmployerQuery($query, $filter, $stepOneId)
    {
        if ($filter === 'cancelled_employer') {
            $query->whereHas('productionOrders', function($q) {
                $q->where('status', 'renewal_resolution_cancelled');
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
            } elseif ($filter === 'pending_daily_check') {
                 $q
                   ->where('daily_check_enabled', true)
                   ->where(function ($sub) {
                       $sub->whereNull('last_daily_checked_at')
                         ->orWhereDate('last_daily_checked_at', '<', now()->today());
                   });
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
    public function batchStats(Request $request)
    {
        $request->validate([
            'employer_ids' => 'required|array',
            'employer_ids.*' => 'exists:employers,id',
            'search' => 'nullable|string'
        ]);

        $employerIds = $request->input('employer_ids');
        $search = $request->input('search');

        // Fetch visible employers to apply logic
        $employers = Employer::with(['jobOwner', 'addresses'])->whereIn('id', $employerIds)->get();
        $steps = RegistrationStep::renewal()->orderBy('order')->get();
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
            $query->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled']);

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

            $employees = $query->with('registrationSteps')->select('id', 'status', 'daily_check_enabled', 'last_daily_checked_at')->get();

            // Calculate in PHP
            $empStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
            $empNotStarted = 0;
            $empActiveCount = 0;
            $empCancelledCount = 0;
            $empSavedCount = 0;
            $empDailyCheckPending = 0;

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

                if ($emp->daily_check_enabled) {
                    $last = $emp->last_daily_checked_at ? \Carbon\Carbon::parse($emp->last_daily_checked_at) : null;
                    if (!$last || $last->lt(now()->startOfDay())) {
                        $empDailyCheckPending++;
                    }
                }

                $highestStep = $emp->registrationSteps->sortByDesc('order')->first();
                if ($highestStep && isset($empStats[$highestStep->id])) {
                    $empStats[$highestStep->id]++;
                }
            }

            $results[$employer->id] = [
                'stepStats' => $empStats,
                'notStartedCount' => $empNotStarted,
                'activeEmployeesCount' => $empActiveCount,
                'cancelledCount' => $empCancelledCount,
                'savedCount' => $empSavedCount,
                'dailyCheckPendingCount' => $empDailyCheckPending
            ];
        }

        return response()->json($results);
    }

    /**
     * AJAX Method to fetch employee list for an employer.
     */
    public function fetchEmployees(Request $request, $employerId)
    {
        $employerQuery = Employer::query();
        if (auth()->user()->can('manage-tickets')) {
            $employerQuery->withoutGlobalScope('employerTenancy');
        }
        $employer = $employerQuery->withTrashed()->findOrFail($employerId);

        $steps = RegistrationStep::renewal()->orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;

        $query = $employer->employees();

        if (auth()->user()->can('manage-tickets')) {
            $query->withoutGlobalScope('employerTenancy');
        }

        $query->where(function($q) use ($request) {
                if ($request->boolean('hide_cancelled', true)) {
                    $q->where('status', 'renewal_pending');
                } else {
                    $q->whereIn('status', ['renewal_pending', 'renewal_cancelled']);
                }
                $q->orWhere(function($sub) {
                      $sub->where('status', 'renewal_completed')
                          ->where(function($t) {
                              $t->whereNull('resolution_completed_at')
                                ->orWhere('resolution_completed_at', '>=', now()->subHours(24));
                          });
                  });
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
            } elseif ($filter === 'pending_daily_check') {
                 $query->where('status', '!=', 'renewal_cancelled')
                       ->where('daily_check_enabled', true)
                       ->where(function ($sub) {
                           $sub->whereNull('last_daily_checked_at')
                             ->orWhereDate('last_daily_checked_at', '<', now()->today());
                       });
            } elseif (is_numeric($filter)) { // Step ID
                 $query->where('status', '!=', 'renewal_cancelled');
                 // We filter by highest step in PHP below
            }
        }

        $employees = $query->get();

        // If filtering by step in PHP, we must get all first, filter, then manually paginate
        if ($request->has('filter') && is_numeric($request->filter)) {
            $filterStepId = $request->filter;
            $allEmployees = $query->get();
            $filtered = $allEmployees->filter(function($emp) use ($filterStepId) {
                if ($emp->status === 'renewal_cancelled') return false;
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

        $financeOrder = ProductionOrder::with('financialGroups.transactions.items', 'financialGroups.transactions.payments')
            ->where('employer_id', $employerId)
            ->whereIn('status', ['renewal_resolution', 'renewal_resolution_cancelled'])
            ->first();

        $employeeFinancialStatus = \App\Services\FinancialStatusService::calculateStatusForEmployees($financeOrder, $employees->pluck('id'));

        foreach ($employees as $emp) {
            $emp->financialStatus = $employeeFinancialStatus[$emp->id] ?? null;
        }

        return view('production.renewal._employee_list_content', [
            'employees' => $employees,
            'employer' => $employer,
            'steps' => $steps
        ]);
    }

    /**
     * Import View
     */
    public function importView(Request $request)
    {
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
        session()->flash('finish_route', route('production.renewal.index'));

        // Hydrate imported employees from session IDs if available (Restoring Preview Feature)
        $sessionImportedEmployees = collect();
        if (session()->has('imported_employee_ids')) {
            $sessionImportedEmployees = Employee::whereIn('id', session('imported_employee_ids'))->get();
        }

        return view('employees.import', [
            'employers' => $employers,
            'production' => null,
            'back_route' => route('production.renewal.index'),
            'sessionImportedEmployees' => $sessionImportedEmployees,
        ]);
    }

    /**
     * Create View
     */
    public function create(Request $request)
    {
        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $employers = \App\Models\Employer::orderBy('employerNameTh')->get();
        $selectedEmployer = null;

        if ($request->has('employer_id')) {
            $selectedEmployer = \App\Models\Employer::find($request->employer_id);
        }

        return view('production.renewal.create', [
            'employers' => $employers,
            'employer' => $selectedEmployer,
            'formAction' => route('production.renewal.store')
        ]);
    }

    /**
     * Store a new renewal step.
     */
    public function storeStep(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $maxOrder = RegistrationStep::renewal()->max('order') ?? 0;

        RegistrationStep::create([
            'name' => $validated['name'],
            'order' => $maxOrder + 1,
            'type' => 'renewal',
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Update a step (rename/color).
     */
    public function updateStep(Request $request, RegistrationStep $step)
    {
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


    public function loadFinancialTab(Request $request, Employer $employer)
    {
        // Permission Check
        if (!auth()->user()->can('view-finance') && !auth()->user()->can('edit-employees')) {
             abort(403);
        }

        // Finance Order Logic
        $financeOrder = $employer->productionOrders()->whereIn('status', ['renewal_resolution', 'renewal_resolution_cancelled'])->first();

        if (!$financeOrder) {
            $financeOrder = ProductionOrder::create([
                'employer_id' => $employer->id,
                'status'      => 'renewal_resolution',
                'type'         => 'employer',
                'project_name' => 'Renewal Resolution - ' . $employer->employerNameTh,
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
    public function reorderSteps(Request $request)
    {
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
    public function destroyStep(RegistrationStep $step)
    {
        $step->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Store a newly created renewal employee.
     */
    public function store(Request $request)
    {
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

        return redirect()->route('production.renewal.index')
                         ->with('success', 'Renewal Employee created successfully.');
    }

    /**
     * Save Configuration & Auto-Import by Expiry
     */
    public function configureExpiry(Request $request)
    {
        $request->validate([
            'target_expiry_date' => 'required|date',
        ]);

        $date = $request->target_expiry_date;

        // 1. Save Config
        SystemConfig::updateOrCreate(
            ['key' => 'renewal_target_expiry_date'],
            ['value' => $date]
        );

        // 2. Sync Employees
        // Find employees with matching expiry date
        // Logic: Expiry matches AND (MOU Group is NOT 'MOU' or similar exclusions if needed)
        // User text: "whose Work Permit OR Visa expires on this date (excluding MOU types)"

        $count = 0;
        $updated = 0;

        DB::transaction(function() use ($date, &$count, &$updated) {
            $query = Employee::where(function($q) use ($date) {
                $q->whereDate('workPermitExpiryDate', $date)
                  ->orWhereDate('visaExpiryDate', $date);
            })
            // Exclude MOU types (heuristic based on text "excluding MOU types")
            ->where(function($q) {
                $q->whereNull('workPermitMOUGroup')
                  ->orWhere('workPermitMOUGroup', 'not like', '%MOU%');
            })
            // Only touch active employees or those not already in a specific resolution process?
            // "If future employees... non-renewal resolution... system should check 100%"
            // We should probably only pick up 'active' or null status, OR override?
            // Safer to only pick up 'active' or 'pending' or null.
            ->whereNotIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled', 'registration_pending', 'registration_completed']);

            $employees = $query->get();
            $count = $employees->count();

            // Bulk Update
            if ($count > 0) {
                Employee::whereIn('id', $employees->pluck('id'))
                    ->update(['status' => 'renewal_pending']);
                $updated = $count;
            }
        });

        return redirect()->route('production.renewal.index')
            ->with('success', "Configuration saved. {$updated} employees imported to Renewal list.");
    }

    /**
     * API: Update Appointment Date & Location
     */
    public function updateAppointment(Request $request, Employee $employee)
    {
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
    public function toggleAppointmentComplete(Request $request, Employee $employee)
    {
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
    public function updateNotificationSettings(Request $request)
    {
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
    public function getCalendarData(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $query = Employee::query()
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
    public function getAppointmentsByDate(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $date = Carbon::parse($request->date);

        $query = Employee::query()
            ->whereIn('status', ['renewal_pending', 'renewal_completed']);
        if (auth()->user()->can('manage-tickets')) {
            $query->withoutGlobalScope('employerTenancy');
        }

        $employees = $query->whereDate('appointment_date', $date)

            ->whereNull('appointment_completed_at') // Exclude completed
            ->with(['employer'])
            ->get();

        $steps = RegistrationStep::renewal()->orderBy('order')->get();

        $html = view('production.renewal.partials.day_appointments_list', compact('employees', 'steps'))->render();

        return response()->json(['html' => $html]);
    }

    /**
     * API: Update Resolution Auto-Settings
     */
    public function updateResolutionSettings(Request $request)
    {
        if (!auth()->user()->can('manage-settings')) {
            abort(403);
        }

        $request->validate([
            'auto_work_permit_expiry' => 'nullable|date',
            'auto_visa_expiry' => 'nullable|date',
            'auto_mou_group' => 'nullable|string|max:255',
        ]);

        $group = 'renewal';

        SystemSetting::updateOrCreate(
            ['key' => "{$group}_auto_work_permit_expiry"],
            ['value' => $request->auto_work_permit_expiry, 'group' => $group]
        );

        SystemSetting::updateOrCreate(
            ['key' => "{$group}_auto_visa_expiry"],
            ['value' => $request->auto_visa_expiry, 'group' => $group]
        );

        SystemSetting::updateOrCreate(
            ['key' => "{$group}_auto_mou_group"],
            ['value' => $request->auto_mou_group, 'group' => $group]
        );

        return response()->json(['success' => true]);
    }

    // --- Actions (Finalize, Cancel, etc.) ---

    /**
     * Update progress (Toggle a step for an employee).
     */
    public function updateProgress(Request $request, $employeeId)
    {
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

            if ($validated['completed']) {
                $employee->registrationSteps()->syncWithoutDetaching([
                    $validated['step_id'] => ['completed_at' => now()]
                ]);
            } else {
                $employee->registrationSteps()->detach($validated['step_id']);
            }

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

            $globalDailyCheckPending = (clone $globalQuery)
                ->whereIn('status', $activeStatuses)
                ->where('daily_check_enabled', true)
                ->where(function ($q) {
                    $q->whereNull('last_daily_checked_at')
                      ->orWhereDate('last_daily_checked_at', '<', now()->today());
                })->count();

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
                'globalDailyCheckPending' => $globalDailyCheckPending,
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

    public function finalize(Request $request, Employee $employee)
    {
        if (!auth()->user()->can('edit-employees')) abort(403);
        $employee->update([
            'status' => 'renewal_completed',
            'resolution_completed_at' => now()
        ]);
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => $this->getEmployeeCardHtml($employee)
            ]);
        }
        return back()->with('success', 'Employee saved.');
    }

    public function cancel(Request $request, Employee $employee)
    {
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

    public function restore(Request $request, Employee $employee)
    {
        if (!auth()->user()->can('edit-employees')) abort(403);
        $employee->update([
            'status' => 'renewal_pending',
            'resolution_completed_at' => null
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
    public function fetchHistory(Request $request, $employerId)
    {
        $employer = Employer::findOrFail($employerId);

        $employees = $employer->employees()
            ->where('status', 'renewal_completed')
            ->where(function($q) {
                 $q->whereNotNull('resolution_completed_at')
                   ->where('resolution_completed_at', '<', now()->subHours(24));
            })
            ->with(['registrationSteps'])
            ->get();

        $steps = RegistrationStep::renewal()->orderBy('order')->get();

        return view('production.renewal._employee_list_content', [
            'employees' => $employees,
            'employer' => $employer,
            'steps' => $steps
        ])->with('isHistory', true);
    }

    public function destroy(Request $request, Employee $employee)
    {
        if (!auth()->user()->can('edit-employees')) abort(403);
        $employee->delete();
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Employee deleted.');
    }

    public function cancelEmployer(Request $request, Employer $employer)
    {
        if (!auth()->user()->can('edit-employers')) abort(403);
        DB::transaction(function () use ($employer) {
            ProductionOrder::where('employer_id', $employer->id)
                ->whereIn('status', ['renewal_resolution', 'renewal_resolution_cancelled'])
                ->update(['status' => 'renewal_resolution_cancelled']);

            $employer->employees()
                ->where('status', 'renewal_pending')
                ->update(['status' => 'renewal_cancelled']);
        });
        if ($request->ajax()) return response()->json(['success' => true]);
        return back()->with('success', 'Employer renewal cancelled.');
    }

    public function restoreEmployer(Request $request, Employer $employer)
    {
        if (!auth()->user()->can('edit-employers')) abort(403);
        DB::transaction(function () use ($employer) {
            ProductionOrder::where('employer_id', $employer->id)
                ->whereIn('status', ['renewal_resolution', 'renewal_resolution_cancelled'])
                ->update(['status' => 'renewal_resolution']);

            $employer->employees()
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
        $cleanedSearch = str_replace(' ', '', $search);

        return $query->where(function($q) use ($search, $cleanedSearch) {
            $q->where('employeeNameTh', 'like', "%{$search}%")
              ->orWhere('employeeNameEn', 'like', "%{$search}%")
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
    public function fetchTrash(Request $request)
    {
        $query = Employee::onlyTrashed()
            ->with(['employer' => fn($q) => $q->withTrashed(), 'registrationSteps'])
            ->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled'])
            ->latest('deleted_at');

        if ($request->has('search') && $request->search) {
             $search = $request->search;
             // Apply search logic manually to support withTrashed() on relations
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
    public function restoreTrash(Request $request, $id)
    {
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
    private function getEmployeeCardHtml(Employee $employee)
    {
        $steps = RegistrationStep::renewal()->orderBy('order')->get();
        // Uses the shared partial but with renewal steps
        return view('production.registration._employee_card', [
            'employee' => $employee,
            'steps' => $steps,
            'isHistory' => false
        ])->render();
    }

    public function updateRemarks(Request $request, Employee $employee)
    {
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

    public function toggleOperator(Request $request, $employeeId)
    {
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

    public function updateInsurance(Request $request, $employeeId)
    {
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
