<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Models\LedgerEntry;
use App\Models\TaxInvoice;
use App\Models\WhtCertificate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

/**
 * Builds the end-of-month "one-click" bundle: a ZIP containing the
 * monthly Summary workbook, a detailed Ledger workbook, the three
 * tax-form workbooks reused from TaxReportService, and every
 * supporting attachment (tax invoices, WHT certs, ledger receipts).
 *
 * Returns the absolute path of a temporary ZIP file. Caller is
 * responsible for streaming + deleteFileAfterSend.
 */
class MonthlyExportService
{
    public function __construct(protected TaxReportService $taxReports)
    {
    }

    /**
     * @return array{0:string,1:string}  [absoluteZipPath, downloadFilename]
     */
    public function buildBundle(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();
        $period = sprintf('%04d-%02d', $year, $month);

        // Eager-load related data we'll need for several sheets/attachments
        $entries = LedgerEntry::with(['bankAccount', 'creator'])
            ->whereBetween('entry_date', [$start, $end])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();
        $accounts = BankAccount::orderBy('account_type')->orderBy('bank_name')->get();
        $incomeCategories = IncomeCategory::all()->keyBy('id');
        $expenseCategories = ExpenseCategory::all()->keyBy('id');

        $tempDir = storage_path('app/tmp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $zipPath = $tempDir . DIRECTORY_SEPARATOR . sprintf('monthly-bundle-%s-%s.zip', $period, bin2hex(random_bytes(4)));

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Cannot create ZIP at {$zipPath}");
        }

        // 1. Summary.xlsx (multi-sheet)
        $summary = $this->buildSummaryWorkbook($year, $month, $entries, $accounts, $incomeCategories, $expenseCategories);
        $zip->addFromString("Summary-{$period}.xlsx", $this->workbookToString($summary));

        // 2. Ledger.xlsx (full detail)
        $ledger = $this->buildLedgerWorkbook($year, $month, $entries);
        $zip->addFromString("Ledger-{$period}.xlsx", $this->workbookToString($ledger));

        // 3. Tax forms (reuse TaxReportService)
        [$vatBook, $vatName] = $this->taxReports->exportVatXlsx($year, $month);
        $zip->addFromString($vatName, $this->workbookToString($vatBook));

        foreach (['pnd3', 'pnd53'] as $whtType) {
            [$book, $name] = $this->taxReports->exportWhtXlsx($year, $month, $whtType);
            $zip->addFromString($name, $this->workbookToString($book));
        }

        // 4. Attachments
        $this->addAttachments($zip, $year, $month, $entries);

        $zip->close();

        return [$zipPath, "Monthly-Bundle-{$period}.zip"];
    }

    // -------- Summary workbook --------

    protected function buildSummaryWorkbook(int $year, int $month, $entries, $accounts, $incomeCategories, $expenseCategories): Spreadsheet
    {
        $period = sprintf('%02d/%04d', $month, $year);
        $book = new Spreadsheet();
        $book->removeSheetByIndex(0);

        $totals = $this->aggregateTotals($entries);

        // Sheet 1: Overview
        $sheet = $book->createSheet();
        $sheet->setTitle('Overview');
        $sheet->setCellValue('A1', "Monthly Summary — {$period}");
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $r = 3;
        $rows = [
            ['Total Income', $totals['income_net']],
            ['Total Expense', $totals['expense_net']],
            ['Net Cash Flow', $totals['income_net'] - $totals['expense_net']],
            ['', ''],
            ['Output VAT', $totals['output_vat']],
            ['Input VAT', $totals['input_vat']],
            ['Net VAT', $totals['output_vat'] - $totals['input_vat']],
            ['', ''],
            ['WHT Issued (we paid suppliers)', $totals['wht_issued']],
            ['WHT Received (customers withheld from us)', $totals['wht_received']],
            ['', ''],
            ['Ledger Entries', count($entries)],
        ];
        foreach ($rows as [$label, $value]) {
            $sheet->setCellValue("A{$r}", $label);
            $sheet->setCellValue("B{$r}", $value);
            if (is_numeric($value)) {
                $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $r++;
        }
        $sheet->getStyle('A3:B' . ($r - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(20);

        // Sheet 2: By Bank
        $this->buildByBankSheet($book, $entries, $accounts);

        // Sheet 3: Income by Category
        $this->buildByCategorySheet($book, $entries, 'income', 'Income by Category', $incomeCategories);

        // Sheet 4: Expense by Category
        $this->buildByCategorySheet($book, $entries, 'expense', 'Expense by Category', $expenseCategories);

        // Sheet 5: Cash Flow daily
        $this->buildCashFlowSheet($book, $year, $month, $entries);

        $book->setActiveSheetIndex(0);
        return $book;
    }

    protected function buildByBankSheet(Spreadsheet $book, $entries, $accounts): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle('By Bank');

        $columns = ['Bank Account', 'Type', 'Inflow (Income)', 'Outflow (Expense)', 'Net', 'Current Balance'];
        foreach ($columns as $i => $col) {
            $sheet->getCell([$i + 1, 1])->setValue($col);
        }
        $this->styleHeader($sheet, 'A1:F1');

        $entriesByAccount = $entries->groupBy('bank_account_id');
        $row = 2;
        foreach ($accounts as $acc) {
            $bucket = $entriesByAccount->get($acc->id, collect());
            $inflow = (float) $bucket->where('type', 'income')->sum('net_amount');
            $outflow = (float) $bucket->where('type', 'expense')->sum('net_amount');
            $sheet->setCellValue("A{$row}", $acc->bank_name . ' — ' . ($acc->account_name ?: $acc->account_number));
            $sheet->setCellValue("B{$row}", $acc->account_type === 'personal' ? 'Personal' : 'Company');
            $sheet->setCellValue("C{$row}", $inflow);
            $sheet->setCellValue("D{$row}", $outflow);
            $sheet->setCellValue("E{$row}", $inflow - $outflow);
            $sheet->setCellValue("F{$row}", (float) $acc->current_balance);
            $row++;
        }

        $sheet->getStyle("A2:F" . ($row - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        foreach (['C', 'D', 'E', 'F'] as $col) {
            $sheet->getStyle("{$col}2:{$col}" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        }
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    protected function buildByCategorySheet(Spreadsheet $book, $entries, string $type, string $title, $categories): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle($title);

        $columns = ['Category', 'Count', 'Gross Total', 'VAT', 'WHT', 'Net Total'];
        foreach ($columns as $i => $col) {
            $sheet->getCell([$i + 1, 1])->setValue($col);
        }
        $this->styleHeader($sheet, 'A1:F1');

        $rows = $entries->where('type', $type)->groupBy('category_id');
        $row = 2;
        $totals = ['count' => 0, 'gross' => 0.0, 'vat' => 0.0, 'wht' => 0.0, 'net' => 0.0];
        foreach ($rows as $catId => $bucket) {
            $catName = '— Uncategorized —';
            if ($catId && isset($categories[$catId])) {
                $catName = $categories[$catId]->name;
            }
            $count = $bucket->count();
            $gross = (float) $bucket->sum('gross_amount');
            $vat = (float) $bucket->sum('vat_amount');
            $wht = (float) $bucket->sum('wht_amount');
            $net = (float) $bucket->sum('net_amount');

            $sheet->setCellValue("A{$row}", $catName);
            $sheet->setCellValue("B{$row}", $count);
            $sheet->setCellValue("C{$row}", $gross);
            $sheet->setCellValue("D{$row}", $vat);
            $sheet->setCellValue("E{$row}", $wht);
            $sheet->setCellValue("F{$row}", $net);
            $row++;

            $totals['count'] += $count;
            $totals['gross'] += $gross;
            $totals['vat'] += $vat;
            $totals['wht'] += $wht;
            $totals['net'] += $net;
        }

        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->setCellValue("B{$row}", $totals['count']);
        $sheet->setCellValue("C{$row}", $totals['gross']);
        $sheet->setCellValue("D{$row}", $totals['vat']);
        $sheet->setCellValue("E{$row}", $totals['wht']);
        $sheet->setCellValue("F{$row}", $totals['net']);
        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E0E0']],
        ]);

        $sheet->getStyle("A2:F{$row}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        foreach (['C', 'D', 'E', 'F'] as $col) {
            $sheet->getStyle("{$col}2:{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        }
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    protected function buildCashFlowSheet(Spreadsheet $book, int $year, int $month, $entries): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle('Cash Flow');

        $columns = ['Date', 'Income', 'Expense', 'Net'];
        foreach ($columns as $i => $col) {
            $sheet->getCell([$i + 1, 1])->setValue($col);
        }
        $this->styleHeader($sheet, 'A1:D1');

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $daysInMonth = $end->day;

        $byDate = $entries->groupBy(fn ($e) => optional($e->entry_date)->format('Y-m-d'));

        $row = 2;
        $cumulative = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateKey = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $bucket = $byDate->get($dateKey, collect());
            $income = (float) $bucket->where('type', 'income')->sum('net_amount');
            $expense = (float) $bucket->where('type', 'expense')->sum('net_amount');
            $net = $income - $expense;
            $cumulative += $net;

            $sheet->setCellValue("A{$row}", sprintf('%02d/%02d/%04d', $d, $month, $year));
            $sheet->setCellValue("B{$row}", $income);
            $sheet->setCellValue("C{$row}", $expense);
            $sheet->setCellValue("D{$row}", $net);
            $row++;
        }

        $sheet->getStyle("A1:D" . ($row - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        foreach (['B', 'C', 'D'] as $col) {
            $sheet->getStyle("{$col}2:{$col}" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        }
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    // -------- Ledger detail workbook --------

    protected function buildLedgerWorkbook(int $year, int $month, $entries): Spreadsheet
    {
        $period = sprintf('%02d/%04d', $month, $year);
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Ledger Entries');

        $sheet->setCellValue('A1', "Ledger Entries — {$period}");
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $columns = ['Entry #', 'Date', 'Type', 'Bank', 'Counterparty', 'Tax ID', 'Description', 'Gross', 'VAT', 'WHT', 'Net'];
        foreach ($columns as $i => $col) {
            $sheet->getCell([$i + 1, 3])->setValue($col);
        }
        $this->styleHeader($sheet, 'A3:K3');

        $row = 4;
        foreach ($entries as $e) {
            $sheet->getCell("A{$row}")->setValueExplicit($e->entry_no ?? '', DataType::TYPE_STRING);
            $sheet->setCellValue("B{$row}", optional($e->entry_date)->format('d/m/Y'));
            $sheet->setCellValue("C{$row}", ucfirst($e->type));
            $sheet->setCellValue("D{$row}", $e->bankAccount?->bank_name ?? '-');
            $sheet->setCellValue("E{$row}", $e->counterparty_name ?? '');
            $sheet->getCell("F{$row}")->setValueExplicit($e->counterparty_tax_id ?? '', DataType::TYPE_STRING);
            $sheet->setCellValue("G{$row}", $e->description ?? '');
            $sheet->setCellValue("H{$row}", (float) $e->gross_amount);
            $sheet->setCellValue("I{$row}", (float) $e->vat_amount);
            $sheet->setCellValue("J{$row}", (float) $e->wht_amount);
            $sheet->setCellValue("K{$row}", ($e->type === 'income' ? 1 : -1) * (float) $e->net_amount);
            $row++;
        }

        $sheet->getStyle("A3:K" . ($row - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        foreach (['H', 'I', 'J', 'K'] as $col) {
            $sheet->getStyle("{$col}4:{$col}" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        }
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        return $book;
    }

    // -------- Attachments --------

    protected function addAttachments(ZipArchive $zip, int $year, int $month, $entries): void
    {
        $disk = Storage::disk('public');

        // Tax invoices issued in period
        $invoices = TaxInvoice::whereYear('invoice_date', $year)
            ->whereMonth('invoice_date', $month)
            ->where('status', 'issued')
            ->get();
        foreach ($invoices as $inv) {
            $path = sprintf('tax_invoices/%04d/%s.pdf', $inv->fiscal_year, $inv->invoice_no);
            if ($disk->exists($path)) {
                $zip->addFile($disk->path($path), "attachments/tax_invoices/{$inv->invoice_no}.pdf");
            }
        }

        // WHT certificates in period
        $certs = WhtCertificate::where('tax_period_year', $year)
            ->where('tax_period_month', $month)
            ->get();
        foreach ($certs as $cert) {
            if ($cert->certificate_path && $disk->exists($cert->certificate_path)) {
                $zip->addFile($disk->path($cert->certificate_path), "attachments/wht_certificates/{$cert->cert_no}.pdf");
            }
        }

        // Ledger receipts + attached files
        foreach ($entries as $entry) {
            if ($entry->receipt_path && $disk->exists($entry->receipt_path)) {
                $ext = pathinfo($entry->receipt_path, PATHINFO_EXTENSION) ?: 'bin';
                $zip->addFile($disk->path($entry->receipt_path), "attachments/receipts/{$entry->entry_no}.{$ext}");
            }
            foreach ($entry->attached_files ?? [] as $i => $file) {
                $path = $file['path'] ?? null;
                if (!$path || !$disk->exists($path)) {
                    continue;
                }
                $name = $file['name'] ?? ("attach-{$i}." . (pathinfo($path, PATHINFO_EXTENSION) ?: 'bin'));
                $zip->addFile($disk->path($path), "attachments/receipts/{$entry->entry_no}-extra-{$i}-{$name}");
            }
        }
    }

    // -------- Helpers --------

    protected function aggregateTotals($entries): array
    {
        $income = $entries->where('type', 'income');
        $expense = $entries->where('type', 'expense');

        // VAT: output from issued tax invoices already counted in TaxReportService.
        // For the summary overview we use the ledger-side totals so the bundle
        // is self-consistent even when invoices were drafted but never issued.
        $outputVat = (float) $income->where('vat_treatment', 'taxable')->sum('vat_amount');
        $inputVat = (float) $expense->where('vat_amount', '>', 0)->sum('vat_amount');

        return [
            'income_net' => (float) $income->sum('net_amount'),
            'expense_net' => (float) $expense->sum('net_amount'),
            'output_vat' => $outputVat,
            'input_vat' => $inputVat,
            'wht_issued' => (float) $expense->whereIn('wht_type', ['pnd3', 'pnd53'])->sum('wht_amount'),
            'wht_received' => (float) $income->whereIn('wht_type', ['pnd3', 'pnd53'])->sum('wht_amount'),
        ];
    }

    protected function workbookToString(Spreadsheet $book): string
    {
        $writer = new Xlsx($book);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
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
}
