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

    public function __construct(SignatureGeneratorService $signatureService)
    {
        // Path to Thai font (Assumes font files exist in public/fonts)
        $this->fontPath = public_path('fonts/THSarabunNew.php');
        $this->signatureService = $signatureService;
    }

    public function generateForEmployees(PdfTemplate $template, Collection $employees, $options = [])
    {
        // Options: 'output_type' => 'download' | 'save_to_slot'
        //          'slot_name' => string (required if save_to_slot)

        $outputType = $options['output_type'] ?? 'download';
        $results = [];

        foreach ($employees as $employee) {
            $pdfContent = $this->generateSinglePdf($template, $employee);

            if ($outputType === 'save_to_slot') {
                $this->saveToSlot($employee, $pdfContent, $options['slot_name']);
                $results[] = ['employee' => $employee->id, 'status' => 'saved'];
            } else {
                $filename = $this->generateFilename($template, $employee);
                $results[] = ['filename' => $filename, 'content' => $pdfContent];
            }
        }

        return $results;
    }

    protected function generateSinglePdf(PdfTemplate $template, Employee $employee)
    {
        $pdf = new Fpdi();

        // Load the template file
        $templatePath = Storage::disk('public')->path($template->file_path);

        try {
            $pageCount = $pdf->setSourceFile($templatePath);
        } catch (\setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException $e) {
            throw new \Exception('This PDF file uses an unsupported compression format (likely PDF 1.5+). Please open the file in a PDF viewer, choose "Print to PDF", and try uploading the new file.');
        } catch (\Exception $e) {
            throw new \Exception('Failed to process PDF template: ' . $e->getMessage());
        }

        // Font Handling
        $fontLoaded = false;
        if (file_exists($this->fontPath)) {
             $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
             $pdf->SetFont('THSarabunNew', '', 14);
             $fontLoaded = true;
        } else {
             $pdf->SetFont('Arial', '', 12);
        }

        // Generate Signatures for this session (Consistent per person)
        $employeeSignature = $this->signatureService->generate('EMP-' . $employee->id);

        // Employer Signature: Use Employer ID.
        // Note: In real app, we might check if employer has a REAL uploaded signature first.
        // For now, we generate one procedurally for consistency.
        $employerSignature = $this->signatureService->generate('EMPR-' . $employee->employer_id);

        // Save temp files for FPDF to read
        $tempEmpSigPath = tempnam(sys_get_temp_dir(), 'sig_emp_');
        file_put_contents($tempEmpSigPath, $employeeSignature);

        $tempEmprSigPath = tempnam(sys_get_temp_dir(), 'sig_empr_');
        file_put_contents($tempEmprSigPath, $employerSignature);

        try {
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
        } finally {
            // Cleanup temp files
            if (file_exists($tempEmpSigPath)) unlink($tempEmpSigPath);
            if (file_exists($tempEmprSigPath)) unlink($tempEmprSigPath);
        }

        return $pdf->Output('S');
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
        $filename = 'generated/' . $employee->id . '/' . Str::slug($slotName) . '_' . time() . '.pdf';

        Storage::disk('public')->put($filename, $content);

        // Ensure we check if relation exists or model exists
        // Assuming EmployeeGeneratedDocument model exists based on trace
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
