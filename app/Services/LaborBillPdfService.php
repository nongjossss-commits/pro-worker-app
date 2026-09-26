<?php

namespace App\Services;

use App\Models\LaborBill;
use App\Services\Pdf\LaborBillPdfDocument;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use setasign\Fpdi\Fpdi;

/**
 * Generate PDF for a LaborBill (FPDF, THSarabunNew/CP874, A4) — a designed
 * billing statement: brand accent bar and colours (Super Admin → Branding),
 * company header, "bill to" box, three summary figures, a zebra-striped item
 * table that continues onto extra pages with its header repeated, totals with
 * the amount in words, and two signature boxes. No VAT: this is an internal
 * statement, not a tax document. Items are the period's ledger entries plus a
 * "ยอดยกมา" (carried forward) line. See Pdf\LaborBillPdfDocument.
 */
class LaborBillPdfService
{
    protected string $fontDir;
    protected bool $fontLoaded = false;

    // Layout (A4, mm)
    protected const LEFT = 12;
    protected const RIGHT = 198;
    protected const WIDTH = 186;
    protected const BOTTOM_LIMIT = 281; // content must end above the footer (line at 284)

    /** Brand accent as [r, g, b] (Super Admin → Branding). */
    protected array $accent = [249, 115, 22];

    public function __construct()
    {
        $this->fontDir = public_path('fonts');
    }

    public function generate(LaborBill $bill): string
    {
        $bill->loadMissing('team', 'financialProfile');
        $entries = $bill->team->ledgerEntries()
            ->with(['member', 'chargeType'])
            ->whereBetween('entry_date', [$bill->period_start->toDateString(), $bill->period_end->toDateString()])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        try {
            $this->accent = $this->rgb(BrandService::current()['primary_color'] ?? '#F97316');
        } catch (\Throwable $e) {
            // keep the default accent
        }

        $pdf = new LaborBillPdfDocument('P', 'mm', 'A4');
        $pdf->SetMargins(self::LEFT, 12, 210 - self::RIGHT);
        $pdf->SetAutoPageBreak(false);
        $pdf->AliasNbPages();
        $this->setupFont($pdf);
        $pdf->fontFamily = $this->fontLoaded ? 'THSarabunNew' : 'Arial';
        $pdf->pageLabel = $this->txt('หน้า');
        $pdf->footerLeft = $this->txt('ใบวางบิลเลขที่ ' . $bill->bill_no . '  ·  ' . ($bill->team->name ?? '-'));

        $this->newPage($pdf, $bill);
        $y = $this->renderHeader($pdf, $bill);
        $y = $this->renderBillTo($pdf, $bill, $y + 5);
        $y = $this->renderSummaryTiles($pdf, $bill, $y + 4);
        $y = $this->renderItemsTable($pdf, $bill, $entries, $y + 6);
        $y = $this->renderTotals($pdf, $bill, $y + 4);
        $this->renderSignature($pdf, $bill, $y + 5);

        return $pdf->Output('S');
    }

    public function generateAndStore(LaborBill $bill): string
    {
        $binary = $this->generate($bill);
        $path = sprintf('labor_bills/%04d/%s.pdf', $bill->period_end->year, $bill->bill_no);
        Storage::disk('public')->put($path, $binary);
        return $path;
    }

    // ---------- Font / text helpers ----------

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

    protected function font(LaborBillPdfDocument $pdf, float $size): void
    {
        $pdf->SetFont($pdf->fontFamily, '', $size);
    }

    protected function rgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            return [249, 115, 22];
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /** The accent mixed with white — for soft backgrounds. */
    protected function tint(float $amount): array
    {
        return array_map(fn ($c) => (int) round($c + (255 - $c) * $amount), $this->accent);
    }

    protected function money(float $amount): string
    {
        return number_format($amount, 2);
    }

    /**
     * Pick a starting font size for the company name based on length — a
     * coarse pre-shrink so normal names stay at full size and only very
     * long ones (bilingual "ไทย/English" names etc.) start smaller before
     * MultiCell wraps whatever still doesn't fit on one line.
     */
    protected function nameFontSize(string $name): float
    {
        $len = mb_strlen($name);
        if ($len > 70) return 13;
        if ($len > 45) return 15;
        return 18;
    }

    // ---------- Page furniture ----------

    /** New page: brand bar across the top + VOID watermark (drawn under the content). */
    protected function newPage(LaborBillPdfDocument $pdf, LaborBill $bill): void
    {
        $pdf->AddPage('P', 'A4');
        $pdf->SetFillColor(...$this->accent);
        $pdf->Rect(0, 0, 210, 3.5, 'F');

        if ($bill->status === 'void') {
            $this->font($pdf, 96);
            $pdf->SetTextColor(254, 226, 226);
            $pdf->SetXY(0, 125);
            $pdf->Cell(210, 40, 'VOID', 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }
    }

    /** Small header for continuation pages; returns the Y to continue from. */
    protected function continuationHeader(LaborBillPdfDocument $pdf, LaborBill $bill): float
    {
        $this->newPage($pdf, $bill);
        $profile = $bill->financialProfile;
        $this->font($pdf, 12);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetXY(self::LEFT, 9);
        $pdf->Cell(120, 6, $this->txt($profile?->name ?: ''), 0, 0, 'L');
        $pdf->Cell(66, 6, $this->txt('ใบวางบิล ' . $bill->bill_no . ' (ต่อ)'), 0, 0, 'R');
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Line(self::LEFT, 16, self::RIGHT, 16);
        return 20;
    }

    // ---------- Sections ----------

    /**
     * Company (left) and document title + number / dates (right). Returns
     * the Y where the header ends — the company block can wrap to several
     * lines for long bilingual names, so later sections start from here.
     */
    protected function renderHeader(LaborBillPdfDocument $pdf, LaborBill $bill): float
    {
        $profile = $bill->financialProfile;
        $top = 11;
        $textX = self::LEFT;
        $hasLogo = false;

        if ($profile && $profile->logo_path && Storage::disk('public')->exists($profile->logo_path)) {
            try {
                $pdf->Image(Storage::disk('public')->path($profile->logo_path), self::LEFT, $top, 22);
                $textX = self::LEFT + 26;
                $hasLogo = true;
            } catch (\Throwable $e) {
                // ignore — bad image format etc.
            }
        }

        // Right column: title + meta
        $titleX = 128;
        $titleW = self::RIGHT - $titleX;
        $pdf->SetXY($titleX, $top - 1);
        $this->font($pdf, 26);
        $pdf->ink(30, 41, 59);
        $pdf->setBold(true, 0.25);
        $pdf->Cell($titleW, 10, $this->txt('ใบวางบิล'), 0, 2, 'R');
        $pdf->setBold(false);
        $this->font($pdf, 11);
        $pdf->SetTextColor(...$this->accent);
        $pdf->Cell($titleW, 5, 'BILLING STATEMENT', 0, 2, 'R');

        $metaY = $pdf->GetY() + 2;
        $rows = [
            ['เลขที่ / No.', $bill->bill_no],
            ['วันที่ออก / Date', ($bill->issued_at ?? $bill->created_at)?->format('d/m/Y') ?? '-'],
            ['งวด / Period', $bill->period_start->format('d/m/Y') . ' - ' . $bill->period_end->format('d/m/Y')],
        ];
        foreach ($rows as $i => [$label, $value]) {
            $pdf->SetXY($titleX, $metaY + $i * 5.5);
            $this->font($pdf, 11);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(26, 5.5, $this->txt($label), 0, 0, 'L');
            $this->font($pdf, 12.5);
            $pdf->ink(30, 41, 59);
            $pdf->setBold($i === 0, 0.15);
            $pdf->Cell($titleW - 26, 5.5, $this->txt($value), 0, 0, 'R');
            $pdf->setBold(false);
        }
        $rightBottom = $metaY + count($rows) * 5.5;

        if ($bill->status === 'void') {
            $stampY = $rightBottom + 2;
            $pdf->SetDrawColor(220, 38, 38);
            $pdf->SetFillColor(254, 242, 242);
            $pdf->SetLineWidth(0.4);
            $pdf->roundedRect($titleX + 18, $stampY, $titleW - 18, 8, 1.5, 'DF');
            $pdf->SetLineWidth(0.2);
            $this->font($pdf, 13);
            $pdf->ink(220, 38, 38);
            $pdf->setBold(true, 0.15);
            $pdf->SetXY($titleX + 18, $stampY + 0.5);
            $pdf->Cell($titleW - 18, 7, $this->txt('ยกเลิกแล้ว / VOID'), 0, 0, 'C');
            $pdf->setBold(false);
            $rightBottom = $stampY + 9;
            if ($bill->void_reason) {
                $this->font($pdf, 10);
                $pdf->SetTextColor(185, 28, 28);
                $pdf->SetXY($titleX, $rightBottom);
                $pdf->MultiCell($titleW, 4.5, $this->txt('เหตุผล: ' . $bill->void_reason), 0, 'R');
                $rightBottom = $pdf->GetY();
            }
        }

        // Left column: company
        $textW = $titleX - 4 - $textX;
        $name = $profile?->name ?: '';
        $pdf->SetXY($textX, $top);
        $this->font($pdf, $this->nameFontSize($name));
        $pdf->ink(...$this->accent);
        $pdf->setBold(true, 0.2);
        $pdf->MultiCell($textW, 7, $this->txt($name), 0, 'L');
        $pdf->setBold(false);

        $this->font($pdf, 11);
        $pdf->SetTextColor(71, 85, 105);
        if ($profile?->address) {
            $oneLineAddress = preg_replace('/\s+/u', ' ', trim((string) $profile->address));
            $pdf->SetX($textX);
            $pdf->MultiCell($textW, 4.8, $this->txt($oneLineAddress), 0, 'L');
        }
        $meta = [];
        if ($profile?->tax_id) {
            $meta[] = 'เลขประจำตัวผู้เสียภาษี ' . $profile->tax_id . ($profile->branch ? ' (' . $profile->branch . ')' : '');
        }
        if ($profile?->phone) {
            $meta[] = 'โทร. ' . $profile->phone;
        }
        if ($profile?->email) {
            $meta[] = $profile->email;
        }
        if ($meta) {
            $pdf->SetX($textX);
            $pdf->MultiCell($textW, 4.8, $this->txt(implode('   ·   ', $meta)), 0, 'L');
        }
        $leftBottom = max($pdf->GetY(), $hasLogo ? $top + 22 : 0);

        $bottom = max($leftBottom, $rightBottom) + 3;
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(self::LEFT, $bottom, self::RIGHT, $bottom);
        $pdf->SetLineWidth(0.2);

        return $bottom;
    }

    /** "Bill to" box — the team (and its billing details, if filled in). */
    protected function renderBillTo(LaborBillPdfDocument $pdf, LaborBill $bill, float $y): float
    {
        $team = $bill->team;
        $lines = [];
        if ($team?->customer_tax_id) {
            $lines[] = 'เลขประจำตัวผู้เสียภาษี ' . $team->customer_tax_id . ($team->customer_branch ? ' (' . $team->customer_branch . ')' : '');
        }
        if ($team?->customer_address) {
            $lines[] = preg_replace('/\s+/u', ' ', trim((string) $team->customer_address));
        }

        $innerW = self::WIDTH - 10;
        $this->font($pdf, 11);
        $bodyH = 0;
        foreach ($lines as $line) {
            $bodyH += $pdf->nbLines($innerW, $this->txt($line)) * 4.8;
        }
        $h = 6 + 8 + $bodyH + 3;

        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->roundedRect(self::LEFT, $y, self::WIDTH, $h, 2, 'DF');
        $pdf->SetFillColor(...$this->accent);
        $pdf->Rect(self::LEFT, $y + 2, 1.2, $h - 4, 'F');

        $pdf->SetXY(self::LEFT + 5, $y + 2);
        $this->font($pdf, 10.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell($innerW, 5, $this->txt('เรียกเก็บจาก / BILL TO'), 0, 2, 'L');
        $this->font($pdf, 16);
        $pdf->ink(15, 23, 42);
        $pdf->setBold(true, 0.18);
        $pdf->Cell($innerW, 8, $this->txt($team->name ?? '-'), 0, 2, 'L');
        $pdf->setBold(false);
        $this->font($pdf, 11);
        $pdf->SetTextColor(71, 85, 105);
        foreach ($lines as $line) {
            $pdf->SetX(self::LEFT + 5);
            $pdf->MultiCell($innerW, 4.8, $this->txt($line), 0, 'L');
        }

        return $y + $h;
    }

    /** Three figures at a glance: brought forward / this period / total due. */
    protected function renderSummaryTiles(LaborBillPdfDocument $pdf, LaborBill $bill, float $y): float
    {
        $gap = 4;
        $w = (self::WIDTH - 2 * $gap) / 3;
        $h = 17;
        $tiles = [
            ['ยอดยกมา', 'Brought forward', (float) $bill->previous_balance, false],
            ['ยอดงวดนี้', 'This period', (float) $bill->period_charges, false],
            ['ยอดที่ต้องชำระ', 'Total due', (float) $bill->total_due, true],
        ];
        foreach ($tiles as $i => [$th, $en, $amount, $main]) {
            $x = self::LEFT + $i * ($w + $gap);
            if ($main) {
                $pdf->SetFillColor(...$this->accent);
                $pdf->SetDrawColor(...$this->accent);
            } else {
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetDrawColor(226, 232, 240);
            }
            $pdf->roundedRect($x, $y, $w, $h, 2, 'DF');

            $pdf->SetXY($x + 4, $y + 2);
            $this->font($pdf, 11);
            $main ? $pdf->SetTextColor(255, 255, 255) : $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell($w - 8, 5, $this->txt($th . ' / ' . $en), 0, 2, 'L');
            $this->font($pdf, 17);
            $main ? $pdf->ink(255, 255, 255) : $pdf->ink(15, 23, 42);
            $pdf->setBold(true, 0.2);
            $pdf->Cell($w - 8, 9, $this->money($amount), 0, 0, 'R');
            $pdf->setBold(false);
        }

        return $y + $h;
    }

    /** Column definitions [label, width, align] — widths add up to WIDTH. */
    protected function columns(): array
    {
        return [
            ['#', 8, 'C'],
            ['วันที่', 21, 'C'],
            ['รายการ / Description', 89, 'L'],
            ['จำนวน', 17, 'C'],
            ['ราคา/หน่วย', 24, 'R'],
            ['จำนวนเงิน (บาท)', 27, 'R'],
        ];
    }

    protected function tableHeader(LaborBillPdfDocument $pdf, float $y): float
    {
        $pdf->SetFillColor(...$this->tint(0.88));
        $pdf->Rect(self::LEFT, $y, self::WIDTH, 8, 'F');
        $pdf->SetDrawColor(...$this->accent);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(self::LEFT, $y + 8, self::RIGHT, $y + 8);
        $pdf->SetLineWidth(0.2);

        $this->font($pdf, 11.5);
        $pdf->ink(30, 41, 59);
        $pdf->setBold(true, 0.12);
        $x = self::LEFT;
        foreach ($this->columns() as [$label, $w, $align]) {
            $pdf->SetXY($x, $y + 1.5);
            $pdf->Cell($w, 5, $this->txt($label), 0, 0, $align);
            $x += $w;
        }
        $pdf->setBold(false);

        return $y + 8;
    }

    protected function renderItemsTable(LaborBillPdfDocument $pdf, LaborBill $bill, $entries, float $y): float
    {
        $y = $this->tableHeader($pdf, $y);
        $rows = [];

        if ((float) $bill->previous_balance !== 0.0) {
            $rows[] = [
                'date' => $bill->period_start->format('d/m/Y'),
                'title' => 'ยอดยกมาจากงวดก่อน / Balance brought forward',
                'sub' => '',
                'qty' => '',
                'rate' => '',
                'amount' => (float) $bill->previous_balance,
                'muted' => true,
            ];
        }

        $nationalities = ['qty_laos' => 'ลาว', 'qty_myanmar' => 'เมียนมา', 'qty_cambodia' => 'กัมพูชา', 'qty_vietnam' => 'เวียดนาม', 'qty_other' => 'อื่นๆ'];
        foreach ($entries as $entry) {
            $sub = [];
            if ($entry->member) {
                $sub[] = 'ลูกทีม: ' . $entry->member->name;
            }
            $breakdown = [];
            foreach ($nationalities as $col => $label) {
                if ((int) $entry->{$col} > 0) {
                    $breakdown[] = $label . ' ' . (int) $entry->{$col};
                }
            }
            if ($breakdown) {
                $sub[] = implode(' · ', $breakdown);
            }

            $rows[] = [
                'date' => $entry->entry_date->format('d/m/Y'),
                'title' => (string) $entry->description,
                'sub' => implode('   ', $sub),
                'qty' => $entry->quantity ? number_format((float) $entry->quantity) : '',
                'rate' => $entry->unit_rate !== null && $entry->quantity ? $this->money((float) $entry->unit_rate) : '',
                'amount' => (float) $entry->amount,
                'payment' => (float) $entry->amount < 0 || $entry->labor_bill_payment_id,
            ];
        }

        $cols = $this->columns();
        $descW = $cols[2][1];

        if (!$rows) {
            $this->font($pdf, 12);
            $pdf->SetTextColor(148, 163, 184);
            $pdf->SetXY(self::LEFT, $y + 3);
            $pdf->Cell(self::WIDTH, 8, $this->txt('ไม่มีรายการในงวดนี้ / No entries in this period'), 0, 0, 'C');
            $y += 14;
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->Line(self::LEFT, $y, self::RIGHT, $y);
            return $y;
        }

        foreach ($rows as $i => $row) {
            $this->font($pdf, 12.5);
            $titleLines = $pdf->nbLines($descW, $this->txt($row['title']));
            $subLines = 0;
            if ($row['sub'] !== '') {
                $this->font($pdf, 10.5);
                $subLines = $pdf->nbLines($descW, $this->txt($row['sub']));
            }
            $rowH = max(8, $titleLines * 5.4 + $subLines * 4.4 + 3);

            if ($y + $rowH > self::BOTTOM_LIMIT) {
                $y = $this->tableHeader($pdf, $this->continuationHeader($pdf, $bill));
            }

            // Zebra stripes — skipped on a void bill so the VOID watermark underneath stays visible.
            if ($i % 2 === 1 && $bill->status !== 'void') {
                $pdf->SetFillColor(248, 250, 252);
                $pdf->Rect(self::LEFT, $y, self::WIDTH, $rowH, 'F');
            }

            $x = self::LEFT;
            $muted = !empty($row['muted']);
            foreach ([(string) ($i + 1), $row['date']] as $c => $value) {
                $pdf->SetXY($x, $y + 1.5);
                $this->font($pdf, 12);
                $pdf->SetTextColor(100, 116, 139);
                $pdf->Cell($cols[$c][1], 5.4, $this->txt($value), 0, 0, 'C');
                $x += $cols[$c][1];
            }

            // Description + detail line
            $pdf->SetXY($x, $y + 1.5);
            $this->font($pdf, 12.5);
            $muted ? $pdf->SetTextColor(100, 116, 139) : $pdf->SetTextColor(15, 23, 42);
            $pdf->MultiCell($descW, 5.4, $this->txt($row['title']), 0, 'L');
            if ($row['sub'] !== '') {
                $pdf->SetX($x);
                $this->font($pdf, 10.5);
                $pdf->SetTextColor(100, 116, 139);
                $pdf->MultiCell($descW, 4.4, $this->txt($row['sub']), 0, 'L');
            }
            $x += $descW;

            $this->font($pdf, 12);
            $pdf->SetTextColor(51, 65, 85);
            $pdf->SetXY($x, $y + 1.5);
            $pdf->Cell($cols[3][1], 5.4, $this->txt($row['qty']), 0, 0, 'C');
            $x += $cols[3][1];
            $pdf->SetXY($x, $y + 1.5);
            $pdf->Cell($cols[4][1], 5.4, $row['rate'], 0, 0, 'R');
            $x += $cols[4][1];

            $pdf->SetXY($x, $y + 1.5);
            $this->font($pdf, 12.5);
            if (!empty($row['payment'])) {
                $pdf->ink(21, 128, 61); // payments received (negative) in green
            } else {
                $pdf->ink(15, 23, 42);
            }
            $pdf->setBold(!$muted, 0.12);
            $pdf->Cell($cols[5][1], 5.4, $this->money($row['amount']), 0, 0, 'R');
            $pdf->setBold(false);

            $y += $rowH;
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->Line(self::LEFT, $y, self::RIGHT, $y);
        }

        return $y;
    }

    /** Amount in words (left) and the totals (right). */
    protected function renderTotals(LaborBillPdfDocument $pdf, LaborBill $bill, float $y): float
    {
        $blockH = 34;
        if ($y + $blockH > self::BOTTOM_LIMIT) {
            $y = $this->continuationHeader($pdf, $bill);
        }

        // Totals (right)
        $tx = 120;
        $tw = self::RIGHT - $tx;
        $lines = [
            ['ยอดยกมา / Brought forward', (float) $bill->previous_balance],
            ['ยอดงวดนี้ / This period', (float) $bill->period_charges],
        ];
        foreach ($lines as $i => [$label, $amount]) {
            $pdf->SetXY($tx, $y + $i * 6.5);
            $this->font($pdf, 12);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->Cell($tw - 30, 6.5, $this->txt($label), 0, 0, 'L');
            $pdf->ink(15, 23, 42);
            $pdf->Cell(30, 6.5, $this->money($amount), 0, 0, 'R');
        }
        $gy = $y + count($lines) * 6.5 + 2;
        $pdf->SetFillColor(...$this->accent);
        $pdf->roundedRect($tx, $gy, $tw, 11, 1.8, 'F');
        $pdf->SetXY($tx + 3, $gy + 1.5);
        $this->font($pdf, 13);
        $pdf->ink(255, 255, 255);
        $pdf->setBold(true, 0.15);
        $pdf->Cell($tw - 36, 8, $this->txt('ยอดที่ต้องชำระทั้งสิ้น'), 0, 0, 'L');
        $this->font($pdf, 16);
        $pdf->setBold(true, 0.2);
        $pdf->Cell(30, 8, $this->money((float) $bill->total_due), 0, 0, 'R');
        $pdf->setBold(false);
        $totalsBottom = $gy + 11;

        // Amount in words + note (left)
        $lw = $tx - 6 - self::LEFT;
        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->roundedRect(self::LEFT, $y, $lw, 15, 1.8, 'DF');
        $pdf->SetXY(self::LEFT + 4, $y + 1.8);
        $this->font($pdf, 10.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell($lw - 8, 4.5, $this->txt('จำนวนเงินตัวอักษร / Amount in words'), 0, 2, 'L');
        $this->font($pdf, 13);
        $pdf->ink(15, 23, 42);
        $pdf->setBold(true, 0.12);
        $pdf->Cell($lw - 8, 7, $this->txt('(' . $this->bahtText((float) $bill->total_due) . ')'), 0, 0, 'L');
        $pdf->setBold(false);

        $pdf->SetXY(self::LEFT, $y + 17);
        $this->font($pdf, 10);
        $pdf->SetTextColor(148, 163, 184);
        $pdf->MultiCell($lw, 4.3, $this->txt('เอกสารนี้เป็นใบแจ้งยอดเรียกเก็บ ไม่ใช่ใบกำกับภาษี'), 0, 'L');

        return max($totalsBottom, $pdf->GetY());
    }

    /** "Received by" (left) and the authorized signatory with signature / stamp (right). */
    protected function renderSignature(LaborBillPdfDocument $pdf, LaborBill $bill, float $y): void
    {
        $blockH = 37;
        if ($y + $blockH > self::BOTTOM_LIMIT) {
            $y = $this->continuationHeader($pdf, $bill);
        }
        // Sit near the bottom of the page when there is room, like a printed form.
        $y = max($y, self::BOTTOM_LIMIT - $blockH);

        $profile = $bill->financialProfile;
        $boxW = 80;
        $boxes = [
            [self::LEFT + 6, 'ผู้รับวางบิล / Received by', null],
            [self::RIGHT - 6 - $boxW, 'ผู้มีอำนาจลงนาม / Authorized Signatory', $profile],
        ];

        foreach ($boxes as [$x, $title, $signer]) {
            $lineY = $y + 20;
            if ($signer) {
                if ($signer->stamp_path && Storage::disk('public')->exists($signer->stamp_path)) {
                    try {
                        $pdf->Image(Storage::disk('public')->path($signer->stamp_path), $x + 4, $y - 2, 26, 26);
                    } catch (\Throwable $e) {
                    }
                }
                if ($signer->signature_path && Storage::disk('public')->exists($signer->signature_path)) {
                    try {
                        $pdf->Image(Storage::disk('public')->path($signer->signature_path), $x + ($boxW - 45) / 2, $y + 1, 45, 18);
                    } catch (\Throwable $e) {
                    }
                }
            }
            $pdf->SetDrawColor(148, 163, 184);
            $pdf->SetLineWidth(0.3);
            $pdf->Line($x + 6, $lineY, $x + $boxW - 6, $lineY);
            $pdf->SetLineWidth(0.2);

            $pdf->SetXY($x, $lineY + 1);
            $this->font($pdf, 11.5);
            $pdf->SetTextColor(15, 23, 42);
            $name = $signer?->authorized_signatory_name;
            $pdf->Cell($boxW, 5, $this->txt($name ? '(' . $name . ')' : '(                                                   )'), 0, 2, 'C');
            $this->font($pdf, 11);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->Cell($boxW, 5, $this->txt($title), 0, 2, 'C');
            $this->font($pdf, 10.5);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell($boxW, 5, $this->txt('วันที่ / Date  ______ / ______ / __________'), 0, 2, 'C');
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
