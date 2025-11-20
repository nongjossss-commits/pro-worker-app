<?php

namespace App\Helpers;

use App\Models\Employee;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\Schema;

class CompletenessHelper
{
    /**
     * Get the list of missing fields for a specific employee.
     *
     * @param Employee $employee
     * @param array|null $mandatoryFields
     * @return array
     */
    public static function getMissingFields(Employee $employee, ?array $mandatoryFields = null): array
    {
        if ($mandatoryFields === null) {
            $config = SystemConfig::where('key', 'employee_mandatory_fields')->first();
            $mandatoryFields = $config ? json_decode($config->value, true) : [];
        }

        if (empty($mandatoryFields)) {
            return [];
        }

        $validColumns = Schema::getColumnListing('employees');
        $validMandatoryFields = array_intersect($mandatoryFields, $validColumns);

        $missing = [];
        foreach ($validMandatoryFields as $field) {
            // Check if the field is null or empty string
            if (is_null($employee->$field) || (is_string($employee->$field) && trim($employee->$field) === '')) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Get total count of incomplete employees.
     *
     * @return int
     */
    public static function getIncompleteCount(): int
    {
        $config = SystemConfig::where('key', 'employee_mandatory_fields')->first();
        $mandatoryFields = $config ? json_decode($config->value, true) : [];

        if (empty($mandatoryFields)) {
            return 0;
        }

        $validColumns = Schema::getColumnListing('employees');
        $mandatoryFields = array_intersect($mandatoryFields, $validColumns);

        if (empty($mandatoryFields)) {
            return 0;
        }

        return Employee::whereNull('terminated_at')
            ->where(function ($query) use ($mandatoryFields) {
                foreach ($mandatoryFields as $field) {
                    $query->orWhereNull($field)
                          ->orWhere($field, '');
                }
            })
            ->count();
    }
}
