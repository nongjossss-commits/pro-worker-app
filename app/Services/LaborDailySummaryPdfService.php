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
 */
class LaborDailySummaryPdfService
{
    protected string $fontDir;
    protected bool $fontLoaded = false;

    public function __construct()
    {
        $this->fontDir = public_path('fonts');
    }

    public function generate(Carbon $from, Carbon $to, array $billingReport, $teamSummary, $categorySummary, $accounts): string
    {
        $pdf = new Fpdi();
        $this->setupFont($pdf);
        $pdf->AddPage('P', 'A4');

        $this->renderHeader($pdf, $from, $to);
        $this->renderAccountBalances($pdf, $accounts);
        $this->renderTeamSummary($pdf, $teamSummary);
        $this->renderBillingSummary($pdf, $billingReport);
        $this->renderCategorySummary($pdf, $categorySummary);

        return $pdf->Output('S');
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

    protected function sectionTitle(Fpdi $pdf, string $title): void
    {
        $pdf->Ln(4);
        $pdf->SetFont('THSarabunNew', '', 13);
        $pdf->Cell(190, 7, $this->txt($title), 0, 1, 'L');
    }

    protected function tableHeader(Fpdi $pdf, array $widths, array $labels): void
    {
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->SetFillColor(230, 230, 230);
        foreach ($labels as $i => $label) {
            $align = $i === 0 ? 'L' : 'C';
            $pdf->Cell($widths[$i], 6, $this->txt($label), 1, 0, $align, true);
        }
        $pdf->Ln();
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
        $this->sectionTitle($pdf, 'คงเหลือแต่ละบัญชี');

        $widths = [90, 50, 50];
        $this->tableHeader($pdf, $widths, ['บัญชี', 'ธนาคาร', 'คงเหลือ']);

        $pdf->SetFont('THSarabunNew', '', 10);
        $total = 0.0;
        foreach ($accounts as $account) {
            $pdf->Cell($widths[0], 6, $this->txt($account->name), 1, 0, 'L');
            $pdf->Cell($widths[1], 6, $this->txt($account->bank_name ?: '-'), 1, 0, 'C');
            $pdf->Cell($widths[2], 6, number_format($account->computed_balance, 2), 1, 1, 'R');
            $total += (float) $account->computed_balance;
        }
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->Cell($widths[0] + $widths[1], 6, $this->txt('รวมทั้งหมด'), 1, 0, 'R');
        $pdf->Cell($widths[2], 6, number_format($total, 2), 1, 1, 'R');
    }

    protected function renderTeamSummary(Fpdi $pdf, $teamSummary): void
    {
        $this->sectionTitle($pdf, 'สรุปตามทีม (รับเข้าบัญชีบริษัท / ยอดเรียกเก็บ)');

        $widths = [55, 35, 35, 35, 30];
        $this->tableHeader($pdf, $widths, ['ทีม', 'รับช่วงนี้', 'ยอดรวม', 'ชำระแล้ว', 'คงค้าง']);

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
        $this->sectionTitle($pdf, 'สรุปยอดเรียกเก็บ (Central Billing Ledger)');

        $widths = [55, 34, 34, 34, 33];
        $this->tableHeader($pdf, $widths, ['ทีม', 'ยกมา', 'เรียกเก็บ', 'ชำระ', 'คงเหลือ']);

        $pdf->SetFont('THSarabunNew', '', 10);
        foreach ($billingReport['rows'] as $r) {
            $pdf->Cell($widths[0], 6, $this->txt($r['team']->name), 1, 0, 'L');
            $pdf->Cell($widths[1], 6, number_format($r['opening_balance'], 2), 1, 0, 'R');
            $pdf->Cell($widths[2], 6, number_format($r['charges'], 2), 1, 0, 'R');
            $pdf->Cell($widths[3], 6, number_format(abs($r['payments']), 2), 1, 0, 'R');
            $pdf->Cell($widths[4], 6, number_format($r['closing_balance'], 2), 1, 1, 'R');
        }
        $pdf->SetFont('THSarabunNew', '', 10);
        $t = $billingReport['totals'];
        $pdf->Cell($widths[0], 6, $this->txt('รวมทั้งหมด'), 1, 0, 'R');
        $pdf->Cell($widths[1], 6, number_format($t['opening_balance'], 2), 1, 0, 'R');
        $pdf->Cell($widths[2], 6, number_format($t['charges'], 2), 1, 0, 'R');
        $pdf->Cell($widths[3], 6, number_format(abs($t['payments']), 2), 1, 0, 'R');
        $pdf->Cell($widths[4], 6, number_format($t['closing_balance'], 2), 1, 1, 'R');
    }

    protected function renderCategorySummary(Fpdi $pdf, $categorySummary): void
    {
        $this->sectionTitle($pdf, 'แยกตามหมวดหมู่');

        $widths = [30, 100, 60];
        $this->tableHeader($pdf, $widths, ['ประเภท', 'หมวดหมู่', 'ยอดรวม']);

        $pdf->SetFont('THSarabunNew', '', 10);
        foreach ($categorySummary as $row) {
            $pdf->Cell($widths[0], 6, $this->txt($row->type === 'income' ? 'รับ' : 'จ่าย'), 1, 0, 'C');
            $pdf->Cell($widths[1], 6, $this->txt($row->label), 1, 0, 'L');
            $pdf->Cell($widths[2], 6, number_format($row->total, 2), 1, 1, 'R');
        }
        if (count($categorySummary) === 0) {
            $pdf->Cell(array_sum($widths), 6, $this->txt('ไม่มีข้อมูลในช่วงนี้'), 1, 1, 'C');
        }
    }
}
