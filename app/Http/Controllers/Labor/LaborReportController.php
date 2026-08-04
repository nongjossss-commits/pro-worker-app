<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
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

        return view('labor.reports.index', [
            'report' => $report,
            'from' => $from,
            'to' => $to,
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

    protected function resolveRange(Request $request): array
    {
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
}
