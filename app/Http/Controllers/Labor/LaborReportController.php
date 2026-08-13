<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Services\LaborDailySummaryPdfService;
use App\Services\LaborReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Cross-team billing summary — read-only, so unlike the rest of Central
 * Billing this is open to labor-shareholder too (their whole role is
 * oversight). Only `labor-team` is excluded: their own team page already
 * shows their own numbers, and this report would otherwise leak every
 * other team's balance to them.
 */
class LaborReportController extends Controller
{
    public function index(Request $request, LaborReportService $service)
    {
        abort_if($request->user()->hasRole('labor-team'), 403);

        [$from, $to] = $this->resolveRange($request);
        $report = $service->summarize($from, $to);
        $teamSummary = $service->teamPaymentSummary($from, $to);
        $categorySummary = $service->categorySummary($from, $to);
        $accounts = $service->accountBalances();

        return view('labor.reports.index', [
            'report' => $report,
            'teamSummary' => $teamSummary,
            'categorySummary' => $categorySummary,
            'accounts' => $accounts,
            'from' => $from,
            'to' => $to,
            'activePeriod' => $request->input('period', 'month'),
            'activeDate' => $request->filled('date') ? Carbon::parse($request->input('date')) : Carbon::now(),
        ]);
    }

    public function pdf(Request $request, LaborReportService $service, LaborDailySummaryPdfService $pdfService)
    {
        abort_if($request->user()->hasRole('labor-team'), 403);

        [$from, $to] = $this->resolveRange($request);

        $binary = $pdfService->generate(
            $from,
            $to,
            $service->summarize($from, $to),
            $service->teamPaymentSummary($from, $to),
            $service->categorySummary($from, $to),
            $service->accountBalances()
        );

        $filename = sprintf('labor-summary_%s_%s.pdf', $from->format('Ymd'), $to->format('Ymd'));

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function export(Request $request, LaborReportService $service)
    {
        abort_if($request->user()->hasRole('labor-team'), 403);

        [$from, $to] = $this->resolveRange($request);
        $report = $service->summarize($from, $to);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Labor Billing Summary');

        $sheet->setCellValue('A1', 'Pro Walker Labor — สรุปยอดเรียกเก็บ ' . $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y'));
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $columns = ['ทีมงาน', 'ยอดยกมา', 'ยอดเรียกเก็บ (งวดนี้)', 'ยอดชำระ (งวดนี้)', 'ยอดคงเหลือ', 'บิลที่ออก'];
        foreach ($columns as $i => $col) {
            $sheet->getCell([$i + 1, 3])->setValue($col);
        }
        $sheet->getStyle('A3:F3')->applyFromArray(['font' => ['bold' => true]]);

        $row = 4;
        foreach ($report['rows'] as $r) {
            $sheet->setCellValue("A{$row}", $r['team']->name);
            $sheet->setCellValue("B{$row}", $r['opening_balance']);
            $sheet->setCellValue("C{$row}", $r['charges']);
            $sheet->setCellValue("D{$row}", $r['payments']);
            $sheet->setCellValue("E{$row}", $r['closing_balance']);
            $sheet->setCellValue("F{$row}", $r['bills_issued']);
            $row++;
        }

        $sheet->setCellValue("A{$row}", 'รวมทั้งหมด');
        $sheet->setCellValue("B{$row}", $report['totals']['opening_balance']);
        $sheet->setCellValue("C{$row}", $report['totals']['charges']);
        $sheet->setCellValue("D{$row}", $report['totals']['payments']);
        $sheet->setCellValue("E{$row}", $report['totals']['closing_balance']);
        $sheet->setCellValue("F{$row}", $report['totals']['bills_issued']);
        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray(['font' => ['bold' => true]]);

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = sprintf('labor-billing-summary_%s_%s.xlsx', $from->format('Ymd'), $to->format('Ymd'));

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * `period` + `date` (e.g. from the "go back to a specific day" picker
     * and its prev/next buttons) takes priority when both are present —
     * the plain `from`/`to` pair (still what the JS preset buttons submit)
     * is the fallback, kept so old bookmarked/shared URLs keep working.
     */
    protected function resolveRange(Request $request): array
    {
        if ($request->filled('period') && $request->filled('date')) {
            return $this->resolvePeriodRange($request->input('period'), Carbon::parse($request->input('date')));
        }

        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))
            : Carbon::now()->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))
            : Carbon::now();

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->startOfDay(), $to->startOfDay()];
    }

    protected function resolvePeriodRange(string $period, Carbon $date): array
    {
        return match ($period) {
            'day' => [$date->copy()->startOfDay(), $date->copy()->startOfDay()],
            'week' => [$date->copy()->startOfWeek(Carbon::SUNDAY)->startOfDay(), $date->copy()->endOfWeek(Carbon::SATURDAY)->startOfDay()],
            'month' => [$date->copy()->startOfMonth()->startOfDay(), $date->copy()->endOfMonth()->startOfDay()],
            'quarter' => [$date->copy()->firstOfQuarter()->startOfDay(), $date->copy()->lastOfQuarter()->startOfDay()],
            'year' => [$date->copy()->startOfYear()->startOfDay(), $date->copy()->endOfYear()->startOfDay()],
            default => [$date->copy()->startOfDay(), $date->copy()->startOfDay()],
        };
    }
}
