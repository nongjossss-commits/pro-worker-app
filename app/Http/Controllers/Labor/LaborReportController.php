<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Services\BrandService;
use App\Services\LaborDailySummaryPdfService;
use App\Services\LaborReportService;
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
        $categoryTransactions = $service->categoryTransactions($from, $to);
        $accounts = $service->accountBalancesForRange($from, $to);

        return view('labor.reports.index', [
            'report' => $report,
            'teamSummary' => $teamSummary,
            'categoryTransactions' => $categoryTransactions,
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
            $service->categoryTransactions($from, $to),
            $service->accountBalancesForRange($from, $to)
        );

        $filename = sprintf('labor-summary_%s_%s.pdf', $from->format('Ymd'), $to->format('Ymd'));

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * One workbook, one sheet, four stacked sections — Per Team, Team
     * Summary, Account Balances, then the itemized Category Breakdown —
     * so accounting staff have a single file that mirrors the whole
     * Reports page to forward to shareholders. Section banners are
     * color-coded by which ledger the numbers belong to — orange for the
     * Central Billing Ledger (what teams owe), green for Company Books
     * (the office's own cash) — mirroring the Reports page's own accent
     * colors so the exported file reads the same way.
     */
    public function export(Request $request, LaborReportService $service)
    {
        abort_if($request->user()->hasRole('labor-team'), 403);

        [$from, $to] = $this->resolveRange($request);
        $report = $service->summarize($from, $to);
        $teamSummary = $service->teamPaymentSummary($from, $to);
        $accounts = $service->accountBalancesForRange($from, $to);
        $categoryTransactions = $service->categoryTransactions($from, $to);

        $orange = ltrim(BrandService::current()['primary_color'], '#');
        $green = '16A34A';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Labor Summary');

        $sheet->setCellValue('A1', 'Pro Walker Labor — สรุปประจำงวด ' . $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y'));
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $row = 3;

        // --- Section: Per Team (Central Billing Ledger) ---
        $row = $this->writeSectionTitle($sheet, $row, 'Per Team (Central Billing Ledger)', $orange);
        $row = $this->writeHeaderRow($sheet, $row, ['ทีมงาน', 'ยอดยกมา', 'ยอดเรียกเก็บ (งวดนี้)', 'ยอดชำระ (งวดนี้)', 'ยอดคงเหลือ', 'บิลที่ออก'], $orange);
        $dataStart = $row;
        foreach ($report['rows'] as $r) {
            $sheet->fromArray([
                $r['team']->name, $r['opening_balance'], $r['charges'], $r['payments'], $r['closing_balance'], $r['bills_issued'],
            ], null, "A{$row}", true);
            $row++;
        }
        $sheet->fromArray([
            'รวมทั้งหมด', $report['totals']['opening_balance'], $report['totals']['charges'],
            $report['totals']['payments'], $report['totals']['closing_balance'], $report['totals']['bills_issued'],
        ], null, "A{$row}", true);
        $this->styleTotalRow($sheet, "A{$row}:F{$row}", $orange);
        $sheet->getStyle("B{$dataStart}:E{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle("F{$dataStart}:F{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $row += 2;

        // --- Section: Team Summary (Company Books) ---
        $row = $this->writeSectionTitle($sheet, $row, 'Team Summary (สมุดบัญชีบริษัท)', $green);
        $row = $this->writeHeaderRow($sheet, $row, ['ทีมงาน', 'รับช่วงนี้', 'ยอดออกบิลรวม', 'ชำระแล้วรวม', 'ยอดคงค้าง'], $green);
        $dataStart = $row;
        foreach ($teamSummary as $r) {
            $sheet->fromArray([$r->team->name, $r->received_in_range, $r->total_due, $r->total_paid, $r->balance_due], null, "A{$row}", true);
            $row++;
        }
        if ($row > $dataStart) {
            $sheet->getStyle("B{$dataStart}:E" . ($row - 1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        }
        $row += 1;

        // --- Section: Account Balances ---
        $row = $this->writeSectionTitle($sheet, $row, 'Account Balances (กระทบยอด ตามช่วงเวลา)', $green);
        $row = $this->writeHeaderRow($sheet, $row, ['บัญชี', 'ธนาคาร', 'ยอดยกมา', 'รับ', 'จ่าย', 'คงเหลือ'], $green);
        $dataStart = $row;
        foreach ($accounts->rows as $r) {
            $sheet->fromArray([
                $r->account->name, $r->account->bank_name ?: '-', $r->opening_balance, $r->income, $r->expense, $r->closing_balance,
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
        $row = $this->writeHeaderRow($sheet, $row, ['วันที่', 'ประเภท', 'หมวดหมู่ / รายละเอียด', 'จำนวน', 'จำนวนเงิน'], $green);
        $amountRows = [];
        foreach ($categoryTransactions->groups as $group) {
            $sheet->setCellValue("C{$row}", ($group->type === 'income' ? 'รับ: ' : 'จ่าย: ') . $group->label);
            $sheet->setCellValue("E{$row}", $group->subtotal);
            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->tint($green, 0.85)]],
            ]);
            $amountRows[] = $row;
            $row++;

            foreach ($group->items as $item) {
                $sheet->setCellValue("A{$row}", $item->transaction_date->format('d/m/Y'));
                $sheet->setCellValue("B{$row}", $item->type === 'income' ? 'รับ' : 'จ่าย');
                $sheet->setCellValue("C{$row}", '   ' . $item->description);
                $sheet->setCellValue("D{$row}", $item->quantity ?? '-');
                $sheet->setCellValue("E{$row}", $item->amount);
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray(['font' => ['color' => ['rgb' => '64748B']]]);
                $amountRows[] = $row;
                $row++;
            }
            $row++;
        }
        $sheet->setCellValue("C{$row}", 'ยอดรวมรับ');
        $sheet->setCellValue("E{$row}", $categoryTransactions->income_total);
        $amountRows[] = $row;
        $row++;
        $sheet->setCellValue("C{$row}", 'ยอดรวมจ่าย');
        $sheet->setCellValue("E{$row}", $categoryTransactions->expense_total);
        $amountRows[] = $row;
        $row++;
        $sheet->setCellValue("C{$row}", 'ยอดรวมสุทธิ');
        $sheet->setCellValue("E{$row}", $categoryTransactions->net);
        $amountRows[] = $row;
        $this->styleTotalRow($sheet, "C{$row}:E{$row}", $green);

        foreach ($amountRows as $amountRow) {
            $sheet->getStyle("E{$amountRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = sprintf('labor-summary_%s_%s.xlsx', $from->format('Ymd'), $to->format('Ymd'));

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Full-width colored banner (merged A:G) — the Excel equivalent of the
     * Reports page's tinted, icon-led card headers, so each section reads
     * as its own distinct block instead of one continuous sheet of tables.
     */
    private function writeSectionTitle($sheet, int $row, string $title, string $colorHex): int
    {
        $sheet->setCellValue("A{$row}", $title);
        $sheet->mergeCells("A{$row}:G{$row}");
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

    /**
     * A total/grand-total row: bold text on a light tint of the section's
     * color plus a solid top border — the sheet equivalent of the Reports
     * page's `table-light fw-bold` + thick top border on its tfoot rows.
     */
    private function styleTotalRow($sheet, string $range, string $colorHex): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->tint($colorHex, 0.85)]],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $colorHex]]],
        ]);
    }

    /**
     * Blend a hex color toward white — used for section-tinted header/total
     * row fills (a full-strength banner color would be unreadable behind
     * black text at row scale).
     */
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
