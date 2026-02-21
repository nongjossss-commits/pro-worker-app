<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionOrder;
use App\Models\SystemConfig;
use App\Models\RegistrationStep;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
    public function index(Request $request)
    {
        // 1. Fetch Workflow Steps (Needed for stats calculation and view)
        $steps = RegistrationStep::renewal()->orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;
        $lastStepId = $steps->sortByDesc('order')->first()?->id;

        // --- 2. Global Employee Query (Lightweight) ---
        $employeeQuery = Employee::query()
            ->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled'])
            ->select('id', 'employer_id', 'status', 'employeeNameTh', 'employeeNameEn', 'employeeTitleTh', 'employeeTitleEn', 'employeePhoto', 'employeeNationality', 'operator_id');

        if (auth()->user()->can('manage-tickets')) {
            $employeeQuery->withoutGlobalScope('employerTenancy');
        }

        $employeeQuery->with(['registrationSteps' => function($q) {
                $q->select('registration_steps.id', 'registration_steps.order', 'employee_registration_status.employee_id');
            }]);

        if ($request->has('search') && $request->search) {
            $this->applySearchToQuery($employeeQuery, $request->search);
        }

        $allEmployees = $employeeQuery->get();

        // --- 3. Calculate Stats ---
        $totalEmployees = 0;
        $totalCancelled = 0;
        $totalSaved = 0;
        $notStartedCount = 0;
        $totalDailyCheckPending = 0;
        $stepStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();

        // Group by Employer
        $employeesByEmployer = $allEmployees->groupBy('employer_id');
        $filteredEmployerIds = $allEmployees->pluck('employer_id')->unique();

        foreach ($allEmployees as $emp) {
            if ($emp->status === 'renewal_cancelled') {
                $totalCancelled++;
            } else {
                $totalEmployees++;
                if ($emp->status === 'renewal_completed') {
                    $totalSaved++;
                }

                // Daily Check
                if ($emp->is_daily_check_pending) {
                    $totalDailyCheckPending++;
                }

                // Not Started Logic
                if ($stepOneId && !$emp->registrationSteps->contains('id', $stepOneId)) {
                    $notStartedCount++;
                }

                // Step Stats (Highest Step)
                $highestStep = $emp->registrationSteps->sortByDesc('order')->first();
                if ($highestStep && isset($stepStats[$highestStep->id])) {
                    $stepStats[$highestStep->id]++;
                }
            }
        }

        $totalEmployers = $filteredEmployerIds->count();

        // --- 4. Fetch Employers ---
        $employerQuery = Employer::withTrashed()->whereIn('id', $filteredEmployerIds)
            ->with(['jobOwner', 'customFields', 'addresses']);

        // NEW: Address options (before address filtering)
        $addressOptions = $this->getAddressOptions($employerQuery);

        // NEW: Apply address filters
        $employerQuery = $this->applyAddressFilters($employerQuery, $request);

        if (auth()->user()->can('manage-tickets')) {
            $employerQuery->withoutGlobalScope('employerTenancy');
        }

        if ($request->has('filter') && $request->filter) {
            $filter = $request->filter;
            $filteredEmployerIds = $filteredEmployerIds->filter(function($empId) use ($employeesByEmployer, $filter, $stepOneId) {
                $emps = $employeesByEmployer[$empId] ?? collect();
                if ($filter === 'saved') return $emps->contains('status', 'renewal_completed');
                if ($filter === 'cancelled') return $emps->contains('status', 'renewal_cancelled');

                if ($filter === 'not_started') {
                    return $emps->contains(function($e) use ($stepOneId) {
                         return $e->status !== 'renewal_cancelled' && !$e->registrationSteps->contains('id', $stepOneId);
                    });
                }

                if ($filter === 'pending_daily_check') {
                    return $emps->contains(function($e) {
                         return $e->status !== 'renewal_cancelled' && $e->is_daily_check_pending;
                    });
                }

                // Step Filter
                if (is_numeric($filter)) {
                    return $emps->contains(function($e) use ($filter) {
                         if ($e->status === 'renewal_cancelled') return false;
                         $h = $e->registrationSteps->sortByDesc('order')->first();
                         return $h && $h->id == $filter;
                    });
                }

                return true; // Default fallback
            });
            $employerQuery->whereIn('id', $filteredEmployerIds);
        }

        // Operator Filter (Server-Side)
        if ($request->has('operator_filter') && $request->operator_filter) {
            $opFilter = $request->operator_filter;
            $employerQuery->whereHas('employees', function($q) use ($opFilter) {
                $q->whereIn('status', ['renewal_pending', 'renewal_completed'])
                  ->where('operator_id', $opFilter);
            });
        }

        // Insurance Filter (Server-Side)
        if ($request->has('insurance_filter') && $request->insurance_filter) {
            $insFilter = $request->insurance_filter;
            $employerQuery->whereHas('employees', function($q) use ($insFilter) {
                $q->whereIn('status', ['renewal_pending', 'renewal_completed']);
                if ($insFilter === 'none') {
                    $q->where(function($sq) {
                        $sq->whereNull('insurance_type')->orWhere('insurance_type', '');
                    });
                } else {
                    $q->where('insurance_type', $insFilter);
                }
            });
        }

        // Calculate Cancelled Count (Global for these filtered IDs)
        $cancelledEmployersCount = Employer::whereIn('id', $filteredEmployerIds)
            ->whereHas('productionOrders', function($q) {
                $q->where('status', 'renewal_resolution_cancelled');
            })->count();

        // Active Operators for Filter
        $operatorIds = (clone $employeeQuery) // Reusing the global query which has operators
            ->whereIn('status', ['renewal_pending', 'renewal_completed'])
            ->whereNotNull('operator_id')
            ->distinct()
            ->pluck('operator_id');

        $activeOperators = User::whereIn('id', $operatorIds)->orderBy('name')->get(['id', 'name']);

        // All Users for Assignment
        $allUsers = User::orderBy('name')->get(['id', 'name']);

        $perPage = $request->input('per_page', 20);
        if (!in_array($perPage, [20, 50, 100])) {
            $perPage = 20;
        }

        // SORTING: Push cancelled employers to the end
        $employerQuery->orderByRaw("(
            SELECT CASE WHEN status = 'renewal_resolution_cancelled' THEN 1 ELSE 0 END
            FROM production_orders
            WHERE production_orders.employer_id = employers.id
            AND production_orders.status IN ('renewal_resolution', 'renewal_resolution_cancelled')
            LIMIT 1
        ) ASC");

        $employers = $employerQuery->paginate($perPage)->withQueryString();

        // --- 5. Process Employers ---
        foreach ($employers as $employer) {
            // Finance Order Logic
            $financeOrder = ProductionOrder::with(['financialGroups.transactions', 'financialGroups.advanceItems'])
                ->where('employer_id', $employer->id)
                ->whereIn('status', ['renewal_resolution', 'renewal_resolution_cancelled'])
                ->first();

            if (!$financeOrder) {
                $financeOrder = ProductionOrder::create([
                    'employer_id' => $employer->id,
                    'status'      => 'renewal_resolution',
                    'type'         => 'employer',
                    'project_name' => 'Renewal Resolution - ' . $employer->employerNameTh,
                    'financial_data' => []
                ]);
            }
            $employer->financeOrder = $financeOrder;

            if ($financeOrder->financialGroups->isEmpty()) {
                $financeOrder->financialGroups()->create([
                    'name' => 'General',
                    'financial_data' => $financeOrder->financial_data ?? []
                ]);
            }

            // Stats
            $myEmps = $employeesByEmployer[$employer->id] ?? collect();

            // Prepare Candidates List for Finance Tab
            $employer->activeEmployeesList = $myEmps->where('status', '!=', 'renewal_cancelled')->values();

            // Initialize Employer Stats
            $empStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
            $empNotStarted = 0;
            $empActiveCount = 0;
            $empCancelledCount = 0;
            $empSavedCount = 0;
            $empDailyCheckPending = 0;

            foreach ($myEmps as $emp) {
                if ($emp->status === 'renewal_cancelled') {
                    $empCancelledCount++;
                    continue;
                }

                $empActiveCount++;

                if ($emp->status === 'renewal_completed') {
                    $empSavedCount++;
                }

                if ($emp->is_daily_check_pending) {
                    $empDailyCheckPending++;
                }

                if ($stepOneId && !$emp->registrationSteps->contains('id', $stepOneId)) {
                    $empNotStarted++;
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
            $employer->dailyCheckPendingCount = $empDailyCheckPending;
        }

        // Get Current Expiry Setting
        $currentExpiryConfig = SystemConfig::where('key', 'renewal_target_expiry_date')->value('value');

        return view('production.renewal.index', compact(
            'totalEmployees',
            'totalCancelled',
            'totalSaved',
            'totalEmployers',
            'cancelledEmployersCount',
            'notStartedCount',
            'totalDailyCheckPending',
            'employers',
            'currentExpiryConfig',
            'steps',
            'stepStats',
            'lastStepId',
            'addressOptions',
            'activeOperators',
            'allUsers'
        ));
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

        $query->where(function($q) {
                $q->whereIn('status', ['renewal_pending', 'renewal_cancelled'])
                  ->orWhere(function($sub) {
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
            $query->where('operator_id', $request->operator_filter);
        }

        // Insurance Filter
        if ($request->has('insurance_filter') && $request->insurance_filter) {
            $insFilter = $request->insurance_filter;
            if ($insFilter === 'none') {
                $query->where(function($sq) {
                    $sq->whereNull('insurance_type')->orWhere('insurance_type', '');
                });
            } else {
                $query->where('insurance_type', $insFilter);
            }
        }

        if ($request->has('filter') && $request->filter) {
            $filter = $request->filter;
            if ($filter === 'saved') $query->where('status', 'renewal_completed');
            elseif ($filter === 'cancelled') $query->where('status', 'renewal_cancelled');
            elseif ($filter === 'not_started') {
                 $query->where('status', '!=', 'renewal_cancelled')
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

        // Refine Filter in PHP if needed (for Exact Highest Step)
        if ($request->has('filter') && is_numeric($request->filter)) {
            $filterStepId = $request->filter;
            $employees = $employees->filter(function($emp) use ($filterStepId) {
                if ($emp->status === 'renewal_cancelled') return false;
                $highest = $emp->registrationSteps->sortByDesc('order')->first();
                return $highest && $highest->id == $filterStepId;
            });
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
        $validated['status'] = 'renewal_pending';

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

            $allEmployees = $allQuery->whereIn('status', ['renewal_pending', 'renewal_completed'])
                                    ->with('registrationSteps')
                                    ->get();

            $steps = RegistrationStep::renewal()->orderBy('order')->get();
            $stepOneId = $steps->sortBy('order')->first()?->id;

            $globalStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
            $globalNotStarted = 0;

            foreach ($allEmployees as $emp) {
                if ($stepOneId && !$emp->registrationSteps->contains('id', $stepOneId)) {
                    $globalNotStarted++;
                }
                $highest = $emp->registrationSteps->sortByDesc('order')->first();
                if ($highest && isset($globalStats[$highest->id])) {
                    $globalStats[$highest->id]++;
                }
            }

            $empQuery = Employee::query();
            if (auth()->user()->can('manage-tickets')) {
                $empQuery->withoutGlobalScope('employerTenancy');
            }
            $empQuery->whereNull('deleted_at');

            $employerEmployeesQuery = $empQuery->where('employer_id', $employee->employer_id)
                                        ->whereIn('status', ['renewal_pending', 'renewal_completed'])
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

    public function toggleOperator(Request $request, $employeeId)
    {
        $employee = Employee::findOrFail($employeeId);

        if ($request->has('operator_id')) {
            $userId = $request->input('operator_id');
            if (empty($userId)) {
                $employee->update(['operator_id' => null]);
                $message = 'Operator unassigned.';
            } else {
                $user = User::find($userId);
                if ($user) {
                    $employee->update(['operator_id' => $user->id]);
                    $message = 'Operator assigned to ' . $user->name;
                } else {
                     return response()->json(['success' => false, 'message' => 'User not found'], 404);
                }
            }
        } else {
            $userId = auth()->id();
            if ($employee->operator_id === $userId) {
                $employee->update(['operator_id' => null]);
                $message = 'Operator unassigned.';
            } else {
                $employee->update(['operator_id' => $userId]);
                $message = 'Operator assigned to ' . auth()->user()->name;
            }
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'html' => $this->getEmployeeCardHtml($employee)
        ]);
    }

    public function updateInsurance(Request $request, Employee $employee)
    {
        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $validated = $request->validate([
            'insurance_type' => 'required|string',
            'social_security_number' => 'nullable|string',
            'hospital_name' => 'nullable|string',
            'sso_issue_date' => 'nullable|date',
            'sso_expiry_date' => 'nullable|date',
            'insurance_detail_hospital' => 'nullable|string',
            'insurance_expiry_date_hospital' => 'nullable|date',
            'insurance_detail_private' => 'nullable|string',
            'insurance_expiry_date_private' => 'nullable|date',
        ]);

        $insuranceType = $validated['insurance_type'];
        $updateData = ['insurance_type' => $insuranceType];

        if ($insuranceType === 'ประกันสังคม') {
            $updateData['social_security_number'] = $request->input('social_security_number');
            $updateData['sso_issue_date'] = $request->input('sso_issue_date');
            $updateData['sso_expiry_date'] = $request->input('sso_expiry_date');
            $updateData['hospital_name'] = $request->input('hospital_name');
        } elseif ($insuranceType === 'ประกันโรงพยาบาล') {
            $updateData['insurance_detail_hospital'] = $request->input('insurance_detail_hospital');
            $updateData['insurance_expiry_date_hospital'] = $request->input('insurance_expiry_date_hospital');
        } elseif ($insuranceType === 'ประกันเอกชน') {
            $updateData['insurance_detail_private'] = $request->input('insurance_detail_private');
            $updateData['insurance_expiry_date_private'] = $request->input('insurance_expiry_date_private');
        }

        $employee->update($updateData);

        return response()->json([
            'success' => true,
            'html' => $this->getEmployeeCardHtml($employee)
        ]);
    }
}
