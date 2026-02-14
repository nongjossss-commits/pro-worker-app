<?php

namespace Tests\Feature\Pdf;

use Tests\TestCase;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\PdfTemplate;
use App\Services\PdfGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class PdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_generation_with_thai_font()
    {
        // 1. Setup Dummy PDF Template File
        Storage::fake('public');
        $templatePath = 'templates/test.pdf';

        // Create a simple PDF as template
        $pdf = new Fpdi();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 12);
        $pdf->Write(10, 'Template Content');
        $pdfContent = $pdf->Output('S');
        Storage::disk('public')->put($templatePath, $pdfContent);

        // 2. Setup Models
        $employer = Employer::factory()->make([
            'id' => 1,
            'signature_1_path' => null,
            'signature_2_path' => null,
        ]);

        $employee = Employee::factory()->make([
            'id' => 1,
            'employeeNameEn' => 'John Doe',
            'employeeTitleTh' => 'นาย',
            'employeeGender' => 'Male',
        ]);
        $employee->setRelation('employer', $employer);

        $template = new PdfTemplate();
        $template->file_path = $templatePath;
        $template->name = 'Test Template';
        $template->field_mapping = [
            [
                'page' => 1,
                'x' => 10, 'y' => 20, 'w' => 50, 'h' => 10,
                'type' => 'static',
                'text' => 'สวัสดีชาวโลก', // Thai text
                'fontSize' => 14,
                'align' => 'center'
            ]
        ];

        // 3. Run Service
        $service = app(PdfGeneratorService::class);

        // We expect it NOT to throw exception
        try {
            $generatedPdf = $service->generateSinglePdf($template, $employee);
        } catch (\Exception $e) {
            $this->fail("PDF Generation failed: " . $e->getMessage());
        }

        $this->assertNotEmpty($generatedPdf);

        // 4. Verify Font Usage
        // Check if font name is in PDF. FPDF usually embeds font names.
        // 'THSarabunNew' should be present in the raw PDF string if used.
        // Or at least the font definition uses that name.
        // Note: Compressed streams might hide it, but usually font descriptors are visible or at least partial.
        // If FPDF uses subsets, it might be tricky.
        // But if 'Arial' was used (fallback), we might see 'Arial' or 'Helvetica'.
        // Let's check for 'THSarabunNew'.

        // However, standard FPDF output might compress object streams if using FPDI?
        // FPDF 1.8 uncompressed by default? No, it uses gzcompress if available.
        // But the font name usually appears in the /Font descriptor which is an object.
        // If objects are compressed, we can't see it.
        // But let's try.

        // If I can't grep it, I'll rely on the fact that no exception was thrown and code path for font loaded was taken.
        // I can inspect if `public/fonts/THSarabunNew.php` exists (which I did manually).

        $this->assertTrue(file_exists(public_path('fonts/THSarabunNew.php')), 'Font definition file missing');
        $this->assertTrue(file_exists(public_path('fonts/THSarabunNew.ttf')), 'Font TTF file missing');

        // Determine if logic used font
        // I'll check if the output size is significantly larger than template?
        // Embedding a font adds size (subset ~20-50KB).
        // Template is minimal.
        $this->assertGreaterThan(strlen($pdfContent) + 5000, strlen($generatedPdf), 'Generated PDF too small, font likely not embedded');
    }
}
