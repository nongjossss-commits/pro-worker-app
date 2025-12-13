<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionOrder;
use App\Models\RegistrationStep;
use App\Models\EmployeeCustomField;
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
        // Use the centralized count logic (reusing filter logic)
        $counts = $this->calculateCounts($request);

        $totalEmployees = $counts['global']['total']; // Active (Pending+Completed)
        $notStartedCount = $counts['global']['not_started'];
        $totalCancelled = $counts['global']['cancelled']; // New
        $totalSaved = $counts['global']['saved']; // New
        $totalEmployers = $counts['global']['total_employers'];

        // 1. Build Query for Employees List (Reuse logic for consistency)
        $query = Employee::whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled'])
                         ->with('registrationSteps');

        // Apply Search Filter (Duplicate logic from calculateCounts to ensure list matches stats)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('employeeNameTh', 'like', "%{$search}%")
                  ->orWhere('employeeNameEn', 'like', "%{$search}%")
                  ->orWhere('employeePassport', 'like', "%{$search}%")
                  ->orWhereHas('employer', function($q2) use ($search) {
                      $q2->where('employerNameTh', 'like', "%{$search}%")
                         ->orWhere('employerNameEn', 'like', "%{$search}%");
                  });
            });
        }

        $registrationEmployees = $query->get();

        // 2. Fetch Workflow Steps
        $steps = RegistrationStep::orderBy('order')->get();
        $lastStepId = $steps->sortByDesc('order')->first()?->id;
        $stepOneId = $steps->sortBy('order')->first()?->id;

        // 3. Calculate "Highest Step" Stats
        $stepStats = $steps->pluck('id')->mapWithKeys(function ($id) {
            return [$id => 0];
        })->toArray();

        foreach ($registrationEmployees as $emp) {
            if ($emp->status === 'registration_cancelled') continue;

            $highestStep = $emp->registrationSteps->sortByDesc('order')->first();
            if ($highestStep) {
                if (isset($stepStats[$highestStep->id])) {
                    $stepStats[$highestStep->id]++;
                }
            }
        }

        // 4. Fetch Employers (Filtered)
        $filteredEmployerIds = $registrationEmployees->pluck('employer_id')->unique();

        $employers = Employer::whereIn('id', $filteredEmployerIds)
            ->with(['employees' => function($q) use ($registrationEmployees) {
                $q->whereIn('id', $registrationEmployees->pluck('id'))
                  ->with(['registrationSteps', 'customFields']);
            }])->get();

        foreach ($employers as $employer) {
            $financeOrder = ProductionOrder::firstOrCreate(
                [
                    'employer_id' => $employer->id,
                    'status'      => 'registration_resolution'
                ],
                [
                    'type'         => 'employer',
                    'project_name' => 'Registration Resolution - ' . $employer->employerNameTh,
                    'financial_data' => []
                ]
            );
            $employer->financeOrder = $financeOrder;
            $employer->financeOrder->setRelation('items', $employer->employees);

            // Calculate per-employer step stats
            $stats = $steps->pluck('id')->mapWithKeys(function ($id) {
                return [$id => 0];
            })->toArray();

            $employerNotStarted = 0;
            $employerCancelledCount = 0; // New
            $employerSavedCount = 0; // New

            foreach ($employer->employees as $emp) {
                // Cancelled Count
                if ($emp->status === 'registration_cancelled') {
                    $employerCancelledCount++;
                    continue; // Skip steps calculation for cancelled
                }

                // Saved Count
                if ($emp->status === 'registration_completed') {
                    $employerSavedCount++;
                }

                // Not Started Count
                if (!$emp->registrationSteps->contains('id', $stepOneId)) {
                    $employerNotStarted++;
                }

                // Steps Stats
                $highestStep = $emp->registrationSteps->sortByDesc('order')->first();
                if ($highestStep) {
                    if (isset($stats[$highestStep->id])) {
                        $stats[$highestStep->id]++;
                    }
                }
            }

            $employer->stepStats = $stats;
            $employer->notStartedCount = $employerNotStarted;
            $employer->activeEmployeesCount = $employer->employees->where('status', '!=', 'registration_cancelled')->count();
            // Attach new counts
            $employer->cancelledCount = $employerCancelledCount;
            $employer->savedCount = $employerSavedCount;
        }

        return view('production.registration.index', compact(
            'totalEmployees',
            'totalEmployers',
            'notStartedCount',
            'totalCancelled', // New
            'totalSaved', // New
            'steps',
            'stepStats',
            'employers',
            'lastStepId'
        ));
    }

    /**
     * Helper to Calculate Counts (Respecting Search Filters)
     */
    private function calculateCounts(Request $request, $employerId = null)
    {
        // Base Query (Always fetch ALL relevant records first to calculate accurate Global stats)
        $query = Employee::whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled']);

        // NOTE: We do NOT filter by $employerId here yet.
        // We need the full dataset to calculate global totals.
        // We will filter in memory for the specific employer stats later.

        // Apply Search (Same logic as index)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('employeeNameTh', 'like', "%{$search}%")
                  ->orWhere('employeeNameEn', 'like', "%{$search}%")
                  ->orWhere('employeePassport', 'like', "%{$search}%")
                  ->orWhereHas('employer', function($q2) use ($search) {
                      $q2->where('employerNameTh', 'like', "%{$search}%")
                         ->orWhere('employerNameEn', 'like', "%{$search}%");
                  });
            });
        }

        // We need to fetch basic data to determine "Not Started" (requires relationship)
        // Optimization: Select only necessary columns and eager load minimal relation
        $employees = $query->with('registrationSteps:id')->get(['id', 'status', 'employer_id']);

        $steps = RegistrationStep::orderBy('order')->get(['id', 'order']);
        $stepOneId = $steps->sortBy('order')->first()?->id;

        // Global Counters
        $globalTotal = $employees->where('status', '!=', 'registration_cancelled')->count();
        $globalCancelled = $employees->where('status', 'registration_cancelled')->count();
        $globalSaved = $employees->where('status', 'registration_completed')->count();
        $globalEmployers = $employees->pluck('employer_id')->unique()->count();

        $globalNotStarted = $employees->filter(function ($emp) use ($stepOneId) {
             if ($emp->status === 'registration_cancelled') return false;
             return !$emp->registrationSteps->contains('id', $stepOneId);
        })->count();

        // Build Response
        $result = [
            'global' => [
                'total' => $globalTotal,
                'not_started' => $globalNotStarted,
                'cancelled' => $globalCancelled,
                'saved' => $globalSaved,
                'total_employers' => $globalEmployers
            ],
            'employer' => null
        ];

        // If specific employer requested, calculate their specific stats from the filtered set
        if ($employerId) {
            $empEmployees = $employees->where('employer_id', $employerId);

            $empTotal = $empEmployees->where('status', '!=', 'registration_cancelled')->count();
            $empCancelled = $empEmployees->where('status', 'registration_cancelled')->count();
            $empSaved = $empEmployees->where('status', 'registration_completed')->count();
            $empNotStarted = $empEmployees->filter(function ($emp) use ($stepOneId) {
                 if ($emp->status === 'registration_cancelled') return false;
                 return !$emp->registrationSteps->contains('id', $stepOneId);
            })->count();

            $result['employer'] = [
                'id' => $employerId,
                'total' => $empTotal,
                'not_started' => $empNotStarted,
                'cancelled' => $empCancelled,
                'saved' => $empSaved
            ];
        }

        return $result;
    }

    /**
     * Finalize an employee (Save to Database).
     */
    public function finalize(Request $request, Employee $employee)
    {
        $employee->update(['status' => 'registration_completed']);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'counts' => $this->calculateCounts($request, $employee->employer_id)
            ]);
        }
        return back()->with('success', 'Employee saved to database.');
    }

    /**
     * Cancel an employee (Grey out).
     */
    public function cancel(Request $request, Employee $employee)
    {
        $employee->update(['status' => 'registration_cancelled']);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'counts' => $this->calculateCounts($request, $employee->employer_id)
            ]);
        }
        return back()->with('success', 'Employee registration cancelled.');
    }

    /**
     * Restore an employee (Back to pending).
     */
    public function restore(Request $request, Employee $employee)
    {
        $employee->update(['status' => 'registration_pending']);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'counts' => $this->calculateCounts($request, $employee->employer_id)
            ]);
        }
        return back()->with('success', 'Employee restored to pending.');
    }

    /**
     * Soft delete an employee.
     */
    public function destroy(Request $request, Employee $employee)
    {
        $employerId = $employee->employer_id;
        $employee->delete(); // Soft delete

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'counts' => $this->calculateCounts($request, $employerId)
            ]);
        }
        return back()->with('success', 'Employee deleted.');
    }

    /**
     * Bulk Finalize employees.
     */
    public function bulkFinalize(Request $request)
    {
        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        Employee::whereIn('id', $request->input('employee_ids'))
            ->update(['status' => 'registration_completed']);

        if ($request->ajax()) {
            // For bulk, we might affect multiple employers.
            // For simplicity/performance, return global counts.
            // If the UI needs per-employer updates for bulk actions across employers, logic gets complex.
            // Assuming bulk action usually refreshes or we just return global.
            return response()->json([
                'success' => true,
                'counts' => $this->calculateCounts($request)
            ]);
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

        return view('employees.import', [
            'employers' => $employers,
            'production' => null,
            'back_route' => route('production.registration.index'),
        ]);
    }

    public function create(Request $request)
    {
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

        $maxOrder = RegistrationStep::max('order') ?? 0;

        RegistrationStep::create([
            'name' => $validated['name'],
            'order' => $maxOrder + 1,
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
     * Update progress (Toggle a step for an employee).
     */
    public function updateProgress(Request $request, Employee $employee)
    {
        try {
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
            // Reusing calculateCounts logic here would be ideal but response structure differs (steps vs totals).
            // For now, I'll update the counts via calculateCounts and append them to the existing response.

            $counts = $this->calculateCounts($request, $employee->employer_id);

            // Existing logic for step stats...
            $allQuery = Employee::query();
            if (method_exists($allQuery, 'withoutGlobalScopes')) {
                $allQuery->withoutGlobalScopes();
            }

            // ... (rest of existing Step Stats logic) ...
            // Wait, to avoid duplication and inconsistencies, I should probably rely on calculateCounts
            // for the basic counts and keep the step stats logic here.

            $allEmployees = $allQuery->whereIn('status', ['registration_pending', 'registration_completed'])
                                    ->with('registrationSteps')
                                    ->get();

            $steps = RegistrationStep::orderBy('order')->get();
            $stepOneId = $steps->sortBy('order')->first()?->id;

            $globalStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();

            foreach ($allEmployees as $emp) {
                $highest = $emp->registrationSteps->sortByDesc('order')->first();
                if ($highest && isset($globalStats[$highest->id])) {
                    $globalStats[$highest->id]++;
                }
            }

            $empQuery = Employee::query();
            if (method_exists($empQuery, 'withoutGlobalScopes')) {
                $empQuery->withoutGlobalScopes();
            }

            $employerStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
            $employerEmployees = $empQuery->where('employer_id', $employee->employer_id)
                                        ->whereIn('status', ['registration_pending', 'registration_completed'])
                                        ->with('registrationSteps')
                                        ->get();

            foreach ($employerEmployees as $emp) {
                 $highest = $emp->registrationSteps->sortByDesc('order')->first();
                 if ($highest && isset($employerStats[$highest->id])) {
                     $employerStats[$highest->id]++;
                 }
            }

            return response()->json([
                'success' => true,
                'globalStats' => $globalStats,
                'employerStats' => $employerStats,
                // Append the new counts
                'counts' => $counts
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating progress for employee {$employee->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        } catch (\Throwable $e) {
             DB::rollBack();
             Log::error("Fatal Error updating progress for employee {$employee->id}: " . $e->getMessage());
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
        $validated = $request->validate([
            'field_name' => 'required|string|max:255',
            'field_type' => 'required|in:text,date,file',
            'field_value' => 'nullable|string',
            'field_file' => 'nullable|file|max:10240', // 10MB
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
            $employee->load('customFields');
            return response()->json([
                'success' => true,
                'message' => 'Field added successfully.',
                'employee' => $employee,
                'newField' => $field
            ]);
        }

        return back()->with('success', 'Field added successfully.');
    }

    public function destroyCustomField(EmployeeCustomField $field)
    {
        if ($field->field_type === 'file' && $field->file_path) {
            Storage::disk('public')->delete($field->file_path);
        }
        $field->delete();
        return back()->with('success', 'Field removed.');
    }
}
