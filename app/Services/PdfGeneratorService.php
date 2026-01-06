<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PdfTemplate;
use App\Models\GlobalWitness;
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

    public function __construct(SignatureGeneratorService $signatureService)
    {
        // Path to Thai font (Assumes font files exist in public/fonts)
        $this->fontPath = public_path('fonts/THSarabunNew.php');
        $this->signatureService = $signatureService;
    }

    public function generateForEmployees(PdfTemplate $template, Collection $employees, $options = [])
    {
        // Options: 'output_type' => 'download' | 'save_to_slot' | 'raw_content'
        //          'slot_name' => string (required if save_to_slot)

        $outputType = $options['output_type'] ?? 'download';
        $results = [];

        foreach ($employees as $employee) {
            try {
                $pdfContent = $this->generateSinglePdf($template, $employee);
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
                // If this is a batch process (save_to_slot or raw_content might be batch),
                // we want to catch individual errors so the whole batch doesn't fail.
                // However, if it's a synchronous single download, we might want to rethrow?
                // For now, consistent behavior: log and maybe return error status.
                // But the Controller expects simple array.
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
                // If simple download, maybe throwing is better to alert user?
                // For now, rethrow to ensure synchronous errors are seen.
                if ($outputType === 'download') {
                    throw $e;
                }
            }
        }

        return $results;
    }

    public function generateSinglePdf(PdfTemplate $template, Employee $employee)
    {
        $pdf = new Fpdi();
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

        // --- Signature Logic ---
        $tempSigPaths = [];

        // 1. Employee Signature (Persist Check)
        // Per user request: Signatures must be random every time for documents.
        // We will ALWAYS generate a new one here if we want per-document uniqueness,
        // BUT the legacy logic was: Check if exists -> Use it.
        // To fulfill "Random New Every Time for Employees creating documents", we should regenerate it
        // OR simply generate a temp one for this PDF without saving it to the profile.
        // DECISION: Generate a unique temp signature for this PDF instance. Do not overwrite profile unless empty.

        $empSigPath = null;
        // Option A: Use existing if set? User said "Random new every time".
        // This implies ignoring the saved profile signature for the document generation.
        // Let's generate a fresh one for the PDF.
        $seed = 'EMP-' . $employee->id . '-' . uniqid(more_entropy: true);
        $content = $this->signatureService->generate($seed);
        $temp = tempnam(sys_get_temp_dir(), 'sig_emp_');
        file_put_contents($temp, $content);
        $empSigPath = $temp;
        $tempSigPaths[] = $temp;

        // Also update the profile if it was empty, so they have *something*
        if (!$employee->signature_path || !Storage::disk('public')->exists($employee->signature_path)) {
             $filename = 'signatures/employees/emp_' . $employee->id . '_' . time() . '.png';
             Storage::disk('public')->put($filename, $content);
             $employee->update(['signature_path' => $filename]);
        }

        // 2. Employer Signatures (Check file -> Generate Fallback)
        $employer = $employee->employer;

        // Signer 1
        $emprSig1Path = null;
        if ($employer->signature_1_path && Storage::disk('public')->exists($employer->signature_1_path)) {
            $emprSig1Path = Storage::disk('public')->path($employer->signature_1_path);
        } else {
             // Generate temporary unique
             $content = $this->signatureService->generate('EMPR-' . $employer->id . '-1-' . uniqid());
             $temp = tempnam(sys_get_temp_dir(), 'sig_empr1_');
             file_put_contents($temp, $content);
             $emprSig1Path = $temp;
             $tempSigPaths[] = $temp;
        }

        // Signer 2
        $emprSig2Path = null;
        if ($employer->signature_2_path && Storage::disk('public')->exists($employer->signature_2_path)) {
            $emprSig2Path = Storage::disk('public')->path($employer->signature_2_path);
        } else {
             $content = $this->signatureService->generate('EMPR-' . $employer->id . '-2-' . uniqid());
             $temp = tempnam(sys_get_temp_dir(), 'sig_empr2_');
             file_put_contents($temp, $content);
             $emprSig2Path = $temp;
             $tempSigPaths[] = $temp;
        }

        // 3. Witness Signatures
        // Pre-load all global witnesses
        $globalWitnesses = GlobalWitness::all()->keyBy('alias'); // alias: witness_1, witness_2...

        $witnessSigPaths = [];
        for ($i = 1; $i <= 4; $i++) {
            $alias = "witness_{$i}";
            $witness = $globalWitnesses->get($alias);

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

                    // --- Handle Signatures ---
                    if (isset($item['type']) && $item['type'] === 'signature') {
                        $w = ($item['w'] / 100) * $size['width'];
                        $h = ($item['h'] / 100) * $size['height'];

                        $group = $item['signatureGroup'] ?? 'employee';
                        $targetPath = null;

                        if ($group === 'employee') $targetPath = $empSigPath;
                        elseif ($group === 'employer') $targetPath = $emprSig1Path;
                        elseif ($group === 'employer_2') $targetPath = $emprSig2Path;
                        elseif (str_starts_with($group, 'witness_')) $targetPath = $witnessSigPaths[$group] ?? null;

                        if ($targetPath && file_exists($targetPath)) {
                            // FPDF Image supports PNG/JPG. If path is real, it works.
                            // If temp, it works.
                            $pdf->Image($targetPath, $x, $y, $w, $h, 'PNG');
                        }
                        continue;
                    }

                    // --- Handle Text ---
                    $text = '';
                    if ($item['type'] === 'static') {
                        $text = $item['text'] ?? '';
                    } elseif ($item['type'] === 'db') {
                        $text = $this->resolveValue($employee, $item['key'], $template);
                    }

                    if ($text) {
                        // Font Size & Positioning Logic
                        // Default size
                        $fontSize = $item['fontSize'] ?? 12;

                        // 1. Check Auto-Fit (Fit to Height)
                        if (!empty($item['autoFit'])) {
                            $boxH = ($item['h'] / 100) * $size['height'];
                            // Conversion: 1 pt = 1/72 inch. 1 unit ~ 1mm (approx in FPDF default).
                            // A rough heuristic: Font size (pt) ~ Box Height (mm) * 2
                            // But accurate math depends on PDF unit. Assuming mm (default FPDF).
                            // 14pt font ~= 5mm height visually.
                            // So $fontSize = $boxH * 2.8;
                            $fontSize = $boxH * 2.5;
                        }

                        $pdf->SetFontSize($fontSize);

                        // 2. Encoding
                        if ($fontLoaded) {
                            $encodedText = @iconv('UTF-8', 'cp874', $text);
                            if ($encodedText === false) $encodedText = $text;
                        } else {
                            $encodedText = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
                        }

                        // 3. Alignment (Center vs Left)
                        // Requirement: Default to center, but allow overrides.
                        $boxW = ($item['w'] / 100) * $size['width'];
                        $align = $item['align'] ?? 'center'; // Default changed to center as per user request
                        $textX = $x;

                        if ($align === 'center') {
                            $textWidth = $pdf->GetStringWidth($encodedText);
                            // Center in box: X + (BoxW - TextW) / 2
                            $textX = $x + ($boxW - $textWidth) / 2;
                        }

                        // 4. Vertical Alignment (Bottom Anchor)
                        // Requirement: Anchor text to the bottom edge of the box so users can align it precisely.
                        // Standard FPDF Write() prints text *below* the current Y position.
                        // To align text so its baseline is near the bottom of the box, we need to set the Y position
                        // such that (Y + LineHeight) ≈ BoxBottom.
                        // Note: $fontSize is in points (pt), coordinates are usually in user units (often mm).
                        // 1 pt = 0.3528 mm.

                        $boxH = ($item['h'] / 100) * $size['height'];
                        $bottomY = $y + $boxH;

                        // Calculate appropriate Line Height (usually ~1.2x font size)
                        // Converting font size (pt) to user units (approx) for calculation
                        // Assuming 1 unit = 1mm for standard FPDF.
                        $fontSizeInUnits = $fontSize / 2.83; // 2.83 pts per mm
                        $lineHeight = $fontSizeInUnits * 1.0;

                        // We want the text to sit on the bottom line.
                        // If we Write at $textY, the text appears in the band [$textY, $textY + $lineHeight].
                        // So we want $textY + $lineHeight = $bottomY.
                        // Thus $textY = $bottomY - $lineHeight.

                        // Adding a tiny padding (0.5mm) so it doesn't touch the line exactly
                        $textY = $bottomY - $lineHeight - 0.5;

                        $pdf->SetXY($textX, $textY);
                        $pdf->Write(0, $encodedText);
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

    public function tryNormalizePdf($inputPath)
    {
        $outputPath = tempnam(sys_get_temp_dir(), 'norm_') . '.pdf';

        // Strategy 0: Node.js (pdf-lib) - preferred strategy now
        $nodeScriptPath = base_path('scripts/normalize_pdf.js');
        if (file_exists($nodeScriptPath)) {
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

        // If we reach here, all strategies failed
        $errorMsg = "Automatic PDF repair failed. The system attempted to convert the PDF to version 1.4 but could not find the necessary tools (Node.js, Ghostscript, or Python).\n\n" .
                    "SOLUTION: Please ensure 'node' is installed and 'pdf-lib' is added to package.json.";

        Log::error('PDF Normalization Failed', ['errors' => $errors]);

        throw new \Exception($errorMsg);
    }

    protected function resolveValue(Employee $employee, $key, PdfTemplate $template = null)
    {
        // 1. Handle Witness Fields
        if (str_starts_with($key, 'witness_')) {
            // key format: witness_1.name_th
            $parts = explode('.', $key);
            if (count($parts) === 2) {
                $alias = $parts[0];
                $field = $parts[1]; // name_th or name_en
                $witness = GlobalWitness::where('alias', $alias)->first();
                return $witness ? $witness->{$field} : '';
            }
        }

        // 2. Handle Employer Signer 2
        if ($key === 'employer.signer_2_name_th') return $employee->employer->signer_2_name_th;
        if ($key === 'employer.signer_2_name_en') return $employee->employer->signer_2_name_en;

        // 3. Handle Special Employer Address Fields
        if ($key === 'employer.address_th') {
            return $this->formatAddress($employee->employer->addresses->first(), 'th');
        }
        if ($key === 'employer.address_en') {
            return $this->formatAddress($employee->employer->addresses->first(), 'en');
        }

        // 4. Handle Standard Dot Notation
        $value = data_get($employee, $key);

        // 5. Auto-Prefix Logic (NEW)
        if ($template && !empty($template->meta_data['auto_prefix_titles'])) {
            $value = $this->applyAutoPrefix($employee, $key, $value);
        }

        // 6. Formatting
        if ($value instanceof Carbon) {
            return $value->format('d/m/Y');
        }

        return (string) $value;
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
            $filePath = 'employee_files/' . $employee->id . '/' . $slotName . '_' . time() . '.pdf';
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
                $filePath = 'employer_documents/' . $employer->id . '/' . $slotName . '_' . $employee->id . '_' . time() . '.pdf';
                Storage::disk('public')->put($filePath, $content);

                $employer->update([$slotName => $filePath]);

                if ($template && preg_match('/employer_doc_other_(\d+)/', $slotName, $matches)) {
                    $index = $matches[1];
                    $employer->update(["employer_doc_other_{$index}_desc" => "Auto: " . $employee->employeeNameEn . " - " . $template->name]);
                }
            }
        } else {
            // Fallback for unknown slots (e.g. generated/) - mostly for audit history
            $filePath = 'generated/' . $employee->id . '/' . Str::slug($slotName) . '_' . time() . '.pdf';
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
        // Fixed: Append Employee ID to ensure uniqueness for duplicate names
        return Str::slug($template->name . '-' . $employee->employeeNameEn . '-' . $employee->id) . '.pdf';
    }
}
