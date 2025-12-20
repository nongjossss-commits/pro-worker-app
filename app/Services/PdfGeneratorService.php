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

    public function __construct()
    {
        // Path to Thai font (Assumes font files exist in public/fonts)
        // If using standard FPDF, we need .php and .z (or .ttf if using tFPDF)
        $this->fontPath = public_path('fonts/THSarabunNew.php');
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
        // Use standard Fpdi. If Thai characters are needed, ensure
        // the environment has tFPDF or the font definitions are correct.
        // For this implementation, we try to be safe.
        $pdf = new Fpdi();

        // Load the template file
        $templatePath = Storage::disk('public')->path($template->file_path);

        try {
            $pageCount = $pdf->setSourceFile($templatePath);
        } catch (\Exception $e) {
            // Fallback or error handling if PDF is invalid
            // For now, rethrow or log? We'll let it fail visibly or return empty
            throw $e;
        }

        // Font Handling
        $fontLoaded = false;
        // Check if custom Thai font definition exists
        if (file_exists($this->fontPath)) {
             $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
             $pdf->SetFont('THSarabunNew', '', 14);
             $fontLoaded = true;
        } else {
             // Fallback to Arial if Thai font is missing to prevent crash
             $pdf->SetFont('Arial', '', 12);
        }

        // Iterate Pages
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // Filter items for this page
            $items = collect($template->field_mapping)->where('page', $pageNo);

            foreach ($items as $item) {
                $text = '';

                if ($item['type'] === 'static') {
                    $text = $item['text'] ?? '';
                } elseif ($item['type'] === 'db') {
                    $text = $this->resolveValue($employee, $item['key']);
                }

                if ($text) {
                    // Convert coordinates from % to mm/points
                    $x = ($item['x'] / 100) * $size['width'];
                    $y = ($item['y'] / 100) * $size['height'];

                    // Text Placement
                    $pdf->SetXY($x, $y);

                    // Thai Encoding Conversion (if using standard FPDF with custom font)
                    // Standard FPDF uses ISO-8859-1 or cp874 for Thai if font supports it.
                    // If we successfully loaded THSarabunNew (which is usually CP874 mapped), convert UTF-8.
                    if ($fontLoaded) {
                        // Attempt conversion. If iconv fails, stick to original.
                        $converted = @iconv('UTF-8', 'cp874', $text);
                        if ($converted !== false) {
                            $text = $converted;
                        }
                    } else {
                        // Using Arial (ISO-8859-1). Convert or strip incompatible chars?
                        // For safety, convert to ISO-8859-1//TRANSLIT
                        $text = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
                    }

                    $pdf->Write(0, $text);
                }
            }
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

        $doc = $employee->generatedDocuments()->updateOrCreate(
            ['document_name' => $slotName],
            [
                'file_path' => $filename,
                'generated_at' => now(),
                'created_by' => auth()->id(),
            ]
        );

        return $doc;
    }

    protected function generateFilename(PdfTemplate $template, Employee $employee)
    {
        return Str::slug($template->name . '-' . $employee->employeeNameEn) . '.pdf';
    }
}
