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
        // 1. Build Query for Employees
        $query = Employee::whereIn('status', ['registration_pending', 'registration_completed'])
                         ->with('registrationSteps');

        // Apply Search Filter
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
        $totalEmployees = $registrationEmployees->count();
        $totalEmployers = $registrationEmployees->pluck('employer_id')->unique()->count();

        // 2. Fetch Workflow Steps
        $steps = RegistrationStep::orderBy('order')->get();

        // 3. Calculate "Highest Step" Stats
        // Initialize stats with 0
        $stepStats = $steps->pluck('id')->mapWithKeys(function ($id) {
            return [$id => 0];
        })->toArray();

        foreach ($registrationEmployees as $emp) {
            // Get the highest step 'order' this employee has completed
            // registrationSteps relationship returns the attached steps.
            $highestStep = $emp->registrationSteps->sortByDesc('order')->first();

            if ($highestStep) {
                // Increment stat for this specific step only
                if (isset($stepStats[$highestStep->id])) {
                    $stepStats[$highestStep->id]++;
                }
            }
        }

        // 4. Fetch Employers (Filtered by the same search criteria via employees)
        // We get the unique employer IDs from our filtered employees list
        $filteredEmployerIds = $registrationEmployees->pluck('employer_id')->unique();

        $employers = Employer::whereIn('id', $filteredEmployerIds)
            ->with(['employees' => function($q) use ($registrationEmployees) {
                // Only load the employees that match our main filter
                // We can use whereIn('id', ...)
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
            // Spoof items for the count logic in view
            $employer->financeOrder->setRelation('items', $employer->employees);

            // Calculate per-employer step stats (Highest Step Logic)
            $employer->stepStats = $steps->pluck('id')->mapWithKeys(function ($id) {
                return [$id => 0];
            })->toArray();

            foreach ($employer->employees as $emp) {
                $highestStep = $emp->registrationSteps->sortByDesc('order')->first();
                if ($highestStep) {
                    if (isset($employer->stepStats[$highestStep->id])) {
                        $employer->stepStats[$highestStep->id]++;
                    }
                }
            }
        }

        return view('production.registration.index', compact(
            'totalEmployees',
            'totalEmployers',
            'steps',
            'stepStats',
            'employers'
        ));
    }

    /**
     * Finalize an employee (Save to Database).
     */
    public function finalize(Request $request, Employee $employee)
    {
        // Change status to 'registration_completed'
        // This makes them visible in the main Employee list (since we only filter out 'pending')
        // AND keeps them visible here (since we include 'completed').
        $employee->update(['status' => 'registration_completed']);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Employee saved to database.');
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
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Selected employees saved to database.');
    }

    /**
     * Restore state (Undo Finalize).
     */
    public function restoreState(Request $request, Employee $employee)
    {
        $employee->update(['status' => 'registration_pending']);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Employee restored to pending state.');
    }

    /**
     * Show the form for creating a new registration employee.
     * Reuses the standard employee form view but we might need to inject context.
     */
    public function create(Request $request)
    {
        // We can reuse the standard view, or create a specific one.
        // Let's reuse the logic but pass a flag to the view if needed,
        // OR simply rely on the 'store' route being different.

        $employers = \App\Models\Employer::orderBy('employerNameTh')->get();
        $selectedEmployer = null;

        if ($request->has('employer_id')) {
            $selectedEmployer = \App\Models\Employer::find($request->employer_id);
        }

        // We will render a view that posts to the registration store route.
        // To avoid modifying the 'employees.create' view too much, we can pass a 'formAction' variable.
        return view('production.registration.create', [
            'employers' => $employers,
            'employer' => $selectedEmployer,
            'formAction' => route('production.registration.store')
        ]);
    }

    /**
     * Store a newly created registration employee.
     * Logic is copied from EmployeeController@store but sets status = 'registration_pending'.
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
     * Update a step (rename).
     */
    public function updateStep(Request $request, RegistrationStep $step)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $step->update(['name' => $validated['name']]);

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
        $validated = $request->validate([
            'step_id' => 'required|exists:registration_steps,id',
            'completed' => 'required|boolean',
        ]);

        if ($validated['completed']) {
            $employee->registrationSteps()->syncWithoutDetaching([
                $validated['step_id'] => ['completed_at' => now()]
            ]);
        } else {
            $employee->registrationSteps()->detach($validated['step_id']);
        }

        // --- Recalculate Stats for Response (Highest Step Logic) ---
        // Global Stats
        $allEmployees = Employee::whereIn('status', ['registration_pending', 'registration_completed'])
                                ->with('registrationSteps')
                                ->get();
        $steps = RegistrationStep::orderBy('order')->get();
        $globalStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();

        foreach ($allEmployees as $emp) {
            $highest = $emp->registrationSteps->sortByDesc('order')->first();
            if ($highest && isset($globalStats[$highest->id])) {
                $globalStats[$highest->id]++;
            }
        }

        // Employer Stats
        $employerStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
        $employerEmployees = Employee::where('employer_id', $employee->employer_id)
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
            'employerId' => $employee->employer_id
        ]);
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
        }

        EmployeeCustomField::create($data);

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
