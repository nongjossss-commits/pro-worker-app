<?php

namespace App\Services;

use Carbon\Carbon;
use ReflectionClass;
use setasign\Fpdi\Fpdi;

/**
 * A plain, shareable snapshot of the main Finance "บันทึกรายรับรายจ่าย"
 * books for a chosen period — same FPDI + THSarabunNew + CP874 pattern as
 * LaborDailySummaryPdfService (see that class), just two sections (Account
 * Balances + itemized Category Breakdown, no team-billing section since
 * there's no team-billing concept here) and one accent color throughout
 * (no "Central Billing vs. Company Books" domain split to color-code).
 */
class FinanceDailySummaryPdfService
{
    protected string $fontDir;
    protected bool $fontLoaded = false;
    protected array $color = [22, 163, 74]; // same green used for Labor's "Company Books" domain

    public function __construct()
    {
        $this->fontDir = public_path('fonts');
    }

    public function generate(Carbon $from, Carbon $to, $accounts, $categoryTransactions): string
    {
        $pdf = new Fpdi();
        $this->setupFont($pdf);
        $pdf->AddPage('P', 'A4');

        $this->renderHeader($pdf, $from, $to);
        $this->renderAccountBalances($pdf, $accounts);
        $this->renderCategoryItemized($pdf, $categoryTransactions);

        return $pdf->Output('S');
    }

    protected function ensureSpace(Fpdi $pdf, float $rowHeight, array $widths, array $headerLabels): void
    {
        if ($pdf->GetY() + $rowHeight > 280) {
            $pdf->AddPage('P', 'A4');
            $this->tableHeader($pdf, $widths, $headerLabels);
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

    protected function tint(array $rgb, float $whiteAmount = 0.85): array
    {
        return array_map(fn ($c) => (int) round($c * (1 - $whiteAmount) + 255 * $whiteAmount), $rgb);
    }

    protected function sectionTitle(Fpdi $pdf, string $title): void
    {
        $pdf->Ln(6);
        $pdf->SetFont('THSarabunNew', '', 13);
        $pdf->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(190, 8, '  ' . $this->txt($title), 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(1);
    }

    protected function tableHeader(Fpdi $pdf, array $widths, array $labels): void
    {
        $tint = $this->tint($this->color);
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->SetFillColor($tint[0], $tint[1], $tint[2]);
        foreach ($labels as $i => $label) {
            $align = $i === 0 ? 'L' : 'C';
            $pdf->Cell($widths[$i], 6, $this->txt($label), 1, 0, $align, true);
        }
        $pdf->Ln();
    }

    protected function totalRowFill(Fpdi $pdf): void
    {
        $tint = $this->tint($this->color, 0.82);
        $pdf->SetFillColor($tint[0], $tint[1], $tint[2]);
    }

    protected function renderHeader(Fpdi $pdf, Carbon $from, Carbon $to): void
    {
        $appName = BrandService::current()['app_name'];

        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(190, 8, $this->txt($appName . ' — สรุปรายรับรายจ่ายประจำงวด'), 0, 1, 'C');

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
        $this->sectionTitle($pdf, 'กระทบยอดคงเหลือแต่ละบัญชี (ตามช่วงเวลา)');

        $widths = [55, 35, 25, 25, 25, 25];
        $this->tableHeader($pdf, $widths, ['บัญชี', 'ธนาคาร', 'ยกมา', 'รับ', 'จ่าย', 'คงเหลือ']);

        $pdf->SetFont('THSarabunNew', '', 10);
        foreach ($accounts->rows as $r) {
            $label = $r->account->account_name ?: $r->account->bank_name;
            $pdf->Cell($widths[0], 6, $this->txt($label), 1, 0, 'L');
            $pdf->Cell($widths[1], 6, $this->txt($r->account->bank_name ?: '-'), 1, 0, 'C');
            $pdf->Cell($widths[2], 6, number_format($r->opening_balance, 2), 1, 0, 'R');
            $pdf->Cell($widths[3], 6, number_format($r->income, 2), 1, 0, 'R');
            $pdf->Cell($widths[4], 6, number_format($r->expense, 2), 1, 0, 'R');
            $pdf->Cell($widths[5], 6, number_format($r->closing_balance, 2), 1, 1, 'R');
        }
        $pdf->SetFont('THSarabunNew', '', 10);
        $this->totalRowFill($pdf);
        $pdf->Cell($widths[0] + $widths[1], 6, $this->txt('รวมทั้งหมด'), 1, 0, 'R', true);
        $pdf->Cell($widths[2], 6, number_format($accounts->totals->opening_balance, 2), 1, 0, 'R', true);
        $pdf->Cell($widths[3], 6, number_format($accounts->totals->income, 2), 1, 0, 'R', true);
        $pdf->Cell($widths[4], 6, number_format($accounts->totals->expense, 2), 1, 0, 'R', true);
        $pdf->Cell($widths[5], 6, number_format($accounts->totals->closing_balance, 2), 1, 1, 'R', true);
    }

    protected function renderCategoryItemized(Fpdi $pdf, $categoryTransactions): void
    {
        $this->sectionTitle($pdf, 'แยกตามหมวดหมู่ (แจกแจงรายการ)');

        $widths = [30, 110, 50];
        $labels = ['วันที่', 'รายละเอียด', 'จำนวนเงิน'];
        $this->tableHeader($pdf, $widths, $labels);

        $pdf->SetFont('THSarabunNew', '', 10);

        if ($categoryTransactions->groups->isEmpty()) {
            $pdf->Cell(array_sum($widths), 6, $this->txt('ไม่มีข้อมูลในช่วงนี้'), 1, 1, 'C');
            return;
        }

        foreach ($categoryTransactions->groups as $group) {
            $this->ensureSpace($pdf, 6, $widths, $labels);

            $pdf->SetFont('THSarabunNew', '', 10);
            $pdf->SetFillColor(241, 245, 249);
            $typeLabel = $group->type === 'income' ? 'รับ' : 'จ่าย';
            $pdf->Cell($widths[0] + $widths[1], 6, $this->txt("[{$typeLabel}] " . $group->label), 1, 0, 'L', true);
            $pdf->Cell($widths[2], 6, number_format($group->subtotal, 2), 1, 1, 'R', true);

            foreach ($group->items as $item) {
                $this->ensureSpace($pdf, 6, $widths, $labels);
                $pdf->Cell($widths[0], 6, $this->txt($item->entry_date->format('d/m/Y')), 1, 0, 'C');
                $pdf->Cell($widths[1], 6, $this->txt('  ' . $item->description), 1, 0, 'L');
                $pdf->Cell($widths[2], 6, number_format($item->net_amount, 2), 1, 1, 'R');
            }
        }

        $this->ensureSpace($pdf, 18, $widths, $labels);
        $pdf->SetFont('THSarabunNew', '', 10);
        $this->totalRowFill($pdf);
        $pdf->Cell($widths[0] + $widths[1], 6, $this->txt('ยอดรวมรับ'), 1, 0, 'R', true);
        $pdf->Cell($widths[2], 6, number_format($categoryTransactions->income_total, 2), 1, 1, 'R', true);
        $pdf->Cell($widths[0] + $widths[1], 6, $this->txt('ยอดรวมจ่าย'), 1, 0, 'R', true);
        $pdf->Cell($widths[2], 6, number_format($categoryTransactions->expense_total, 2), 1, 1, 'R', true);
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell($widths[0] + $widths[1], 7, $this->txt('ยอดรวมสุทธิ'), 1, 0, 'R', true);
        $pdf->Cell($widths[2], 7, number_format($categoryTransactions->net, 2), 1, 1, 'R', true);
    }
}
