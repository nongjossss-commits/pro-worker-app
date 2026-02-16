<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionOrder;
use App\Models\RegistrationStep;
use App\Models\EmployeeCustomField;
use App\Models\ProductionCustomField;
use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\AddressFilterTrait;

class RegistrationController extends Controller
{
    use AddressFilterTrait;

    public function __construct()
    {
        // Permissions can be refined later.
        // For now, assume 'admin' or 'staff' access.
        $this->middleware('auth');
    }

    /**
     * Display the main dashboard for Registration Resolution.
     */
    public function index(Request $request)
    {
        $steps = RegistrationStep::registration()->orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;
        $lastStepId = $steps->sortByDesc('order')->first()?->id;

        // --- 1. Global Stats Query (No Fetching All Models) ---
        $statsQuery = Employee::query();

        if (auth()->user()->can('manage-tickets')) {
            $statsQuery->withoutGlobalScope('employerTenancy');
        }

        if ($request->has('search') && $request->search) {
            $this->applySearchToQuery($statsQuery, $request->search);
        }

        // Clone for different counts
        $totalEmployees = (clone $statsQuery)->whereIn('status', ['registration_pending', 'registration_completed'])->count();
        $totalCancelled = (clone $statsQuery)->where('status', 'registration_cancelled')->count();
        $totalSaved = (clone $statsQuery)->where('status', 'registration_completed')->count();
        $totalBiometricsCollected = (clone $statsQuery)->whereIn('status', ['registration_pending', 'registration_completed'])
                                        ->whereNotNull('biometrics_collected_at')->count();

        $notStartedCount = 0;
        if ($stepOneId) {
            $notStartedCount = (clone $statsQuery)
                ->whereIn('status', ['registration_pending', 'registration_completed'])
                ->whereDoesntHave('registrationSteps', function ($q) use ($stepOneId) {
                    $q->where('registration_steps.id', $stepOneId);
                })->count();
        }

        // Step Stats (Optimized via SQL)
        // We filter statsQuery to active employees for step stats usually
        $stepStatsQuery = (clone $statsQuery)->whereIn('status', ['registration_pending', 'registration_completed']);
        $stepStats = $this->getGlobalStepStats($stepStatsQuery, $steps);

        // Total Appointments
        $totalAppointments = Employee::query();
        if (auth()->user()->can('manage-tickets')) {
            $totalAppointments->withoutGlobalScope('employerTenancy');
        }
        $totalAppointments = $totalAppointments->whereIn('status', ['registration_pending', 'registration_completed'])
            ->whereNotNull('appointment_date')
            ->count();

        // Total Employers (Global, relevant to search)
        $totalEmployers = (clone $statsQuery)
            ->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled'])
            ->distinct('employer_id')
            ->count('employer_id');

        // Notification Setting
        $notificationSetting = NotificationSetting::firstOrNew(
            ['notification_type' => 'registration_appointment'],
            ['days_before_expiry' => 3, 'is_enabled' => true]
        );

        // --- 2. Employer List Query (Pagination) ---
        $employerQuery = Employer::withTrashed()->with(['jobOwner', 'customFields', 'addresses']);
        if (auth()->user()->can('manage-tickets')) {
            $employerQuery->withoutGlobalScope('employerTenancy');
        }

        // Apply Search to Employer Query
        if ($request->has('search') && $request->search) {
            $this->applyEmployerLevelSearch($employerQuery, $request->search);
        } else {
            // If no search, we only show employers who have relevant employees
            $employerQuery->whereHas('employees', function($q) {
                 $q->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled']);
            });
        }

        // Apply Address Filters
        $addressOptions = $this->getAddressOptions($employerQuery);
        $employerQuery = $this->applyAddressFilters($employerQuery, $request);

        // Apply "Filter" pills (Server-Side)
        if ($request->has('filter') && $request->filter) {
            $this->applyFilterToEmployerQuery($employerQuery, $request->filter, $stepOneId);
        }

        // Sort and Paginate
        $employerQuery->orderByRaw("(
            SELECT CASE WHEN status = 'registration_resolution_cancelled' THEN 1 ELSE 0 END
            FROM production_orders
            WHERE production_orders.employer_id = employers.id
            AND production_orders.status IN ('registration_resolution', 'registration_resolution_cancelled')
            LIMIT 1
        ) ASC");

        $employers = $employerQuery->paginate($request->input('per_page', 20))->withQueryString();

        // Calculate Cancelled Employers Count (Approximate Global or based on current filter context)
        // Ideally we clone employerQuery before filters? But UI usually expects "Total Cancelled Employers" in the system or matching search.
        // Let's count filtered employers who have cancelled status order.
        // But $employerQuery has pagination. We need a separate query with same filters.
        // To be safe and performant, let's just count global cancelled employers matching search (if any).
        $cancelledEmployersQuery = Employer::whereHas('productionOrders', function($q) {
                $q->where('status', 'registration_resolution_cancelled');
            });
        if (auth()->user()->can('manage-tickets')) {
            $cancelledEmployersQuery->withoutGlobalScope('employerTenancy');
        }
        if ($request->has('search') && $request->search) {
            $this->applyEmployerLevelSearch($cancelledEmployersQuery, $request->search);
        }
        $cancelledEmployersCount = $cancelledEmployersQuery->count();


        // --- 3. Process Visible Employers ---
        // Eager load employees for these 20 employers to calculate their stats
        // We load ALL relevant employees for these employers so we can calculate stats in PHP accurately.
        $employers->load(['employees' => function($q) {
             $q->select('id', 'employer_id', 'status', 'biometrics_collected_at', 'employeeNameTh', 'employeeNameEn', 'employeePassport');
             $q->with(['registrationSteps' => function($sq) {
                 $sq->select('registration_steps.id', 'registration_steps.order', 'employee_registration_status.employee_id');
             }]);
             $q->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled']);
        }]);

        foreach ($employers as $employer) {
            // Finance Order Logic
            $financeOrder = ProductionOrder::with(['financialGroups.transactions.items', 'financialGroups.advanceItems', 'items.employee'])
                ->where('employer_id', $employer->id)
                ->whereIn('status', ['registration_resolution', 'registration_resolution_cancelled'])
                ->first();

            if (!$financeOrder) {
                $financeOrder = ProductionOrder::create([
                    'employer_id' => $employer->id,
                    'status'      => 'registration_resolution',
                    'type'         => 'employer',
                    'project_name' => 'Registration Resolution - ' . $employer->employerNameTh,
                    'financial_data' => []
                ]);
            }
            $employer->financeOrder = $financeOrder;

            if ($financeOrder->financialGroups->isEmpty()) {
                $financeOrder->financialGroups()->create([
                    'name' => 'General',
                    'financial_data' => $financeOrder->financial_data ?? []
                ]);
                $financeOrder->load('financialGroups.transactions.items');
            }

            // --- Stats Calculation ---
            // We must filter $employer->employees based on Search if present
            $myEmps = $employer->employees;

            if ($request->has('search') && $request->search) {
                // If employer itself matches search, show all. Else filter employees.
                $search = trim($request->search);
                $cleanedSearch = str_replace(' ', '', $search);

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

                // Address match logic is complex to check in PHP loop efficiently without loading addresses.
                // But we eager loaded addresses.
                if (!$employerMatches) {
                     foreach($employer->addresses as $addr) {
                         if (stripos($addr->addrProvince, $search) !== false || stripos($addr->addrDistrict, $search) !== false) {
                             $employerMatches = true;
                             break;
                         }
                     }
                }

                if (!$employerMatches) {
                    // Filter employees
                    $myEmps = $myEmps->filter(function($emp) use ($search, $cleanedSearch) {
                        return stripos($emp->employeeNameTh, $search) !== false ||
                               stripos($emp->employeeNameEn, $search) !== false ||
                               stripos($emp->employeePassport, $search) !== false ||
                               stripos(str_replace(' ', '', $emp->employeeNameTh), $cleanedSearch) !== false ||
                               stripos(str_replace(' ', '', $emp->employeeNameEn), $cleanedSearch) !== false;
                    });
                }
            }

            // Determine Financial Status (Optimization: Map only relevant IDs)
            $employeeFinancialStatus = [];
            foreach ($financeOrder->financialGroups as $group) {
                foreach ($group->transactions as $transaction) {
                    foreach ($transaction->items as $item) {
                        if (!$item->employee_id) continue;
                        $empId = $item->employee_id;
                        // Logic simplified for stats presence
                        $employeeFinancialStatus[$empId] = ($transaction->status === 'paid' && ($employeeFinancialStatus[$empId] ?? 'none') === 'none') ? 'paid' : 'partial';
                    }
                }
            }

            // Prepare Candidates List
            $employer->activeEmployeesList = $myEmps->where('status', '!=', 'registration_cancelled')->values();

            // Initialize Stats
            $empStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
            $empNotStarted = 0;
            $empActiveCount = 0;
            $empCancelledCount = 0;
            $empSavedCount = 0;
            $empBiometricsCollected = 0;

            foreach ($myEmps as $emp) {
                $emp->financialStatus = $employeeFinancialStatus[$emp->id] ?? null;

                if ($emp->status === 'registration_cancelled') {
                    $empCancelledCount++;
                    continue;
                }

                $empActiveCount++;

                if ($emp->status === 'registration_completed') {
                    $empSavedCount++;
                }

                if ($stepOneId && !$emp->registrationSteps->contains('id', $stepOneId)) {
                    $empNotStarted++;
                }

                if ($emp->biometrics_collected_at) {
                    $empBiometricsCollected++;
                }

                $highestStep = $emp->registrationSteps->sortByDesc('order')->first();
                if ($highestStep && isset($empStats[$highestStep->id])) {
                    $empStats[$highestStep->id]++;
                }
            }

            $employer->stepStats = $empStats;
            $employer->notStartedCount = $empNotStarted;
            $employer->activeEmployeesCount = $empActiveCount;
            $employer->cancelledCount = $empCancelledCount;
            $employer->savedCount = $empSavedCount;
            $employer->biometricsCollectedCount = $empBiometricsCollected;
        }

        return view('production.registration.index', compact(
            'totalEmployees',
            'totalCancelled',
            'totalSaved',
            'totalEmployers',
            'cancelledEmployersCount',
            'notStartedCount',
            'totalBiometricsCollected',
            'totalAppointments',
            'steps',
            'stepStats',
            'employers',
            'lastStepId',
            'addressOptions',
            'notificationSetting'
        ));
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
        $cleanedSearch = str_replace(' ', '', $search);

        $query->where(function($q) use ($search, $cleanedSearch) {
            // Employer Fields
            $q->where('employerNameTh', 'like', "%{$search}%")
              ->orWhere('employerNameEn', 'like', "%{$search}%")
              ->orWhereRaw("REPLACE(employerNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
              ->orWhereRaw("REPLACE(employerNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
              // Job Owner
              ->orWhereHas('jobOwner', function($q2) use ($search) {
                  $q2->where('name', 'like', "%{$search}%");
              })
              // Address
              ->orWhere(function($addrQ) use ($search) {
                  $addrQ->filterByAddress($search);
              })
              // Employees (Robust Search)
              ->orWhereHas('employees', function($qEmp) use ($search, $cleanedSearch) {
                  $qEmp->where('employeeNameTh', 'like', "%{$search}%")
                       ->orWhere('employeeNameEn', 'like', "%{$search}%")
                       ->orWhere('employeePassport', 'like', "%{$search}%")
                       ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                       ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
              });
        });
    }

    private function applyFilterToEmployerQuery($query, $filter, $stepOneId)
    {
        if ($filter === 'cancelled_employer') {
            // Handled via Query (whereHas) logic in main index if needed, but here we can enforce it.
            // Actually, the main index checks this explicitly for sorting purposes or pagination.
            // But strict filtering:
            $query->whereHas('productionOrders', function($q) {
                $q->where('status', 'registration_resolution_cancelled');
            });
            return;
        }

        // For other filters, we check if the employer has ANY employee matching the criteria
        $query->whereHas('employees', function($q) use ($filter, $stepOneId) {
            if ($filter === 'not_started') {
                 $q->where('status', '!=', 'registration_cancelled')
                   ->whereDoesntHave('registrationSteps', function($sq) use ($stepOneId) {
                       $sq->where('id', $stepOneId);
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
            } elseif (is_numeric($filter)) { // Step ID (Highest Step Logic approximation for filter)
                 // Filtering by highest step in SQL is hard.
                 // We can at least ensure they HAVE the step.
                 // Ideally: check if they have this step AND NOT any higher step?
                 // Simple approximation: Has this step.
                 // This matches "current progress" loosely.
                 $q->where('status', '!=', 'registration_cancelled')
                   ->whereHas('registrationSteps', function($sq) use ($filter) {
                       $sq->where('id', $filter);
                   });
                 // Note: Exact highest step filtering in SQL requires subquery.
                 // e.g. where id = (select step_id from ... limit 1)
                 // I will stick to "Has this step" for now as it's faster and usually sufficient for finding people.
            }
        });
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

        $steps = RegistrationStep::registration()->orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;

        // Apply the same base status filter as the index method to ensure consistency
        $query = $employer->employees();

        if (auth()->user()->can('manage-tickets')) {
            $query->withoutGlobalScope('employerTenancy');
        }

        $query->where(function($q) {
                $q->whereIn('status', ['registration_pending', 'registration_cancelled'])
                  ->orWhere(function($sub) {
                      $sub->where('status', 'registration_completed')
                          ->where(function($t) {
                              $t->whereNull('resolution_completed_at')
                                ->orWhere('resolution_completed_at', '>=', now()->subHours(24));
                          });
                  });
            })
            ->with(['registrationSteps', 'customFields']); // Load everything needed for the card

        // Apply Search (if global search is active)
        if ($request->has('search') && $request->search) {
            $this->applyEmployerSearchToQuery($query, $employer, $request->search);
        }

        // Apply Filter
        if ($request->has('filter') && $request->filter) {
            $filter = $request->filter;
            if ($filter === 'not_started') {
                 $query->where('status', '!=', 'registration_cancelled')
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
            } elseif (is_numeric($filter)) { // Step ID
                 $query->where('status', '!=', 'registration_cancelled');
                 // We filter by highest step in PHP below
            }
        }

        $employees = $query->get();

        // Refine Filter in PHP if needed (for Exact Highest Step)
        if ($request->has('filter') && is_numeric($request->filter)) {
            $filterStepId = $request->filter;
            $employees = $employees->filter(function($emp) use ($filterStepId) {
                if ($emp->status === 'registration_cancelled') return false;
                $highest = $emp->registrationSteps->sortByDesc('order')->first();
                return $highest && $highest->id == $filterStepId;
            });
        }

        // --- Calculate Financial Status ---
        $financeOrder = ProductionOrder::with('financialGroups.transactions.items')
            ->where('employer_id', $employerId)
            ->whereIn('status', ['registration_resolution', 'registration_resolution_cancelled'])
            ->first();

        $employeeFinancialStatus = [];
        if ($financeOrder) {
            foreach ($financeOrder->financialGroups as $group) {
                foreach ($group->transactions as $transaction) {
                    foreach ($transaction->items as $item) {
                        if (!$item->employee_id) continue;

                        $empId = $item->employee_id;
                        $currentStatus = $employeeFinancialStatus[$empId] ?? 'none';
                        $txStatus = $transaction->status;

                        if ($txStatus === 'paid') {
                            if ($currentStatus === 'none') {
                                $employeeFinancialStatus[$empId] = 'paid';
                            }
                        } else {
                            $employeeFinancialStatus[$empId] = 'partial';
                        }
                    }
                }
            }
        }

        foreach ($employees as $emp) {
            $emp->financialStatus = $employeeFinancialStatus[$emp->id] ?? null;
        }

        return view('production.registration._employee_list_content', [
            'employees' => $employees,
            'steps' => $steps,
            'employer' => $employer
        ]);
    }

    /**
     * Cancel an Employer.
     */
    public function cancelEmployer(Request $request, Employer $employer)
    {
        if (!auth()->user()->can('edit-employers')) {
            abort(403);
        }

        DB::transaction(function () use ($employer) {
            // 1. Update Order Status
            $order = ProductionOrder::where('employer_id', $employer->id)
                ->whereIn('status', ['registration_resolution', 'registration_resolution_cancelled'])
                ->first();

            if ($order) {
                $order->update(['status' => 'registration_resolution_cancelled']);
            }

            // 2. Update Employees (Only Pending ones get cancelled)
            $employer->employees()
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
    public function restoreEmployer(Request $request, Employer $employer)
    {
        if (!auth()->user()->can('edit-employers')) {
            abort(403);
        }

        DB::transaction(function () use ($employer) {
            // 1. Update Order Status
            $order = ProductionOrder::where('employer_id', $employer->id)
                ->whereIn('status', ['registration_resolution', 'registration_resolution_cancelled'])
                ->first();

            if ($order) {
                $order->update(['status' => 'registration_resolution']);
            }

            // 2. Update Employees (Cancelled -> Pending)
            $employer->employees()
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
    public function finalize(Request $request, Employee $employee)
    {
        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        // Change status to 'registration_completed'
        $employee->update([
            'status' => 'registration_completed',
            'resolution_completed_at' => now()
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
    public function cancel(Request $request, Employee $employee)
    {
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
    public function restore(Request $request, Employee $employee)
    {
        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $employee->update([
            'status' => 'registration_pending',
            'resolution_completed_at' => null
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
    public function fetchHistory(Request $request, $employerId)
    {
        $employer = Employer::findOrFail($employerId);

        $employees = $employer->employees()
            ->where('status', 'registration_completed')
            ->where(function($q) {
                 $q->whereNotNull('resolution_completed_at')
                   ->where('resolution_completed_at', '<', now()->subHours(24));
            })
            ->with(['registrationSteps'])
            ->get();

        $steps = RegistrationStep::registration()->orderBy('order')->get();

        return view('production.registration._employee_list_content', [
            'employees' => $employees,
            'steps' => $steps,
            'employer' => $employer
        ])->with('isHistory', true);
    }

    /**
     * Soft delete an employee.
     */
    public function destroy(Request $request, Employee $employee)
    {
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
    public function bulkFinalize(Request $request)
    {
        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        Employee::whereIn('id', $request->input('employee_ids'))
            ->update(['status' => 'registration_completed']);

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
    public function restoreState(Request $request, Employee $employee)
    {
        return $this->restore($request, $employee);
    }

    /**
     * Display the Import View for Registration Resolution.
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

        $request->merge(['target_status' => 'registration_pending']);

        session()->flash('finish_route', route('production.registration.index'));

        // Hydrate imported employees from session IDs if available (Restoring Preview Feature)
        $sessionImportedEmployees = collect();
        if (session()->has('imported_employee_ids')) {
            $sessionImportedEmployees = Employee::whereIn('id', session('imported_employee_ids'))->get();
        }

        return view('employees.import', [
            'employers' => $employers,
            'production' => null,
            'back_route' => route('production.registration.index'),
            'sessionImportedEmployees' => $sessionImportedEmployees,
        ]);
    }

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

        return view('production.registration.create', [
            'employers' => $employers,
            'employer' => $selectedEmployer,
            'formAction' => route('production.registration.store')
        ]);
    }

    /**
     * Store a newly created registration employee.
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
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'employeeGender' => 'nullable|string|max:255',
            'employeeDob' => 'nullable|date',
            'employeeAge' => 'nullable|integer',
            'employeePhone' => 'nullable|string|max:255',
            'employeeNationality' => 'nullable|string|max:255',
            'passport_type_cambodia' => 'nullable|string|max:255',
            'employeePassport' => 'nullable|string|max:255',
            'passport_issue_date' => 'nullable|date',
            'passportExpiryDate' => 'nullable|date',
            'pinkCardNo' => 'nullable|string|max:255',
            'visaType' => 'nullable|string|max:255',
            'visaExpiryDate' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'job_description' => 'nullable|string',
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
            'employee_reference_id' => 'nullable|string|max:255',
            'insurance_type' => 'nullable|string|max:255',
            'insurance_detail' => 'nullable|string',
            'insurance_expiry_date' => 'nullable|date',
            'social_security_number' => 'nullable|string|max:255',
            'insurance_detail_hospital' => 'nullable|string|max:255',
            'insurance_detail_private' => 'nullable|string|max:255',
            'insurance_expiry_date_private' => 'nullable|string|max:255',
            'insurance_expiry_date_hospital' => 'nullable|string|max:255',
            'insurance_detail_social' => 'nullable|string|max:255',
            'employeeEmail' => 'nullable|email|max:255|unique:employees,email',
            'employeePassword' => 'nullable|string|min:8',
            'other_doc_1_desc' => 'nullable|string|max:255',
            'other_doc_2_desc' => 'nullable|string|max:255',
            'other_doc_3_desc' => 'nullable|string|max:255',
            'other_doc_4_desc' => 'nullable|string|max:255',
            'employeePhoto' => 'nullable|image|max:2048',
            'insurance_document_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'insurance_document_path_private' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
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
        ]);

        // Forced Status
        $validated['status'] = 'registration_pending';

        // Insurance Mapping (Same as EmployeeController)
        $validated['insuranceType'] = $validated['insurance_type'] ?? null;
        if ($validated['insuranceType'] === 'ประกันสังคม') {
            $validated['socialSecurityNumber'] = $validated['social_security_number'] ?? null;
            $validated['hospitalName'] = $validated['insurance_detail_social'] ?? null;
            $validated['insuranceCompany'] = null;
            $validated['insuranceExpiryDate'] = null;
        } elseif ($validated['insuranceType'] === 'ประกันเอกชน') {
            $validated['insuranceCompany'] = $validated['insurance_detail_private'] ?? null;
            $validated['insuranceExpiryDate'] = $validated['insurance_expiry_date_private'] ?? null;
            $validated['socialSecurityNumber'] = null;
            $validated['hospitalName'] = null;
        } elseif ($validated['insuranceType'] === 'ประกันโรงพยาบาล') {
            $validated['hospitalName'] = $validated['insurance_detail_hospital'] ?? null;
            $validated['insuranceExpiryDate'] = $validated['insurance_expiry_date_hospital'] ?? null;
            $validated['socialSecurityNumber'] = null;
            $validated['insuranceCompany'] = null;
        } else {
            $validated['socialSecurityNumber'] = null;
            $validated['hospitalName'] = null;
            $validated['insuranceCompany'] = null;
            $validated['insuranceExpiryDate'] = null;
        }

        $validated['email'] = $validated['employeeEmail'] ?? null;
        unset($validated['employeeEmail']);

        if (!empty($validated['employeePassword'])) {
            $validated['password'] = $validated['employeePassword'];
        }
        unset($validated['employeePassword']);

        // File Uploads
        $fileFields = [
            'employeePhoto', 'insurance_document_path','insurance_document_path_private',
            'employee_doc_1', 'employee_doc_2', 'employee_doc_3', 'employee_doc_4',
            'employee_doc_5', 'employee_doc_6', 'employee_doc_7', 'employee_doc_8',
            'employee_doc_9', 'employee_doc_10', 'employee_doc_11', 'employee_doc_12'
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

        return redirect()->route('production.registration.index')
                         ->with('success', 'Registration Employee created successfully.');
    }

    /**
     * Store a new registration step.
     */
    public function storeStep(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $maxOrder = RegistrationStep::registration()->max('order') ?? 0;

        RegistrationStep::create([
            'name' => $validated['name'],
            'order' => $maxOrder + 1,
            'type' => 'registration',
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
     * Delete a step.
     */
    public function destroyStep(RegistrationStep $step)
    {
        $step->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Reorder steps.
     */
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
            // Global Stats
            // Ensure no global scope issues, but handle if method doesn't exist just in case
            $allQuery = Employee::query();
            if (auth()->user()->can('manage-tickets')) {
                $allQuery->withoutGlobalScope('employerTenancy');
            }
            $allQuery->whereNull('deleted_at');

            if ($request->has('search') && $request->search) {
                $this->applySearchToQuery($allQuery, $request->search);
            }

            $allEmployees = $allQuery->whereIn('status', ['registration_pending', 'registration_completed'])
                                    ->with('registrationSteps')
                                    ->get();

            $steps = RegistrationStep::registration()->orderBy('order')->get();
            // Determine step 1 ID for "Not Started" logic
            $stepOneId = $steps->sortBy('order')->first()?->id;

            // Global Stats
            $globalStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
            $globalNotStarted = 0;

            foreach ($allEmployees as $emp) {
                // Count Not Started
                if ($stepOneId && !$emp->registrationSteps->contains('id', $stepOneId)) {
                    $globalNotStarted++;
                }

                $highest = $emp->registrationSteps->sortByDesc('order')->first();
                if ($highest && isset($globalStats[$highest->id])) {
                    $globalStats[$highest->id]++;
                }
            }

            // Employer Stats
            $empQuery = Employee::query();
            if (auth()->user()->can('manage-tickets')) {
                $empQuery->withoutGlobalScope('employerTenancy');
            }
            $empQuery->whereNull('deleted_at');

            $employerEmployeesQuery = $empQuery->where('employer_id', $employee->employer_id)
                                        ->whereIn('status', ['registration_pending', 'registration_completed'])
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
                 // Count Not Started
                 if ($stepOneId && !$emp->registrationSteps->contains('id', $stepOneId)) {
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
    public function storeCustomField(Request $request, Employee $employee)
    {
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
    public function storeEmployerCustomField(Request $request, Employer $employer)
    {
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
    public function updateResolutionStatus(Request $request, Employer $employer)
    {
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
    public function updateEmployerCustomField(Request $request, ProductionCustomField $field)
    {
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

    public function destroyCustomField(EmployeeCustomField $field)
    {
        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        if ($field->field_type === 'file' && $field->file_path) {
            Storage::disk('public')->delete($field->file_path);
        }
        $field->delete();
        return back()->with('success', 'Field removed.');
    }

    public function destroyEmployerCustomField(ProductionCustomField $field)
    {
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

        $stats = [
            'global' => [
                'total' => $globalTotal,
                'not_started' => $globalNotStarted,
                'cancelled' => $globalCancelled,
                'saved' => $globalSaved,
                'employers_count' => $globalEmployers,
                'biometrics_collected' => $globalBiometrics
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
                'biometrics_collected' => $empBiometrics
            ];
        }

        return $stats;
    }

    /**
     * Update Biometrics Collection Status and File.
     */
    public function updateBiometrics(Request $request, Employee $employee)
    {
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
    public function toggleBiometrics(Request $request, Employee $employee)
    {
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
        $cleanedSearch = str_replace(' ', '', $search);

        return $query->where(function($q) use ($search, $cleanedSearch) {
            $q->where('employeeNameTh', 'like', "%{$search}%")
              ->orWhere('employeeNameEn', 'like', "%{$search}%")
              ->orWhere('employeePassport', 'like', "%{$search}%")
              // Robust Name Search (Ignore Spaces)
              ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
              ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])

              ->orWhereHas('employer', function($q2) use ($search, $cleanedSearch) {
                  $q2->where('employerNameTh', 'like', "%{$search}%")
                     ->orWhere('employerNameEn', 'like', "%{$search}%")
                     ->orWhereRaw("REPLACE(employerNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                     ->orWhereRaw("REPLACE(employerNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                     ->orWhereHas('jobOwner', function($q3) use ($search) {
                         $q3->where('name', 'like', "%{$search}%");
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
                  ->orWhere('employeePassport', 'like', "%{$search}%")
                  // Robust Name Search
                  ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", ["%{$cleanedSearch}%"])
                  ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", ["%{$cleanedSearch}%"]);
            });
        }
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
        if ($request->has('appointment_date')) {
            $data['appointment_date'] = $request->appointment_date;
        }
        if ($request->has('appointment_location')) {
            $data['appointment_location'] = $request->appointment_location;
        }

        $employee->update($data);

        return response()->json(['success' => true]);
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
            ['notification_type' => 'registration_appointment'],
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

        $query = Employee::query();
        if (auth()->user()->can('manage-tickets')) {
            $query->withoutGlobalScope('employerTenancy');
        }

        $counts = $query->select(DB::raw('DATE(appointment_date) as date'), DB::raw('count(*) as count'))
            ->whereBetween('appointment_date', [$start, $end])
            ->whereIn('status', ['registration_pending', 'registration_completed'])
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

        $query = Employee::query();
        if (auth()->user()->can('manage-tickets')) {
            $query->withoutGlobalScope('employerTenancy');
        }

        $employees = $query->whereDate('appointment_date', $date)
            ->whereIn('status', ['registration_pending', 'registration_completed'])
            ->whereNull('appointment_completed_at') // Exclude completed
            ->with(['employer', 'registrationSteps'])
            ->get();

        $steps = RegistrationStep::registration()->orderBy('order')->get();

        $html = view('production.registration.partials.day_appointments_list', compact('employees', 'steps'))->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Fetch Trash (Deleted Employees with Registration Status)
     */
    public function fetchTrash(Request $request)
    {
        $query = Employee::onlyTrashed()
            ->with(['employer' => fn($q) => $q->withTrashed(), 'registrationSteps'])
            ->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled'])
            ->latest('deleted_at');

        if ($request->has('search') && $request->search) {
             $search = $request->search;
             // Apply search logic manually to support withTrashed() on relations
             $query->where(function($q) use ($search) {
                 $q->where('employeeNameTh', 'like', "%{$search}%")
                   ->orWhere('employeeNameEn', 'like', "%{$search}%")
                   ->orWhere('employeePassport', 'like', "%{$search}%")
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
    public function restoreTrash(Request $request, $id)
    {
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
        $steps = RegistrationStep::registration()->orderBy('order')->get();
        return view('production.registration._employee_card', [
            'employee' => $employee,
            'steps' => $steps,
            'isHistory' => false
        ])->render();
    }
}
