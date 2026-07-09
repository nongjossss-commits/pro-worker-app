<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PdfTemplate;
use App\Models\GlobalWitness;
use App\Models\Witness;
use App\Models\Employer;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Illuminate\Support\Facades\Storage;
use App\Helpers\PdfHelper;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PdfGeneratorService
{
    protected $fontPath;
    protected $signatureService;
    protected $tempFiles = [];

    /**
     * Thai month abbreviations (ม.ค. / ก.พ. / …) — official RID short form.
     * Used by *_month_th and *_th_be / *_th_ce date resolvers.
     */
    protected static $thaiMonthShort = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
    ];

    /**
     * Compose a "day monthTh year" string, e.g. "15 ม.ค. 2536".
     * $yearAdjust adds to the CE year (543 → BE, 0 → CE).
     */
    protected function formatThaiDate(?Carbon $d, int $yearAdjust = 543): string
    {
        if (!$d) return '';
        $month = self::$thaiMonthShort[$d->month] ?? '';
        return $d->format('d') . ' ' . $month . ' ' . ($d->year + $yearAdjust);
    }

    /**
     * Compose a "day MONTH year" string with English month abbrev, e.g. "15 JAN 2026".
     * $yearAdjust adds to the CE year (543 → BE, 0 → CE).
     */
    protected function formatEnglishDate(?Carbon $d, int $yearAdjust = 0): string
    {
        if (!$d) return '';
        return $d->format('d') . ' ' . strtoupper($d->format('M')) . ' ' . ($d->year + $yearAdjust);
    }

    public function __construct(SignatureGeneratorService $signatureService)
    {
        // Path to Thai font (Assumes font files exist in public/fonts)
        $this->fontPath = public_path('fonts/THSarabunNew.php');
        $this->signatureService = $signatureService;
    }

    /**
     * Render a stamp image inside a template area WITHOUT stretching it.
     *
     * The template area ($areaX, $areaY, $areaW, $areaH — all in millimetres
     * for FPDI's default unit) is treated as a bounding box, NOT a fill target:
     *
     *   1. If the caller passed the real physical dimensions of the stamp
     *      (e.g., a 30mm × 30mm round company seal), draw at that exact size.
     *      Scale down uniformly if it exceeds the box.
     *   2. Otherwise fall back to the image file's own aspect ratio and fit
     *      inside the box without stretching.
     *
     * In both cases the stamp is centered inside the area. That matches the
     * paper-world behaviour a finance operator expects: a round seal stays
     * round no matter what template box it lands in.
     *
     * @param  \setasign\Fpdi\Fpdi $pdf
     * @param  string $targetPath   Absolute path to the stamp image file.
     * @param  float  $areaX        Top-left X of the template area (mm).
     * @param  float  $areaY        Top-left Y of the template area (mm).
     * @param  float  $areaW        Template area width (mm).
     * @param  float  $areaH        Template area height (mm).
     * @param  float|null $realWmm  Real stamp width in mm (nullable).
     * @param  float|null $realHmm  Real stamp height in mm (nullable).
     * @return void
     */
    protected function renderStampWithAspect($pdf, string $targetPath, float $areaX, float $areaY, float $areaW, float $areaH, ?float $realWmm = null, ?float $realHmm = null): void
    {
        if (!file_exists($targetPath)) return;

        $stampW = $areaW;
        $stampH = $areaH;

        if ($realWmm && $realHmm && $realWmm > 0 && $realHmm > 0) {
            // Path 1 — real physical dimensions known.
            $stampW = (float) $realWmm;
            $stampH = (float) $realHmm;

            // Scale down uniformly if the stamp would overflow the area.
            if ($stampW > $areaW || $stampH > $areaH) {
                $scale = min($areaW / $stampW, $areaH / $stampH);
                $stampW *= $scale;
                $stampH *= $scale;
            }
        } else {
            // Path 2 — no real dimensions. Preserve aspect ratio of the file.
            $dims = @getimagesize($targetPath);
            if ($dims && !empty($dims[0]) && !empty($dims[1])) {
                $imgPxW = (float) $dims[0];
                $imgPxH = (float) $dims[1];
                $imgRatio = $imgPxW / $imgPxH;
                $boxRatio = $areaW / max($areaH, 0.001);
                if ($imgRatio > $boxRatio) {
                    // Image is wider than the box → fit to width.
                    $stampW = $areaW;
                    $stampH = $areaW / $imgRatio;
                } else {
                    // Image is taller than the box → fit to height.
                    $stampH = $areaH;
                    $stampW = $areaH * $imgRatio;
                }
            }
        }

        // Center inside the area.
        $stampX = $areaX + max(0, ($areaW - $stampW) / 2);
        $stampY = $areaY + max(0, ($areaH - $stampH) / 2);

        $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        $imgType = in_array($ext, ['png', 'jpg', 'jpeg']) ? strtoupper($ext) : 'PNG';
        if ($imgType === 'JPG') $imgType = 'JPEG';

        $pdf->Image($targetPath, $stampX, $stampY, $stampW, $stampH, $imgType);
    }

    public function generateForEmployees(PdfTemplate $template, Collection $employees, $options = [])
    {
        // Options: 'output_type' => 'download' | 'save_to_slot' | 'raw_content'
        //          'slot_name' => string (required if save_to_slot)
        //          'target_employer_id' => int|null
        //          'target_importer_id' => int|null
        //          'target_delegate_id' => int|null
        //          'use_empty_employer' => bool

        $outputType = $options['output_type'] ?? 'download';
        $results = [];

        // Resolve Target Employer (Optimization: Fetch once)
        $targetEmployer = null;
        if (!empty($options['target_employer_id'])) {
            $targetEmployer = Employer::find($options['target_employer_id']);
        }

        $targetImporter = null;
        if (!empty($options['target_importer_id'])) {
            $targetImporter = \App\Models\Importer::find($options['target_importer_id']);
        }

        $targetDelegate = null;
        if (!empty($options['target_delegate_id'])) {
            $targetDelegate = \App\Models\Delegate::find($options['target_delegate_id']);
        }

        $useEmptyEmployer = $options['use_empty_employer'] ?? false;

        // Determine Chunk Size (Employees per page) from Template metadata
        $chunkSize = $template->meta_data['employees_per_page'] ?? 1;
        $chunkSize = max(1, (int)$chunkSize);

        if ($chunkSize > 1) {
            // Processing in chunks (Multiple employees per page)
            $chunks = $employees->chunk($chunkSize);

            foreach ($chunks as $chunkIndex => $chunkEmployees) {
                try {
                    $pdfContent = $this->generateChunkedPdf($template, $chunkEmployees, $targetEmployer, $useEmptyEmployer, $targetImporter, $targetDelegate);

                    if ($outputType === 'save_to_slot') {
                        // For slots, we still want to save a copy to each employee's profile
                        foreach ($chunkEmployees as $employee) {
                            $this->saveToSlot($employee, $pdfContent, $options['slot_name'], $template);
                            $results[] = ['employee' => $employee->id, 'status' => 'saved'];
                        }
                    } elseif ($outputType === 'raw_content') {
                        // Raw content might be weird for chunks, so associate with the first employee
                        $firstEmp = $chunkEmployees->first();
                        $filename = "chunk_" . ($chunkIndex + 1) . "_" . $this->generateFilename($template, $firstEmp);
                        foreach ($chunkEmployees as $employee) {
                            $results[] = [
                                'employee_id' => $employee->id,
                                'filename' => $filename,
                                'content' => $pdfContent
                            ];
                        }
                    } else {
                        $firstEmp = $chunkEmployees->first();
                        $filename = "chunk_" . ($chunkIndex + 1) . "_" . $this->generateFilename($template, $firstEmp);
                        $results[] = ['filename' => $filename, 'content' => $pdfContent];
                    }
                } catch (\Exception $e) {
                    Log::error("PDF Generation Chunk Error: " . $e->getMessage());
                    if ($outputType === 'save_to_slot' || $outputType === 'raw_content') {
                        foreach ($chunkEmployees as $employee) {
                            $results[] = [
                                'employee' => $employee->id,
                                'employee_id' => $employee->id,
                                'status' => 'error',
                                'message' => $e->getMessage()
                            ];
                        }
                    }
                    if ($outputType === 'download') throw $e;
                }
            }
        } else {
            // Standard single employee processing
            foreach ($employees as $employee) {
                try {
                    $pdfContent = $this->generateSinglePdf($template, $employee, $targetEmployer, $useEmptyEmployer, $targetImporter, $targetDelegate);
                    $filename = $this->generateFilename($template, $employee);

                    if ($outputType === 'save_to_slot') {
                        $this->saveToSlot($employee, $pdfContent, $options['slot_name'], $template);
                        $results[] = ['employee' => $employee->id, 'status' => 'saved'];
                    } elseif ($outputType === 'raw_content') {
                        $results[] = [
                            'employee_id' => $employee->id,
                            'filename' => $filename,
                            'content' => $pdfContent
                        ];
                    } else {
                        $results[] = ['filename' => $filename, 'content' => $pdfContent];
                    }
                } catch (\Exception $e) {
                    Log::error("PDF Generation Error (Emp ID: {$employee->id}): " . $e->getMessage());

                    if ($outputType === 'save_to_slot') {
                        $results[] = ['employee' => $employee->id, 'status' => 'error', 'message' => $e->getMessage()];
                    } elseif ($outputType === 'raw_content') {
                        $results[] = [
                            'employee_id' => $employee->id,
                            'status' => 'error',
                            'message' => $e->getMessage()
                        ];
                    }
                    if ($outputType === 'download') {
                        throw $e;
                    }
                }
            }
        }

        return $results;
    }

    public function generatePreviewPdf(PdfTemplate $template)
    {
        $pdf = new Fpdi();

        $reflection = new \ReflectionClass($pdf);
        if ($reflection->hasProperty('fontpath')) {
            $property = $reflection->getProperty('fontpath');
            $property->setAccessible(true);
            $property->setValue($pdf, public_path('fonts') . DIRECTORY_SEPARATOR);
        }

        $templatePath = Storage::disk('public')->path($template->file_path);

        $fontLoaded = false;
        if (file_exists($this->fontPath)) {
             $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
             $pdf->SetFont('THSarabunNew', '', 14);
             $fontLoaded = true;
        } else {
             $pdf->SetFont('Arial', '', 12);
        }

        try {
            $pageCount = $pdf->setSourceFile($templatePath);
        } catch (\Exception $e) {
             try {
                $normalizedPath = $this->tryNormalizePdf($templatePath);
                if ($normalizedPath) {
                    $pageCount = $pdf->setSourceFile($normalizedPath);
                    $this->tempFiles[] = $normalizedPath;
                } else {
                    throw $e;
                }
             } catch (\Exception $ex) {
                 throw new \Exception('Failed to process PDF template: ' . $e->getMessage());
             }
        }

        try {
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                $items = collect($template->field_mapping)->where('page', $pageNo);

                foreach ($items as $item) {
                    $x = ($item['x'] / 100) * $size['width'];
                    $y = ($item['y'] / 100) * $size['height'];

                    if (isset($item['type']) && in_array($item['type'], ['image', 'signature', 'stamp'])) {
                        $w = ($item['w'] / 100) * $size['width'];
                        $h = ($item['h'] / 100) * $size['height'];

                        if ($item['type'] === 'image' && !empty($item['path']) && Storage::disk('public')->exists($item['path'])) {
                            $targetPath = Storage::disk('public')->path($item['path']);
                            $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
                            $imgType = in_array($ext, ['png', 'jpg', 'jpeg']) ? strtoupper($ext) : 'PNG';
                            if ($imgType === 'JPG') $imgType = 'JPEG';

                            $pdf->Image($targetPath, $x, $y, $w, $h, $imgType);
                        } else {
                            // Draw a placeholder box for signatures and stamps
                            $pdf->SetDrawColor(200, 200, 200);
                            $pdf->SetFillColor(240, 240, 240);
                            $pdf->Rect($x, $y, $w, $h, 'DF');

                            $pdf->SetTextColor(150, 150, 150);
                            $groupName = $item['signatureGroup'] ?? $item['type'];

                            $pdf->SetFontSize(10);
                            // Center text in signature box
                            $textX = $x + 2;
                            $textY = $y + ($h / 2) + 2;
                            $pdf->Text($textX, $textY, "[$groupName]");
                            $pdf->SetTextColor(0, 0, 0); // Reset
                        }
                        continue;
                    }

                    $text = '';
                    if ($item['type'] === 'static') {
                        $text = $item['text'] ?? '';
                    } elseif ($item['type'] === 'db') {
                        $key = $item['key'] ?? 'data';
                        $text = "[$key]";
                    }

                    if ($text) {
                        $boxW = ($item['w'] / 100) * $size['width'];
                        $boxH = ($item['h'] / 100) * $size['height'];

                        if ($fontLoaded) {
                            $encodedText = @iconv('UTF-8', 'cp874', $text);
                            if ($encodedText === false) $encodedText = $text;
                        } else {
                            $encodedText = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
                        }

                        $fontSize = $item['fontSize'] ?? 12;

                        if (!empty($item['autoFit'])) {
                            $maxFontSizeH = ($boxH * 0.7) * 2.83;
                            $pdf->SetFontSize($maxFontSizeH);
                            $textWidth = $pdf->GetStringWidth($encodedText);

                            if ($textWidth > ($boxW * 0.95)) {
                                $ratio = ($boxW * 0.95) / $textWidth;
                                $fontSize = $maxFontSizeH * $ratio;
                            } else {
                                $fontSize = $maxFontSizeH;
                            }
                        }

                        $pdf->SetFontSize($fontSize);

                        $align = $item['align'] ?? 'center';
                        $textWidth = $pdf->GetStringWidth($encodedText);
                        $textX = $x;

                        if ($align === 'center') {
                            $textX = $x + ($boxW - $textWidth) / 2;
                        } elseif ($align === 'right') {
                             $textX = $x + $boxW - $textWidth - 1;
                        } else {
                             $textX = $x + 1;
                        }

                        $textY = $y + $boxH;

                        // Optionally draw a light border around text field for visibility in preview
                        $pdf->SetDrawColor(200, 200, 200);
                        $pdf->Rect($x, $y, $boxW, $boxH);

                        $pdf->Text($textX, $textY, $encodedText);
                    }
                }
            }
            $content = $pdf->Output('S');
        } finally {
            foreach ($this->tempFiles as $tempFile) {
                if (file_exists($tempFile)) @unlink($tempFile);
            }
            $this->tempFiles = [];
        }

        return $content;
    }

    /**
     * Quick-print generator for templates that don't require employee data.
     * Renders real values for employer/importer/delegate/witness/static fields,
     * leaves employee-specific fields blank, draws signatures for groups that have data.
     */
    public function generateForOfficeUse(PdfTemplate $template, ?Employer $targetEmployer = null, ?\App\Models\Importer $targetImporter = null, ?\App\Models\Delegate $targetDelegate = null)
    {
        $pdf = new Fpdi();

        $reflection = new \ReflectionClass($pdf);
        if ($reflection->hasProperty('fontpath')) {
            $property = $reflection->getProperty('fontpath');
            $property->setAccessible(true);
            $property->setValue($pdf, public_path('fonts') . DIRECTORY_SEPARATOR);
        }

        $templatePath = Storage::disk('public')->path($template->file_path);

        $fontLoaded = false;
        if (file_exists($this->fontPath)) {
            $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
            $pdf->SetFont('THSarabunNew', '', 14);
            $fontLoaded = true;
        } else {
            $pdf->SetFont('Arial', '', 12);
        }

        try {
            $pageCount = $pdf->setSourceFile($templatePath);
        } catch (\Exception $e) {
            try {
                $normalizedPath = $this->tryNormalizePdf($templatePath);
                if ($normalizedPath) {
                    $pageCount = $pdf->setSourceFile($normalizedPath);
                    $this->tempFiles[] = $normalizedPath;
                } else {
                    throw $e;
                }
            } catch (\Exception $ex) {
                throw new \Exception('Failed to process PDF template: ' . $e->getMessage());
            }
        }

        // Signature paths (employee skipped intentionally)
        $emprSig1Path = null;
        $emprSig2Path = null;
        $emprStampPath = null;
        $delegateSigPath = null;
        $importerSig1Path = null;
        $importerSig2Path = null;
        $importerStampPath = null;
        $tempSigPaths = [];

        if ($targetEmployer) {
            if ($targetEmployer->signature_1_path && Storage::disk('public')->exists($targetEmployer->signature_1_path)) {
                $emprSig1Path = Storage::disk('public')->path($targetEmployer->signature_1_path);
            }
            if ($targetEmployer->signature_2_path && Storage::disk('public')->exists($targetEmployer->signature_2_path)) {
                $emprSig2Path = Storage::disk('public')->path($targetEmployer->signature_2_path);
            }
            if ($targetEmployer->employer_stamp_path && Storage::disk('public')->exists($targetEmployer->employer_stamp_path)) {
                $emprStampPath = Storage::disk('public')->path($targetEmployer->employer_stamp_path);
            }
        }

        if ($targetDelegate && $targetDelegate->signature_path && Storage::disk('public')->exists($targetDelegate->signature_path)) {
            $delegateSigPath = Storage::disk('public')->path($targetDelegate->signature_path);
        }

        if ($targetImporter) {
            if ($targetImporter->signature_1_path && Storage::disk('public')->exists($targetImporter->signature_1_path)) {
                $importerSig1Path = Storage::disk('public')->path($targetImporter->signature_1_path);
            }
            if ($targetImporter->signature_2_path && Storage::disk('public')->exists($targetImporter->signature_2_path)) {
                $importerSig2Path = Storage::disk('public')->path($targetImporter->signature_2_path);
            }
            if ($targetImporter->importer_stamp_path && Storage::disk('public')->exists($targetImporter->importer_stamp_path)) {
                $importerStampPath = Storage::disk('public')->path($targetImporter->importer_stamp_path);
            }
        }

        // Witness signatures: only use real ones, do NOT auto-generate randoms (office use)
        $witnessSigPaths = [];
        for ($i = 1; $i <= 4; $i++) {
            $alias = "witness_{$i}";
            $witnessId = $template->meta_data[$alias . '_id'] ?? null;
            $witness = $witnessId ? Witness::find($witnessId) : null;
            if ($witness && $witness->signature_path && Storage::disk('public')->exists($witness->signature_path)) {
                $witnessSigPaths[$alias] = Storage::disk('public')->path($witness->signature_path);
            }
        }

        try {
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                $items = collect($template->field_mapping)->where('page', $pageNo);

                foreach ($items as $item) {
                    $x = ($item['x'] / 100) * $size['width'];
                    $y = ($item['y'] / 100) * $size['height'];

                    // Images / Signatures / Stamps
                    if (isset($item['type']) && in_array($item['type'], ['image', 'signature', 'stamp'])) {
                        $w = ($item['w'] / 100) * $size['width'];
                        $h = ($item['h'] / 100) * $size['height'];

                        $targetPath = null;
                        $isStamp = false;
                        $stampRealWmm = null;
                        $stampRealHmm = null;

                        if ($item['type'] === 'image') {
                            if (!empty($item['path']) && Storage::disk('public')->exists($item['path'])) {
                                $targetPath = Storage::disk('public')->path($item['path']);
                            }
                        } elseif ($item['type'] === 'signature') {
                            $group = $item['signatureGroup'] ?? 'employee';
                            if ($group === 'employer') $targetPath = $emprSig1Path;
                            elseif ($group === 'employer_2') $targetPath = $emprSig2Path;
                            elseif ($group === 'importer_1') $targetPath = $importerSig1Path;
                            elseif ($group === 'importer_2') $targetPath = $importerSig2Path;
                            elseif ($group === 'delegate') $targetPath = $delegateSigPath;
                            elseif (str_starts_with($group, 'witness_')) $targetPath = $witnessSigPaths[$group] ?? null;
                            // 'employee' group → skipped intentionally
                        } elseif ($item['type'] === 'stamp') {
                            $isStamp = true;
                            $group = $item['signatureGroup'] ?? 'employer_stamp';
                            if ($group === 'importer_stamp') {
                                $targetPath = $importerStampPath;
                                $stampRealWmm = $targetImporter->importer_stamp_width_mm ?? null;
                                $stampRealHmm = $targetImporter->importer_stamp_height_mm ?? null;
                            } else {
                                $targetPath = $emprStampPath;
                                $stampRealWmm = $targetEmployer->employer_stamp_width_mm ?? null;
                                $stampRealHmm = $targetEmployer->employer_stamp_height_mm ?? null;
                            }
                        }

                        if ($targetPath && file_exists($targetPath)) {
                            if ($isStamp) {
                                // Stamps: preserve real aspect + physical size,
                                // center inside the template area. Fixes the
                                // "3cm round seal becomes 8cm oval" bug.
                                $this->renderStampWithAspect($pdf, $targetPath, $x, $y, $w, $h, $stampRealWmm, $stampRealHmm);
                            } else {
                                $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
                                $imgType = in_array($ext, ['png', 'jpg', 'jpeg']) ? strtoupper($ext) : 'PNG';
                                if ($imgType === 'JPG') $imgType = 'JPEG';
                                $pdf->Image($targetPath, $x, $y, $w, $h, $imgType);
                            }
                        }
                        continue;
                    }

                    // Text
                    $text = '';
                    if ($item['type'] === 'static') {
                        $text = $item['text'] ?? '';
                    } elseif ($item['type'] === 'db') {
                        $text = $this->resolveOfficeValue($item['key'] ?? '', $template, $targetEmployer, $targetImporter, $targetDelegate);
                    }

                    if ($text !== '' && $text !== null) {
                        $boxW = ($item['w'] / 100) * $size['width'];
                        $boxH = ($item['h'] / 100) * $size['height'];

                        if ($fontLoaded) {
                            $encodedText = @iconv('UTF-8', 'cp874', $text);
                            if ($encodedText === false) $encodedText = $text;
                        } else {
                            $encodedText = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
                        }

                        $fontSize = $item['fontSize'] ?? 12;
                        if (!empty($item['autoFit'])) {
                            $maxFontSizeH = ($boxH * 0.7) * 2.83;
                            $pdf->SetFontSize($maxFontSizeH);
                            $textWidth = $pdf->GetStringWidth($encodedText);
                            if ($textWidth > ($boxW * 0.95)) {
                                $ratio = ($boxW * 0.95) / $textWidth;
                                $fontSize = $maxFontSizeH * $ratio;
                            } else {
                                $fontSize = $maxFontSizeH;
                            }
                        }
                        $pdf->SetFontSize($fontSize);

                        $align = $item['align'] ?? 'center';
                        $textWidth = $pdf->GetStringWidth($encodedText);
                        $textX = $x;
                        if ($align === 'center') $textX = $x + ($boxW - $textWidth) / 2;
                        elseif ($align === 'right') $textX = $x + $boxW - $textWidth - 1;
                        else $textX = $x + 1;

                        $textY = $y + $boxH;
                        $pdf->Text($textX, $textY, $encodedText);
                    }
                }
            }
            $content = $pdf->Output('S');
        } finally {
            foreach ($tempSigPaths as $path) {
                if (file_exists($path)) @unlink($path);
            }
            foreach ($this->tempFiles as $tempFile) {
                if (file_exists($tempFile)) @unlink($tempFile);
            }
            $this->tempFiles = [];
        }

        return $content;
    }

    /**
     * Resolve field value WITHOUT an employee context.
     * Employee-specific keys return empty string; office entities resolve normally.
     */
    protected function resolveOfficeValue($key, PdfTemplate $template = null, ?Employer $targetEmployer = null, ?\App\Models\Importer $targetImporter = null, ?\App\Models\Delegate $targetDelegate = null)
    {
        if (!$key) return '';

        // Witness fields
        if (str_starts_with($key, 'witness_')) {
            $parts = explode('.', $key);
            if (count($parts) === 2) {
                $alias = $parts[0];
                $field = $parts[1];
                if ($template && !empty($template->meta_data[$alias . '_id'])) {
                    $witness = Witness::find($template->meta_data[$alias . '_id']);
                    if ($witness) return (string) $witness->{$field};
                }
                $gw = GlobalWitness::where('alias', $alias)->first();
                return $gw ? (string) $gw->{$field} : '';
            }
            return '';
        }

        // Employer signer 2 + employer.*
        if ($key === 'employer.signer_2_name_th') return $targetEmployer ? (string) $targetEmployer->signer_2_name_th : '';
        if ($key === 'employer.signer_2_name_en') return $targetEmployer ? (string) $targetEmployer->signer_2_name_en : '';

        if (str_starts_with($key, 'employer.address_')) {
            if (!$targetEmployer) return '';
            $address = $this->getEmployerAddress($targetEmployer);
            if ($key === 'employer.address_th') return $this->formatAddress($address, 'th');
            if ($key === 'employer.address_en') return $this->formatAddress($address, 'en');
            if (!$address) return '-';
            if (str_starts_with($key, 'employer.address_th.')) {
                $field = str_replace('employer.address_th.', '', $key);
                return (string) $address->{$field};
            }
            if (str_starts_with($key, 'employer.address_en.')) {
                $field = str_replace('employer.address_en.', '', $key);
                return (string) $address->{$field};
            }
        }

        if (str_starts_with($key, 'employer.')) {
            if (!$targetEmployer) return '';
            $subKey = substr($key, 9);
            if ($subKey === 'employerNameTh' && method_exists($targetEmployer, 'getRawOriginal')) {
                return $targetEmployer->getRawOriginal('employerNameTh') ?? '';
            }
            return (string) (data_get($targetEmployer, $subKey) ?? '');
        }

        if (str_starts_with($key, 'importer.')) {
            if (!$targetImporter) return '';
            if (str_starts_with($key, 'importer.address_')) {
                $address = $this->getGenericAddress($targetImporter);
                if ($key === 'importer.address_th') return $this->formatAddress($address, 'th');
                if ($key === 'importer.address_en') return $this->formatAddress($address, 'en');
                if (!$address) return '-';
                if (str_starts_with($key, 'importer.address_th.')) {
                    $field = str_replace('importer.address_th.', '', $key);
                    return (string) $address->{$field};
                }
                if (str_starts_with($key, 'importer.address_en.')) {
                    $field = str_replace('importer.address_en.', '', $key);
                    return (string) $address->{$field};
                }
            }
            $subKey = substr($key, 9);
            return (string) (data_get($targetImporter, $subKey) ?? '');
        }

        if (str_starts_with($key, 'delegate.')) {
            if (!$targetDelegate) return '';
            if (str_starts_with($key, 'delegate.address_')) {
                $address = $this->getGenericAddress($targetDelegate);
                if ($key === 'delegate.address_th') return $this->formatAddress($address, 'th');
                if ($key === 'delegate.address_en') return $this->formatAddress($address, 'en');
                if (!$address) return '-';
                if (str_starts_with($key, 'delegate.address_th.')) {
                    $field = str_replace('delegate.address_th.', '', $key);
                    return (string) $address->{$field};
                }
                if (str_starts_with($key, 'delegate.address_en.')) {
                    $field = str_replace('delegate.address_en.', '', $key);
                    return (string) $address->{$field};
                }
            }
            $subKey = substr($key, 9);
            return (string) (data_get($targetDelegate, $subKey) ?? '');
        }

        // Anything else → employee-related → empty
        return '';
    }

    public function generateSinglePdf(PdfTemplate $template, Employee $employee, ?Employer $targetEmployer = null, bool $useEmptyEmployer = false, ?\App\Models\Importer $targetImporter = null, ?\App\Models\Delegate $targetDelegate = null)
    {
        $pdf = new Fpdi();

        // Ensure FPDF looks in public/fonts for custom fonts
        // fontpath is protected in FPDF class, so we use Reflection to set it.
        $reflection = new \ReflectionClass($pdf);
        if ($reflection->hasProperty('fontpath')) {
            $property = $reflection->getProperty('fontpath');
            $property->setAccessible(true);
            $property->setValue($pdf, public_path('fonts') . DIRECTORY_SEPARATOR);
        }

        $templatePath = Storage::disk('public')->path($template->file_path);

        $fontLoaded = false;
        if (file_exists($this->fontPath)) {
             $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
             $pdf->SetFont('THSarabunNew', '', 14);
             $fontLoaded = true;
        } else {
             $pdf->SetFont('Arial', '', 12);
        }

        try {
            $pageCount = $pdf->setSourceFile($templatePath);
        } catch (\Exception $e) {
             try {
                $normalizedPath = $this->tryNormalizePdf($templatePath);
                if ($normalizedPath) {
                    $pageCount = $pdf->setSourceFile($normalizedPath);
                    $this->tempFiles[] = $normalizedPath;
                } else {
                    // Should not happen as tryNormalize throws on failure
                    throw $e;
                }
             } catch (\Exception $ex) {
                 // Include both original error and repair error for better debugging
                 $msg = 'Failed to process PDF template: ' . $e->getMessage();
                 $msg .= ' | Automatic repair also failed: ' . $ex->getMessage();

                 throw new \Exception($msg);
             }
        }

        // --- Determine Effective Employer ---
        $effectiveEmployer = null;
        if ($template->type === 'global') {
            if ($targetEmployer) {
                $effectiveEmployer = $targetEmployer;
            } elseif ($useEmptyEmployer) {
                $effectiveEmployer = null; // Explicitly empty
            } else {
                $effectiveEmployer = $employee->employer; // Fallback to current employer
            }
        } else {
            $effectiveEmployer = $employee->employer;
        }


        // --- Signature Logic ---
        $tempSigPaths = [];

        // 1. Employee Signature (Persistent Logic)
        $empSigPath = null;

        if ($employee->signature_path && Storage::disk('public')->exists($employee->signature_path)) {
            // Use existing persistent signature
            $empSigPath = Storage::disk('public')->path($employee->signature_path);
        } else {
            // Generate new persistent signature
            // Use a random seed for initial creation to ensure uniqueness across employees
            $seed = 'EMP-' . $employee->id . '-' . uniqid(more_entropy: true);
            $content = $this->signatureService->generate($seed);

            $filename = 'signatures/employees/emp_' . $employee->id . '_' . uniqid('', true) . '.png';
            Storage::disk('public')->put($filename, $content);
            $employee->update(['signature_path' => $filename]);

            $empSigPath = Storage::disk('public')->path($filename);
        }

        // 2. Employer Signatures (Check file -> Generate Fallback)
        $emprSig1Path = null;
        $emprSig2Path = null;
        $emprStampPath = null;
        $delegateSigPath = null;
        $importerSig1Path = null;
        $importerSig2Path = null;
        $importerStampPath = null;

        if ($targetDelegate && $targetDelegate->signature_path && Storage::disk('public')->exists($targetDelegate->signature_path)) {
            $delegateSigPath = Storage::disk('public')->path($targetDelegate->signature_path);
        }

        if ($targetImporter) {
            if ($targetImporter->signature_1_path && Storage::disk('public')->exists($targetImporter->signature_1_path)) {
                $importerSig1Path = Storage::disk('public')->path($targetImporter->signature_1_path);
            }
            if ($targetImporter->signature_2_path && Storage::disk('public')->exists($targetImporter->signature_2_path)) {
                $importerSig2Path = Storage::disk('public')->path($targetImporter->signature_2_path);
            }
            if ($targetImporter->importer_stamp_path && Storage::disk('public')->exists($targetImporter->importer_stamp_path)) {
                $importerStampPath = Storage::disk('public')->path($targetImporter->importer_stamp_path);
            }
        }

        if ($effectiveEmployer) {
            // Signer 1
            if ($effectiveEmployer->signature_1_path && Storage::disk('public')->exists($effectiveEmployer->signature_1_path)) {
                $emprSig1Path = Storage::disk('public')->path($effectiveEmployer->signature_1_path);
            } else {
                // Generate persistent unique signature
                $seed = 'EMPR-' . $effectiveEmployer->id . '-1-' . uniqid(more_entropy: true);
                $content = $this->signatureService->generate($seed);
                $filename = 'signatures/employers/empr_' . $effectiveEmployer->id . '_1_' . uniqid('', true) . '.png';
                Storage::disk('public')->put($filename, $content);

                // Update employer model
                $effectiveEmployer->update(['signature_1_path' => $filename]);
                $emprSig1Path = Storage::disk('public')->path($filename);
            }

            // Signer 2
            if ($effectiveEmployer->signature_2_path && Storage::disk('public')->exists($effectiveEmployer->signature_2_path)) {
                $emprSig2Path = Storage::disk('public')->path($effectiveEmployer->signature_2_path);
            } else {
                // Generate persistent unique signature
                $seed = 'EMPR-' . $effectiveEmployer->id . '-2-' . uniqid(more_entropy: true);
                $content = $this->signatureService->generate($seed);
                $filename = 'signatures/employers/empr_' . $effectiveEmployer->id . '_2_' . uniqid('', true) . '.png';
                Storage::disk('public')->put($filename, $content);

                // Update employer model
                $effectiveEmployer->update(['signature_2_path' => $filename]);
                $emprSig2Path = Storage::disk('public')->path($filename);
            }

            if ($effectiveEmployer->employer_stamp_path && Storage::disk('public')->exists($effectiveEmployer->employer_stamp_path)) {
                $emprStampPath = Storage::disk('public')->path($effectiveEmployer->employer_stamp_path);
            }
        }

        // 3. Witness Signatures
        // Load custom witnesses from template metadata if assigned, else fallback to random/global
        $witnessSigPaths = [];
        for ($i = 1; $i <= 4; $i++) {
            $alias = "witness_{$i}";
            $witnessId = $template->meta_data[$alias . '_id'] ?? null;
            $witness = $witnessId ? Witness::find($witnessId) : null;

            if ($witness && $witness->signature_path && Storage::disk('public')->exists($witness->signature_path)) {
                $witnessSigPaths[$alias] = Storage::disk('public')->path($witness->signature_path);
            } else {
                // Generate random temp signature for witness too
                $content = $this->signatureService->generate('WITNESS-' . $alias . '-' . uniqid());
                $temp = tempnam(sys_get_temp_dir(), 'sig_' . $alias . '_');
                file_put_contents($temp, $content);
                $witnessSigPaths[$alias] = $temp;
                $tempSigPaths[] = $temp;
            }
        }

        try {
            // Iterate Pages
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                $items = collect($template->field_mapping)->where('page', $pageNo);

                foreach ($items as $item) {
                    $x = ($item['x'] / 100) * $size['width'];
                    $y = ($item['y'] / 100) * $size['height'];

                    // --- Handle Images, Signatures & Stamps ---
                    if (isset($item['type']) && in_array($item['type'], ['image', 'signature', 'stamp'])) {
                        $w = ($item['w'] / 100) * $size['width'];
                        $h = ($item['h'] / 100) * $size['height'];

                        $targetPath = null;
                        $isStamp = false;
                        $stampRealWmm = null;
                        $stampRealHmm = null;

                        if ($item['type'] === 'image') {
                            if (!empty($item['path']) && Storage::disk('public')->exists($item['path'])) {
                                $targetPath = Storage::disk('public')->path($item['path']);
                            }
                        } elseif ($item['type'] === 'signature') {
                            $group = $item['signatureGroup'] ?? 'employee';

                            if ($group === 'employee') $targetPath = $empSigPath;
                            elseif ($group === 'employer') $targetPath = $emprSig1Path;
                            elseif ($group === 'employer_2') $targetPath = $emprSig2Path;
                            elseif ($group === 'importer_1') $targetPath = $importerSig1Path;
                            elseif ($group === 'importer_2') $targetPath = $importerSig2Path;
                            elseif ($group === 'delegate') $targetPath = $delegateSigPath;
                            elseif (str_starts_with($group, 'witness_')) $targetPath = $witnessSigPaths[$group] ?? null;
                        } elseif ($item['type'] === 'stamp') {
                            $isStamp = true;
                            $group = $item['signatureGroup'] ?? 'employer_stamp';
                            if ($group === 'importer_stamp') {
                                $targetPath = $importerStampPath;
                                $stampRealWmm = $targetImporter->importer_stamp_width_mm ?? null;
                                $stampRealHmm = $targetImporter->importer_stamp_height_mm ?? null;
                            } else {
                                $targetPath = $emprStampPath;
                                $stampRealWmm = $effectiveEmployer->employer_stamp_width_mm ?? null;
                                $stampRealHmm = $effectiveEmployer->employer_stamp_height_mm ?? null;
                            }
                        }

                        if ($targetPath && file_exists($targetPath)) {
                            if ($isStamp) {
                                $this->renderStampWithAspect($pdf, $targetPath, $x, $y, $w, $h, $stampRealWmm, $stampRealHmm);
                            } else {
                                $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
                                $imgType = in_array($ext, ['png', 'jpg', 'jpeg']) ? strtoupper($ext) : 'PNG';
                                if ($imgType === 'JPG') $imgType = 'JPEG';

                                $pdf->Image($targetPath, $x, $y, $w, $h, $imgType);
                            }
                        }
                        continue;
                    }

                    // --- Handle Text ---
                    $text = '';
                    if ($item['type'] === 'static') {
                        $text = $item['text'] ?? '';
                    } elseif ($item['type'] === 'db') {
                        $text = $this->resolveValue($employee, $item['key'], $template, $effectiveEmployer, $targetImporter, $targetDelegate);
                    }

                    if ($text) {
                        // Dimensions
                        $boxW = ($item['w'] / 100) * $size['width'];
                        $boxH = ($item['h'] / 100) * $size['height'];

                        // 1. Encoding
                        // Encode first so we can measure width accurately
                        if ($fontLoaded) {
                            $encodedText = @iconv('UTF-8', 'cp874', $text);
                            if ($encodedText === false) $encodedText = $text;
                        } else {
                            $encodedText = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
                        }

                        // 2. Font Size Logic
                        $fontSize = $item['fontSize'] ?? 12;

                        if (!empty($item['autoFit'])) {
                            // Start with a max font size that fits height comfortably (approx 70% of box height)
                            // 1mm = 2.83 pt. If box is 10mm high, max font size ~ 20pt (7mm)
                            $maxFontSizeH = ($boxH * 0.7) * 2.83;

                            // Temporarily set font to measure width
                            $pdf->SetFontSize($maxFontSizeH);
                            $textWidth = $pdf->GetStringWidth($encodedText);

                            // Check Width Constraint (95% of box width)
                            if ($textWidth > ($boxW * 0.95)) {
                                // Scale down
                                $ratio = ($boxW * 0.95) / $textWidth;
                                $fontSize = $maxFontSizeH * $ratio;
                            } else {
                                $fontSize = $maxFontSizeH;
                            }
                        }

                        $pdf->SetFontSize($fontSize);

                        // 3. Horizontal Alignment
                        $align = $item['align'] ?? 'center';
                        $textWidth = $pdf->GetStringWidth($encodedText);
                        $textX = $x;

                        if ($align === 'center') {
                            $textX = $x + ($boxW - $textWidth) / 2;
                        } elseif ($align === 'right') {
                             $textX = $x + $boxW - $textWidth - 1; // 1mm padding
                        } else {
                             // Left
                             $textX = $x + 1; // 1mm padding
                        }

                        // 4. Vertical Alignment (Bottom - Baseline)
                        // We align the BASELINE of the text to the bottom of the box ($y + $boxH).
                        // This allows Thai descenders (tails) to naturally protrude below the line,
                        // while the main character body sits exactly on the line.
                        $textY = $y + $boxH;

                        // Use Text() instead of Write() because Text() accepts baseline coordinates directly.
                        $pdf->Text($textX, $textY, $encodedText);
                    }
                }
            }
            $content = $pdf->Output('S');

        } finally {
            // Cleanup temp files
            foreach ($tempSigPaths as $path) {
                if (file_exists($path)) @unlink($path);
            }
            foreach ($this->tempFiles as $tempFile) {
                if (file_exists($tempFile)) @unlink($tempFile);
            }
            $this->tempFiles = [];
        }

        return $content;
    }

    public function generateChunkedPdf(PdfTemplate $template, Collection $employees, ?Employer $targetEmployer = null, bool $useEmptyEmployer = false, ?\App\Models\Importer $targetImporter = null, ?\App\Models\Delegate $targetDelegate = null)
    {
        $pdf = new Fpdi();

        $reflection = new \ReflectionClass($pdf);
        if ($reflection->hasProperty('fontpath')) {
            $property = $reflection->getProperty('fontpath');
            $property->setAccessible(true);
            $property->setValue($pdf, public_path('fonts') . DIRECTORY_SEPARATOR);
        }

        $templatePath = Storage::disk('public')->path($template->file_path);

        $fontLoaded = false;
        if (file_exists($this->fontPath)) {
             $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
             $pdf->SetFont('THSarabunNew', '', 14);
             $fontLoaded = true;
        } else {
             $pdf->SetFont('Arial', '', 12);
        }

        try {
            $pageCount = $pdf->setSourceFile($templatePath);
        } catch (\Exception $e) {
             try {
                $normalizedPath = $this->tryNormalizePdf($templatePath);
                if ($normalizedPath) {
                    $pageCount = $pdf->setSourceFile($normalizedPath);
                    $this->tempFiles[] = $normalizedPath;
                } else {
                    throw $e;
                }
             } catch (\Exception $ex) {
                 throw new \Exception('Failed to process PDF template: ' . $e->getMessage());
             }
        }

        // Shared Context (Employer and Witnesses from first employee in chunk)
        $firstEmployee = $employees->first();
        $effectiveEmployer = null;
        if (!$useEmptyEmployer) {
            $effectiveEmployer = $targetEmployer ?: $firstEmployee->employer;
        }

        // --- Prepare Signatures for entire Chunk ---
        $tempSigPaths = [];
        $employeeSigPaths = [];

        foreach ($employees as $emp) {
            if ($emp->signature_path && Storage::disk('public')->exists($emp->signature_path)) {
                $employeeSigPaths[$emp->id] = Storage::disk('public')->path($emp->signature_path);
            } else {
                $seed = 'EMP-' . $emp->id . '-' . uniqid(more_entropy: true);
                $content = $this->signatureService->generate($seed);
                $filename = 'signatures/employees/emp_' . $emp->id . '_' . uniqid('', true) . '.png';
                Storage::disk('public')->put($filename, $content);
                $emp->update(['signature_path' => $filename]);
                $employeeSigPaths[$emp->id] = Storage::disk('public')->path($filename);
            }
        }

        $emprSig1Path = null;
        $emprSig2Path = null;
        $emprStampPath = null;
        $delegateSigPath = null;
        $importerSig1Path = null;
        $importerSig2Path = null;
        $importerStampPath = null;

        if ($targetDelegate && $targetDelegate->signature_path && Storage::disk('public')->exists($targetDelegate->signature_path)) {
            $delegateSigPath = Storage::disk('public')->path($targetDelegate->signature_path);
        }

        if ($targetImporter) {
            if ($targetImporter->signature_1_path && Storage::disk('public')->exists($targetImporter->signature_1_path)) {
                $importerSig1Path = Storage::disk('public')->path($targetImporter->signature_1_path);
            }
            if ($targetImporter->signature_2_path && Storage::disk('public')->exists($targetImporter->signature_2_path)) {
                $importerSig2Path = Storage::disk('public')->path($targetImporter->signature_2_path);
            }
            if ($targetImporter->importer_stamp_path && Storage::disk('public')->exists($targetImporter->importer_stamp_path)) {
                $importerStampPath = Storage::disk('public')->path($targetImporter->importer_stamp_path);
            }
        }

        if ($effectiveEmployer) {
            if ($effectiveEmployer->signature_1_path && Storage::disk('public')->exists($effectiveEmployer->signature_1_path)) {
                $emprSig1Path = Storage::disk('public')->path($effectiveEmployer->signature_1_path);
            } else {
                $seed = 'EMPR-' . $effectiveEmployer->id . '-1-' . uniqid(more_entropy: true);
                $content = $this->signatureService->generate($seed);
                $filename = 'signatures/employers/empr_' . $effectiveEmployer->id . '_1_' . uniqid('', true) . '.png';
                Storage::disk('public')->put($filename, $content);
                $effectiveEmployer->update(['signature_1_path' => $filename]);
                $emprSig1Path = Storage::disk('public')->path($filename);
            }

            if ($effectiveEmployer->signature_2_path && Storage::disk('public')->exists($effectiveEmployer->signature_2_path)) {
                $emprSig2Path = Storage::disk('public')->path($effectiveEmployer->signature_2_path);
            } else {
                $seed = 'EMPR-' . $effectiveEmployer->id . '-2-' . uniqid(more_entropy: true);
                $content = $this->signatureService->generate($seed);
                $filename = 'signatures/employers/empr_' . $effectiveEmployer->id . '_2_' . uniqid('', true) . '.png';
                Storage::disk('public')->put($filename, $content);
                $effectiveEmployer->update(['signature_2_path' => $filename]);
                $emprSig2Path = Storage::disk('public')->path($filename);
            }

            if ($effectiveEmployer->employer_stamp_path && Storage::disk('public')->exists($effectiveEmployer->employer_stamp_path)) {
                $emprStampPath = Storage::disk('public')->path($effectiveEmployer->employer_stamp_path);
            }
        }

        $witnessSigPaths = [];
        $witnessFields = collect($template->field_mapping)->filter(function($item) {
            return ($item['type'] ?? '') === 'signature' && str_starts_with($item['signatureGroup'] ?? '', 'witness_');
        });

        if ($witnessFields->isNotEmpty()) {
            foreach (['witness_1', 'witness_2', 'witness_3', 'witness_4'] as $alias) {
                $witnessId = $template->meta_data[$alias . '_id'] ?? null;
                $witness = $witnessId ? Witness::find($witnessId) : null;

                if ($witness && $witness->signature_path && Storage::disk('public')->exists($witness->signature_path)) {
                    $witnessSigPaths[$alias] = Storage::disk('public')->path($witness->signature_path);
                } else {
                    $seed = 'WITNESS-' . $alias . '-' . ($effectiveEmployer ? $effectiveEmployer->id : 'global') . '-' . uniqid();
                    $temp = tempnam(sys_get_temp_dir(), 'sig_') . '.png';
                    file_put_contents($temp, $this->signatureService->generate($seed));
                    $witnessSigPaths[$alias] = $temp;
                    $tempSigPaths[] = $temp;
                }
            }
        }

        try {
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                $items = collect($template->field_mapping)->where('page', $pageNo);

                foreach ($items as $item) {
                    $x = ($item['x'] / 100) * $size['width'];
                    $y = ($item['y'] / 100) * $size['height'];

                    // Determine which employee to use based on index
                    $employeeIndex = isset($item['employeeIndex']) ? (int)$item['employeeIndex'] : 1;
                    $employee = $employees->values()->get($employeeIndex - 1); // 0-based index

                    if (!$employee && $item['type'] !== 'static' && (!isset($item['signatureGroup']) || $item['signatureGroup'] === 'employee')) {
                        // Skip if employee doesn't exist (e.g., slot 3 but only 2 employees in chunk)
                        // And skip only if it's an employee-specific field
                        continue;
                    }

                    // --- Handle Images, Signatures & Stamps ---
                    if (isset($item['type']) && in_array($item['type'], ['image', 'signature', 'stamp'])) {
                        $w = ($item['w'] / 100) * $size['width'];
                        $h = ($item['h'] / 100) * $size['height'];

                        $targetPath = null;
                        $isStamp = false;
                        $stampRealWmm = null;
                        $stampRealHmm = null;

                        if ($item['type'] === 'image') {
                            if (!empty($item['path']) && Storage::disk('public')->exists($item['path'])) {
                                $targetPath = Storage::disk('public')->path($item['path']);
                            }
                        } elseif ($item['type'] === 'signature') {
                            $group = $item['signatureGroup'] ?? 'employee';

                            if ($group === 'employee' && $employee) $targetPath = $employeeSigPaths[$employee->id] ?? null;
                            elseif ($group === 'employer') $targetPath = $emprSig1Path;
                            elseif ($group === 'employer_2') $targetPath = $emprSig2Path;
                            elseif ($group === 'importer_1') $targetPath = $importerSig1Path;
                            elseif ($group === 'importer_2') $targetPath = $importerSig2Path;
                            elseif ($group === 'delegate') $targetPath = $delegateSigPath;
                            elseif (str_starts_with($group, 'witness_')) $targetPath = $witnessSigPaths[$group] ?? null;
                        } elseif ($item['type'] === 'stamp') {
                            $isStamp = true;
                            $group = $item['signatureGroup'] ?? 'employer_stamp';
                            if ($group === 'importer_stamp') {
                                $targetPath = $importerStampPath;
                                $stampRealWmm = $targetImporter->importer_stamp_width_mm ?? null;
                                $stampRealHmm = $targetImporter->importer_stamp_height_mm ?? null;
                            } else {
                                $targetPath = $emprStampPath;
                                $stampRealWmm = $effectiveEmployer->employer_stamp_width_mm ?? null;
                                $stampRealHmm = $effectiveEmployer->employer_stamp_height_mm ?? null;
                            }
                        }

                        if ($targetPath && file_exists($targetPath)) {
                            if ($isStamp) {
                                $this->renderStampWithAspect($pdf, $targetPath, $x, $y, $w, $h, $stampRealWmm, $stampRealHmm);
                            } else {
                                $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
                                $imgType = in_array($ext, ['png', 'jpg', 'jpeg']) ? strtoupper($ext) : 'PNG';
                                if ($imgType === 'JPG') $imgType = 'JPEG';

                                $pdf->Image($targetPath, $x, $y, $w, $h, $imgType);
                            }
                        }
                        continue;
                    }

                    // --- Handle Text ---
                    $text = '';
                    if ($item['type'] === 'static') {
                        $text = $item['text'] ?? '';
                    } elseif ($item['type'] === 'db') {
                        // Fallback to first employee for non-employee specific fields to prevent errors
                        $contextEmployee = $employee ?: $firstEmployee;
                        $text = $this->resolveValue($contextEmployee, $item['key'], $template, $effectiveEmployer, $targetImporter, $targetDelegate);
                    }

                    if ($text) {
                        $boxW = ($item['w'] / 100) * $size['width'];
                        $boxH = ($item['h'] / 100) * $size['height'];

                        if ($fontLoaded) {
                            $encodedText = @iconv('UTF-8', 'cp874', $text);
                            if ($encodedText === false) $encodedText = $text;
                        } else {
                            $encodedText = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
                        }

                        $fontSize = $item['fontSize'] ?? 12;

                        if (!empty($item['autoFit'])) {
                            $maxFontSizeH = ($boxH * 0.7) * 2.83;
                            $pdf->SetFontSize($maxFontSizeH);
                            $textWidth = $pdf->GetStringWidth($encodedText);

                            if ($textWidth > ($boxW * 0.95)) {
                                $ratio = ($boxW * 0.95) / $textWidth;
                                $fontSize = $maxFontSizeH * $ratio;
                            } else {
                                $fontSize = $maxFontSizeH;
                            }
                        }

                        $pdf->SetFontSize($fontSize);

                        $align = $item['align'] ?? 'center';
                        $textWidth = $pdf->GetStringWidth($encodedText);
                        $textX = $x;

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
            }
            $content = $pdf->Output('S');

        } finally {
            foreach ($tempSigPaths as $path) {
                if (file_exists($path)) @unlink($path);
            }
            foreach ($this->tempFiles as $tempFile) {
                if (file_exists($tempFile)) @unlink($tempFile);
            }
            $this->tempFiles = [];
        }

        return $content;
    }

    public function tryNormalizePdf($inputPath)
    {
        $outputPath = tempnam(sys_get_temp_dir(), 'norm_') . '.pdf';

        // Strategy 0: Node.js (pdf-lib) - preferred strategy now
        $nodeScriptPath = base_path('scripts/normalize_pdf.cjs');
        if (file_exists($nodeScriptPath)) {

            // Check if node_modules exists
            $nodeModulesPath = base_path('node_modules/pdf-lib');

            // Only attempt Node strategy if dependencies exist
            if (file_exists($nodeModulesPath)) {
                $cmd = sprintf(
                    'node %s %s %s 2>&1',
                    escapeshellarg($nodeScriptPath),
                    escapeshellarg($inputPath),
                    escapeshellarg($outputPath)
                );

                exec($cmd, $output, $returnVar);

                if ($returnVar === 0 && file_exists($outputPath) && filesize($outputPath) > 0) {
                    return $outputPath;
                }

                Log::warning('Node.js PDF normalization failed', [
                    'cmd' => $cmd,
                    'return' => $returnVar,
                    'output' => $output
                ]);
            } else {
                Log::warning("Node.js dependency 'pdf-lib' missing. Skipping Node strategy. Please run 'npm install'.");
            }
        }

        // Fallback strategies: Ghostscript or Python
        $scriptPath = base_path('scripts/normalize_pdf.py');
        $scriptExists = file_exists($scriptPath);

        // Define strategies to try
        $strategies = [
            // Strategy 1: Ghostscript (Preferred for stability/speed if installed)
            // Supports: Windows (gswin64c, gswin32c) and Linux/Mac (gs)
            'gswin64c' => ['type' => 'gs'],
            'gswin32c' => ['type' => 'gs'],
            'gs'       => ['type' => 'gs'],

            // Strategy 2: Python (Fallback if script exists)
            // Supports: Windows (py, python) and Linux/Mac (python3, python)
            'py'       => ['type' => 'python'], // Windows launcher
            'python'   => ['type' => 'python'],
            'python3'  => ['type' => 'python'],
        ];

        $errors = [];

        foreach ($strategies as $bin => $config) {
            $cmd = '';

            if ($config['type'] === 'gs') {
                // Ghostscript command to normalize PDF to 1.4
                // -sDEVICE=pdfwrite: Use PDF writer device
                // -dCompatibilityLevel=1.4: Force version 1.4
                // -dNOPAUSE -dQUIET -dBATCH: Non-interactive modes
                $cmd = sprintf(
                    '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
                    $bin,
                    escapeshellarg($outputPath),
                    escapeshellarg($inputPath)
                );
            } elseif ($config['type'] === 'python' && $scriptExists) {
                // Python command
                $cmd = sprintf(
                    '%s %s %s %s 2>&1',
                    $bin,
                    escapeshellarg($scriptPath),
                    escapeshellarg($inputPath),
                    escapeshellarg($outputPath)
                );
            } else {
                continue; // Skip if script missing for python strategy
            }

            // Execute
            exec($cmd, $output, $returnVar);

            // Check success
            // Note: Ghostscript returns 0 on success. Python script also returns 0 on success.
            // Check if output file exists and has content.
            if ($returnVar === 0 && file_exists($outputPath) && filesize($outputPath) > 0) {
                return $outputPath;
            }

            // Collect errors for debugging if all fail
            $errorOutput = implode("\n", $output);

            // Filter out "command not found" type errors to keep logs clean
            // In Windows "is not recognized" or Linux "not found"
            if (empty($errorOutput) ||
                str_contains($errorOutput, 'is not recognized') ||
                str_contains($errorOutput, 'not found')) {
                $errors[] = "$bin: Not installed or not found in PATH.";
            } else {
                $errors[] = "$bin: Failed (Code $returnVar). Output: $errorOutput";
            }

            // Clean up potentially failed empty file
            if (file_exists($outputPath)) @unlink($outputPath);
        }

        // If we reach here, all strategies failed — ลบ temp file ที่สร้างไว้กัน orphan
        if (file_exists($outputPath)) @unlink($outputPath);

        $errorMsg = "Automatic PDF repair failed. The system attempted to convert the PDF to version 1.4 but could not find the necessary tools (Node.js, Ghostscript, or Python).\n\n" .
                    "SOLUTION: Please ensure 'node' is installed and run 'npm install' to install 'pdf-lib'. Alternatively, install Ghostscript or Python with 'pypdf'.";

        Log::error('PDF Normalization Failed', ['errors' => $errors]);

        throw new \Exception($errorMsg);
    }

    protected function resolveValue(Employee $employee, $key, PdfTemplate $template = null, ?Employer $effectiveEmployer = null, ?\App\Models\Importer $targetImporter = null, ?\App\Models\Delegate $targetDelegate = null)
    {
        // 1. Handle Witness Fields
        if (str_starts_with($key, 'witness_')) {
            // key format: witness_1.name_th
            $parts = explode('.', $key);
            if (count($parts) === 2) {
                $alias = $parts[0]; // e.g. witness_1
                $field = $parts[1]; // name_th or name_en

                // First check template-specific witness
                if ($template && !empty($template->meta_data[$alias . '_id'])) {
                    $witness = Witness::find($template->meta_data[$alias . '_id']);
                    if ($witness) {
                        return $witness->{$field};
                    }
                }

                // Fallback to legacy GlobalWitness
                $globalWitness = GlobalWitness::where('alias', $alias)->first();
                return $globalWitness ? $globalWitness->{$field} : '';
            }
        }

        // 2. Handle Employer Signer 2
        if ($key === 'employer.signer_2_name_th') return $effectiveEmployer ? $effectiveEmployer->signer_2_name_th : '';
        if ($key === 'employer.signer_2_name_en') return $effectiveEmployer ? $effectiveEmployer->signer_2_name_en : '';

        // 3. Handle Special Employer Address Fields
        if (str_starts_with($key, 'employer.address_')) {
            if (!$effectiveEmployer) return '';

            $address = $this->getEmployerAddress($effectiveEmployer);

            // Full Formatted
            if ($key === 'employer.address_th') return $this->formatAddress($address, 'th');
            if ($key === 'employer.address_en') return $this->formatAddress($address, 'en');

            if (!$address) return '-';

            // Granular Thai
            if (str_starts_with($key, 'employer.address_th.')) {
                $field = str_replace('employer.address_th.', '', $key);
                return (string) $address->{$field};
            }

             // Granular English
            if (str_starts_with($key, 'employer.address_en.')) {
                $field = str_replace('employer.address_en.', '', $key);
                return (string) $address->{$field};
            }
        }

        // 4. Handle Employer Dot Notation (Intercept for Override)
        if (str_starts_with($key, 'employer.')) {
            if (!$effectiveEmployer) return '';
            $subKey = substr($key, 9); // Remove 'employer.'
            // Use raw value for name fields to exclude name_suffix in documents
            if ($subKey === 'employerNameTh' && method_exists($effectiveEmployer, 'getRawOriginal')) {
                return $effectiveEmployer->getRawOriginal('employerNameTh') ?? '';
            }
            return data_get($effectiveEmployer, $subKey) ?? '';
        }

        // 4.1 Handle Importer Dot Notation
        if (str_starts_with($key, 'importer.')) {
            if (!$targetImporter) return '';

            if (str_starts_with($key, 'importer.address_')) {
                $address = $this->getGenericAddress($targetImporter);
                if ($key === 'importer.address_th') return $this->formatAddress($address, 'th');
                if ($key === 'importer.address_en') return $this->formatAddress($address, 'en');

                if (!$address) return '-';

                if (str_starts_with($key, 'importer.address_th.')) {
                    $field = str_replace('importer.address_th.', '', $key);
                    return (string) $address->{$field};
                }
                if (str_starts_with($key, 'importer.address_en.')) {
                    $field = str_replace('importer.address_en.', '', $key);
                    return (string) $address->{$field};
                }
            }

            $subKey = substr($key, 9); // Remove 'importer.'
            return data_get($targetImporter, $subKey) ?? '';
        }

        // 4.2 Handle Delegate Dot Notation
        if (str_starts_with($key, 'delegate.')) {
            if (!$targetDelegate) return '';

            if (str_starts_with($key, 'delegate.address_')) {
                $address = $this->getGenericAddress($targetDelegate);
                if ($key === 'delegate.address_th') return $this->formatAddress($address, 'th');
                if ($key === 'delegate.address_en') return $this->formatAddress($address, 'en');

                if (!$address) return '-';

                if (str_starts_with($key, 'delegate.address_th.')) {
                    $field = str_replace('delegate.address_th.', '', $key);
                    return (string) $address->{$field};
                }
                if (str_starts_with($key, 'delegate.address_en.')) {
                    $field = str_replace('delegate.address_en.', '', $key);
                    return (string) $address->{$field};
                }
            }

            $subKey = substr($key, 9); // Remove 'delegate.'
            return data_get($targetDelegate, $subKey) ?? '';
        }

        // 4.5 Backwards Compatibility for 'nature_of_work' mapped to 'job_description'
        if ($key === 'nature_of_work') {
            $key = 'job_description';
        }

        // Custom Date Formatting: Employee DOB (CE + BE + Thai month variants)
        if (str_starts_with($key, 'employeeDob_')) {
            if ($employee->employeeDob instanceof Carbon) {
                $d = $employee->employeeDob;
                if ($key === 'employeeDob_day') return $d->format('d');
                if ($key === 'employeeDob_month_en') return strtoupper($d->format('M'));
                if ($key === 'employeeDob_month_th') return self::$thaiMonthShort[$d->month] ?? '';
                if ($key === 'employeeDob_year_ce') return $d->format('Y');
                // BE = CE + 543 (Buddhist calendar)
                if ($key === 'employeeDob_year_be') return (string) ($d->year + 543);
                if ($key === 'employeeDob_be') return $d->format('d/m/') . ($d->year + 543);
                // Thai text formats (e.g. "15 ม.ค. 2536" or "15 ม.ค. 1993")
                if ($key === 'employeeDob_th_be') return $this->formatThaiDate($d, 543);
                if ($key === 'employeeDob_th_ce') return $this->formatThaiDate($d, 0);
                // English text formats (day + EN month abbrev + year)
                if ($key === 'employeeDob_en_be') return $this->formatEnglishDate($d, 543);
                if ($key === 'employeeDob_en_ce') return $this->formatEnglishDate($d, 0);
            }
            return '';
        }

        // Custom Date Formatting: Passport Issue Date
        // NOTE: DB column is passport_issue_date (snake_case) but the template
        // key is passportIssueDate (camelCase). data_get() below can't bridge
        // that so the raw key must be intercepted explicitly here.
        if ($key === 'passportIssueDate') {
            return $employee->passport_issue_date instanceof Carbon
                ? $employee->passport_issue_date->format('d/m/Y')
                : '';
        }
        if (str_starts_with($key, 'passportIssueDate_')) {
            if ($employee->passport_issue_date instanceof Carbon) {
                $d = $employee->passport_issue_date;
                if ($key === 'passportIssueDate_day') return $d->format('d');
                if ($key === 'passportIssueDate_month_en') return strtoupper($d->format('M'));
                if ($key === 'passportIssueDate_month_th') return self::$thaiMonthShort[$d->month] ?? '';
                if ($key === 'passportIssueDate_year_ce') return $d->format('Y');
                if ($key === 'passportIssueDate_year_be') return (string) ($d->year + 543);
                if ($key === 'passportIssueDate_be') return $d->format('d/m/') . ($d->year + 543);
                if ($key === 'passportIssueDate_th_be') return $this->formatThaiDate($d, 543);
                if ($key === 'passportIssueDate_th_ce') return $this->formatThaiDate($d, 0);
                if ($key === 'passportIssueDate_en_be') return $this->formatEnglishDate($d, 543);
                if ($key === 'passportIssueDate_en_ce') return $this->formatEnglishDate($d, 0);
            }
            return '';
        }

        // Document Creation Date — dynamic, resolved at PDF generation time.
        // Only emits a value when the template actually uses one of these keys;
        // an unused key stays absent (no accidental "today's date" on unrelated docs).
        if (str_starts_with($key, 'document_created_date')) {
            $now = now();
            if ($key === 'document_created_date') return $now->format('d/m/Y');
            if ($key === 'document_created_date_be') return $now->format('d/m/') . ($now->year + 543);
            if ($key === 'document_created_date_day') return $now->format('d');
            if ($key === 'document_created_date_month_en') return strtoupper($now->format('M'));
            if ($key === 'document_created_date_month_th') return self::$thaiMonthShort[$now->month] ?? '';
            if ($key === 'document_created_date_year_ce') return $now->format('Y');
            if ($key === 'document_created_date_year_be') return (string) ($now->year + 543);
            if ($key === 'document_created_date_th_be') return $this->formatThaiDate($now, 543);
            if ($key === 'document_created_date_th_ce') return $this->formatThaiDate($now, 0);
            if ($key === 'document_created_date_en_be') return $this->formatEnglishDate($now, 543);
            if ($key === 'document_created_date_en_ce') return $this->formatEnglishDate($now, 0);
            return '';
        }

        // Gender — template historically uses key `employeeGender` but the
        // model exposes an accessor called `gender` (not a column). Without
        // this intercept, data_get() below returns null for that key.
        if ($key === 'employeeGender') {
            return $employee->gender ?? '';
        }

        // Custom Date Formatting: Passport Expiry Date
        if (str_starts_with($key, 'passportExpiryDate_')) {
            if ($employee->passportExpiryDate instanceof Carbon) {
                $d = $employee->passportExpiryDate;
                if ($key === 'passportExpiryDate_day') return $d->format('d');
                if ($key === 'passportExpiryDate_month_en') return strtoupper($d->format('M'));
                if ($key === 'passportExpiryDate_month_th') return self::$thaiMonthShort[$d->month] ?? '';
                if ($key === 'passportExpiryDate_year_ce') return $d->format('Y');
                if ($key === 'passportExpiryDate_year_be') return (string) ($d->year + 543);
                if ($key === 'passportExpiryDate_be') return $d->format('d/m/') . ($d->year + 543);
                if ($key === 'passportExpiryDate_th_be') return $this->formatThaiDate($d, 543);
                if ($key === 'passportExpiryDate_th_ce') return $this->formatThaiDate($d, 0);
                if ($key === 'passportExpiryDate_en_be') return $this->formatEnglishDate($d, 543);
                if ($key === 'passportExpiryDate_en_ce') return $this->formatEnglishDate($d, 0);
            }
            return '';
        }

        // 5. Handle Standard Employee Fields
        // Use raw value for name fields to exclude name_suffix in documents
        if ($key === 'employeeNameEn' && method_exists($employee, 'getRawOriginal')) {
            $value = $employee->getRawOriginal('employeeNameEn');
        } else {
            $value = data_get($employee, $key);
        }

        // 6. Auto-Prefix Logic (NEW)
        if ($template && !empty($template->meta_data['auto_prefix_titles'])) {
            $value = $this->applyAutoPrefix($employee, $key, $value);
        }

        // 7. Formatting
        if ($value instanceof Carbon) {
            return $value->format('d/m/Y');
        }

        return (string) $value;
    }

    protected function getEmployerAddress($employer)
    {
        // Priority 1: Address marked as document address
        $address = $employer->addresses()->where('is_document_address', true)->first();

        // Priority 2: First created address (fallback)
        if (!$address) {
            $address = $employer->addresses()->orderBy('id', 'asc')->first();
        }

        return $address;
    }

    protected function getGenericAddress($model)
    {
        // For models like Importer and Delegate, they might not have is_document_address widely used yet,
        // but we'll check it anyway, then fallback to first address.
        $address = $model->addresses()->where('is_document_address', true)->first();
        if (!$address) {
            $address = $model->addresses()->orderBy('id', 'asc')->first();
        }
        return $address;
    }

    protected function applyAutoPrefix(Employee $employee, $key, $value)
    {
        // Only apply to name fields
        if (!in_array($key, ['employeeNameTh', 'employeeNameEn'])) {
            return $value;
        }

        // Get Gender via Title or Explicit Gender
        // Heuristic: If Title contains 'นาย', it's male. 'นาง', 'นางสาว' is female.
        $titleTh = $employee->employeeTitleTh;
        $isMale = str_contains($titleTh, 'นาย') || strtolower($employee->employeeGender) === 'male' || strtolower($employee->employeeGender) === 'ชาย';
        $isFemale = str_contains($titleTh, 'นาง') || strtolower($employee->employeeGender) === 'female' || strtolower($employee->employeeGender) === 'หญิง';

        // Thai Name
        if ($key === 'employeeNameTh') {
            // Check if already prefixed
            $prefixes = ['นาย', 'นาง', 'นางสาว', 'ด.ช.', 'ด.ญ.'];
            $alreadyPrefixed = false;
            foreach ($prefixes as $prefix) {
                if (str_starts_with($value, $prefix)) {
                    $alreadyPrefixed = true;
                    break;
                }
            }

            if (!$alreadyPrefixed) {
                if ($isMale) return 'นาย ' . $value;
                if ($isFemale) {
                    // Default to Mrs (Nang) or Miss (Nang Sao)? Use title if available, else guess.
                    // If title is explicitly 'นางสาว', use it.
                    if (str_contains($titleTh, 'นางสาว')) return 'นางสาว ' . $value;
                    if (str_contains($titleTh, 'นาง')) return 'นาง ' . $value;
                    // Fallback default
                    return 'นาง ' . $value;
                }
            }
        }

        // English Name
        if ($key === 'employeeNameEn') {
            $prefixes = ['Mr.', 'Mrs.', 'Ms.', 'Miss'];
            $alreadyPrefixed = false;
            foreach ($prefixes as $prefix) {
                // Check case-insensitive
                if (stripos($value, $prefix) === 0) {
                    $alreadyPrefixed = true;
                    break;
                }
            }

            if (!$alreadyPrefixed) {
                if ($isMale) return 'Mr. ' . $value;
                if ($isFemale) {
                     // Check EN title if exists
                     $titleEn = $employee->employeeTitleEn ?? '';
                     if (stripos($titleEn, 'Miss') !== false) return 'Miss ' . $value;
                     if (stripos($titleEn, 'Mrs') !== false) return 'Mrs. ' . $value;
                     return 'Ms. ' . $value;
                }
            }
        }

        return $value;
    }

    protected function formatAddress($address, $lang = 'th')
    {
        if (!$address) return '-';

        if ($lang === 'th') {
            $parts = array_filter([
                $address->addrNo,
                $address->addrMoo ? "หมู่ " . $address->addrMoo : null,
                $address->addrSoi ? "ซอย " . $address->addrSoi : null,
                $address->addrRoad ? "ถนน " . $address->addrRoad : null,
                $address->addrSubDistrict ? "ต." . $address->addrSubDistrict : null,
                $address->addrDistrict ? "อ." . $address->addrDistrict : null,
                $address->addrProvince ? "จ." . $address->addrProvince : null,
                $address->addrZipCode
            ]);
            return implode(' ', $parts);
        } else {
            $parts = array_filter([
                $address->addrNoEn,
                $address->addrMooEn ? "Moo " . $address->addrMooEn : null,
                $address->addrSoiEn ? "Soi " . $address->addrSoiEn : null,
                $address->addrRoadEn ? "Road " . $address->addrRoadEn : null,
                $address->addrSubDistrictEn,
                $address->addrDistrictEn,
                $address->addrProvinceEn,
                $address->addrZipCodeEn
            ]);
            return implode(', ', $parts);
        }
    }

    protected function saveToSlot(Employee $employee, $content, $slotName, PdfTemplate $template = null)
    {
        // 1. Determine Path and Model logic similar to the old Batch Job
        // This ensures the files go where the UI expects them (e.g. employee_files/{id})

        $filePath = null;

        if (str_starts_with($slotName, 'employee_doc_')) {
            // Standard Employee Documents
            // Path: employee_files/{id}/{slotName}_{timestamp}.pdf
            $filePath = 'employee_files/' . $employee->id . '/' . $slotName . '_' . uniqid('', true) . '.pdf';
            Storage::disk('public')->put($filePath, $content);

            // Update Employee Record
            $employee->update([$slotName => $filePath]);

            // Update Description if applicable (slots 9-18 map to other_doc_1..10)
            if ($template && preg_match('/employee_doc_(\d+)/', $slotName, $matches)) {
                $index = (int)$matches[1];
                if ($index >= 9 && $index <= 18) {
                    $descIndex = $index - 8;
                    $employee->update(["other_doc_{$descIndex}_desc" => $template->name]);
                }
            }

        } elseif (str_starts_with($slotName, 'employer_doc_other_')) {
            // Employer Documents (attached via Employee context)
            if ($employee->employer) {
                $employer = $employee->employer;
                $filePath = 'employer_documents/' . $employer->id . '/' . $slotName . '_' . $employee->id . '_' . uniqid('', true) . '.pdf';
                Storage::disk('public')->put($filePath, $content);

                $employer->update([$slotName => $filePath]);

                if ($template && preg_match('/employer_doc_other_(\d+)/', $slotName, $matches)) {
                    $index = $matches[1];
                    $employer->update(["employer_doc_other_{$index}_desc" => "Auto: " . $employee->employeeNameEn . " - " . $template->name]);
                }
            }
        } else {
            // Fallback for unknown slots (e.g. generated/) - mostly for audit history
            $filePath = 'generated/' . $employee->id . '/' . Str::slug($slotName) . '_' . uniqid('', true) . '.pdf';
            Storage::disk('public')->put($filePath, $content);
        }

        // 2. Also Create Log Entry in EmployeeGeneratedDocument (New System)
        // This keeps the audit log intact while ensuring the UI (Old System) works.
        $doc = \App\Models\EmployeeGeneratedDocument::updateOrCreate(
            ['employee_id' => $employee->id, 'document_name' => $slotName],
            ['file_path' => $filePath, 'generated_at' => now(), 'created_by' => auth()->id() ?? 0]
        );

        return $doc;
    }

    public function generateFilename(PdfTemplate $template, Employee $employee)
    {
        // Use Thai name if available (raw value without suffix for clean filenames)
        $employeeName = $employee->employeeNameTh ?: ($employee->getRawOriginal('employeeNameEn') ?: 'Unknown');

        // Construct descriptive filename: [TemplateName]_[EmployeeName]_[EmployeeID].pdf
        $filename = "{$template->name}_{$employeeName}_{$employee->id}.pdf";

        // Sanitize (allow Thai characters, remove system reserved chars)
        $filename = preg_replace('~[\\\\/:*?"<>|]~', '_', $filename);

        return $filename;
    }
}
