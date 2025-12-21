<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionOrder;
use App\Models\SystemConfig;
use App\Models\RegistrationStep;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RenewalController extends Controller
{
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
        $steps = RegistrationStep::orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;
        $lastStepId = $steps->sortByDesc('order')->first()?->id;

        // --- 2. Global Employee Query (Lightweight) ---
        $employeeQuery = Employee::query()
            ->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled'])
            ->select('id', 'employer_id', 'status')
            ->with(['registrationSteps' => function($q) {
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
        $employerQuery = Employer::whereIn('id', $filteredEmployerIds)
            ->with(['jobOwner', 'customFields']);

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

        $employers = $employerQuery->get();

        // --- 5. Process Employers ---
        foreach ($employers as $employer) {
            // Finance Order Logic
            $financeOrder = ProductionOrder::with('financialGroups.transactions')
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

            // Initialize Employer Stats
            $empStats = $steps->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();
            $empNotStarted = 0;
            $empActiveCount = 0;
            $empCancelledCount = 0;
            $empSavedCount = 0;

            foreach ($myEmps as $emp) {
                if ($emp->status === 'renewal_cancelled') {
                    $empCancelledCount++;
                    continue;
                }

                $empActiveCount++;

                if ($emp->status === 'renewal_completed') {
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

            $employer->stepStats = $empStats;
            $employer->notStartedCount = $empNotStarted;
            $employer->activeEmployeesCount = $empActiveCount;
            $employer->cancelledCount = $empCancelledCount;
            $employer->savedCount = $empSavedCount;
        }

        $employers = $employers->sortBy(function($emp) {
            return $emp->financeOrder->status === 'renewal_resolution_cancelled' ? 1 : 0;
        });

        $cancelledEmployersCount = $employers->where('financeOrder.status', 'renewal_resolution_cancelled')->count();

        // Get Current Expiry Setting
        $currentExpiryConfig = SystemConfig::where('key', 'renewal_target_expiry_date')->value('value');

        return view('production.renewal.index', compact(
            'totalEmployees',
            'totalCancelled',
            'totalSaved',
            'totalEmployers',
            'cancelledEmployersCount',
            'notStartedCount',
            'employers',
            'currentExpiryConfig',
            'steps',
            'stepStats',
            'lastStepId'
        ));
    }

    /**
     * AJAX Method to fetch employee list for an employer.
     */
    public function fetchEmployees(Request $request, Employer $employer)
    {
        $steps = RegistrationStep::orderBy('order')->get();
        $stepOneId = $steps->sortBy('order')->first()?->id;

        $query = $employer->employees()
            ->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled'])
            ->with(['registrationSteps', 'customFields']);

        if ($request->has('search') && $request->search) {
            $this->applyEmployerSearchToQuery($query, $employer, $request->search);
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
            }
            elseif (is_numeric($filter)) { // Step ID
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

        return view('employees.import', [
            'employers' => $employers,
            'production' => null,
            'back_route' => route('production.renewal.index'),
        ]);
    }

    /**
     * Import Store (Standard Excel Import)
     * Reuses ImportEmployeeController but sets status to renewal_pending via request param
     */
    // Note: The standard ImportEmployeeController handles the import and redirection.
    // We just need to ensure the form in `importView` posts to the right place or carries the status.
    // The `employees.import` route uses `ImportEmployeeController`.
    // We pass `target_status` in the request to `importView`, which `employees.import` view should respect if it uses it.
    // If `employees.import` view uses `request('target_status')` to set a hidden field, we are good.

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

    public function finalize(Request $request, Employee $employee)
    {
        if (!auth()->user()->can('edit-employees')) abort(403);
        $employee->update(['status' => 'renewal_completed']);
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Employee saved.');
    }

    public function cancel(Request $request, Employee $employee)
    {
        if (!auth()->user()->can('edit-employees')) abort(403);
        $employee->update(['status' => 'renewal_cancelled']);
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Employee cancelled.');
    }

    public function restore(Request $request, Employee $employee)
    {
        if (!auth()->user()->can('edit-employees')) abort(403);
        $employee->update(['status' => 'renewal_pending']);
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Employee restored.');
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
        return $query->where(function($q) use ($search) {
            $q->where('employeeNameTh', 'like', "%{$search}%")
              ->orWhere('employeeNameEn', 'like', "%{$search}%")
              ->orWhere('employeePassport', 'like', "%{$search}%")
              ->orWhereHas('employer', function($q2) use ($search) {
                  $q2->where('employerNameTh', 'like', "%{$search}%")
                     ->orWhere('employerNameEn', 'like', "%{$search}%");
              });
        });
    }

    private function applyEmployerSearchToQuery($query, $employer, $search)
    {
        // Simple search for now
        return $query->where(function($q) use ($search) {
            $q->where('employeeNameTh', 'like', "%{$search}%")
              ->orWhere('employeeNameEn', 'like', "%{$search}%")
              ->orWhere('employeePassport', 'like', "%{$search}%");
        });
    }
}
