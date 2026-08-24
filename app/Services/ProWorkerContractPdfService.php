<?php

namespace App\Services;

use App\Models\CompanyProfile;
use App\Models\ProWorkerContractTemplate;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

/**
 * Renders a Pro Worker <-> Employer contract PDF from a template's
 * field_mapping + the values filled in at issuance — a deliberately
 * smaller sibling of PdfGeneratorService (same FPDI + THSarabunNew/CP874
 * mechanics, and the same image-fit/mark-drawing math, kept as its own
 * copy rather than shared since this is a deliberately separate system —
 * see LaborContractTemplateController's docblock). Every text-type field
 * is a plain label->text lookup (no Employee/Employer data resolution);
 * image/stamp/signature/mark fields are fixed at template-build time (see
 * builder.blade.php) and need no per-issuance value at all.
 *
 * The template file is validated/normalized for FPDI compatibility at
 * upload time (see ProWorkerContractTemplateController::store()), so no
 * normalize-fallback is needed here.
 */
class ProWorkerContractPdfService
{
    protected string $fontPath;

    public function __construct()
    {
        $this->fontPath = public_path('fonts/THSarabunNew.php');
    }

    /**
     * @param  array<string,string>  $fieldValues  keyed by each field's `key`
     *         (address_th/address_en values are pre-composed by the caller —
     *         see ProWorkerContractController — this service just draws text).
     */
    public function generate(ProWorkerContractTemplate $template, array $fieldValues, string $contractNo): string
    {
        $content = $this->render($template, $fieldValues, $contractNo);

        $path = 'proworker_contracts/' . $contractNo . '.pdf';
        Storage::disk('public')->put($path, $content);

        return $path;
    }

    /**
     * Renders a check-the-layout preview with the given (possibly
     * incomplete) field values — raw PDF bytes only, never persisted to
     * Storage and never consumes a real contract_no (see
     * ProWorkerContractService::generateContractNo()), so previewing has
     * zero side effects. Called directly from LaborContractController's
     * preview action, both for first-time issuance and for edits. No QR
     * code is drawn (there's no real contract_no yet for it to encode) —
     * the company logo still renders normally.
     */
    public function preview(ProWorkerContractTemplate $template, array $fieldValues): string
    {
        return $this->render($template, $fieldValues, __('PREVIEW'), includeQr: false);
    }

    /**
     * On-demand download variant with the Contractor's signature/stamp
     * fields optionally omitted (see LaborContractController::download()
     * — the choice is asked fresh every time someone downloads, so this
     * is never persisted to Storage; the CANONICAL stored file from
     * issue()/update() always keeps them). Uses the real contract_no, so
     * the QR/contract-number corner block is identical to the stored copy.
     */
    public function renderVariant(ProWorkerContractTemplate $template, array $fieldValues, string $contractNo, bool $includeSignatureStamp): string
    {
        return $this->render($template, $fieldValues, $contractNo, includeQr: true, includeSignatureStamp: $includeSignatureStamp);
    }

    /**
     * @param  array<string,string>  $fieldValues
     */
    protected function render(ProWorkerContractTemplate $template, array $fieldValues, string $contractNo, bool $includeQr = true, bool $includeSignatureStamp = true): string
    {
        $pdf = new Fpdi();

        $reflection = new \ReflectionClass($pdf);
        if ($reflection->hasProperty('fontpath')) {
            $property = $reflection->getProperty('fontpath');
            $property->setAccessible(true);
            $property->setValue($pdf, public_path('fonts') . DIRECTORY_SEPARATOR);
        }

        $fontLoaded = false;
        if (file_exists($this->fontPath)) {
            $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
            $pdf->SetFont('THSarabunNew', '', 14);
            $fontLoaded = true;
        } else {
            $pdf->SetFont('Arial', '', 12);
        }

        $templatePath = Storage::disk('public')->path($template->file_path);
        $pageCount = $pdf->setSourceFile($templatePath);

        $companyProfile = CompanyProfile::where('is_default', true)->first() ?? CompanyProfile::first();
        $qrPath = $includeQr ? $this->ensureQrCode($contractNo) : null;

        $encode = function (string $text) use ($fontLoaded) {
            if ($fontLoaded) {
                $encoded = @iconv('UTF-8', 'cp874', $text);
            } else {
                $encoded = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
            }

            return $encoded === false ? $text : $encoded;
        };

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // Anti-forgery marks — auto-printed on EVERY page (not
            // draggable builder fields, since none of this exists until
            // issuance), required on every page not just page 1 — see
            // LaborContractController's docblock. Split top/bottom on
            // purpose: the contract number sits right at the top edge so
            // it never overlaps/mixes with the document's own header
            // content in the middle of the page, while the QR code lives
            // in the bottom-right corner where a phone camera can be lined
            // up against it without the rest of the page in the way.
            $cornerX = $size['width'] - 22;

            $pdf->SetFontSize(9);
            $pdf->SetXY($cornerX - 28, 3);
            $pdf->Cell(50, 4, $encode(__('Contract No.') . ': ' . $contractNo), 0, 0, 'R');

            if ($companyProfile && $companyProfile->logo_path && Storage::disk('public')->exists($companyProfile->logo_path)) {
                try {
                    $pdf->Image(Storage::disk('public')->path($companyProfile->logo_path), $cornerX, 8, 14);
                } catch (\Throwable $e) {
                    // ignore — bad image format etc., rest of the corner block still renders
                }
            }

            if ($qrPath && Storage::disk('public')->exists($qrPath)) {
                try {
                    $pdf->Image(Storage::disk('public')->path($qrPath), $cornerX, $size['height'] - 20, 14);
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            $items = collect($template->field_mapping ?? [])->where('page', $pageNo);

            foreach ($items as $item) {
                $x = ($item['x'] / 100) * $size['width'];
                $y = ($item['y'] / 100) * $size['height'];
                $boxW = ($item['w'] / 100) * $size['width'];
                $boxH = ($item['h'] / 100) * $size['height'];

                $type = $item['type'] ?? 'text';

                if (in_array($type, ['signature', 'stamp'], true) && !$includeSignatureStamp) {
                    continue;
                }

                if (in_array($type, ['image', 'stamp', 'signature'], true)) {
                    $this->drawImageFit($pdf, $item['path'] ?? null, $x, $y, $boxW, $boxH);
                    continue;
                }

                if ($type === 'mark') {
                    $this->drawMark($pdf, $item, $x, $y, $boxW, $boxH);
                    continue;
                }

                if ($type === 'issue_date') {
                    $text = $this->formatIssueDate($item['dateFormat'] ?? 'full');
                } else {
                    $key = $item['key'] ?? null;
                    $text = $key ? ($fieldValues[$key] ?? '') : '';
                }
                if ($text === '' || $text === null) {
                    continue;
                }

                $encodedText = $encode((string) $text);

                $fontSize = $item['fontSize'] ?? 14;
                if (!empty($item['autoFit'])) {
                    $maxFontSizeH = ($boxH * 0.7) * 2.83;
                    $pdf->SetFontSize($maxFontSizeH);
                    $textWidth = $pdf->GetStringWidth($encodedText);
                    $fontSize = $textWidth > ($boxW * 0.95) ? $maxFontSizeH * (($boxW * 0.95) / $textWidth) : $maxFontSizeH;
                }
                $pdf->SetFontSize($fontSize);

                $align = $item['align'] ?? 'left';
                $textWidth = $pdf->GetStringWidth($encodedText);
                if ($align === 'center') {
                    $textX = $x + ($boxW - $textWidth) / 2;
                } elseif ($align === 'right') {
                    $textX = $x + $boxW - $textWidth - 1;
                } else {
                    $textX = $x + 1;
                }

                $textY = $y + $boxH;
                $pdf->Text($textX, $textY, $encodedText);
            }
        }

        return $pdf->Output('S');
    }

    /**
     * The `issue_date` field type — unlike every other field type, this
     * one is never read from $fieldValues (there's no blank for it on the
     * issuance form at all, see _fields.blade.php's type filter): it's
     * computed fresh from "now" every time this method runs, i.e. every
     * time generate()/renderVariant() is called — first issuance, a later
     * correction via update(), or a signature-less download variant — so
     * it always reflects when THIS specific PDF was produced.
     *
     * `day` is shared between the Thai and English sides on purpose (the
     * digit doesn't change with language/calendar) — only month and year
     * have separate Thai/English variants, since the document places both
     * a Thai date line and an English date line side by side:
     *   day / month (Thai) / year (พ.ศ., Buddhist Era = Gregorian + 543) / full (Thai)
     *   month_en / year_ce (plain Gregorian) / full_en (English)
     */
    protected function formatIssueDate(string $format): string
    {
        static $thaiMonths = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
        ];
        static $englishMonths = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        $now = now();
        $buddhistYear = $now->year + 543;

        return match ($format) {
            'day' => (string) $now->day,
            'month' => $thaiMonths[$now->month],
            'month_en' => $englishMonths[$now->month],
            'year' => (string) $buddhistYear,
            'year_ce' => (string) $now->year,
            'full_en' => $now->day . ' ' . $englishMonths[$now->month] . ' ' . $now->year,
            default => $now->day . ' ' . $thaiMonths[$now->month] . ' ' . $buddhistYear,
        };
    }

    /**
     * Generates (once — idempotent, checked via Storage::exists() first so
     * update()'s PDF re-render never regenerates it) a QR code PNG linking
     * to the public, no-login verify page for this contract number (see
     * routes/labor.php's un-authenticated route group and
     * LaborContractController::publicVerify()). Scanning it confirms the
     * number is real and shows minimal employer/company info only — never
     * the full field_values, per the confidentiality requirement between
     * contracting parties. Returns null (soft-fail, same style as
     * drawImageFit()) if generation fails for any reason — a missing QR
     * shouldn't block contract issuance.
     */
    protected function ensureQrCode(string $contractNo): ?string
    {
        $path = 'proworker_contracts/qrcodes/' . $contractNo . '.png';

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        try {
            $url = route('labor.contracts.public-verify', $contractNo);
            $result = (new Builder(writer: new PngWriter(), data: $url, size: 300, margin: 8))->build();
            Storage::disk('public')->put($path, $result->getString());

            return $path;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Draws an image (used for the image/stamp/signature field types)
     * scaled to fit within the box while preserving its own aspect ratio —
     * same "no real mm dimensions known" fallback path as
     * PdfGeneratorService::renderStampWithAspect(), since these fields
     * carry no employer-style width_mm/height_mm data.
     */
    protected function drawImageFit($pdf, ?string $relativePath, float $areaX, float $areaY, float $areaW, float $areaH): void
    {
        if (!$relativePath || !Storage::disk('public')->exists($relativePath)) {
            return;
        }

        $fullPath = Storage::disk('public')->path($relativePath);

        $w = $areaW;
        $h = $areaH;
        $dims = @getimagesize($fullPath);
        if ($dims && !empty($dims[0]) && !empty($dims[1])) {
            $imgRatio = $dims[0] / $dims[1];
            $boxRatio = $areaW / max($areaH, 0.001);
            if ($imgRatio > $boxRatio) {
                $w = $areaW;
                $h = $areaW / $imgRatio;
            } else {
                $h = $areaH;
                $w = $areaH * $imgRatio;
            }
        }

        $x = $areaX + (($areaW - $w) / 2);
        $y = $areaY + (($areaH - $h) / 2);

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $imgType = in_array($ext, ['jpg', 'jpeg']) ? 'JPEG' : 'PNG';

        $pdf->Image($fullPath, $x, $y, $w, $h, $imgType);
    }

    /**
     * Draws the check/cross/circle mark shape — ported from
     * PdfGeneratorService::drawMark()/drawEllipseOutline() (kept as its own
     * copy, see class docblock).
     */
    protected function drawMark($pdf, array $item, float $x, float $y, float $w, float $h): void
    {
        [$r, $g, $b] = $this->hexToRgb($item['color'] ?? '#16a34a');
        $pdf->SetDrawColor($r, $g, $b);
        $pdf->SetLineWidth(max($w, $h) * 0.08);

        $shape = $item['markShape'] ?? 'check';

        if ($shape === 'cross') {
            $pdf->Line($x, $y, $x + $w, $y + $h);
            $pdf->Line($x + $w, $y, $x, $y + $h);
        } elseif ($shape === 'circle') {
            $this->drawEllipseOutline($pdf, $x + ($w / 2), $y + ($h / 2), $w / 2, $h / 2);
        } else {
            $pdf->Line($x + ($w * 0.05), $y + ($h * 0.55), $x + ($w * 0.4), $y + ($h * 0.9));
            $pdf->Line($x + ($w * 0.4), $y + ($h * 0.9), $x + ($w * 0.95), $y + ($h * 0.15));
        }

        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(0, 0, 0);
    }

    /**
     * FPDF has no Ellipse()/Circle() primitive — approximate one with short
     * connected line segments (ported from PdfGeneratorService).
     */
    protected function drawEllipseOutline($pdf, float $cx, float $cy, float $rx, float $ry, int $segments = 36): void
    {
        $prevX = $cx + $rx;
        $prevY = $cy;

        for ($i = 1; $i <= $segments; $i++) {
            $angle = (2 * M_PI * $i) / $segments;
            $px = $cx + ($rx * cos($angle));
            $py = $cy + ($ry * sin($angle));
            $pdf->Line($prevX, $prevY, $px, $py);
            $prevX = $px;
            $prevY = $py;
        }
    }

    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return [0, 0, 0];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
