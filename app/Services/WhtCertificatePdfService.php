<?php

namespace App\Services;

use App\Models\WhtCertificate;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use setasign\Fpdi\Fpdi;

/**
 * Generate PDF for WhtCertificate — Thai standard ภ.ง.ด.3 / ภ.ง.ด.53 layout.
 *
 * - FPDF-based fixed layout
 * - THSarabunNew + CP874 encoding
 * - Renders signature/stamp from FinancialProfile based on source bank account
 */
class WhtCertificatePdfService
{
    protected string $fontDir;
    protected bool $fontLoaded = false;

    public function __construct()
    {
        $this->fontDir = public_path('fonts');
    }

    public function generate(WhtCertificate $cert): string
    {
        $pdf = new Fpdi();
        $this->setupFont($pdf);
        $pdf->AddPage('P', 'A4');

        $this->renderHeader($pdf, $cert);
        $this->renderParties($pdf, $cert);
        $this->renderIncomeTable($pdf, $cert);
        $this->renderFooter($pdf, $cert);
        $this->renderWatermark($pdf, $cert);

        return $pdf->Output('S');
    }

    public function generateAndStore(WhtCertificate $cert): string
    {
        $binary = $this->generate($cert);
        $path = sprintf(
            'wht_certificates/%04d/%02d/%s.pdf',
            $cert->tax_period_year,
            $cert->tax_period_month,
            $cert->cert_no
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

    protected function renderHeader(Fpdi $pdf, WhtCertificate $cert): void
    {
        // Title
        $pdf->SetXY(10, 12);
        $pdf->SetFont('THSarabunNew', '', 18);
        $pdf->Cell(190, 8, $this->txt('หนังสือรับรองการหักภาษี ณ ที่จ่าย'), 0, 2, 'C');
        $pdf->SetFont('THSarabunNew', '', 12);
        $pdf->Cell(190, 5, 'Withholding Tax Certificate', 0, 2, 'C');

        $formLabel = strtoupper($cert->wht_type);
        $formLabelTh = $cert->wht_type === 'pnd3' ? 'ภ.ง.ด.3' : 'ภ.ง.ด.53';
        $pdf->SetFont('THSarabunNew', '', 13);
        $pdf->Cell(190, 6, $this->txt('ตามมาตรา 50 ทวิ แห่งประมวลรัษฎากร — แบบ ' . $formLabelTh), 0, 2, 'C');

        // Cert number + period
        $pdf->Ln(2);
        $pdf->SetFont('THSarabunNew', '', 11);
        $pdf->Cell(100, 5, $this->txt('เลขที่ / No. ' . $cert->cert_no), 0, 0, 'L');
        $period = sprintf('%02d/%04d', $cert->tax_period_month, $cert->tax_period_year);
        $pdf->Cell(90, 5, $this->txt('เดือนภาษี / Tax period: ' . $period), 0, 1, 'R');

        $direction = $cert->type === 'issued'
            ? 'ออกโดยผู้มีหน้าที่หัก (Issued)'
            : 'ได้รับจากผู้จ่าย (Received)';
        $pdf->Cell(0, 5, $this->txt('ประเภท: ' . $direction), 0, 1, 'L');

        $pdf->SetLineWidth(0.3);
        $pdf->Line(10, $pdf->GetY() + 1, 200, $pdf->GetY() + 1);
    }

    protected function renderParties(Fpdi $pdf, WhtCertificate $cert): void
    {
        $pdf->SetY($pdf->GetY() + 5);

        // Payer
        $pdf->SetFont('THSarabunNew', '', 13);
        $pdf->Cell(0, 7, $this->txt('ผู้จ่ายเงิน (Payer)'), 0, 1, 'L');
        $pdf->SetFont('THSarabunNew', '', 12);
        $pdf->Cell(0, 6, $this->txt('ชื่อ: ' . $cert->payer_name), 0, 1, 'L');
        if ($cert->payer_tax_id) {
            $pdf->Cell(0, 6, $this->txt('เลขประจำตัวผู้เสียภาษี: ' . $cert->payer_tax_id), 0, 1, 'L');
        }

        $pdf->Ln(2);

        // Payee
        $pdf->SetFont('THSarabunNew', '', 13);
        $pdf->Cell(0, 7, $this->txt('ผู้รับเงิน (Payee)'), 0, 1, 'L');
        $pdf->SetFont('THSarabunNew', '', 12);
        $pdf->Cell(0, 6, $this->txt('ชื่อ: ' . $cert->payee_name), 0, 1, 'L');
        if ($cert->payee_tax_id) {
            $pdf->Cell(0, 6, $this->txt('เลขประจำตัวผู้เสียภาษี: ' . $cert->payee_tax_id), 0, 1, 'L');
        }
    }

    protected function renderIncomeTable(Fpdi $pdf, WhtCertificate $cert): void
    {
        $pdf->Ln(4);
        $pdf->SetFont('THSarabunNew', '', 12);
        $pdf->Cell(0, 6, $this->txt('รายการเงินได้และภาษีหัก ณ ที่จ่าย'), 0, 1, 'L');

        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont('THSarabunNew', '', 12);
        $pdf->Cell(80, 8, $this->txt('ประเภทเงินได้'), 1, 0, 'C', true);
        $pdf->Cell(35, 8, $this->txt('วันที่จ่ายเงิน'), 1, 0, 'C', true);
        $pdf->Cell(35, 8, $this->txt('จำนวนเงินที่จ่าย'), 1, 0, 'C', true);
        $pdf->Cell(20, 8, $this->txt('อัตรา %'), 1, 0, 'C', true);
        $pdf->Cell(20, 8, $this->txt('ภาษีที่หัก'), 1, 1, 'C', true);

        $incomeLabel = $this->humanizeIncomeType($cert->income_type);

        $pdf->Cell(80, 9, $this->txt($incomeLabel), 1, 0, 'L');
        $pdf->Cell(35, 9, optional($cert->paid_at)->format('d/m/Y') ?: '-', 1, 0, 'C');
        $pdf->Cell(35, 9, number_format($cert->amount_paid, 2), 1, 0, 'R');
        $pdf->Cell(20, 9, rtrim(rtrim((string) $cert->wht_rate, '0'), '.'), 1, 0, 'C');
        $pdf->Cell(20, 9, number_format($cert->wht_amount, 2), 1, 1, 'R');

        // Total row
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('THSarabunNew', '', 13);
        $pdf->Cell(150, 9, $this->txt('รวมภาษีที่หัก ณ ที่จ่าย (Total WHT)'), 1, 0, 'R', true);
        $pdf->Cell(40, 9, number_format($cert->wht_amount, 2), 1, 1, 'R', true);
    }

    protected function renderFooter(Fpdi $pdf, WhtCertificate $cert): void
    {
        $pdf->Ln(8);

        // Signature line — issuer side (เราออกใบ = our company signs; ลูกค้าออกให้เรา = customer signs)
        $pdf->SetFont('THSarabunNew', '', 11);
        $signerLabel = $cert->type === 'issued'
            ? 'ผู้มีหน้าที่หักภาษี ณ ที่จ่าย (Withholder)'
            : 'ผู้มีหน้าที่หักภาษี ณ ที่จ่าย'; // payer is the withholder regardless

        $x = 120;
        $pdf->SetXY($x, $pdf->GetY() + 10);
        $pdf->Cell(70, 5, '..........................................................', 0, 2, 'C');
        $pdf->SetX($x);
        $pdf->Cell(70, 5, $this->txt('(................................................)'), 0, 2, 'C');
        $pdf->SetX($x);
        $pdf->Cell(70, 5, $this->txt($signerLabel), 0, 2, 'C');
        $pdf->SetX($x);
        $pdf->Cell(70, 5, $this->txt('วันที่ ........./.........../............'), 0, 2, 'C');
    }

    protected function renderWatermark(Fpdi $pdf, WhtCertificate $cert): void
    {
        if ($cert->status === 'draft') {
            $pdf->SetFont('THSarabunNew', '', 48);
            $pdf->SetTextColor(180, 180, 180);
            $pdf->SetXY(40, 130);
            $pdf->Cell(130, 30, 'DRAFT', 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }
    }

    protected function humanizeIncomeType(?string $type): string
    {
        return match ($type) {
            'service' => 'ค่าบริการ / Service',
            'rent' => 'ค่าเช่า / Rent',
            'advertising' => 'ค่าโฆษณา / Advertising',
            'transport' => 'ค่าขนส่ง / Transport',
            'contract' => 'รับจ้างทำของ / Contract',
            'other' => 'อื่นๆ / Other',
            default => '-',
        };
    }
}
