<?php

namespace App\Services;

use App\Exceptions\EmployeeQuotaExceededException;
use App\Models\Employee;
use App\Models\SuperAdminSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Encapsulates the Super-Admin-controlled cap on how many employees the
 * system is allowed to hold.
 *
 * The cap counts only ACTIVE employees — not terminated, not cancelled,
 * not soft-deleted — so the figure matches the badge on /employees and
 * deleting an employee genuinely frees a slot for a new one.
 *
 * Read paths cache for 60 seconds to keep the index/form pages snappy.
 * Mutation paths (`forgetCache`) bust the cache on changes (max value
 * saved, employee created, deleted, terminated, restored).
 */
class EmployeeQuotaService
{
    public const SETTING_KEY = 'max_employees';
    public const CACHE_MAX = 'employee_quota_max';
    public const CACHE_COUNT = 'employee_quota_count';
    public const CACHE_TTL = 60; // seconds

    /**
     * Configured maximum. Null/0 = unlimited (no cap).
     */
    public static function getMax(): ?int
    {
        return Cache::remember(self::CACHE_MAX, self::CACHE_TTL, function () {
            $setting = SuperAdminSetting::where('key', self::SETTING_KEY)->first();
            $value = $setting?->value;
            if ($value === null || $value === '') return null;
            $int = (int) $value;
            return $int > 0 ? $int : null;
        });
    }

    /**
     * Current count of active employees — matches the badge on /employees.
     * Bypasses the employerTenancy global scope so the count is the
     * system-wide total even when a non-admin user is browsing.
     */
    public static function getCurrentCount(): int
    {
        return Cache::remember(self::CACHE_COUNT, self::CACHE_TTL, function () {
            return Employee::query()
                ->withoutGlobalScope('employerTenancy')
                ->whereNull('terminated_at')
                ->where(function (Builder $q) {
                    $q->whereNotIn('status', ['registration_cancelled'])
                      ->orWhereNull('status');
                })
                ->count();
        });
    }

    public static function isLimitReached(): bool
    {
        $max = self::getMax();
        if (!$max) return false;
        return self::getCurrentCount() >= $max;
    }

    /**
     * Remaining slots — null when unlimited, otherwise max - current
     * (clamped to 0 so the UI never displays negative numbers if the
     * admin lowered the cap below the current population).
     */
    public static function getRemaining(): ?int
    {
        $max = self::getMax();
        if (!$max) return null;
        return max(0, $max - self::getCurrentCount());
    }

    /**
     * Bouncer used by Employee::creating(). Throws so the model event
     * cancels the INSERT and the controller/import flow can surface the
     * message via the exception's render() method.
     */
    public static function ensureCanCreate(int $additional = 1): void
    {
        $max = self::getMax();
        if (!$max) return;

        $current = self::getCurrentCount();
        if ($current + $additional > $max) {
            throw new EmployeeQuotaExceededException($current, $max, $additional);
        }
    }

    /**
     * Bust both caches. Called from Employee model events + the Super
     * Admin save handler.
     */
    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_MAX);
        Cache::forget(self::CACHE_COUNT);
    }
}
