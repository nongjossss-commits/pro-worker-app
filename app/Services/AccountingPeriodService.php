<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * The "accounting day" boundary — deliberately NOT midnight. A day's books
 * (entry_date = that day) stay open for edits until 05:00 the FOLLOWING
 * calendar day, then lock. There is no explicit "close" action/cron: the
 * open/closed state is always computed live from the clock, so it can
 * never be missed or run late. Once closed, a normal edit/delete is
 * refused (see LedgerService) — the only way to fix a closed day's figures
 * is LedgerService::createCorrection(), which posts a reversal + a
 * corrected replacement dated today rather than touching the original.
 *
 * Kept as its own tiny service (not inlined into LedgerService) so the
 * exact same rule can be reused for Pro Walker Labor's Company Books in a
 * later phase, once this is validated in the main Finance module.
 */
class AccountingPeriodService
{
    public const CUTOFF_HOUR = 5;

    /**
     * True if entries dated $date can still be freely created/edited/deleted.
     */
    public static function isOpen(Carbon $date): bool
    {
        return Carbon::now()->lt(self::closesAt($date));
    }

    /**
     * The exact moment $date's books lock — 05:00 on the calendar day
     * after $date.
     */
    public static function closesAt(Carbon $date): Carbon
    {
        return $date->copy()->startOfDay()->addDay()->setTime(self::CUTOFF_HOUR, 0, 0);
    }
}
