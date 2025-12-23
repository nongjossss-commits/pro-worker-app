<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PdfGeneratorService;
use App\Models\Employee;
use App\Models\PdfTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;

class PdfGenerationTest extends TestCase
{
    // Use RefreshDatabase if available, but for unit tests with mocks it might not be strictly needed unless we save models
    // use RefreshDatabase;

    public function test_service_throws_error_for_invalid_employee_data()
    {
        // Mock the SignatureGeneratorService
        $signatureMock = Mockery::mock(\App\Services\SignatureGeneratorService::class);
        $signatureMock->shouldReceive('generate')->andReturn('fake_signature_content');

        $service = new PdfGeneratorService($signatureMock);

        // Mock Template
        $template = new PdfTemplate();
        $template->name = 'Test Template';
        $template->file_path = 'templates/test.pdf';
        // Missing field_mapping intentionally to see behavior, or corrupted path

        // Mock Storage to fail on file exists check or path resolution
        Storage::fake('public');
        // We don't create the file, so setSourceFile inside service should fail

        // Mock Employee
        $employee = new Employee();
        $employee->id = 1;
        $employee->employeeNameEn = 'John Doe';

        $employees = collect([$employee]);

        // Expect Exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error processing employee John Doe (ID: 1)');

        $service->generateForEmployees($template, $employees, ['output_type' => 'raw_content']);
    }
}
