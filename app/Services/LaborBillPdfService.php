<?php

namespace App\Services;

use App\Models\LaborBill;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use setasign\Fpdi\Fpdi;

/**
 * Generate PDF for a LaborBill — same visual conventions as
 * TaxInvoicePdfService (FPDF, THSarabunNew/CP874, A4) but simpler content:
 * no VAT (this is an internal statement, not a tax document), items are the
 * period's ledger entries plus a "ยอดยกมา" (carried forward) line.
 */
class LaborBillPdfService
{
    protected string $fontDir;
    protected bool $fontLoaded = false;

    public function __construct()
    {
        $this->fontDir = public_path('fonts');
    }

    public function generate(LaborBill $bill): string
    {
        $bill->loadMissing('team', 'financialProfile');
        $entries = $bill->team->ledgerEntries()
            ->whereBetween('entry_date', [$bill->period_start->toDateString(), $bill->period_end->toDateString()])
            ->orderBy('entry_date')
            ->get();

        $pdf = new Fpdi();
        $this->setupFont($pdf);
        $pdf->AddPage('P', 'A4');

        $this->renderHeader($pdf, $bill);
        $this->renderParties($pdf, $bill);
        $this->renderBillMeta($pdf, $bill);
        $this->renderItemsTable($pdf, $bill, $entries);
        $this->renderTotals($pdf, $bill);
        $this->renderSignature($pdf, $bill);
        $this->renderWatermark($pdf, $bill);

        return $pdf->Output('S');
    }

    public function generateAndStore(LaborBill $bill): string
    {
        $binary = $this->generate($bill);
        $path = sprintf('labor_bills/%04d/%s.pdf', $bill->period_end->year, $bill->bill_no);
        Storage::disk('public')->put($path, $binary);
        return $path;
    }

    // ---------- Layout helpers (mirrors TaxInvoicePdfService) ----------

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

    protected function renderHeader(Fpdi $pdf, LaborBill $bill): void
    {
        $profile = $bill->financialProfile;
        $logoY = 10;

        if ($profile && $profile->logo_path && Storage::disk('public')->exists($profile->logo_path)) {
            try {
                $pdf->Image(Storage::disk('public')->path($profile->logo_path), 12, $logoY, 18);
            } catch (\Throwable $e) {
                // ignore — bad image format etc.
            }
        }

        $pdf->SetXY(33, $logoY);
        $pdf->SetFont('THSarabunNew', '', 14);
        $pdf->Cell(110, 6, $this->txt($profile?->name ?: ''), 0, 2, 'L');
        $pdf->SetFont('THSarabunNew', '', 10);
        if ($profile?->address) {
            $oneLineAddress = preg_replace('/\s+/u', ' ', trim((string) $profile->address));
            $pdf->SetX(33);
            $pdf->MultiCell(110, 4, $this->txt($oneLineAddress), 0, 'L');
        }
        $metaParts = [];
        if ($profile?->phone)  $metaParts[] = 'โทร. ' . $profile->phone;
        if ($profile?->tax_id) $metaParts[] = 'เลขผู้เสียภาษี: ' . $profile->tax_id;
        if (!empty($metaParts)) {
            $pdf->SetX(33);
            $pdf->Cell(110, 4, $this->txt(implode('   ', $metaParts)), 0, 2, 'L');
        }

        $pdf->SetXY(140, $logoY);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(55, 7, $this->txt('ใบวางบิล'), 0, 2, 'R');
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->Cell(55, 4, 'BILLING STATEMENT', 0, 2, 'R');

        $pdf->SetLineWidth(0.3);
        $pdf->Line(10, 32, 200, 32);
    }

    protected function renderParties(Fpdi $pdf, LaborBill $bill): void
    {
        $pdf->SetXY(10, 35);
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(120, 5, $this->txt('ทีมงาน / Team'), 0, 2, 'L');

        $pdf->SetFont('THSarabunNew', '', 12);
        $pdf->Cell(120, 5, $this->txt($bill->team->name ?? '-'), 0, 2, 'L');
    }

    protected function renderBillMeta(Fpdi $pdf, LaborBill $bill): void
    {
        $pdf->SetXY(135, 35);
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->Cell(25, 5, $this->txt('เลขที่ / No.'), 0, 0, 'L');
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(40, 5, $this->txt($bill->bill_no), 0, 2, 'L');

        $pdf->SetX(135);
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->Cell(25, 5, $this->txt('งวด / Period'), 0, 0, 'L');
        $pdf->SetFont('THSarabunNew', '', 11);
        $period = $bill->period_start->format('d/m/Y') . ' - ' . $bill->period_end->format('d/m/Y');
        $pdf->Cell(60, 5, $this->txt($period), 0, 2, 'L');
    }

    protected function renderItemsTable(Fpdi $pdf, LaborBill $bill, $entries): void
    {
        $y = 55;
        $pdf->SetXY(10, $y);

        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(30, 7, $this->txt('วันที่'), 1, 0, 'C', true);
        $pdf->Cell(120, 7, $this->txt('รายการ / Description'), 1, 0, 'C', true);
        $pdf->Cell(40, 7, $this->txt('จำนวนเงิน (บาท)'), 1, 1, 'C', true);

        $pdf->SetFont('THSarabunNew', '', 10);

        if ((float) $bill->previous_balance !== 0.0) {
            $pdf->Cell(30, 6, $this->txt($bill->period_start->format('d/m/Y')), 1, 0, 'C');
            $pdf->Cell(120, 6, $this->txt('ยอดยกมา / Balance brought forward'), 1, 0, 'L');
            $pdf->Cell(40, 6, number_format((float) $bill->previous_balance, 2), 1, 1, 'R');
        }

        foreach ($entries as $entry) {
            // A page break here would need a fresh header — out of scope for
            // the first version; long periods should be split by Accounting
            // Staff into shorter billing windows instead.
            $pdf->Cell(30, 6, $this->txt($entry->entry_date->format('d/m/Y')), 1, 0, 'C');
            $pdf->Cell(120, 6, $this->txt($entry->description), 1, 0, 'L');
            $pdf->Cell(40, 6, number_format((float) $entry->amount, 2), 1, 1, 'R');
        }
    }

    protected function renderTotals(Fpdi $pdf, LaborBill $bill): void
    {
        $pdf->SetFont('THSarabunNew', '', 13);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(150, 9, $this->txt('ยอดคงค้างรวมทั้งสิ้น / Total Due'), 1, 0, 'R', true);
        $pdf->Cell(40, 9, number_format((float) $bill->total_due, 2), 1, 1, 'R', true);

        $pdf->SetFont('THSarabunNew', '', 12);
        $bahtText = $this->bahtText((float) $bill->total_due);
        $pdf->Cell(190, 7, $this->txt('(' . $bahtText . ')'), 1, 1, 'C');
    }

    protected function renderSignature(Fpdi $pdf, LaborBill $bill): void
    {
        $profile = $bill->financialProfile;
        $blockY = $pdf->GetY() + 15;
        if ($blockY > 235) {
            $blockY = 235;
        }

        $sigBoxX = 130;
        $sigBoxW = 60;
        $sigImgW = 45;
        $sigImgH = 18;

        if ($profile && $profile->signature_path && Storage::disk('public')->exists($profile->signature_path)) {
            $sigCenterX = $sigBoxX + ($sigBoxW - $sigImgW) / 2;
            try {
                $pdf->Image(Storage::disk('public')->path($profile->signature_path), $sigCenterX, $blockY, $sigImgW, $sigImgH);
            } catch (\Throwable $e) {
            }
        }

        if ($profile && $profile->stamp_path && Storage::disk('public')->exists($profile->stamp_path)) {
            $stampW = 32;
            $stampH = 32;
            $stampX = $sigBoxX - 10;
            $stampY = $blockY - 3;
            try {
                $pdf->Image(Storage::disk('public')->path($profile->stamp_path), $stampX, $stampY, $stampW, $stampH);
            } catch (\Throwable $e) {
            }
        }

        $lineY = $blockY + $sigImgH + 1;
        $pdf->SetXY($sigBoxX, $lineY);
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell($sigBoxW, 5, '..........................................................', 0, 2, 'C');

        $signatoryName = $profile?->authorized_signatory_name ?: '';
        if ($signatoryName) {
            $pdf->SetX($sigBoxX);
            $pdf->Cell($sigBoxW, 5, $this->txt('(' . $signatoryName . ')'), 0, 2, 'C');
        }
        $pdf->SetX($sigBoxX);
        $pdf->Cell($sigBoxW, 5, $this->txt('ผู้มีอำนาจลงนาม / Authorized Signatory'), 0, 2, 'C');
    }

    protected function renderWatermark(Fpdi $pdf, LaborBill $bill): void
    {
        if ($bill->status === 'void') {
            $pdf->SetFont('THSarabunNew', '', 60);
            $pdf->SetTextColor(220, 0, 0);
            $pdf->SetXY(40, 130);
            $pdf->Cell(130, 30, $this->txt('VOID — ยกเลิก'), 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }
    }

    protected function bahtText(float $amount): string
    {
        $units = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
        $nums = ['ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];

        $negative = $amount < 0;
        $amount = abs($amount);
        $baht = (int) floor($amount);
        $satang = (int) round(($amount - $baht) * 100);

        $bahtPart = $this->numberToThaiWords($baht, $nums, $units);
        $bahtPart = $bahtPart === '' ? 'ศูนย์' : $bahtPart;
        $result = $bahtPart . 'บาท';

        if ($satang > 0) {
            $result .= $this->numberToThaiWords($satang, $nums, $units) . 'สตางค์';
        } else {
            $result .= 'ถ้วน';
        }

        return ($negative ? 'ติดลบ ' : '') . $result;
    }

    protected function numberToThaiWords(int $n, array $nums, array $units): string
    {
        if ($n === 0) {
            return '';
        }
        if ($n >= 1000000) {
            $millions = intdiv($n, 1000000);
            $remainder = $n % 1000000;
            return $this->numberToThaiWords($millions, $nums, $units) . 'ล้าน' . $this->numberToThaiWords($remainder, $nums, $units);
        }

        $str = (string) $n;
        $len = strlen($str);
        $result = '';
        for ($i = 0; $i < $len; $i++) {
            $digit = (int) $str[$i];
            $position = $len - $i - 1;
            if ($digit === 0) {
                continue;
            }
            if ($position === 0 && $digit === 1 && $len > 1) {
                $result .= 'เอ็ด';
            } elseif ($position === 1 && $digit === 2) {
                $result .= 'ยี่' . $units[$position];
            } elseif ($position === 1 && $digit === 1) {
                $result .= $units[$position];
            } else {
                $result .= $nums[$digit] . $units[$position];
            }
        }
        return $result;
    }
}
