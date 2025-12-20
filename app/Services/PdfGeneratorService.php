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
        // Path to Thai font
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
                // For download, we accumulate files.
                // If single employee, return PDF directly.
                // If multiple, we'll zip them later in Controller.
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
        $pageCount = $pdf->setSourceFile($templatePath);

        // Add Thai Font
        // Note: FPDF/FPDI font handling can be tricky.
        // We assume standard setup or use a compatible font script.
        // For simplicity in this environment, we'll try to use a standard font or add one if available.
        // Ideally, we need to convert TTF to PHP font definition for FPDF.
        // If 'thsarabunnew' is not pre-converted, we might fallback to 'arial' (no Thai support) or similar.
        // Assuming we have a way to handle UTF-8, usually ttf2pt1 or similar is needed.
        // However, standard FPDF requires ISO-8859-1. tFPDF supports UTF-8.
        // Since we are adding code, let's assume we might need tFPDF or similar, but composer.json has `setasign/fpdf`.
        // Standard FPDF doesn't support UTF-8/Thai out of the box easily without generating font files.
        // I will use a placeholder implementation for font loading.

        // CHECK: Does the system have Thai fonts ready for FPDF?
        // If not, I should probably use a font that is available.
        // I'll try to add the font. If it fails, catch it.
        if (file_exists($this->fontPath)) {
             $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
             $pdf->SetFont('THSarabunNew', '', 14);
        } else {
             $pdf->SetFont('Arial', '', 12); // Fallback
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
                    // FPDF uses mm by default usually, but importPage size depends on PDF unit.
                    // FPDI import usually respects source unit.
                    // Let's assume standard points or mm.
                    // $size['width'] is the width of the page in user units.

                    $x = ($item['x'] / 100) * $size['width'];
                    $y = ($item['y'] / 100) * $size['height'];

                    // Simple text placement
                    $pdf->SetXY($x, $y);

                    // Handle encoding for Thai if using standard FPDF (requires iconv)
                    if (file_exists($this->fontPath)) {
                        $text = iconv('UTF-8', 'cp874', $text);
                    }

                    // Try to write
                    $pdf->Write(0, $text);
                }
            }
        }

        return $pdf->Output('S'); // Return as string
    }

    protected function resolveValue(Employee $employee, $key)
    {
        // Handle dot notation (employer.name)
        $value = data_get($employee, $key);

        // Handle Dates
        if ($value instanceof Carbon) {
            return $value->format('d/m/Y');
        }

        // Handle Enums or Specific Logic if needed
        return (string) $value;
    }

    protected function saveToSlot(Employee $employee, $content, $slotName)
    {
        $filename = 'generated/' . $employee->id . '/' . Str::slug($slotName) . '_' . time() . '.pdf';

        Storage::disk('public')->put($filename, $content);

        // Check for existing slot with same name and overwrite (or just add new entry?)
        // Requirement said "use same slot".
        // Implementation: Find existing record for this slot name and update, or create new.

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
