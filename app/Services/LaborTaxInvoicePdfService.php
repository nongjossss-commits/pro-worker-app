<?php

namespace App\Services;

use App\Models\LaborTaxInvoice;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use setasign\Fpdi\Fpdi;

/**
 * Generate PDF for LaborTaxInvoice — near-identical layout to
 * TaxInvoicePdfService (main app's ใบกำกับภาษี), adapted for the Pro Walker
 * Labor module. Kept as its own copy rather than reusing TaxInvoicePdfService
 * directly since this project's convention is copy-and-adapt per document
 * type (see LaborBillPdfService, WhtCertificatePdfService).
 */
class LaborTaxInvoicePdfService
{
    protected string $fontDir;
    protected bool $fontLoaded = false;

    public function __construct()
    {
        $this->fontDir = public_path('fonts');
    }

    public function generate(LaborTaxInvoice $invoice, string $copyLabel = 'original'): string
    {
        $invoice->loadMissing('issuerProfile');

        $pdf = new Fpdi();
        $this->setupFont($pdf);
        $pdf->AddPage('P', 'A4');

        $headerBottom = $this->renderHeader($pdf, $invoice, $copyLabel);
        $this->renderParties($pdf, $invoice, $headerBottom);
        $this->renderInvoiceMeta($pdf, $invoice, $headerBottom);
        $this->renderItemsTable($pdf, $invoice, $headerBottom);
        $this->renderTotals($pdf, $invoice);
        $this->renderPaymentInfo($pdf, $invoice);
        $this->renderSignature($pdf, $invoice);
        $this->renderWatermark($pdf, $invoice);

        return $pdf->Output('S');
    }

    public function generateAndStore(LaborTaxInvoice $invoice, string $copyLabel = 'original'): string
    {
        $binary = $this->generate($invoice, $copyLabel);
        $path = sprintf('labor_tax_invoices/%04d/%s.pdf', $invoice->fiscal_year, $invoice->invoice_no);
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
    protected function renderHeader(Fpdi $pdf, LaborTaxInvoice $invoice, string $copyLabel): float
    {
        $profile = $invoice->issuerProfile;
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
        $pdf->Cell(55, 7, $this->txt('ใบกำกับภาษี'), 0, 2, 'R');
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->Cell(55, 4, 'TAX INVOICE', 0, 2, 'R');

        $pdf->SetFont('THSarabunNew', '', 9);
        $labelTh = $copyLabel === 'copy' ? 'สำเนา (Copy)' : 'ต้นฉบับ (Original)';
        $pdf->Cell(55, 4, $this->txt($labelTh), 0, 2, 'R');
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

    protected function renderParties(Fpdi $pdf, LaborTaxInvoice $invoice, float $headerBottom): void
    {
        $pdf->SetXY(10, $headerBottom + 3);
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(120, 5, $this->txt('ลูกค้า / Customer'), 0, 2, 'L');

        $pdf->SetFont('THSarabunNew', '', 12);
        $pdf->Cell(120, 5, $this->txt($invoice->customer_name), 0, 2, 'L');

        $pdf->SetFont('THSarabunNew', '', 10);
        if ($invoice->customer_tax_id) {
            $line = 'เลขประจำตัวผู้เสียภาษี: ' . $invoice->customer_tax_id;
            if ($invoice->customer_branch) {
                $line .= '   สาขา: ' . $invoice->customer_branch;
            }
            $pdf->Cell(120, 4, $this->txt($line), 0, 2, 'L');
        }
        if ($invoice->customer_address) {
            $oneLine = preg_replace('/\s+/u', ' ', trim((string) $invoice->customer_address));
            $pdf->MultiCell(120, 4, $this->txt($oneLine), 0, 'L');
        }
    }

    protected function renderInvoiceMeta(Fpdi $pdf, LaborTaxInvoice $invoice, float $headerBottom): void
    {
        $pdf->SetXY(135, $headerBottom + 3);
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->Cell(25, 5, $this->txt('เลขที่ / No.'), 0, 0, 'L');
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(40, 5, $this->txt($invoice->invoice_no), 0, 2, 'L');

        $pdf->SetX(135);
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->Cell(25, 5, $this->txt('วันที่ / Date'), 0, 0, 'L');
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(40, 5, $this->txt(optional($invoice->invoice_date)->format('d/m/Y') ?: '-'), 0, 2, 'L');

        if ($invoice->bill) {
            $pdf->SetX(135);
            $pdf->SetFont('THSarabunNew', '', 9);
            $pdf->Cell(65, 4, $this->txt('อ้างอิงใบวางบิล: ' . $invoice->bill->bill_no), 0, 2, 'L');
        }
    }

    protected function renderItemsTable(Fpdi $pdf, LaborTaxInvoice $invoice, float $headerBottom): void
    {
        $y = $headerBottom + 38;
        $pdf->SetXY(10, $y);

        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(15, 7, $this->txt('ลำดับ'), 1, 0, 'C', true);
        $pdf->Cell(115, 7, $this->txt('รายการ / Description'), 1, 0, 'C', true);
        $pdf->Cell(20, 7, $this->txt('จำนวน'), 1, 0, 'C', true);
        $pdf->Cell(40, 7, $this->txt('จำนวนเงิน (บาท)'), 1, 1, 'C', true);

        $pdf->SetFont('THSarabunNew', '', 11);
        $description = $invoice->notes ?: ($invoice->bill
            ? 'ค่าบริการตามใบวางบิลเลขที่ ' . $invoice->bill->bill_no
                . ' งวด ' . $invoice->bill->period_start->format('d/m/Y') . '-' . $invoice->bill->period_end->format('d/m/Y')
            : 'ค่าบริการ');
        $pdf->Cell(15, 7, '1', 1, 0, 'C');
        $pdf->Cell(115, 7, $this->txt($description), 1, 0, 'L');
        $pdf->Cell(20, 7, '1', 1, 0, 'C');
        $pdf->Cell(40, 7, number_format((float) $invoice->subtotal, 2), 1, 1, 'R');
    }

    protected function renderTotals(Fpdi $pdf, LaborTaxInvoice $invoice): void
    {
        $pdf->SetFont('THSarabunNew', '', 12);

        $pdf->Cell(150, 7, $this->txt('รวมเป็นเงิน (Subtotal)'), 1, 0, 'R');
        $pdf->Cell(40, 7, number_format((float) $invoice->subtotal, 2), 1, 1, 'R');

        $vatLabel = 'ภาษีมูลค่าเพิ่ม ' . rtrim(rtrim((string) $invoice->vat_rate, '0'), '.') . '% (VAT)';
        $pdf->Cell(150, 7, $this->txt($vatLabel), 1, 0, 'R');
        $pdf->Cell(40, 7, number_format((float) $invoice->vat_amount, 2), 1, 1, 'R');

        $pdf->SetFont('THSarabunNew', '', 13);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(150, 9, $this->txt('จำนวนเงินรวมทั้งสิ้น (Grand Total)'), 1, 0, 'R', true);
        $pdf->Cell(40, 9, number_format((float) $invoice->total, 2), 1, 1, 'R', true);

        $pdf->SetFont('THSarabunNew', '', 12);
        $bahtText = $this->bahtText((float) $invoice->total);
        $pdf->Cell(190, 7, $this->txt('(' . $bahtText . ')'), 1, 1, 'C');
    }

    protected function renderPaymentInfo(Fpdi $pdf, LaborTaxInvoice $invoice): void
    {
        $methods = $invoice->payment_methods;
        if (!is_array($methods) || empty($methods)) {
            return;
        }

        $boxX = 10;
        $boxW = 115;
        $boxY = max(170, min($pdf->GetY() + 4, 200));

        $pdf->SetXY($boxX, $boxY);
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->Cell($boxW, 4, $this->txt('ช่องทางการชำระเงิน / Payment'), 0, 2, 'L');

        $pdf->SetFont('THSarabunNew', '', 10);
        foreach ($methods as $m) {
            $this->renderPaymentLine($pdf, $m, $boxX, $boxW);
        }
    }

    protected function renderPaymentLine(Fpdi $pdf, array $m, float $boxX, float $boxW): void
    {
        $type = $m['type'] ?? '';
        if ($type === '') {
            return;
        }

        $checkboxSize = 2.8;
        $textIndent   = 5.5;
        $lineH        = 4.2;
        $dots         = ' .................. ';

        $y = $pdf->GetY();

        $pdf->SetDrawColor(80, 80, 80);
        $pdf->Rect($boxX, $y + 1.0, $checkboxSize, $checkboxSize);

        switch ($type) {
            case 'cash':
                $pdf->SetXY($boxX + $textIndent, $y);
                $pdf->Cell($boxW - $textIndent, $lineH, $this->txt('ชำระเป็นเงินสด' . $dots . 'บาท'), 0, 2, 'L');
                break;

            case 'promptpay':
                $id = trim((string) ($m['promptpay_id'] ?? ''));
                $pdf->SetXY($boxX + $textIndent, $y);
                $pdf->Cell($boxW - $textIndent, $lineH, $this->txt('PromptPay: ' . ($id !== '' ? $id : '-') . $dots . 'บาท'), 0, 2, 'L');
                break;

            case 'transfer':
                $bank = trim((string) ($m['bank_name'] ?? ''));
                $name = trim((string) ($m['account_name'] ?? ''));
                $num  = trim((string) ($m['account_number'] ?? ''));
                $code = trim((string) ($m['bank_code'] ?? ''));

                $badge = $this->resolveBankBadge($code, $bank);
                $badgeX = $boxX + $textIndent;
                $badgeSize = 5.5;
                $rgb = $this->hexToRgbArray($badge['color']);
                $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
                $pdf->SetDrawColor($rgb[0], $rgb[1], $rgb[2]);
                $pdf->Rect($badgeX, $y + 0.2, $badgeSize, $badgeSize, 'F');

                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('THSarabunNew', '', 8);
                $pdf->SetXY($badgeX, $y + 0.4);
                $pdf->Cell($badgeSize, $badgeSize - 0.4, $this->txt($badge['initial']), 0, 0, 'C');

                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetDrawColor(80, 80, 80);
                $pdf->SetFont('THSarabunNew', '', 10);

                $textX = $badgeX + $badgeSize + 1.5;
                $pdf->SetXY($textX, $y);
                $bankLine = 'โอน ' . ($bank !== '' ? $bank : '-');
                if ($name !== '') {
                    $bankLine .= '  ·  ' . $name;
                }
                $pdf->Cell($boxW - ($textX - $boxX), $lineH, $this->txt($bankLine), 0, 2, 'L');

                if ($num !== '') {
                    $pdf->SetX($textX);
                    $pdf->Cell($boxW - ($textX - $boxX), $lineH, $this->txt('เลขที่บัญชี: ' . $num), 0, 2, 'L');
                }
                break;

            case 'other':
                $note = trim((string) ($m['note'] ?? ''));
                $pdf->SetXY($boxX + $textIndent, $y);
                $pdf->Cell($boxW - $textIndent, $lineH, $this->txt('อื่นๆ: ' . ($note !== '' ? $note : '-') . $dots . 'บาท'), 0, 2, 'L');
                break;

            default:
                $pdf->SetXY($boxX, $y + $lineH);
                return;
        }
    }

    protected function resolveBankBadge(string $code, string $bankName): array
    {
        $presets = config('thai_banks', []);
        if ($code !== '') {
            foreach ($presets as $preset) {
                if (($preset['code'] ?? null) === $code) {
                    return [
                        'color'   => $preset['color']   ?? '#6B7280',
                        'initial' => $preset['initial'] ?? mb_substr($bankName ?: '?', 0, 1),
                    ];
                }
            }
        }
        return [
            'color'   => '#6B7280',
            'initial' => mb_substr($bankName ?: '?', 0, 1),
        ];
    }

    protected function hexToRgbArray(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return [107, 114, 128];
        }
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    protected function renderSignature(Fpdi $pdf, LaborTaxInvoice $invoice): void
    {
        $profile = $invoice->issuerProfile;
        $blockY = $pdf->GetY() + 10;
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

    protected function renderWatermark(Fpdi $pdf, LaborTaxInvoice $invoice): void
    {
        if ($invoice->status === 'void') {
            $pdf->SetFont('THSarabunNew', '', 60);
            $pdf->SetTextColor(220, 0, 0);
            $pdf->SetXY(40, 130);
            $pdf->Cell(130, 30, $this->txt('VOID — ยกเลิก'), 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        } elseif ($invoice->status === 'draft') {
            $pdf->SetFont('THSarabunNew', '', 48);
            $pdf->SetTextColor(180, 180, 180);
            $pdf->SetXY(40, 130);
            $pdf->Cell(130, 30, 'DRAFT', 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }
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
