<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\TaxReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TaxReportController extends Controller
{
    public function __construct(protected TaxReportService $service)
    {
    }

    public function index(Request $request)
    {
        [$year, $month] = $this->resolvePeriod($request);
        return view('financial.tax_reports.index', compact('year', 'month'));
    }

    public function vat(Request $request)
    {
        [$year, $month] = $this->resolvePeriod($request);
        $report = $this->service->vatReport($year, $month);
        return view('financial.tax_reports.vat', compact('report', 'year', 'month'));
    }

    public function exportVat(Request $request)
    {
        [$year, $month] = $this->resolvePeriod($request);
        [$spreadsheet, $filename] = $this->service->exportVatXlsx($year, $month);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function wht(Request $request)
    {
        [$year, $month] = $this->resolvePeriod($request);
        $whtType = $request->input('wht_type', 'pnd53');
        if (!in_array($whtType, ['pnd3', 'pnd53'], true)) {
            $whtType = 'pnd53';
        }
        $report = $this->service->whtReport($year, $month, $whtType);
        return view('financial.tax_reports.wht', compact('report', 'year', 'month', 'whtType'));
    }

    public function exportWht(Request $request)
    {
        [$year, $month] = $this->resolvePeriod($request);
        $whtType = $request->input('wht_type', 'pnd53');
        if (!in_array($whtType, ['pnd3', 'pnd53'], true)) {
            $whtType = 'pnd53';
        }
        [$spreadsheet, $filename] = $this->service->exportWhtXlsx($year, $month, $whtType);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function resolvePeriod(Request $request): array
    {
        $period = $request->input('period');
        if ($period && preg_match('/^(\d{4})-(\d{2})$/', $period, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        $year = (int) $request->input('year', Carbon::now()->year);
        $month = (int) $request->input('month', Carbon::now()->month);
        if ($month < 1 || $month > 12) {
            $month = Carbon::now()->month;
        }
        return [$year, $month];
    }
}
