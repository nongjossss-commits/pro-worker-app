<?php

namespace App\Services;

use App\Models\LaborBillPayment;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use setasign\Fpdi\Fpdi;

/**
 * Generate PDF for a LaborBillPayment receipt (ใบเสร็จรับเงิน) — no existing
 * receipt PDF anywhere in the codebase to copy (see LaborBillPaymentService
 * docblock), so this follows TaxInvoicePdfService's visual conventions
 * (FPDF, THSarabunNew/CP874, header from FinancialProfile, baht-text total,
 * signature block) adapted for a payment receipt instead of a tax document.
 */
class LaborReceiptPdfService
{
    protected string $fontDir;
    protected bool $fontLoaded = false;

    public function __construct()
    {
        $this->fontDir = public_path('fonts');
    }

    public function generate(LaborBillPayment $payment): string
    {
        $payment->loadMissing('bill.team', 'bill.financialProfile', 'bankAccount', 'whtCertificate');

        $pdf = new Fpdi();
        $this->setupFont($pdf);
        $pdf->AddPage('P', 'A4');

        $headerBottom = $this->renderHeader($pdf, $payment);
        $this->renderParties($pdf, $payment, $headerBottom);
        $this->renderReceiptMeta($pdf, $payment, $headerBottom);
        $this->renderItemsTable($pdf, $payment, $headerBottom);
        $this->renderTotals($pdf, $payment);
        $this->renderSignature($pdf, $payment);

        return $pdf->Output('S');
    }

    public function generateAndStore(LaborBillPayment $payment): string
    {
        $binary = $this->generate($payment);
        $path = sprintf('labor_receipts/%04d/%s.pdf', $payment->paid_at->year, $payment->receipt_no);
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

    /**
     * Pick a starting font size for the company name based on length — a
     * coarse pre-shrink so normal names stay full size and only very long
     * ones (bilingual "ไทย/English" names etc.) start smaller before
     * MultiCell wraps whatever still doesn't fit on one line.
     */
    protected function nameFontSize(string $name): float
    {
        $len = mb_strlen($name);
        if ($len > 70) return 11;
        if ($len > 45) return 12;
        return 14;
    }

    /**
     * Render the header. Returns the Y (mm) where the header block ends —
     * every section below it must start from this value instead of a fixed
     * Y, because the company-name block (left column) can wrap to 2-3 lines
     * for long bilingual names and grow taller than the original fixed
     * layout assumed.
     */
    protected function renderHeader(Fpdi $pdf, LaborBillPayment $payment): float
    {
        $profile = $payment->bill->financialProfile;
        $logoY = 10;

        if ($profile && $profile->logo_path && Storage::disk('public')->exists($profile->logo_path)) {
            try {
                $pdf->Image(Storage::disk('public')->path($profile->logo_path), 12, $logoY, 18);
            } catch (\Throwable $e) {
            }
        }

        // Title block (right column) first — fixed/short height.
        $pdf->SetXY(140, $logoY);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(55, 7, $this->txt('ใบเสร็จรับเงิน'), 0, 2, 'R');
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->Cell(55, 4, 'RECEIPT', 0, 2, 'R');
        $rightBottomY = $pdf->GetY();

        // Company block (left column) capped to 100mm wide (33→133, 7mm
        // clear of the title at X=140). MultiCell wraps naturally instead of
        // overflowing horizontally on long names.
        $pdf->SetXY(33, $logoY);
        $pdf->SetFont('THSarabunNew', '', $this->nameFontSize($profile?->name ?: ''));
        $pdf->MultiCell(100, 5.5, $this->txt($profile?->name ?: ''), 0, 'L');

        $pdf->SetFont('THSarabunNew', '', 10);
        if ($profile?->address) {
            $oneLineAddress = preg_replace('/\s+/u', ' ', trim((string) $profile->address));
            $pdf->SetX(33);
            $pdf->MultiCell(100, 4, $this->txt($oneLineAddress), 0, 'L');
        }
        $metaParts = [];
        if ($profile?->phone)  $metaParts[] = 'โทร. ' . $profile->phone;
        if ($profile?->tax_id) $metaParts[] = 'เลขผู้เสียภาษี: ' . $profile->tax_id;
        if (!empty($metaParts)) {
            $pdf->SetX(33);
            $pdf->Cell(100, 4, $this->txt(implode('   ', $metaParts)), 0, 2, 'L');
        }
        $leftBottomY = $pdf->GetY();

        $headerBottom = max($leftBottomY, $rightBottomY + 2, 32);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(10, $headerBottom, 200, $headerBottom);

        return $headerBottom;
    }

    protected function renderParties(Fpdi $pdf, LaborBillPayment $payment, float $headerBottom): void
    {
        $team = $payment->bill->team;

        $pdf->SetXY(10, $headerBottom + 3);
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(120, 5, $this->txt('ได้รับเงินจาก / Received from'), 0, 2, 'L');

        $pdf->SetFont('THSarabunNew', '', 12);
        $pdf->Cell(120, 5, $this->txt($team->name ?? '-'), 0, 2, 'L');

        $pdf->SetFont('THSarabunNew', '', 10);
        if ($team?->customer_tax_id) {
            $pdf->Cell(120, 4, $this->txt('เลขประจำตัวผู้เสียภาษี: ' . $team->customer_tax_id), 0, 2, 'L');
        }
    }

    protected function renderReceiptMeta(Fpdi $pdf, LaborBillPayment $payment, float $headerBottom): void
    {
        $pdf->SetXY(135, $headerBottom + 3);
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->Cell(25, 5, $this->txt('เลขที่ / No.'), 0, 0, 'L');
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(40, 5, $this->txt($payment->receipt_no), 0, 2, 'L');

        $pdf->SetX(135);
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->Cell(25, 5, $this->txt('วันที่ / Date'), 0, 0, 'L');
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(40, 5, $this->txt($payment->paid_at->format('d/m/Y')), 0, 2, 'L');

        $pdf->SetX(135);
        $pdf->SetFont('THSarabunNew', '', 9);
        $pdf->Cell(65, 4, $this->txt('อ้างอิงใบวางบิล: ' . $payment->bill->bill_no), 0, 2, 'L');
    }

    protected function renderItemsTable(Fpdi $pdf, LaborBillPayment $payment, float $headerBottom): void
    {
        $y = $headerBottom + 33;
        $pdf->SetXY(10, $y);

        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(15, 7, $this->txt('ลำดับ'), 1, 0, 'C', true);
        $pdf->Cell(120, 7, $this->txt('รายการ / Description'), 1, 0, 'C', true);
        $pdf->Cell(55, 7, $this->txt('จำนวนเงิน (บาท)'), 1, 1, 'C', true);

        $methodLabel = match ($payment->payment_method) {
            'cash' => 'เงินสด',
            'transfer' => 'โอนเงิน' . ($payment->bankAccount ? ' (' . $payment->bankAccount->bank_name . ')' : ''),
            'promptpay' => 'พร้อมเพย์',
            default => 'อื่นๆ',
        };

        $pdf->SetFont('THSarabunNew', '', 11);
        $description = 'ชำระค่าบริการตามใบวางบิลเลขที่ ' . $payment->bill->bill_no . ' — ' . $methodLabel;
        $pdf->Cell(15, 7, '1', 1, 0, 'C');
        $pdf->Cell(120, 7, $this->txt($description), 1, 0, 'L');
        $pdf->Cell(55, 7, number_format((float) $payment->amount, 2), 1, 1, 'R');

        if ($payment->whtCertificate) {
            $cert = $payment->whtCertificate;
            $pdf->SetFont('THSarabunNew', '', 9);
            $note = 'หมายเหตุ: หัก ณ ที่จ่าย ' . number_format((float) $cert->wht_amount, 2)
                . ' บาท ตามใบรับรองเลขที่ ' . $cert->cert_no;
            $pdf->Cell(190, 5, $this->txt($note), 0, 1, 'L');
        }
    }

    protected function renderTotals(Fpdi $pdf, LaborBillPayment $payment): void
    {
        $pdf->SetFont('THSarabunNew', '', 13);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(150, 9, $this->txt('จำนวนเงินที่ได้รับ / Amount Received'), 1, 0, 'R', true);
        $pdf->Cell(40, 9, number_format((float) $payment->amount, 2), 1, 1, 'R', true);

        $pdf->SetFont('THSarabunNew', '', 12);
        $bahtText = $this->bahtText((float) $payment->amount);
        $pdf->Cell(190, 7, $this->txt('(' . $bahtText . ')'), 1, 1, 'C');
    }

    protected function renderSignature(Fpdi $pdf, LaborBillPayment $payment): void
    {
        $profile = $payment->bill->financialProfile;
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
        $pdf->Cell($sigBoxW, 5, $this->txt('ผู้รับเงิน / Received by'), 0, 2, 'C');
    }

    protected function bahtText(float $amount): string
    {
        $units = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
        $nums = ['ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];

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
        return $result;
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
