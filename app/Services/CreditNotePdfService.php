<?php

namespace App\Services;

use App\Models\CreditNote;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use setasign\Fpdi\Fpdi;

/**
 * Generate PDF for CreditNote — same Thai standard layout engine as
 * TaxInvoicePdfService (FPDF, THSarabunNew/CP874, issuer logo/signature/
 * stamp from FinancialProfile), simplified for a credit note's shape: no
 * item quantities, no payment-method block — instead shows the bill being
 * reduced and the required reason.
 */
class CreditNotePdfService
{
    protected string $fontDir;
    protected bool $fontLoaded = false;

    public function __construct()
    {
        $this->fontDir = public_path('fonts');
    }

    public function generate(CreditNote $note, string $copyLabel = 'original'): string
    {
        $note->loadMissing('issuerProfile', 'relatedTaxInvoice');

        $pdf = new Fpdi();
        $this->setupFont($pdf);
        $pdf->AddPage('P', 'A4');

        $this->renderHeader($pdf, $note, $copyLabel);
        $this->renderParties($pdf, $note);
        $this->renderNoteMeta($pdf, $note);
        $this->renderReason($pdf, $note);
        $this->renderTotals($pdf, $note);
        $this->renderSignature($pdf, $note);
        $this->renderWatermark($pdf, $note);

        return $pdf->Output('S');
    }

    /**
     * Generate and persist PDF to storage — same path convention as
     * TaxInvoicePdfService::generateAndStore(), consumed by
     * MonthlyExportService::addAttachments().
     */
    public function generateAndStore(CreditNote $note, string $copyLabel = 'original'): string
    {
        $binary = $this->generate($note, $copyLabel);
        $path = sprintf('credit_notes/%04d/%s.pdf', $note->fiscal_year, $note->credit_note_no);
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

    protected function renderHeader(Fpdi $pdf, CreditNote $note, string $copyLabel): void
    {
        $profile = $note->issuerProfile;
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
        $pdf->Cell(55, 7, $this->txt('ใบลดหนี้'), 0, 2, 'R');
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->Cell(55, 4, 'CREDIT NOTE', 0, 2, 'R');

        $pdf->SetFont('THSarabunNew', '', 9);
        $labelTh = $copyLabel === 'copy' ? 'สำเนา (Copy)' : 'ต้นฉบับ (Original)';
        $pdf->Cell(55, 4, $this->txt($labelTh), 0, 2, 'R');

        $pdf->SetLineWidth(0.3);
        $pdf->Line(10, 32, 200, 32);
    }

    protected function renderParties(Fpdi $pdf, CreditNote $note): void
    {
        $pdf->SetXY(10, 35);
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(120, 5, $this->txt('ลูกค้า / Customer'), 0, 2, 'L');

        $pdf->SetFont('THSarabunNew', '', 12);
        $pdf->Cell(120, 5, $this->txt($note->customer_name), 0, 2, 'L');

        $pdf->SetFont('THSarabunNew', '', 10);
        if ($note->customer_tax_id) {
            $line = 'เลขประจำตัวผู้เสียภาษี: ' . $note->customer_tax_id;
            if ($note->customer_branch) {
                $line .= '   สาขา: ' . $note->customer_branch;
            }
            $pdf->Cell(120, 4, $this->txt($line), 0, 2, 'L');
        }
        if ($note->customer_address) {
            $oneLine = preg_replace('/\s+/u', ' ', trim((string) $note->customer_address));
            $pdf->MultiCell(120, 4, $this->txt($oneLine), 0, 'L');
        }
    }

    protected function renderNoteMeta(Fpdi $pdf, CreditNote $note): void
    {
        $pdf->SetXY(135, 35);
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->Cell(25, 5, $this->txt('เลขที่ / No.'), 0, 0, 'L');
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(40, 5, $this->txt($note->credit_note_no), 0, 2, 'L');

        $pdf->SetX(135);
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->Cell(25, 5, $this->txt('วันที่ / Date'), 0, 0, 'L');
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(40, 5, $this->txt(optional($note->credit_note_date)->format('d/m/Y') ?: '-'), 0, 2, 'L');

        $pdf->SetX(135);
        $pdf->SetFont('THSarabunNew', '', 10);
        $pdf->Cell(25, 5, $this->txt('ลดยอดบิล'), 0, 0, 'L');
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(40, 5, $this->txt('#' . $note->financial_transaction_id), 0, 2, 'L');

        if ($note->relatedTaxInvoice) {
            $pdf->SetX(135);
            $pdf->SetFont('THSarabunNew', '', 10);
            $pdf->Cell(25, 5, $this->txt('อ้างอิงใบกำกับ'), 0, 0, 'L');
            $pdf->SetFont('THSarabunNew', '', 11);
            $pdf->Cell(40, 5, $this->txt($note->relatedTaxInvoice->invoice_no), 0, 2, 'L');
        }
    }

    protected function renderReason(Fpdi $pdf, CreditNote $note): void
    {
        $y = 70;
        $pdf->SetXY(10, $y);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(190, 7, $this->txt('เหตุผลการลดหนี้ / Reason'), 1, 1, 'L', true);

        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->SetX(10);
        $pdf->MultiCell(190, 6, $this->txt($note->reason), 1, 'L');
    }

    protected function renderTotals(Fpdi $pdf, CreditNote $note): void
    {
        $y = max($pdf->GetY() + 5, 100);
        $pdf->SetXY(10, $y);
        $pdf->SetFont('THSarabunNew', '', 12);

        $pdf->Cell(150, 7, $this->txt('มูลค่าก่อนภาษี (Subtotal)'), 1, 0, 'R');
        $pdf->Cell(40, 7, number_format($note->subtotal, 2), 1, 1, 'R');

        $vatLabel = 'ภาษีมูลค่าเพิ่ม ' . rtrim(rtrim((string) $note->vat_rate, '0'), '.') . '% (VAT)';
        $pdf->SetX(10);
        $pdf->Cell(150, 7, $this->txt($vatLabel), 1, 0, 'R');
        $pdf->Cell(40, 7, number_format($note->vat_amount, 2), 1, 1, 'R');

        $pdf->SetX(10);
        $pdf->SetFont('THSarabunNew', '', 13);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(150, 9, $this->txt('จำนวนเงินลดหนี้รวมทั้งสิ้น (Total Credit)'), 1, 0, 'R', true);
        $pdf->Cell(40, 9, number_format($note->total_credit, 2), 1, 1, 'R', true);

        $pdf->SetX(10);
        $pdf->SetFont('THSarabunNew', '', 12);
        $bahtText = $this->bahtText((float) $note->total_credit);
        $pdf->Cell(190, 7, $this->txt('(' . $bahtText . ')'), 1, 1, 'C');
    }

    protected function renderSignature(Fpdi $pdf, CreditNote $note): void
    {
        $profile = $note->issuerProfile;
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
                // bad image — skip
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

    protected function renderWatermark(Fpdi $pdf, CreditNote $note): void
    {
        if ($note->status === 'void') {
            $pdf->SetFont('THSarabunNew', '', 60);
            $pdf->SetTextColor(220, 0, 0);
            $pdf->SetXY(40, 130);
            $pdf->Cell(130, 30, $this->txt('VOID — ยกเลิก'), 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        } elseif ($note->status === 'draft') {
            $pdf->SetFont('THSarabunNew', '', 48);
            $pdf->SetTextColor(180, 180, 180);
            $pdf->SetXY(40, 130);
            $pdf->Cell(130, 30, 'DRAFT', 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }
    }

    /**
     * Convert numeric amount to Thai-baht text — identical algorithm to
     * TaxInvoicePdfService::bahtText()/numberToThaiWords().
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
