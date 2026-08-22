<?php

namespace App\Helpers;

use App\Models\Employee;
use App\Models\Employer;

/**
 * Retroactive duplicate-data scan — surfaces identity-field collisions that
 * already exist in the database (created before EmployeeController::
 * checkDuplicate()/EmployerController::checkDuplicate() existed to warn
 * about them at save-time). Always a live query (no cached/stored "flag"
 * table), same style as CompletenessHelper::getIncompleteCount() — so a
 * group disappears from the list the instant someone fixes it, with
 * nothing left to go stale.
 */
class DuplicateDataHelper
{
    public const EMPLOYEE_FIELDS = [
        'employeePassport' => 'เลขพาสปอร์ต',
        'employeeWorkPermit' => 'เลขที่ใบอนุญาตทำงาน',
        'pinkCardNo' => 'เลขบัตรชมพู',
        'employee_id_number' => 'เลขประจำตัว',
    ];

    public const EMPLOYER_FIELDS = [
        'employerTaxId' => 'เลขประจำตัวนายจ้าง',
    ];

    public static function getGroupCount(): int
    {
        return count(self::getGroups());
    }

    /**
     * @return array<int, array{model: string, field: string, label: string, value: string, records: array}>
     */
    public static function getGroups(): array
    {
        $groups = [];

        foreach (self::EMPLOYEE_FIELDS as $field => $label) {
            foreach (self::duplicateValues(Employee::class, $field) as $value) {
                $matches = Employee::withoutGlobalScope('employerTenancy')
                    ->with('employer')
                    ->where($field, $value)
                    ->get();

                $groups[] = [
                    'model' => 'employee',
                    'field' => $field,
                    'label' => $label,
                    'value' => $value,
                    'records' => $matches->map(function (Employee $employee) {
                        return [
                            'id' => $employee->id,
                            'name' => $employee->employeeNameTh ?: $employee->employeeNameEn,
                            'sub_label' => $employee->employer->employerNameTh ?? '-',
                            'photo_url' => $employee->photo_url,
                            'edit_url' => route('employees.edit', $employee->id),
                            'terminated' => (bool) $employee->terminated_at,
                        ];
                    })->all(),
                ];
            }
        }

        foreach (self::EMPLOYER_FIELDS as $field => $label) {
            foreach (self::duplicateValues(Employer::class, $field) as $value) {
                $matches = Employer::withoutGlobalScope('employerTenancy')
                    ->where($field, $value)
                    ->get();

                $groups[] = [
                    'model' => 'employer',
                    'field' => $field,
                    'label' => $label,
                    'value' => $value,
                    'records' => $matches->map(function (Employer $employer) {
                        return [
                            'id' => $employer->id,
                            'name' => $employer->employerNameTh,
                            'sub_label' => $employer->businessType ?: '-',
                            'photo_url' => null,
                            'edit_url' => route('employers.edit', $employer->id),
                            'terminated' => false,
                        ];
                    })->all(),
                ];
            }
        }

        return $groups;
    }

    /**
     * @return array<int, string>
     */
    protected static function duplicateValues(string $modelClass, string $field): array
    {
        return $modelClass::withoutGlobalScope('employerTenancy')
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->select($field)
            ->groupBy($field)
            ->havingRaw('COUNT(*) > 1')
            ->pluck($field)
            ->all();
    }
}
