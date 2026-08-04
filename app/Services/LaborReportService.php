<?php

namespace App\Services;

use App\Models\LaborTeam;
use Carbon\Carbon;

/**
 * Cross-team billing summary for a date range — the "daily/weekly/monthly"
 * report is really just this same aggregation with different from/to
 * bounds, so there's one method rather than three near-duplicate ones.
 */
class LaborReportService
{
    public function summarize(Carbon $from, Carbon $to): array
    {
        $teams = LaborTeam::where('is_active', true)->orderBy('name')->get();

        $rows = [];
        $totals = [
            'opening_balance' => 0.0,
            'charges' => 0.0,
            'payments' => 0.0,
            'closing_balance' => 0.0,
            'bills_issued' => 0,
        ];

        foreach ($teams as $team) {
            $opening = (float) $team->ledgerEntries()
                ->where('entry_date', '<', $from->toDateString())
                ->sum('amount');

            $charges = (float) $team->ledgerEntries()
                ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
                ->where('amount', '>', 0)
                ->sum('amount');

            $payments = (float) $team->ledgerEntries()
                ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
                ->where('amount', '<', 0)
                ->sum('amount');

            $closing = $opening + $charges + $payments;

            $billsIssued = $team->bills()
                ->where('status', '!=', 'void')
                ->whereBetween('issued_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->count();

            $rows[] = [
                'team' => $team,
                'opening_balance' => $opening,
                'charges' => $charges,
                'payments' => $payments,
                'closing_balance' => $closing,
                'bills_issued' => $billsIssued,
            ];

            $totals['opening_balance'] += $opening;
            $totals['charges'] += $charges;
            $totals['payments'] += $payments;
            $totals['closing_balance'] += $closing;
            $totals['bills_issued'] += $billsIssued;
        }

        return ['rows' => $rows, 'totals' => $totals];
    }
}
