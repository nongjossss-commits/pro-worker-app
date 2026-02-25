<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
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

        // --- ADDED FILTERING LOGIC ---
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', $searchTerm)
                  ->orWhere('employeeNameEn', 'like', $searchTerm)
                  ->orWhere('employeePassport', 'like', $searchTerm)
                  ->orWhere('pinkCardNo', 'like', $searchTerm)
                  ->orWhere('employeeWorkPermit', 'like', $searchTerm)
                  ->orWhere('employee_id_number', 'like', $searchTerm)
                  ->orWhere('name_list_number', 'like', $searchTerm)
                  ->orWhereHas('employer', function ($employerQuery) use ($searchTerm) {
                      $employerQuery->where('employerNameTh', 'like', $searchTerm)
                                    ->orWhere('employerNameEn', 'like', $searchTerm)
                                    ->orWhere(function($addrQ) use ($searchTerm) {
                                        $addrQ->filterByAddress($searchTerm);
                                    });
                  });
            });
        }

        if ($request->filled('work_permit_expiry_date')) {
            $query->whereDate('workPermitExpiryDate', $request->input('work_permit_expiry_date'));
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
}
