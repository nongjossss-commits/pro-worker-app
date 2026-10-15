<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use App\Traits\AddressFilterTrait;

class IncompleteEmployeeController extends Controller
{
    use AddressFilterTrait;

    public function index(Request $request)
    {
        // 1. Get mandatory fields from config
        $config = SystemConfig::where('key', 'employee_mandatory_fields')->first();
        $mandatoryFields = $config ? json_decode($config->value, true) : [];

        // --- Sanitize Fields ---
        // Ensure we only check against columns that actually exist in the 'employees' table.
        // This prevents SQL errors if the config contains legacy/invalid keys.
        if (!empty($mandatoryFields)) {
            $validColumns = Schema::getColumnListing('employees');
            $mandatoryFields = array_intersect($mandatoryFields, $validColumns);
        }

        $query = Employee::query();
        $query->whereNull('terminated_at');

        // Two-stage filter — see EmployeeController::index() for the same pattern.
        $employerAlreadyFiltered = $request->filled('employer_filter_id');
        if ($employerAlreadyFiltered) {
            $query->where('employer_id', $request->input('employer_filter_id'));
        }


        // --- ADDED FILTERING LOGIC ---
        // Every term must match (TwoStageSearch): box 1's typed text (when it isn't a
        // pick) and box 2's — an employee field, or (unless one employer is locked)
        // the employer / job owner / address.
        foreach (\App\Services\TwoStageSearch::terms($request) as $rawSearch) {
            if (preg_match('/^ID:\s*(\d+)$/i', $rawSearch, $idMatch)) {
                $query->where('id', (int) $idMatch[1]);
                continue;
            }
            \App\Services\TwoStageSearch::whereEmployeeOrEmployer($query, $rawSearch, !$employerAlreadyFiltered);
        }
        \App\Services\TwoStageSearch::applyEmployeePicks($query, $request);

        if ($request->filled('work_permit_expiry_date')) {
            // Work permit OR visa expiring on that date.
            $query->where(function ($q) use ($request) {
                $q->whereDate('workPermitExpiryDate', $request->input('work_permit_expiry_date'))
                  ->orWhereDate('visaExpiryDate', $request->input('work_permit_expiry_date'));
            });
        }

        if ($request->filled('nationality')) {
            $query->where('employeeNationality', $request->input('nationality'));
        }

        if ($request->filled('mou_group')) {
            $query->where('workPermitMOUGroup', $request->input('mou_group'));
        }

        if ($request->filled('pink_card')) {
            if ($request->input('pink_card') === 'yes') {
                $query->where(function ($q) {
                    $q->whereNotNull('pinkCardNo')->where('pinkCardNo', '!=', '');
                });
            } elseif ($request->input('pink_card') === 'no') {
                $query->where(function ($q) {
                    $q->whereNull('pinkCardNo')->orWhere('pinkCardNo', '=', '');
                });
            }
        }

        if ($request->filled('passport_type_myanmar')) {
            $query->where('passportType', $request->input('passport_type_myanmar'));
        }

        if ($request->filled('passport_type_cambodia')) {
            $query->where('passport_type_cambodia', $request->input('passport_type_cambodia'));
        }
        // --- END: ADDED FILTERING LOGIC ---

        // NEW: Address options (before address filtering)
        $addressOptions = $this->getAddressOptions($query, 'employer_id');

        // NEW: Apply address filters
        $query = $this->applyAddressFilters($query, $request, 'employer');

        // Only apply mandatory fields check if there are mandatory fields defined
        if (!empty($mandatoryFields)) {
            // Use a closure to group the OR conditions: (field1 IS NULL OR field1 = '' OR field2 IS NULL ...)
            $query->where(function (Builder $q) use ($mandatoryFields) {
                foreach ($mandatoryFields as $field) {
                    $q->orWhereNull($field)
                      ->orWhere($field, '');
                }
            });
        } else {
             // If no mandatory fields are defined, technically no employee is "incomplete" based on this logic.
             // So we should return an empty result set.
             // However, the original code returned an empty view.
             // To be consistent with original logic:
             return view('admin.incomplete_employees.index', [
                'employees' => collect([]),
                'mandatoryFields' => $mandatoryFields,
                'totalIncomplete' => 0,
                'is_incomplete_view' => true,
                'perPageOptions' => [12, 25, 50, 100],
                'currentPerPage' => 12,
                'currentView' => 'card',
                'addressOptions' => $addressOptions
            ]);
        }

        $totalIncomplete = (clone $query)->count(); // Count matching filter AND incomplete criteria

        // 3. Paginate
        $perPageOptions = [12, 25, 50, 100];
        $currentPerPage = $request->input('per_page', 12);
        $employees = $query->with('employer')->latest()->paginate($currentPerPage)->withQueryString();
        \App\Models\Employee::preloadForCards($employees);

        return view('admin.incomplete_employees.index', [
            'employees' => $employees,
            'mandatoryFields' => $mandatoryFields,
            'totalIncomplete' => $totalIncomplete, // This is now the filtered count
            'is_incomplete_view' => true,
            'perPageOptions' => $perPageOptions,
            'currentPerPage' => $currentPerPage,
            'currentView' => $request->input('view', 'card'),
            'addressOptions' => $addressOptions
        ]);
    }

    /**
     * Live-search preview for the index() search box — same fields index()
     * matches on, scoped to the same "has an incomplete mandatory field"
     * base condition so a suggestion never yields zero results on click.
     */
    public function previewSearch(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $config = SystemConfig::where('key', 'employee_mandatory_fields')->first();
        $mandatoryFields = $config ? json_decode($config->value, true) : [];
        if (!empty($mandatoryFields)) {
            $validColumns = Schema::getColumnListing('employees');
            $mandatoryFields = array_intersect($mandatoryFields, $validColumns);
        }
        if (empty($mandatoryFields)) {
            return response()->json([]);
        }

        // Same as the Employees menu, limited to active employees with an empty
        // mandatory field (TwoStageSearch).
        return response()->json(\App\Services\TwoStageSearch::preview($request, $q, fn () => Employee::whereNull('terminated_at')
            ->where(function (Builder $mq) use ($mandatoryFields) {
                foreach ($mandatoryFields as $field) {
                    $mq->orWhereNull($field)->orWhere($field, '');
                }
            })));
    }

    /**
     * Live-search preview for box 1 of index() — same combined
     * (employer + incomplete-employee) pattern as EmployeeController's
     * previewSearchCombined().
     */
    public function previewSearchCombined(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $config = SystemConfig::where('key', 'employee_mandatory_fields')->first();
        $mandatoryFields = $config ? json_decode($config->value, true) : [];
        if (!empty($mandatoryFields)) {
            $validColumns = Schema::getColumnListing('employees');
            $mandatoryFields = array_intersect($mandatoryFields, $validColumns);
        }
        if (empty($mandatoryFields)) {
            return response()->json([]);
        }

        // Same as the Employees menu, limited to active employees with an empty
        // mandatory field (TwoStageSearch).
        return response()->json(\App\Services\TwoStageSearch::preview($request, $q, fn () => Employee::whereNull('terminated_at')
            ->where(function (Builder $mq) use ($mandatoryFields) {
                foreach ($mandatoryFields as $field) {
                    $mq->orWhereNull($field)->orWhere($field, '');
                }
            })));
    }
}
