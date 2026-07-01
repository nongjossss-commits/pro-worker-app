<?php

namespace App\Services;

use App\Models\ServiceContract;
use Illuminate\Support\Facades\Cache;

/**
 * Central authority for "what mode is the whole installation in right now?"
 *
 * Modes:
 *   - active         : now ≤ effective_end
 *   - grace          : end_date < now ≤ grace_end_date (temporary extension on)
 *   - read_only      : now > effective_end  → everyone except super-admin
 *                      loses write access, GET requests still work
 *   - unconfigured   : no contract row yet — treated as active so a brand-new
 *                      installation is fully usable until an admin fills in
 *                      the first contract
 *
 * The middleware EnforceContractStatus and the <x-contract-status-banner>
 * component read from this service.
 *
 * Results are cached for 60 seconds so every request during a page load
 * doesn't hit the DB. Mutation paths call forgetCache() when they change
 * a contract.
 */
class ContractStatusService
{
    public const MODE_ACTIVE = 'active';
    public const MODE_GRACE = 'grace';
    public const MODE_READ_ONLY = 'read_only';
    public const MODE_UNCONFIGURED = 'unconfigured';

    public const CACHE_KEY = 'contract_status_snapshot';
    public const CACHE_TTL = 60;

    // Warn banner threshold (days before effective_end)
    public const WARN_DAYS = 7;

    /**
     * Snapshot: [mode, effective_end, days_remaining, grace_active, contract_id].
     * Callers should prefer the specific helpers below.
     */
    public static function snapshot(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $contract = ServiceContract::current();
            if (!$contract) {
                return [
                    'mode' => self::MODE_UNCONFIGURED,
                    'contract_id' => null,
                    'contract_type' => null,
                    'customer_name' => null,
                    'end_date' => null,
                    'effective_end' => null,
                    'days_remaining' => null,
                    'grace_active' => false,
                    'grace_end' => null,
                ];
            }

            $today = now()->startOfDay();
            $effectiveEnd = $contract->effectiveEndDate();

            if (!$effectiveEnd) {
                $mode = self::MODE_UNCONFIGURED;
                $days = null;
            } else {
                if ($today->greaterThan($effectiveEnd)) {
                    $mode = self::MODE_READ_ONLY;
                } elseif ($contract->isInGracePeriod()) {
                    $mode = self::MODE_GRACE;
                } else {
                    $mode = self::MODE_ACTIVE;
                }
                // Use floor+abs so partial days round consistently to
                // "N whole days remaining" — days_remaining is 0 on the day
                // the contract expires, negative not returned (see mode).
                $days = (int) $today->diffInDays($effectiveEnd, false);
                if ($days < 0) $days = 0;
            }

            return [
                'mode' => $mode,
                'contract_id' => $contract->id,
                'contract_type' => $contract->contract_type,
                'customer_name' => $contract->customer_name,
                'end_date' => $contract->end_date?->format('Y-m-d'),
                'effective_end' => $effectiveEnd?->format('Y-m-d'),
                'days_remaining' => $days,
                'grace_active' => $mode === self::MODE_GRACE,
                'grace_end' => $contract->grace_end_date?->format('Y-m-d'),
            ];
        });
    }

    public static function currentMode(): string
    {
        return self::snapshot()['mode'];
    }

    /**
     * True when the whole system should behave as read-only for non-super-admin
     * users. Super-admin always keeps write access so they can renew.
     */
    public static function isReadOnly(): bool
    {
        return self::currentMode() === self::MODE_READ_ONLY;
    }

    public static function isInGracePeriod(): bool
    {
        return self::currentMode() === self::MODE_GRACE;
    }

    /**
     * "Should we show the warning banner?" — true when active and days
     * remaining is within the warn window.
     */
    public static function isNearExpiry(): bool
    {
        $s = self::snapshot();
        if ($s['mode'] !== self::MODE_ACTIVE) return false;
        return $s['days_remaining'] !== null && $s['days_remaining'] <= self::WARN_DAYS;
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
