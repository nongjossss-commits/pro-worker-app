<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PdfTemplate;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
                    // Legacy support (though controller now handles saving directly)
                    $this->saveToSlot($employee, $pdfContent, $options['slot_name']);
                    $results[] = ['employee' => $employee->id, 'status' => 'saved'];
                } elseif ($outputType === 'raw_content') {
                    $results[] = [
                        'employee_id' => $employee->id,
                        'filename' => $filename,
                        'content' => $pdfContent
                    ];
                } else {
                    // Download
                    $results[] = ['filename' => $filename, 'content' => $pdfContent];
                }
            } catch (\Exception $e) {
                // If one employee fails, we can either abort everything or skip.
                // Aborting ensures the user knows something went wrong.
                // However, with clearer error messages, we can improve this loop.
                // For now, let's rethrow with context.
                throw new \Exception("Error processing employee {$employee->employeeNameEn} (ID: {$employee->id}): " . $e->getMessage());
            }
        }

        return $results;
    }

    protected function generateSinglePdf(PdfTemplate $template, Employee $employee)
    {
        $pdf = new Fpdi();

        // Load the template file
        $templatePath = Storage::disk('public')->path($template->file_path);

        // Font Handling
        $fontLoaded = false;
        if (file_exists($this->fontPath)) {
             $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
             $pdf->SetFont('THSarabunNew', '', 14);
             $fontLoaded = true;
        } else {
             $pdf->SetFont('Arial', '', 12);
        }

        try {
            try {
                $pageCount = $pdf->setSourceFile($templatePath);
            } catch (\setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException $e) {
                // Try to normalize the PDF using Python script
                $normalizedPath = $this->tryNormalizePdf($templatePath);
                if ($normalizedPath) {
                    try {
                        $pageCount = $pdf->setSourceFile($normalizedPath);
                        $this->tempFiles[] = $normalizedPath; // Mark for deletion
                    } catch (\Exception $ex) {
                        throw new \Exception('Automatic PDF repair failed. Please use "Print to PDF". Original error: ' . $e->getMessage());
                    }
                } else {
                    throw new \Exception('This PDF file uses an unsupported compression format (likely PDF 1.5+). Please open the file in a PDF viewer, choose "Print to PDF", and try uploading the new file.');
                }
            } catch (\Exception $e) {
                throw new \Exception('Failed to process PDF template: ' . $e->getMessage());
            }

            // Generate Signatures for this session (Consistent per person)
            $employeeSignature = $this->signatureService->generate('EMP-' . $employee->id);

            // Employer Signature: Use Employer ID.
            $employerSignature = $this->signatureService->generate('EMPR-' . $employee->employer_id);

            // Save temp files for FPDF to read
            $tempEmpSigPath = tempnam(sys_get_temp_dir(), 'sig_emp_');
            file_put_contents($tempEmpSigPath, $employeeSignature);

            $tempEmprSigPath = tempnam(sys_get_temp_dir(), 'sig_empr_');
            file_put_contents($tempEmprSigPath, $employerSignature);

            // Iterate Pages
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                // Filter items for this page
                $items = collect($template->field_mapping)->where('page', $pageNo);

                foreach ($items as $item) {
                    $x = ($item['x'] / 100) * $size['width'];
                    $y = ($item['y'] / 100) * $size['height'];

                    // Handle Signatures
                    if (isset($item['type']) && $item['type'] === 'signature') {
                        $w = ($item['w'] / 100) * $size['width'];
                        $h = ($item['h'] / 100) * $size['height'];

                        $sigPath = ($item['signatureGroup'] ?? 'employee') === 'employer'
                                   ? $tempEmprSigPath
                                   : $tempEmpSigPath;

                        // Place image
                        $pdf->Image($sigPath, $x, $y, $w, $h, 'PNG');
                        continue;
                    }

                    // Handle Text
                    $text = '';
                    if ($item['type'] === 'static') {
                        $text = $item['text'] ?? '';
                    } elseif ($item['type'] === 'db') {
                        $text = $this->resolveValue($employee, $item['key']);
                    }

                    if ($text) {
                        $pdf->SetXY($x, $y);

                        // Font Size Handling
                        $fontSize = $item['fontSize'] ?? 12;
                        $pdf->SetFontSize($fontSize);

                        if ($fontLoaded) {
                            $converted = @iconv('UTF-8', 'cp874', $text);
                            if ($converted !== false) {
                                $text = $converted;
                            }
                        } else {
                            $text = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
                        }

                        $pdf->Write(0, $text);
                    }
                }
            }

            // Output PDF content
            $content = $pdf->Output('S');

        } finally {
            // Cleanup temp files (Signatures)
            if (isset($tempEmpSigPath) && file_exists($tempEmpSigPath)) @unlink($tempEmpSigPath);
            if (isset($tempEmprSigPath) && file_exists($tempEmprSigPath)) @unlink($tempEmprSigPath);

            // Cleanup normalized PDF files
            foreach ($this->tempFiles as $tempFile) {
                if (file_exists($tempFile)) @unlink($tempFile);
            }
            $this->tempFiles = []; // Reset for next call
        }

        return $content;
    }

    protected function tryNormalizePdf($inputPath)
    {
        $outputPath = tempnam(sys_get_temp_dir(), 'norm_') . '.pdf';
        $scriptPath = base_path('scripts/normalize_pdf.py');

        // Check if script exists
        if (!file_exists($scriptPath)) {
            return false;
        }

        // Construct command
        $cmd = "python3 " . escapeshellarg($scriptPath) . " " . escapeshellarg($inputPath) . " " . escapeshellarg($outputPath) . " 2>&1";

        exec($cmd, $output, $returnVar);

        if ($returnVar === 0 && file_exists($outputPath) && filesize($outputPath) > 0) {
            return $outputPath;
        }

        $errorOutput = implode("\n", $output);
        \Illuminate\Support\Facades\Log::error('PDF Normalization Failed', [
            'command' => $cmd,
            'output' => $output,
            'return_var' => $returnVar
        ]);

        // Check for common errors to provide a better hint
        if (str_contains($errorOutput, 'ModuleNotFoundError')) {
            throw new \Exception('PDF Repair failed: Missing Python dependency. Please install pypdf (pip install pypdf). Detail: ' . $errorOutput);
        }

        return false;
    }

    protected function resolveValue(Employee $employee, $key)
    {
        // 1. Handle Special Employer Address Fields
        if ($key === 'employer.address_th') {
            return $this->formatAddress($employee->employer->addresses->first(), 'th');
        }
        if ($key === 'employer.address_en') {
            return $this->formatAddress($employee->employer->addresses->first(), 'en');
        }

        // 2. Handle Standard Dot Notation
        $value = data_get($employee, $key);

        // 3. Formatting
        if ($value instanceof Carbon) {
            return $value->format('d/m/Y');
        }

        return (string) $value;
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

    protected function saveToSlot(Employee $employee, $content, $slotName)
    {
        // This is kept for legacy compatibility but is largely bypassed by 'raw_content' option
        $filename = 'generated/' . $employee->id . '/' . Str::slug($slotName) . '_' . time() . '.pdf';

        Storage::disk('public')->put($filename, $content);

        $doc = \App\Models\EmployeeGeneratedDocument::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'document_name' => $slotName
            ],
            [
                'file_path' => $filename,
                'generated_at' => now(),
                'created_by' => auth()->id() ?? 0,
            ]
        );

        return $doc;
    }

    protected function generateFilename(PdfTemplate $template, Employee $employee)
    {
        return Str::slug($template->name . '-' . $employee->employeeNameEn) . '.pdf';
    }
}
