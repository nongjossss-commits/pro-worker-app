<?php

namespace App\Services;

use App\Models\TaxInvoice;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use setasign\Fpdi\Fpdi;

/**
 * Generate PDF for TaxInvoice — Thai standard layout.
 *
 * - FPDF-based (no template import) — layout fixed per Thai tax law
 * - Uses THSarabunNew font with CP874 encoding (same pattern as PdfGeneratorService)
 * - Renders issuer logo, signature, stamp from FinancialProfile
 * - Output: raw PDF binary string (stream from controller)
 */
class TaxInvoicePdfService
{
    protected string $fontDir;
    protected bool $fontLoaded = false;

    public function __construct()
    {
        $this->fontDir = public_path('fonts');
    }

    /**
     * Generate PDF binary for the given invoice.
     * Copy label: 'original' or 'copy' (printed top-right corner)
     */
    public function generate(TaxInvoice $invoice, string $copyLabel = 'original'): string
    {
        $invoice->loadMissing('issuerProfile');

        $pdf = new Fpdi();
        $this->setupFont($pdf);
        $pdf->AddPage('P', 'A4');

        $this->renderHeader($pdf, $invoice, $copyLabel);
        $this->renderParties($pdf, $invoice);
        $this->renderInvoiceMeta($pdf, $invoice);
        $this->renderItemsTable($pdf, $invoice);
        $this->renderTotals($pdf, $invoice);
        $this->renderSignature($pdf, $invoice);
        $this->renderWatermark($pdf, $invoice);

        return $pdf->Output('S');
    }

    /**
     * Generate and persist PDF to storage. Returns the relative path under
     * the 'public' disk, suitable for storing in `tax_invoices.notes` or
     * a future `pdf_path` column.
     */
    public function generateAndStore(TaxInvoice $invoice, string $copyLabel = 'original'): string
    {
        $binary = $this->generate($invoice, $copyLabel);
        $path = sprintf(
            'tax_invoices/%04d/%s.pdf',
            $invoice->fiscal_year,
            $invoice->invoice_no
        );
        Storage::disk('public')->put($path, $binary);
        return $path;
    }

    // ---------- Layout helpers ----------

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

    protected function renderHeader(Fpdi $pdf, TaxInvoice $invoice, string $copyLabel): void
    {
        $profile = $invoice->issuerProfile;
        $logoY = 10;

        // Issuer logo (top-left)
        if ($profile && $profile->logo_path && Storage::disk('public')->exists($profile->logo_path)) {
            try {
                $pdf->Image(Storage::disk('public')->path($profile->logo_path), 12, $logoY, 25);
            } catch (\Throwable $e) {
                // ignore — bad image format etc.
            }
        }

        // Issuer block (top-left, beside logo)
        $pdf->SetXY(40, $logoY);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(120, 7, $this->txt($profile?->name ?: ''), 0, 2, 'L');
        $pdf->SetFont('THSarabunNew', '', 12);
        if ($profile?->address) {
            $pdf->Cell(120, 5, $this->txt($profile->address), 0, 2, 'L');
        }
        if ($profile?->phone) {
            $pdf->Cell(120, 5, $this->txt('โทร. ' . $profile->phone), 0, 2, 'L');
        }
        if ($profile?->tax_id) {
            $pdf->Cell(120, 5, $this->txt('เลขประจำตัวผู้เสียภาษี: ' . $profile->tax_id), 0, 2, 'L');
        }

        // Title (top-right)
        $pdf->SetXY(140, $logoY);
        $pdf->SetFont('THSarabunNew', '', 18);
        $pdf->Cell(55, 8, $this->txt('ใบกำกับภาษี'), 0, 2, 'R');
        $pdf->SetFont('THSarabunNew', '', 12);
        $pdf->Cell(55, 5, 'TAX INVOICE', 0, 2, 'R');

        // Copy label (ต้นฉบับ / สำเนา)
        $pdf->SetFont('THSarabunNew', '', 11);
        $labelTh = $copyLabel === 'copy' ? 'สำเนา (Copy)' : 'ต้นฉบับ (Original)';
        $pdf->Cell(55, 5, $this->txt($labelTh), 0, 2, 'R');

        // Divider line
        $pdf->SetLineWidth(0.3);
        $pdf->Line(10, 40, 200, 40);
    }

    protected function renderParties(Fpdi $pdf, TaxInvoice $invoice): void
    {
        $pdf->SetXY(10, 44);
        $pdf->SetFont('THSarabunNew', '', 12);
        $pdf->Cell(190, 6, $this->txt('ลูกค้า / Customer'), 0, 2, 'L');

        $pdf->SetFont('THSarabunNew', '', 13);
        $pdf->Cell(0, 6, $this->txt($invoice->customer_name), 0, 2, 'L');

        $pdf->SetFont('THSarabunNew', '', 11);
        if ($invoice->customer_tax_id) {
            $line = 'เลขประจำตัวผู้เสียภาษี: ' . $invoice->customer_tax_id;
            if ($invoice->customer_branch) {
                $line .= '   สาขา: ' . $invoice->customer_branch;
            }
            $pdf->Cell(0, 5, $this->txt($line), 0, 2, 'L');
        }
        if ($invoice->customer_address) {
            $pdf->MultiCell(120, 5, $this->txt($invoice->customer_address), 0, 'L');
        }
    }

    protected function renderInvoiceMeta(Fpdi $pdf, TaxInvoice $invoice): void
    {
        // Top-right meta block (over Customer block)
        $pdf->SetXY(135, 44);
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(30, 6, $this->txt('เลขที่ / No.'), 0, 0, 'L');
        $pdf->SetFont('THSarabunNew', '', 13);
        $pdf->Cell(35, 6, $this->txt($invoice->invoice_no), 0, 2, 'L');

        $pdf->SetX(135);
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(30, 6, $this->txt('วันที่ / Date'), 0, 0, 'L');
        $pdf->SetFont('THSarabunNew', '', 13);
        $pdf->Cell(35, 6, $this->txt(optional($invoice->invoice_date)->format('d/m/Y') ?: '-'), 0, 2, 'L');
    }

    protected function renderItemsTable(Fpdi $pdf, TaxInvoice $invoice): void
    {
        $y = 90;
        $pdf->SetXY(10, $y);

        // Header row
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont('THSarabunNew', '', 12);
        $pdf->Cell(15, 8, $this->txt('ลำดับ'), 1, 0, 'C', true);
        $pdf->Cell(115, 8, $this->txt('รายการ / Description'), 1, 0, 'C', true);
        $pdf->Cell(20, 8, $this->txt('จำนวน'), 1, 0, 'C', true);
        $pdf->Cell(40, 8, $this->txt('จำนวนเงิน (บาท)'), 1, 1, 'C', true);

        // Body row — single line of subtotal (extend in future for line items)
        $pdf->SetFont('THSarabunNew', '', 12);
        $description = $invoice->notes ?: 'ค่าบริการ';
        $pdf->Cell(15, 8, '1', 1, 0, 'C');
        $pdf->Cell(115, 8, $this->txt($description), 1, 0, 'L');
        $pdf->Cell(20, 8, '1', 1, 0, 'C');
        $pdf->Cell(40, 8, number_format($invoice->subtotal, 2), 1, 1, 'R');

        // Empty filler rows for visual balance
        for ($i = 0; $i < 5; $i++) {
            $pdf->Cell(15, 7, '', 1, 0, 'C');
            $pdf->Cell(115, 7, '', 1, 0, 'L');
            $pdf->Cell(20, 7, '', 1, 0, 'C');
            $pdf->Cell(40, 7, '', 1, 1, 'R');
        }
    }

    protected function renderTotals(Fpdi $pdf, TaxInvoice $invoice): void
    {
        $pdf->SetFont('THSarabunNew', '', 12);

        $pdf->Cell(150, 7, $this->txt('รวมเป็นเงิน (Subtotal)'), 1, 0, 'R');
        $pdf->Cell(40, 7, number_format($invoice->subtotal, 2), 1, 1, 'R');

        $vatLabel = 'ภาษีมูลค่าเพิ่ม ' . rtrim(rtrim((string) $invoice->vat_rate, '0'), '.') . '% (VAT)';
        $pdf->Cell(150, 7, $this->txt($vatLabel), 1, 0, 'R');
        $pdf->Cell(40, 7, number_format($invoice->vat_amount, 2), 1, 1, 'R');

        $pdf->SetFont('THSarabunNew', '', 13);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(150, 9, $this->txt('จำนวนเงินรวมทั้งสิ้น (Grand Total)'), 1, 0, 'R', true);
        $pdf->Cell(40, 9, number_format($invoice->total, 2), 1, 1, 'R', true);

        // Amount in Thai words
        $pdf->SetFont('THSarabunNew', '', 12);
        $bahtText = $this->bahtText((float) $invoice->total);
        $pdf->Cell(190, 7, $this->txt('(' . $bahtText . ')'), 1, 1, 'C');
    }

    protected function renderSignature(Fpdi $pdf, TaxInvoice $invoice): void
    {
        // Fixed layout — ignore FinancialProfile.signature_position / stamp_position
        // (those JSON values are stored as pixels of a different PdfTemplate canvas,
        // not millimetres of this layout.)
        $profile = $invoice->issuerProfile;
        $blockY = $pdf->GetY() + 10;
        if ($blockY > 235) {
            $blockY = 235;
        }

        // Signature image (above the line)
        $sigBoxX = 130;        // right column
        $sigBoxW = 60;         // box width
        $sigImgW = 45;
        $sigImgH = 18;

        if ($profile && $profile->signature_path && Storage::disk('public')->exists($profile->signature_path)) {
            $sigCenterX = $sigBoxX + ($sigBoxW - $sigImgW) / 2;
            try {
                $pdf->Image(
                    Storage::disk('public')->path($profile->signature_path),
                    $sigCenterX,
                    $blockY,
                    $sigImgW,
                    $sigImgH
                );
            } catch (\Throwable $e) {
                // bad image — skip
            }
        }

        // Stamp partially overlapping signature on its left
        if ($profile && $profile->stamp_path && Storage::disk('public')->exists($profile->stamp_path)) {
            $stampW = 32;
            $stampH = 32;
            $stampX = $sigBoxX - 10;
            $stampY = $blockY - 3;
            try {
                $pdf->Image(
                    Storage::disk('public')->path($profile->stamp_path),
                    $stampX,
                    $stampY,
                    $stampW,
                    $stampH
                );
            } catch (\Throwable $e) {
            }
        }

        // Signature line + caption below the image
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

    protected function renderWatermark(Fpdi $pdf, TaxInvoice $invoice): void
    {
        if ($invoice->status === 'void' || $invoice->status === 'cancelled') {
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

    /**
     * Convert numeric amount to Thai-baht text (e.g. "หนึ่งพันบาทถ้วน").
     */
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
        // Handle millions recursively
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
            // Special cases per Thai numbering rules
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
