<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionOrder;
use App\Models\RegistrationStep;
use App\Models\EmployeeCustomField;
use App\Models\ProductionCustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
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
        // 2. Fetch Workflow Steps (Needed for stats calculation and view)
        $steps = RegistrationStep::registration()->orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;
        $lastStepId = $steps->sortByDesc('order')->first()?->id;

        // --- 1. Global Employee Query (Lightweight) ---
        // Fetch ALL employees relevant to the current search to calculate stats in PHP.
        // We only fetch ID, Status, EmployerID to keep it fast.
        $employeeQuery = Employee::query()
            ->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled'])
            ->select('id', 'employer_id', 'status'); // Lightweight Select

        if (auth()->user()->can('manage-tickets')) {
            $employeeQuery->withoutGlobalScope('employerTenancy');
        }

        $employeeQuery->with(['registrationSteps' => function($q) {
                $q->select('registration_steps.id', 'registration_steps.order', 'employee_registration_status.employee_id'); // Lightweight Relation
            }]);

        // Apply Search (Same logic as before)
        if ($request->has('search') && $request->search) {
            $this->applySearchToQuery($employeeQuery, $request->search);
        }

        // Execute Lightweight Query
        $allEmployees = $employeeQuery->get();

        // --- 2. Calculate Stats (In PHP, O(N)) ---
        // Initialize Global Stats
        $totalEmployees = 0;
        $totalCancelled = 0;
        $totalSaved = 0;
        $notStartedCount = 0;
        $stepStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();

        // Group by Employer for Per-Employer Stats assignment later
        $employeesByEmployer = $allEmployees->groupBy('employer_id');
        $filteredEmployerIds = $allEmployees->pluck('employer_id')->unique();

        foreach ($allEmployees as $emp) {
            // Global Totals
            if ($emp->status === 'registration_cancelled') {
                $totalCancelled++;
            } else {
                // Active (Pending or Completed)
                $totalEmployees++;

                if ($emp->status === 'registration_completed') {
                    $totalSaved++;
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

        // --- 3. Fetch Employers (Optimization: No 'employees' eager load) ---
        $employerQuery = Employer::withTrashed()->whereIn('id', $filteredEmployerIds)
            ->with(['jobOwner', 'customFields']);

        if (auth()->user()->can('manage-tickets')) {
            $employerQuery->withoutGlobalScope('employerTenancy');
        }

        // Apply Server-Side Filtering to the Employer List if 'filter' param is present
        // Note: The filter logic here is slightly complex because we need to know WHICH employers have matching employees.
        // We can use the $employeesByEmployer collection we already have!
        if ($request->has('filter') && $request->filter) {
            $filter = $request->filter;
            // Filter the $filteredEmployerIds based on the $employeesByEmployer data
            $filteredEmployerIds = $filteredEmployerIds->filter(function($empId) use ($employeesByEmployer, $filter, $stepOneId) {
                $emps = $employeesByEmployer[$empId] ?? collect();

                if ($filter === 'cancelled_employer') {
                    // Check employer status - need to check financeOrder later or fetch it here.
                    // This is tricky because we fetch financeOrder later.
                    // Let's defer 'cancelled_employer' filter until after fetching employers?
                    // Or join/whereHas. 'cancelled_employer' is finance status.
                    // Let's handle it in the PHP loop below.
                    return true;
                }

                if ($filter === 'not_started') {
                    return $emps->contains(function($e) use ($stepOneId) {
                         return $e->status !== 'registration_cancelled' && !$e->registrationSteps->contains('id', $stepOneId);
                    });
                }
                if ($filter === 'saved') {
                     return $emps->contains('status', 'registration_completed');
                }
                if ($filter === 'cancelled') {
                     return $emps->contains('status', 'registration_cancelled');
                }
                // Step Filter
                return $emps->contains(function($e) use ($filter) {
                     if ($e->status === 'registration_cancelled') return false;
                     $h = $e->registrationSteps->sortByDesc('order')->first();
                     return $h && $h->id == $filter;
                });
            });

            // Re-apply IDs to query
            $employerQuery->whereIn('id', $filteredEmployerIds);
        }

        $employers = $employerQuery->get();

        // --- 4. Process Employers (Assign Stats) ---
        foreach ($employers as $employer) {
            // Finance Order Logic (Same as before)
            $financeOrder = ProductionOrder::with('financialGroups.transactions')
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

            // Ensure at least one default financial group exists
            if ($financeOrder->financialGroups->isEmpty()) {
                $financeOrder->financialGroups()->create([
                    'name' => 'General',
                    'financial_data' => $financeOrder->financial_data ?? []
                ]);
                $financeOrder->load('financialGroups.transactions');
            }

            // Calculate Employer-Specific Stats from our Lightweight Collection
            $myEmps = $employeesByEmployer[$employer->id] ?? collect();

            // Initialize Employer Stats
            $empStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
            $empNotStarted = 0;
            $empActiveCount = 0;
            $empCancelledCount = 0;
            $empSavedCount = 0;

            foreach ($myEmps as $emp) {
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

                $highestStep = $emp->registrationSteps->sortByDesc('order')->first();
                if ($highestStep && isset($empStats[$highestStep->id])) {
                    $empStats[$highestStep->id]++;
                }
            }

            // Assign stats to employer object (used in view)
            $employer->stepStats = $empStats;
            $employer->notStartedCount = $empNotStarted;
            $employer->activeEmployeesCount = $empActiveCount;
            $employer->cancelledCount = $empCancelledCount;
            $employer->savedCount = $empSavedCount;

            // NOTE: We do NOT assign $employer->employees here to keep response light.
            // The view will lazily load them.
        }

        // Post-Fetch Filter for Cancelled Employer
        if ($request->input('filter') === 'cancelled_employer') {
            $employers = $employers->filter(function($emp) {
                return $emp->financeOrder->status === 'registration_resolution_cancelled';
            });
        }

        // Sort Employers
        $employers = $employers->sortBy(function($emp) {
            return $emp->financeOrder->status === 'registration_resolution_cancelled' ? 1 : 0;
        });

        $cancelledEmployersCount = $employers->where('financeOrder.status', 'registration_resolution_cancelled')->count();

        return view('production.registration.index', compact(
            'totalEmployees',
            'totalCancelled',
            'totalSaved',
            'totalEmployers',
            'cancelledEmployersCount',
            'notStartedCount',
            'steps',
            'stepStats',
            'employers',
            'lastStepId'
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

        $steps = RegistrationStep::registration()->orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;

        // Apply the same base status filter as the index method to ensure consistency
        $query = $employer->employees();

        if (auth()->user()->can('manage-tickets')) {
            $query->withoutGlobalScope('employerTenancy');
        }

        $query->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled'])
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
        $employee->update(['status' => 'registration_completed']);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
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

        $employee->update(['status' => 'registration_pending']);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'stats' => $this->getStats($employee->employer_id, $request)
            ]);
        }
        return back()->with('success', 'Employee restored to pending.');
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

            return response()->json([
                'success' => true,
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

        $stats = [
            'global' => [
                'total' => $globalTotal,
                'not_started' => $globalNotStarted,
                'cancelled' => $globalCancelled,
                'saved' => $globalSaved,
                'employers_count' => $globalEmployers
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

            $stats['employer'] = [
                'id' => $employerId,
                'total' => $empTotal,
                'not_started' => $empNotStarted,
                'cancelled' => $empCancelled,
                'saved' => $empSaved
            ];
        }

        return $stats;
    }

    /**
     * Apply Search Filter to Global Query
     */
    private function applySearchToQuery($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('employeeNameTh', 'like', "%{$search}%")
              ->orWhere('employeeNameEn', 'like', "%{$search}%")
              ->orWhere('employeePassport', 'like', "%{$search}%")
              ->orWhereHas('employer', function($q2) use ($search) {
                  $q2->where('employerNameTh', 'like', "%{$search}%")
                     ->orWhere('employerNameEn', 'like', "%{$search}%")
                     ->orWhereHas('jobOwner', function($q3) use ($search) {
                         $q3->where('name', 'like', "%{$search}%");
                     });
              });
        });
    }

    /**
     * Apply Search Filter to Specific Employer's Employees
     */
    private function applyEmployerSearchToQuery($query, $employer, $search)
    {
        $employerMatches = false;
        if (stripos($employer->employerNameTh, $search) !== false ||
            stripos($employer->employerNameEn, $search) !== false) {
            $employerMatches = true;
        }

        if (!$employerMatches && $employer->jobOwner && stripos($employer->jobOwner->name, $search) !== false) {
            $employerMatches = true;
        }

        if ($employerMatches) {
            // If the employer itself matches the search term, we do NOT filter employees by name.
            // We show ALL employees for this employer (subject to other status filters).
            return $query;
        } else {
            // Employer does not match, so user is searching for specific employee
            return $query->where(function($q) use ($search) {
                $q->where('employeeNameTh', 'like', "%{$search}%")
                  ->orWhere('employeeNameEn', 'like', "%{$search}%")
                  ->orWhere('employeePassport', 'like', "%{$search}%");
            });
        }
    }
}
