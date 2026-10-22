<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Filters of the "Job History" popup on each employer card (Workflow,
 * Registration / Renewal resolution): finished employees by month (or a
 * date range), plus two search boxes — employee (name / numbers) and team.
 * Rendered by partials/_history_panel.blade.php, driven by
 * public/js/history-filter.js.
 *
 *  - month: 'YYYY-MM' | 'all' | 'none' (finished before dates were kept).
 *    Default: the latest month — unless a search / date range is used, then
 *    all months (so a search finds people in any month).
 */
class HistoryFilter
{
    public const NO_DATE = 'none';

    public static function params(Request $request): array
    {
        return [
            'month' => (string) $request->input('month', ''),
            'from' => (string) $request->input('from', ''),
            'to' => (string) $request->input('to', ''),
            'q' => trim((string) $request->input('q', '')),
            'team' => trim((string) $request->input('team', '')),
        ];
    }

    /**
     * Month buckets of the finished dates, newest first:
     * ['2026-10' => ['label' => 'ตุลาคม 2569', 'count' => 12], ..., 'none' => [...]].
     */
    public static function months(iterable $dates): array
    {
        $buckets = [];
        $noDate = 0;
        foreach ($dates as $d) {
            if (! $d) {
                $noDate++;
                continue;
            }
            $key = Carbon::parse($d)->format('Y-m');
            $buckets[$key] = ($buckets[$key] ?? 0) + 1;
        }
        krsort($buckets);

        $out = [];
        foreach ($buckets as $key => $count) {
            $out[$key] = ['label' => self::monthLabel($key), 'count' => $count];
        }
        if ($noDate) {
            $out[self::NO_DATE] = ['label' => __('No date (older records)'), 'count' => $noDate];
        }

        return $out;
    }

    public static function monthLabel(string $ym): string
    {
        $d = Carbon::createFromFormat('Y-m-d', $ym . '-01');
        $locale = app()->getLocale();
        $year = $locale === 'th' ? $d->year + 543 : $d->year;

        return $d->copy()->locale($locale)->translatedFormat('F') . ' ' . $year;
    }

    /** The month actually used: the request's, else latest (or all when searching / using dates). */
    public static function selectedMonth(array $params, array $months): string
    {
        if ($params['month'] !== '') {
            return $params['month'];
        }
        if ($params['q'] !== '' || $params['team'] !== '' || $params['from'] !== '' || $params['to'] !== '') {
            return 'all';
        }

        return array_key_first($months) ?? 'all';
    }

    /**
     * Narrow $query to the chosen month / date range on $dateColumn and apply
     * the two search boxes through the menu's own callbacks.
     */
    public static function apply($query, array $params, string $month, string $dateColumn, \Closure $employeeSearch, \Closure $teamSearch): void
    {
        if ($month === self::NO_DATE) {
            $query->whereNull($dateColumn);
        } elseif ($month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfDay();
            $query->whereBetween($dateColumn, [$start, $start->copy()->endOfMonth()]);
        }
        if ($params['from'] !== '' && ($from = self::date($params['from']))) {
            $query->where($dateColumn, '>=', $from->startOfDay());
        }
        if ($params['to'] !== '' && ($to = self::date($params['to']))) {
            $query->where($dateColumn, '<=', $to->endOfDay());
        }
        if ($params['q'] !== '') {
            $employeeSearch($query, $params['q']);
        }
        if ($params['team'] !== '') {
            $teamSearch($query, $params['team']);
        }
    }

    private static function date(string $v): ?Carbon
    {
        try {
            return Carbon::parse($v);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
