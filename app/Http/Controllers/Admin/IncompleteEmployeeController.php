<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class IncompleteEmployeeController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get mandatory fields
        $config = SystemConfig::where('key', 'employee_mandatory_fields')->first();
        $mandatoryFields = $config ? json_decode($config->value, true) : [];

        if (empty($mandatoryFields)) {
            return view('admin.incomplete_employees.index', [
                'employees' => collect([]),
                'mandatoryFields' => $mandatoryFields,
                'totalIncomplete' => 0,
                'is_incomplete_view' => true // Flag for view if needed
            ]);
        }

        // 2. Build Query
        $query = Employee::query();

        // Filter to only show active employees (not terminated)
        // The prompt implies "active" employees need to fill data.
        // "Terminated" employees typically don't update their data.
        // Assuming we only care about active employees.
        $query->whereNull('terminated_at');

        // Use a closure to group the OR conditions
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
