<?php

namespace App\Support;

use App\Models\SuperAdminSetting;

/**
 * Names of the employee "Other Document 1–10" attachment slots (employee_doc_9
 * – employee_doc_18) as set by Super Admin → Settings → Attachment Files
 * Settings (keys employee_other_N_desc). Falls back to "Other Document N".
 *
 * Shared lists (advanced download / export / edit, completeness settings…)
 * use these central names. An employee's own edit form / preview shows that
 * employee's renamed title (other_doc_N_desc) when an admin changed it.
 */
class OtherDocumentNames
{
    protected static ?array $cache = null;

    /** Name of "Other Document $n" (1–10). */
    public static function employee(int $n): string
    {
        if (self::$cache === null) {
            try {
                self::$cache = SuperAdminSetting::where('key', 'like', 'employee_other_%_desc')->pluck('value', 'key')->all();
            } catch (\Throwable $e) {
                self::$cache = [];
            }
        }
        $name = trim((string) (self::$cache["employee_other_{$n}_desc"] ?? ''));

        return $name !== '' ? $name : __('Other Document :n', ['n' => $n]);
    }

    /** Name for an attachment slot number 9–18 (employee_doc_N). */
    public static function forSlot(int $slot): string
    {
        return self::employee($slot - 8);
    }

    /** The title an employee's own slot shows: their renamed title, else the central name. */
    public static function forEmployee($employee, int $n): string
    {
        $own = trim((string) ($employee?->{"other_doc_{$n}_desc"} ?? ''));

        return $own !== '' ? $own : self::employee($n);
    }

    public static function forget(): void
    {
        self::$cache = null;
    }
}
