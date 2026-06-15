<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\MonthlyExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonthlyExportController extends Controller
{
    public function __construct(protected MonthlyExportService $service)
    {
    }

    /**
     * GET /finance/monthly-bundle/export?period=YYYY-MM
     * Streams a ZIP that contains the monthly Summary + Ledger workbooks,
     * the three Revenue-Department tax forms, and every receipt / cert /
     * invoice PDF attached to that period. The temp file is removed by
     * Laravel's deleteFileAfterSend after streaming completes.
     */
    public function export(Request $request)
    {
        [$year, $month] = $this->resolvePeriod($request);
        [$zipPath, $downloadName] = $this->service->buildBundle($year, $month);

        return response()->download($zipPath, $downloadName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    protected function resolvePeriod(Request $request): array
    {
        $period = $request->input('period');
        if ($period && preg_match('/^(\d{4})-(\d{2})$/', $period, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        return [
            (int) $request->input('year', Carbon::now()->year),
            (int) $request->input('month', Carbon::now()->month),
        ];
    }
}
