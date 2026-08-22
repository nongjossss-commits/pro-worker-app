<?php

namespace App\Services;

use Carbon\Carbon;
use ReflectionClass;
use setasign\Fpdi\Fpdi;

/**
 * A plain, shareable snapshot of company-wide Labor figures for a chosen
 * period — meant to be sent to a LINE/WeChat group as-is, not a formal
 * document (no logo/signature/stamp, unlike LaborReceiptPdfService and the
 * other Labor PDF services it otherwise mirrors: direct FPDI, THSarabunNew
 * + CP874, no drag-drop template builder).
 *
 * Section banners are color-coded by which ledger the numbers belong to —
 * orange for the Central Billing Ledger (what teams owe), green for
 * Company Books (the office's own cash) — mirroring the Reports page's own
 * accent colors (see resources/views/labor/reports/index.blade.php) so the
 * PDF reads the same way as the web page it's exported from.
 */
class LaborDailySummaryPdfService
{
    protected string $fontDir;
    protected bool $fontLoaded = false;
    protected array $orangeRgb;
    protected array $greenRgb = [22, 163, 74];

    public function __construct()
    {
        $this->fontDir = public_path('fonts');
        $this->orangeRgb = $this->hexToRgbArray(BrandService::current()['primary_color']);
    }

    public function generate(Carbon $from, Carbon $to, array $billingReport, $teamSummary, $categoryTransactions, $accounts): string
    {
        $pdf = new Fpdi();
        $this->setupFont($pdf);
        $pdf->AddPage('P', 'A4');

        $this->renderHeader($pdf, $from, $to);
        $this->renderAccountBalances($pdf, $accounts);
        $this->renderTeamSummary($pdf, $teamSummary);
        $this->renderBillingSummary($pdf, $billingReport);
        $this->renderCategoryItemized($pdf, $categoryTransactions);

        return $pdf->Output('S');
    }

    /**
     * AddPage() + redraw the given table header if the next row wouldn't
     * fit above the bottom margin — none of the other render*() methods
     * need this (their row counts are bounded by team/account count), but
     * an itemized transaction list can run to any length.
     */
    protected function ensureSpace(Fpdi $pdf, float $rowHeight, array $widths, array $headerLabels, array $colorRgb): void
    {
        if ($pdf->GetY() + $rowHeight > 280) {
            $pdf->AddPage('P', 'A4');
            $this->tableHeader($pdf, $widths, $headerLabels, $colorRgb);
        }
    }

    protected function setupFont(Fpdi $pdf): void
    {
        $reflection = new ReflectionClass($pdf);
        if ($reflection->hasProperty('fontpath')) {
            $property = $reflection->getProperty('fontpath');
            $property->setAccessible(true);
            $property->setValue($pdf, $this->fontDir . DIRECTORY_SEPARATOR);
        }

        if (file_exists($this->fontDir . DIRECTORY_SEPARATOR . 'THSarabunNew.php')) {
            $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
            $pdf->SetFont('THSarabunNew', '', 14);
            $this->fontLoaded = true;
        } else {
            $pdf->SetFont('Arial', '', 12);
        }
    }

    protected function txt(string $text): string
    {
        if ($this->fontLoaded) {
            $encoded = @iconv('UTF-8', 'cp874//IGNORE', $text);
            return $encoded === false ? $text : $encoded;
        }
        $encoded = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
        return $encoded === false ? $text : $encoded;
    }

    protected function hexToRgbArray(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return [249, 115, 22];
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /**
     * Blend an RGB triple toward white — used for the tinted table-header
     * fill under a full-strength colored section banner (a solid banner
     * color would be unreadable behind small header text).
     */
    protected function tint(array $rgb, float $whiteAmount = 0.85): array
    {
        return array_map(fn ($c) => (int) round($c * (1 - $whiteAmount) + 255 * $whiteAmount), $rgb);
    }

    /**
     * Full-width colored banner — the PDF equivalent of the Reports page's
     * tinted, icon-led card headers, so each section reads as its own
     * distinct block instead of one continuous run of tables.
     */
    protected function sectionTitle(Fpdi $pdf, string $title, array $colorRgb): void
    {
        $pdf->Ln(6);
        $pdf->SetFont('THSarabunNew', '', 13);
        $pdf->SetFillColor($colorRgb[0], $colorRgb[1], $colorRgb[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(190, 8, '  ' . $this->txt($title), 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(1);
    }

    protected function tableHeader(Fpdi $pdf, array $widths, array $labels, array $colorRgb): void
    {
        $tint = $this->tint($colorRgb);
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->SetFillColor($tint[0], $tint[1], $tint[2]);
        foreach ($labels as $i => $label) {
            $align = $i === 0 ? 'L' : 'C';
            $pdf->Cell($widths[$i], 6, $this->txt($label), 1, 0, $align, true);
        }
        $pdf->Ln();
    }

    /**
     * A total/grand-total row: bold text on a light tint of the section's
     * color — the PDF equivalent of the Reports page's `table-light
     * fw-bold` tfoot rows. Caller is responsible for the Cell() calls;
     * this just arms the fill color they draw with.
     */
    protected function totalRowFill(Fpdi $pdf, array $colorRgb): void
    {
        $tint = $this->tint($colorRgb, 0.82);
        $pdf->SetFillColor($tint[0], $tint[1], $tint[2]);
    }

    protected function renderHeader(Fpdi $pdf, Carbon $from, Carbon $to): void
    {
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(190, 8, $this->txt('Pro Walker Labor — สรุปประจำงวด'), 0, 1, 'C');

        $pdf->SetFont('THSarabunNew', '', 12);
        $period = $from->isSameDay($to)
            ? $from->format('d/m/Y')
            : $from->format('d/m/Y') . ' — ' . $to->format('d/m/Y');
        $pdf->Cell(190, 6, $this->txt($period), 0, 1, 'C');
        $pdf->Cell(190, 5, $this->txt('พิมพ์เมื่อ ' . now()->format('d/m/Y H:i')), 0, 1, 'C');

        $pdf->SetLineWidth(0.3);
        $pdf->Line(10, $pdf->GetY() + 2, 200, $pdf->GetY() + 2);
        $pdf->Ln(6);
    }

    protected function renderAccountBalances(Fpdi $pdf, $accounts): void
    {
        $color = $this->greenRgb;
        $this->sectionTitle($pdf, 'กระทบยอดคงเหลือแต่ละบัญชี (ตามช่วงเวลา)', $color);

        $widths = [55, 35, 25, 25, 25, 25];
        $this->tableHeader($pdf, $widths, ['บัญชี', 'ธนาคาร', 'ยกมา', 'รับ', 'จ่าย', 'คงเหลือ'], $color);

        $pdf->SetFont('THSarabunNew', '', 10);
        foreach ($accounts->rows as $r) {
            $pdf->Cell($widths[0], 6, $this->txt($r->account->name), 1, 0, 'L');
            $pdf->Cell($widths[1], 6, $this->txt($r->account->bank_name ?: '-'), 1, 0, 'C');
            $pdf->Cell($widths[2], 6, number_format($r->opening_balance, 2), 1, 0, 'R');
            $pdf->Cell($widths[3], 6, number_format($r->income, 2), 1, 0, 'R');
            $pdf->Cell($widths[4], 6, number_format($r->expense, 2), 1, 0, 'R');
            $pdf->Cell($widths[5], 6, number_format($r->closing_balance, 2), 1, 1, 'R');
        }
        $pdf->SetFont('THSarabunNew', '', 10);
        $this->totalRowFill($pdf, $color);
        $pdf->Cell($widths[0] + $widths[1], 6, $this->txt('รวมทั้งหมด'), 1, 0, 'R', true);
        $pdf->Cell($widths[2], 6, number_format($accounts->totals->opening_balance, 2), 1, 0, 'R', true);
        $pdf->Cell($widths[3], 6, number_format($accounts->totals->income, 2), 1, 0, 'R', true);
        $pdf->Cell($widths[4], 6, number_format($accounts->totals->expense, 2), 1, 0, 'R', true);
        $pdf->Cell($widths[5], 6, number_format($accounts->totals->closing_balance, 2), 1, 1, 'R', true);
    }

    protected function renderTeamSummary(Fpdi $pdf, $teamSummary): void
    {
        $color = $this->greenRgb;
        $this->sectionTitle($pdf, 'สรุปตามทีม (รับเข้าบัญชีบริษัท / ยอดเรียกเก็บ)', $color);

        $widths = [55, 35, 35, 35, 30];
        $this->tableHeader($pdf, $widths, ['ทีม', 'รับช่วงนี้', 'ยอดรวม', 'ชำระแล้ว', 'คงค้าง'], $color);

        $pdf->SetFont('THSarabunNew', '', 10);
        foreach ($teamSummary as $row) {
            $pdf->Cell($widths[0], 6, $this->txt($row->team->name), 1, 0, 'L');
            $pdf->Cell($widths[1], 6, number_format($row->received_in_range, 2), 1, 0, 'R');
            $pdf->Cell($widths[2], 6, number_format($row->total_due, 2), 1, 0, 'R');
            $pdf->Cell($widths[3], 6, number_format($row->total_paid, 2), 1, 0, 'R');
            $pdf->Cell($widths[4], 6, number_format($row->balance_due, 2), 1, 1, 'R');
        }
        if (count($teamSummary) === 0) {
            $pdf->Cell(array_sum($widths), 6, $this->txt('ไม่มีข้อมูลในช่วงนี้'), 1, 1, 'C');
        }
    }

    protected function renderBillingSummary(Fpdi $pdf, array $billingReport): void
    {
        $color = $this->orangeRgb;
        $this->sectionTitle($pdf, 'สรุปยอดเรียกเก็บ (Central Billing Ledger)', $color);

        $widths = [55, 34, 34, 34, 33];
        $this->tableHeader($pdf, $widths, ['ทีม', 'ยกมา', 'เรียกเก็บ', 'ชำระ', 'คงเหลือ'], $color);

        $pdf->SetFont('THSarabunNew', '', 10);
        foreach ($billingReport['rows'] as $r) {
            $pdf->Cell($widths[0], 6, $this->txt($r['team']->name), 1, 0, 'L');
            $pdf->Cell($widths[1], 6, number_format($r['opening_balance'], 2), 1, 0, 'R');
            $pdf->Cell($widths[2], 6, number_format($r['charges'], 2), 1, 0, 'R');
            $pdf->Cell($widths[3], 6, number_format(abs($r['payments']), 2), 1, 0, 'R');
            $pdf->Cell($widths[4], 6, number_format($r['closing_balance'], 2), 1, 1, 'R');
        }
        $pdf->SetFont('THSarabunNew', '', 10);
        $this->totalRowFill($pdf, $color);
        $t = $billingReport['totals'];
        $pdf->Cell($widths[0], 6, $this->txt('รวมทั้งหมด'), 1, 0, 'R', true);
        $pdf->Cell($widths[1], 6, number_format($t['opening_balance'], 2), 1, 0, 'R', true);
        $pdf->Cell($widths[2], 6, number_format($t['charges'], 2), 1, 0, 'R', true);
        $pdf->Cell($widths[3], 6, number_format(abs($t['payments']), 2), 1, 0, 'R', true);
        $pdf->Cell($widths[4], 6, number_format($t['closing_balance'], 2), 1, 1, 'R', true);
    }

    /**
     * Itemized breakdown: a bold category-header row (type + label +
     * subtotal) followed by every individual transaction under it (date,
     * description, qty, amount), then income/expense/net grand totals —
     * so the daily/period PDF shows exactly which line items made up each
     * category, not just its sum. Page-breaks via ensureSpace() since this
     * list can run long for a busy month/quarter/year. Item rows alternate
     * a faint fill (zebra striping) so a row doesn't visually bleed into
     * its neighbor when scanning across the amount column.
     */
    protected function renderCategoryItemized(Fpdi $pdf, $categoryTransactions): void
    {
        $color = $this->greenRgb;
        $this->sectionTitle($pdf, 'แยกตามหมวดหมู่ (แจกแจงรายการ)', $color);

        $widths = [30, 90, 20, 50];
        $labels = ['วันที่', 'รายละเอียด', 'จำนวน', 'จำนวนเงิน'];
        $this->tableHeader($pdf, $widths, $labels, $color);

        $pdf->SetFont('THSarabunNew', '', 10);

        if ($categoryTransactions->groups->isEmpty()) {
            $pdf->Cell(array_sum($widths), 6, $this->txt('ไม่มีข้อมูลในช่วงนี้'), 1, 1, 'C');
            return;
        }

        foreach ($categoryTransactions->groups as $group) {
            $this->ensureSpace($pdf, 6, $widths, $labels, $color);

            $pdf->SetFont('THSarabunNew', '', 10);
            $pdf->SetFillColor(241, 245, 249);
            $typeLabel = $group->type === 'income' ? 'รับ' : 'จ่าย';
            $pdf->Cell($widths[0] + $widths[1], 6, $this->txt("[{$typeLabel}] " . $group->label), 1, 0, 'L', true);
            $pdf->Cell($widths[2], 6, '', 1, 0, 'C', true);
            $pdf->Cell($widths[3], 6, number_format($group->subtotal, 2), 1, 1, 'R', true);

            foreach ($group->items as $itemIndex => $item) {
                $this->ensureSpace($pdf, 6, $widths, $labels, $color);
                $zebra = $itemIndex % 2 === 1;
                if ($zebra) {
                    $pdf->SetFillColor(249, 250, 251);
                }
                $pdf->Cell($widths[0], 6, $this->txt($item->transaction_date->format('d/m/Y')), 1, 0, 'C', $zebra);
                $pdf->Cell($widths[1], 6, $this->txt('  ' . $item->description), 1, 0, 'L', $zebra);
                $pdf->Cell($widths[2], 6, $item->quantity !== null ? (string) $item->quantity : '-', 1, 0, 'C', $zebra);
                $pdf->Cell($widths[3], 6, number_format($item->amount, 2), 1, 1, 'R', $zebra);
            }
        }

        $this->ensureSpace($pdf, 18, $widths, $labels, $color);
        $pdf->SetFont('THSarabunNew', '', 10);
        $this->totalRowFill($pdf, $color);
        $pdf->Cell($widths[0] + $widths[1] + $widths[2], 6, $this->txt('ยอดรวมรับ'), 1, 0, 'R', true);
        $pdf->Cell($widths[3], 6, number_format($categoryTransactions->income_total, 2), 1, 1, 'R', true);
        $pdf->Cell($widths[0] + $widths[1] + $widths[2], 6, $this->txt('ยอดรวมจ่าย'), 1, 0, 'R', true);
        $pdf->Cell($widths[3], 6, number_format($categoryTransactions->expense_total, 2), 1, 1, 'R', true);
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell($widths[0] + $widths[1] + $widths[2], 7, $this->txt('ยอดรวมสุทธิ'), 1, 0, 'R', true);
        $pdf->Cell($widths[3], 7, number_format($categoryTransactions->net, 2), 1, 1, 'R', true);
    }
}
