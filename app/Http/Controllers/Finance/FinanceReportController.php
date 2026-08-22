<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\FinanceBookReportService;
use App\Services\FinanceDailySummaryPdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * "รายงานประจำวัน" — the main Finance daily/period report, mirroring the
 * two book-specific sections of Labor\LaborReportController (Account
 * Balances + itemized Category Breakdown) — deliberately no "Per Team"/
 * "Team Summary" equivalent, since team-billing has no analog in the main
 * app. See FinanceBookReportService for the underlying queries.
 */
class FinanceReportController extends Controller
{
    public function index(Request $request, FinanceBookReportService $service)
    {
        [$from, $to] = $this->resolveRange($request);
        $accounts = $service->accountBalancesForRange($from, $to);
        $categoryTransactions = $service->categoryTransactions($from, $to);

        return view('financial.books-reports.index', [
            'accounts' => $accounts,
            'categoryTransactions' => $categoryTransactions,
            'from' => $from,
            'to' => $to,
            'activePeriod' => $request->input('period', 'month'),
            'activeDate' => $request->filled('date') ? Carbon::parse($request->input('date')) : Carbon::now(),
        ]);
    }

    public function pdf(Request $request, FinanceBookReportService $service, FinanceDailySummaryPdfService $pdfService)
    {
        [$from, $to] = $this->resolveRange($request);

        $binary = $pdfService->generate(
            $from,
            $to,
            $service->accountBalancesForRange($from, $to),
            $service->categoryTransactions($from, $to)
        );

        $filename = sprintf('finance-summary_%s_%s.pdf', $from->format('Ymd'), $to->format('Ymd'));

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function export(Request $request, FinanceBookReportService $service)
    {
        [$from, $to] = $this->resolveRange($request);
        $accounts = $service->accountBalancesForRange($from, $to);
        $categoryTransactions = $service->categoryTransactions($from, $to);

        $green = '16A34A';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Finance Summary');

        $appName = \App\Services\BrandService::current()['app_name'];
        $sheet->setCellValue('A1', $appName . ' — สรุปรายรับรายจ่ายประจำงวด ' . $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y'));
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $row = 3;

        // --- Section: Account Balances ---
        $row = $this->writeSectionTitle($sheet, $row, 'Account Balances (กระทบยอด ตามช่วงเวลา)', $green);
        $row = $this->writeHeaderRow($sheet, $row, ['บัญชี', 'ธนาคาร', 'ยอดยกมา', 'รับ', 'จ่าย', 'คงเหลือ'], $green);
        $dataStart = $row;
        foreach ($accounts->rows as $r) {
            $sheet->fromArray([
                $r->account->account_name ?: $r->account->bank_name, $r->account->bank_name ?: '-',
                $r->opening_balance, $r->income, $r->expense, $r->closing_balance,
            ], null, "A{$row}", true);
            $row++;
        }
        $sheet->fromArray([
            'รวมทั้งหมด', '', $accounts->totals->opening_balance, $accounts->totals->income, $accounts->totals->expense, $accounts->totals->closing_balance,
        ], null, "A{$row}", true);
        $this->styleTotalRow($sheet, "A{$row}:F{$row}", $green);
        $sheet->getStyle("C{$dataStart}:F{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $row += 2;

        // --- Section: Category Breakdown (itemized) ---
        $row = $this->writeSectionTitle($sheet, $row, 'Category Breakdown — แจกแจงรายการ', $green);
        $row = $this->writeHeaderRow($sheet, $row, ['วันที่', 'ประเภท', 'หมวดหมู่ / รายละเอียด', 'จำนวนเงิน'], $green);
        $amountRows = [];
        foreach ($categoryTransactions->groups as $group) {
            $sheet->setCellValue("C{$row}", ($group->type === 'income' ? 'รับ: ' : 'จ่าย: ') . $group->label);
            $sheet->setCellValue("D{$row}", $group->subtotal);
            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->tint($green, 0.85)]],
            ]);
            $amountRows[] = $row;
            $row++;

            foreach ($group->items as $item) {
                $sheet->setCellValue("A{$row}", $item->entry_date->format('d/m/Y'));
                $sheet->setCellValue("B{$row}", $item->type === 'income' ? 'รับ' : 'จ่าย');
                $sheet->setCellValue("C{$row}", '   ' . $item->description);
                $sheet->setCellValue("D{$row}", $item->net_amount);
                $sheet->getStyle("A{$row}:D{$row}")->applyFromArray(['font' => ['color' => ['rgb' => '64748B']]]);
                $amountRows[] = $row;
                $row++;
            }
            $row++;
        }
        $sheet->setCellValue("C{$row}", 'ยอดรวมรับ');
        $sheet->setCellValue("D{$row}", $categoryTransactions->income_total);
        $amountRows[] = $row;
        $row++;
        $sheet->setCellValue("C{$row}", 'ยอดรวมจ่าย');
        $sheet->setCellValue("D{$row}", $categoryTransactions->expense_total);
        $amountRows[] = $row;
        $row++;
        $sheet->setCellValue("C{$row}", 'ยอดรวมสุทธิ');
        $sheet->setCellValue("D{$row}", $categoryTransactions->net);
        $amountRows[] = $row;
        $this->styleTotalRow($sheet, "C{$row}:D{$row}", $green);

        foreach ($amountRows as $amountRow) {
            $sheet->getStyle("D{$amountRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = sprintf('finance-summary_%s_%s.xlsx', $from->format('Ymd'), $to->format('Ymd'));

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function writeSectionTitle($sheet, int $row, string $title, string $colorHex): int
    {
        $sheet->setCellValue("A{$row}", $title);
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorHex]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);

        return $row + 1;
    }

    private function writeHeaderRow($sheet, int $row, array $labels, string $colorHex): int
    {
        foreach ($labels as $i => $label) {
            $sheet->getCell([$i + 1, $row])->setValue($label);
        }
        $lastCol = Coordinate::stringFromColumnIndex(count($labels));
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->tint($colorHex, 0.88)]],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $colorHex]]],
        ]);

        return $row + 1;
    }

    private function styleTotalRow($sheet, string $range, string $colorHex): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->tint($colorHex, 0.85)]],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $colorHex]]],
        ]);
    }

    private function tint(string $hex, float $whiteAmount): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $blend = fn ($c) => (int) round($c * (1 - $whiteAmount) + 255 * $whiteAmount);

        return sprintf('%02X%02X%02X', $blend($r), $blend($g), $blend($b));
    }

    /**
     * Same `period`+`date` (preset buttons) / `from`+`to` (plain range)
     * resolution as LaborReportController::resolveRange().
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
