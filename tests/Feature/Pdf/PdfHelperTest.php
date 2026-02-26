<?php

namespace Tests\Feature\Pdf;

use App\Helpers\PdfHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfHelperTest extends TestCase
{
    public function test_stream_file_converts_image_to_pdf_and_returns_response()
    {
        Storage::fake('public');

        // Create a dummy image
        $image = UploadedFile::fake()->image('test.jpg', 100, 100);
        $path = $image->store('test_files', 'public');

        // This call should NOT throw "Call to undefined method"
        try {
            $response = PdfHelper::streamFile('public', $path, 'inline', 'test_image.pdf');

            // If we reach here, check status
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        } catch (\Error $e) {
            $this->fail("Caught Error: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->fail("Caught Exception: " . $e->getMessage());
        }
    }

    public function test_stream_file_with_thai_filename_pdf()
    {
        Storage::fake('public');

        // Create a dummy PDF (fake content)
        $pdfContent = '%PDF-1.4 header dummy content';
        $path = 'test_files/thai_test.pdf';
        Storage::disk('public')->put($path, $pdfContent);

        $thaiFilename = 'ทดสอบ_123.pdf';

        $response = PdfHelper::streamFile('public', $path, 'attachment', $thaiFilename);

        $this->assertEquals(200, $response->getStatusCode());

        // Verify Content-Disposition header handles sanitization (strict ASCII)
        $disposition = $response->headers->get('Content-Disposition');

        // Should contain sanitized filename "_123.pdf"
        // Note: Laravel's BinaryFileResponse might wrap it in quotes.
        $this->assertStringContainsString('_123.pdf', $disposition);
        $this->assertStringNotContainsString('ทดสอบ', $disposition);
    }
}
