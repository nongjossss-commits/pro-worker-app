<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionOrder;
use App\Models\SystemConfig;
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
        // --- 1. Global Employee Query (Lightweight) ---
        $employeeQuery = Employee::query()
            ->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled'])
            ->select('id', 'employer_id', 'status');

        if ($request->has('search') && $request->search) {
            $this->applySearchToQuery($employeeQuery, $request->search);
        }

        $allEmployees = $employeeQuery->get();

        // --- 2. Calculate Stats ---
        $totalEmployees = 0;
        $totalCancelled = 0;
        $totalSaved = 0;
        $notStartedCount = 0; // Renewal might not have steps yet, but keeping structure

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
                } else {
                    $notStartedCount++; // Pending
                }
            }
        }

        $totalEmployers = $filteredEmployerIds->count();

        // --- 3. Fetch Employers ---
        $employerQuery = Employer::whereIn('id', $filteredEmployerIds)
            ->with(['jobOwner', 'customFields']);

        if ($request->has('filter') && $request->filter) {
            $filter = $request->filter;
            $filteredEmployerIds = $filteredEmployerIds->filter(function($empId) use ($employeesByEmployer, $filter) {
                $emps = $employeesByEmployer[$empId] ?? collect();
                if ($filter === 'saved') return $emps->contains('status', 'renewal_completed');
                if ($filter === 'cancelled') return $emps->contains('status', 'renewal_cancelled');
                if ($filter === 'pending') return $emps->contains('status', 'renewal_pending');
                return true;
            });
            $employerQuery->whereIn('id', $filteredEmployerIds);
        }

        $employers = $employerQuery->get();

        // --- 4. Process Employers ---
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
            $empActiveCount = 0;
            $empCancelledCount = 0;
            $empSavedCount = 0;

            foreach ($myEmps as $emp) {
                if ($emp->status === 'renewal_cancelled') {
                    $empCancelledCount++;
                } else {
                    $empActiveCount++;
                    if ($emp->status === 'renewal_completed') {
                        $empSavedCount++;
                    }
                }
            }

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
            'currentExpiryConfig'
        ));
    }

    /**
     * AJAX Method to fetch employee list for an employer.
     */
    public function fetchEmployees(Request $request, Employer $employer)
    {
        $query = $employer->employees()
            ->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled'])
            ->with(['customFields']);

        if ($request->has('search') && $request->search) {
            $this->applyEmployerSearchToQuery($query, $employer, $request->search);
        }

        if ($request->has('filter') && $request->filter) {
            $filter = $request->filter;
            if ($filter === 'saved') $query->where('status', 'renewal_completed');
            elseif ($filter === 'cancelled') $query->where('status', 'renewal_cancelled');
            elseif ($filter === 'pending') $query->where('status', 'renewal_pending');
        }

        $employees = $query->get();

        return view('production.renewal._employee_list_content', [
            'employees' => $employees,
            'employer' => $employer
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
