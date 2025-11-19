<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class IncompleteEmployeeController extends Controller
{
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

        if (empty($mandatoryFields)) {
            return view('admin.incomplete_employees.index', [
                'employees' => collect([]),
                'mandatoryFields' => $mandatoryFields,
                'totalIncomplete' => 0,
                'is_incomplete_view' => true
            ]);
        }

        // 2. Build Query
        $query = Employee::query();

        // Filter to only show active employees (not terminated)
        $query->whereNull('terminated_at');

        // Use a closure to group the OR conditions: (field1 IS NULL OR field1 = '' OR field2 IS NULL ...)
        $query->where(function (Builder $q) use ($mandatoryFields) {
            foreach ($mandatoryFields as $field) {
                $q->orWhereNull($field)
                  ->orWhere($field, '');
            }
        });

        // 3. Paginate
        $perPage = $request->input('per_page', 12);
        $employees = $query->latest()->paginate($perPage)->withQueryString();

        return view('admin.incomplete_employees.index', [
            'employees' => $employees,
            'mandatoryFields' => $mandatoryFields,
            'totalIncomplete' => $employees->total(),
            'is_incomplete_view' => true
        ]);
    }
}
