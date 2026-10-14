<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SuperAdminSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Which employee details appear on the employee tiles (Employees, the
 * employer page's Employees tab, Groups & Teams, Central Trash, Employment
 * History). Chosen by Super Admin → Settings → "Employee Card Details".
 *
 * Only the detail lines under the employer name are configurable; photo,
 * names, nationality, employer, status and buttons always stay. Until a
 * Super Admin saves a choice, the cards show what they always did
 * (DEFAULT_FIELDS).
 */
class EmployeeCardFieldsService
{
    public const SETTING_KEY = 'employee_card.fields';
    public const CACHE_KEY = 'employee_card_fields';
    public const MAX_FIELDS = 6;
    public const DEFAULT_FIELDS = ['employeePassport', 'passportExpiryDate', 'workPermitMOUGroup', 'ninetyDayReportDate'];

    /** Per-request copy so a list of 100 cards reads the setting once. */
    protected static ?array $selected = null;

    /**
     * Selectable fields, grouped like the employee edit form.
     * Keys are columns of `employees` (plus the `age` accessor); values are
     * English labels passed through __() when shown.
     */
    public static function available(): array
    {
        return [
            'Personal Information' => [
                'father_name' => "Father's Name",
                'mother_name' => "Mother's Name",
                'employeeDob' => 'Date of Birth',
                'age' => 'Age',
                'employeePhone' => 'Phone',
                'email' => 'Email',
                'height' => 'Height',
                'weight' => 'Weight',
            ],
            'Passport & Visa' => [
                'employeePassport' => 'Passport No.',
                'passportType' => 'Passport Type (Myanmar)',
                'passport_type_cambodia' => 'Passport Type (Cambodia)',
                'passport_issue_date' => 'Passport Issue Date',
                'passport_issue_place' => 'Passport Issue Place',
                'passportExpiryDate' => 'Passport Expiry Date',
                'pinkCardNo' => 'Pink Card No.',
                'visaType' => 'Visa Type',
                'visaEndorsementDate' => 'Visa Endorsement Date',
                'visaEndorsementNo' => 'Visa Endorsement No.',
                'visa_issue_place' => 'Visa Issue Place',
                'visaExpiryDate' => 'Visa Expiry Date',
            ],
            'Employment & Work Permit' => [
                'job_title' => 'Job Title',
                'job_description' => 'Job Description',
                'department' => 'Department',
                'startDate' => 'Start Date',
                'workPermitMOUGroup' => 'Work Permit Type',
                'employeeWorkPermit' => 'Work Permit No.',
                'workPermitIssueDate' => 'Work Permit Issue Date',
                'workPermitExpiryDate' => 'Work Permit Expiry Date',
                'ninetyDayReportDate' => '90-Day Report',
                'name_list_number' => 'Name List No.',
                'request_number' => 'Request Number',
                'employee_id_number' => 'ID Number',
                'tax_id_number' => 'Tax ID',
                'employer_employee_id' => 'Employer-Employee ID',
                'employee_reference_id' => 'Reference ID',
                'outsource_code' => 'Outsource Code',
            ],
            'Insurance & Bank' => [
                'insurance_type' => 'Insurance Type',
                'social_security_number' => 'Social Security No.',
                'insurance_detail' => 'Hospital Rights',
                'insurance_detail_hospital' => 'Hospital Name',
                'insurance_expiry_date_hospital' => 'Hospital Insurance Expiry',
                'insurance_detail_private' => 'Insurance Company',
                'insurance_expiry_date_private' => 'Private Insurance Expiry',
                'bank_name' => 'Bank Name',
                'bank_account_number' => 'Bank Account Number',
            ],
        ];
    }

    /** Flat [key => English label]. */
    public static function labels(): array
    {
        return array_merge(...array_values(self::available()));
    }

    public static function label(string $key): string
    {
        return __(self::labels()[$key] ?? $key);
    }

    /** The chosen field keys, in display order (at most MAX_FIELDS). */
    public static function selected(): array
    {
        if (self::$selected !== null) {
            return self::$selected;
        }

        $raw = Cache::remember(self::CACHE_KEY, 300, function () {
            return SuperAdminSetting::where('key', self::SETTING_KEY)->value('value');
        });

        $fields = $raw === null ? self::DEFAULT_FIELDS : (json_decode($raw, true) ?: []);

        return self::$selected = self::clean($fields);
    }

    /** Keep known keys only, no duplicates, at most MAX_FIELDS. */
    public static function clean(array $fields): array
    {
        $known = self::labels();
        $fields = array_values(array_unique(array_filter($fields, fn ($f) => is_string($f) && isset($known[$f]))));

        return array_slice($fields, 0, self::MAX_FIELDS);
    }

    public static function save(array $fields): void
    {
        SuperAdminSetting::updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => json_encode(self::clean($fields))]
        );
        self::forgetCache();
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::$selected = null;
    }

    /** Display value for one field ('-' when empty; dates as d/m/Y). */
    public static function value(Employee $employee, string $key): string
    {
        $value = $employee->{$key};

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }
        if ($value === null || $value === '') {
            return '-';
        }

        return (string) $value;
    }
}
