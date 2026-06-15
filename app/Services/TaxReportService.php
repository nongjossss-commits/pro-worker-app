<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\TaxInvoice;
use App\Models\WhtCertificate;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Compute monthly VAT (ภ.พ.30) and WHT (ภ.ง.ด.3/53) reports
 * + Excel export aligned with Revenue Department forms.
 */
class TaxReportService
{
    /**
     * VAT report for a given (year, month).
     *
     * Returns:
     *   - output_invoices: TaxInvoice collection (issued, fiscal_year=year, invoice_date in month)
     *   - output_subtotal, output_vat
     *   - input_entries: LedgerEntry collection (expense + vat_amount > 0 + tax_invoice_no)
     *   - input_subtotal, input_vat
     *   - net_vat (positive = ต้องจ่ายเพิ่ม; negative = ได้คืน)
     */
    public function vatReport(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        // Output VAT — issued tax invoices (exclude void/cancelled)
        $outputInvoices = TaxInvoice::with('issuerProfile')
            ->where('status', 'issued')
            ->whereBetween('invoice_date', [$start, $end])
            ->orderBy('invoice_date')
            ->orderBy('invoice_no')
            ->get();

        $outputSubtotal = (float) $outputInvoices->sum('subtotal');
        $outputVat = (float) $outputInvoices->sum('vat_amount');

        // Input VAT — expense ledger entries with VAT + tax invoice reference
        $inputEntries = LedgerEntry::with('bankAccount')
            ->where('type', 'expense')
            ->where('vat_amount', '>', 0)
            ->whereNotNull('tax_invoice_no')
            ->whereBetween('entry_date', [$start, $end])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $inputSubtotal = (float) $inputEntries->sum('subtotal');
        $inputVat = (float) $inputEntries->sum('vat_amount');

        return [
            'year' => $year,
            'month' => $month,
            'period_label' => $start->format('m/Y'),
            'output_invoices' => $outputInvoices,
            'output_subtotal' => round($outputSubtotal, 2),
            'output_vat' => round($outputVat, 2),
            'input_entries' => $inputEntries,
            'input_subtotal' => round($inputSubtotal, 2),
            'input_vat' => round($inputVat, 2),
            'net_vat' => round($outputVat - $inputVat, 2),
        ];
    }

    /**
     * WHT report for a (year, month, wht_type).
     * Only 'issued' direction is filed with the Revenue Dept.
     */
    public function whtReport(int $year, int $month, string $whtType): array
    {
        $certificates = WhtCertificate::where('type', 'issued')
            ->where('wht_type', $whtType)
            ->where('tax_period_year', $year)
            ->where('tax_period_month', $month)
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get();

        $totalAmountPaid = (float) $certificates->sum('amount_paid');
        $totalWht = (float) $certificates->sum('wht_amount');

        return [
            'year' => $year,
            'month' => $month,
            'wht_type' => $whtType,
            'form_label' => $whtType === 'pnd3' ? 'ภ.ง.ด.3' : 'ภ.ง.ด.53',
            'period_label' => sprintf('%02d/%04d', $month, $year),
            'certificates' => $certificates,
            'total_amount_paid' => round($totalAmountPaid, 2),
            'total_wht' => round($totalWht, 2),
            'count' => $certificates->count(),
        ];
    }

    /**
     * Excel export of ภ.พ.30 — 3 sheets: Output VAT, Input VAT, Summary.
     */
    public function exportVatXlsx(int $year, int $month): array
    {
        $report = $this->vatReport($year, $month);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->buildVatOutputSheet($spreadsheet, $report);
        $this->buildVatInputSheet($spreadsheet, $report);
        $this->buildVatSummarySheet($spreadsheet, $report);

        $spreadsheet->setActiveSheetIndex(2);

        $filename = sprintf('PP30-%04d-%02d.xlsx', $year, $month);
        return [$spreadsheet, $filename];
    }

    /**
     * Excel export of ภ.ง.ด.3 หรือ ภ.ง.ด.53 — single sheet matching Revenue form columns.
     */
    public function exportWhtXlsx(int $year, int $month, string $whtType): array
    {
        $report = $this->whtReport($year, $month, $whtType);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($report['form_label']);

        // Title row
        $sheet->setCellValue('A1', 'แบบ ' . $report['form_label'] . ' — เดือนภาษี ' . $report['period_label']);
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Header row
        $columns = ['ลำดับ', 'เลขที่ใบหัก', 'วันที่จ่าย', 'ผู้รับเงิน', 'เลขประจำตัวผู้เสียภาษี', 'ประเภทเงินได้', 'จำนวนเงินที่จ่าย', 'ภาษีหัก ณ ที่จ่าย'];
        foreach ($columns as $i => $col) {
            $cell = $sheet->getCell([$i + 1, 3]);
            $cell->setValue($col);
        }
        $this->styleHeader($sheet, 'A3:H3');

        // Data rows
        $row = 4;
        foreach ($report['certificates'] as $idx => $cert) {
            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->getCell("B{$row}")->setValueExplicit($cert->cert_no, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", optional($cert->paid_at)->format('d/m/Y'));
            $sheet->setCellValue("D{$row}", $cert->payee_name);
            $sheet->getCell("E{$row}")->setValueExplicit($cert->payee_tax_id ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("F{$row}", $this->humanizeIncomeType($cert->income_type));
            $sheet->setCellValue("G{$row}", (float) $cert->amount_paid);
            $sheet->setCellValue("H{$row}", (float) $cert->wht_amount);
            $row++;
        }

        // Total row
        $sheet->setCellValue("F{$row}", 'รวม');
        $sheet->setCellValue("G{$row}", $report['total_amount_paid']);
        $sheet->setCellValue("H{$row}", $report['total_wht']);
        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E0E0']],
        ]);

        // Body borders + alignment
        $sheet->getStyle("A4:H{$row}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        foreach (['G', 'H'] as $col) {
            $sheet->getStyle("{$col}4:{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        }
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = sprintf('%s-%04d-%02d.xlsx',
            $report['form_label'] === 'ภ.ง.ด.3' ? 'PND3' : 'PND53',
            $year,
            $month
        );
        return [$spreadsheet, $filename];
    }

    // -------- Sheet builders --------

    protected function buildVatOutputSheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Output VAT');

        $sheet->setCellValue('A1', 'ภาษีขาย (Output VAT) — เดือน ' . $report['period_label']);
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $columns = ['ลำดับ', 'เลขที่ใบกำกับ', 'วันที่', 'ลูกค้า', 'เลขประจำตัวผู้เสียภาษี', 'มูลค่าก่อน VAT', 'VAT'];
        foreach ($columns as $i => $col) {
            $sheet->getCell([$i + 1, 3])->setValue($col);
        }
        $this->styleHeader($sheet, 'A3:G3');

        $row = 4;
        foreach ($report['output_invoices'] as $idx => $inv) {
            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->getCell("B{$row}")->setValueExplicit($inv->invoice_no, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", optional($inv->invoice_date)->format('d/m/Y'));
            $sheet->setCellValue("D{$row}", $inv->customer_name);
            $sheet->getCell("E{$row}")->setValueExplicit($inv->customer_tax_id ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("F{$row}", (float) $inv->subtotal);
            $sheet->setCellValue("G{$row}", (float) $inv->vat_amount);
            $row++;
        }
        $sheet->setCellValue("E{$row}", 'รวม');
        $sheet->setCellValue("F{$row}", $report['output_subtotal']);
        $sheet->setCellValue("G{$row}", $report['output_vat']);
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E0E0']],
        ]);

        $sheet->getStyle("A4:G{$row}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        foreach (['F', 'G'] as $col) {
            $sheet->getStyle("{$col}4:{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        }
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    protected function buildVatInputSheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Input VAT');

        $sheet->setCellValue('A1', 'ภาษีซื้อ (Input VAT) — เดือน ' . $report['period_label']);
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $columns = ['ลำดับ', 'Ledger #', 'วันที่', 'ผู้ขาย/ผู้จ่าย', 'เลขที่ใบกำกับซื้อ', 'มูลค่าก่อน VAT', 'VAT'];
        foreach ($columns as $i => $col) {
            $sheet->getCell([$i + 1, 3])->setValue($col);
        }
        $this->styleHeader($sheet, 'A3:G3');

        $row = 4;
        foreach ($report['input_entries'] as $idx => $entry) {
            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->getCell("B{$row}")->setValueExplicit($entry->entry_no ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", optional($entry->entry_date)->format('d/m/Y'));
            $sheet->setCellValue("D{$row}", $entry->counterparty_name ?? '-');
            $sheet->getCell("E{$row}")->setValueExplicit($entry->tax_invoice_no ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("F{$row}", (float) $entry->subtotal);
            $sheet->setCellValue("G{$row}", (float) $entry->vat_amount);
            $row++;
        }
        $sheet->setCellValue("E{$row}", 'รวม');
        $sheet->setCellValue("F{$row}", $report['input_subtotal']);
        $sheet->setCellValue("G{$row}", $report['input_vat']);
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E0E0']],
        ]);

        $sheet->getStyle("A4:G{$row}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        foreach (['F', 'G'] as $col) {
            $sheet->getStyle("{$col}4:{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        }
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    protected function buildVatSummarySheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Summary');

        $sheet->setCellValue('A1', 'แบบ ภ.พ.30 — เดือนภาษี ' . $report['period_label']);
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $rows = [
            ['ยอดขาย (Output Subtotal)', $report['output_subtotal']],
            ['ภาษีขาย (Output VAT)', $report['output_vat']],
            ['ยอดซื้อ (Input Subtotal)', $report['input_subtotal']],
            ['ภาษีซื้อ (Input VAT)', $report['input_vat']],
            ['ภาษีสุทธิ (Net VAT)', $report['net_vat']],
        ];
        $r = 3;
        foreach ($rows as [$label, $value]) {
            $sheet->setCellValue("A{$r}", $label);
            $sheet->setCellValue("B{$r}", $value);
            $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
            $r++;
        }
        // Highlight net
        $netRow = $r - 1;
        $sheet->getStyle("A{$netRow}:B{$netRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 13],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF2CC']],
        ]);

        $sheet->getStyle("A3:B{$netRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(20);
    }

    protected function styleHeader($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']],
        ]);
    }

    protected function humanizeIncomeType(?string $type): string
    {
        return match ($type) {
            'service' => 'ค่าบริการ',
            'rent' => 'ค่าเช่า',
            'advertising' => 'ค่าโฆษณา',
            'transport' => 'ค่าขนส่ง',
            'contract' => 'รับจ้างทำของ',
            'other' => 'อื่นๆ',
            default => '-',
        };
    }
}
