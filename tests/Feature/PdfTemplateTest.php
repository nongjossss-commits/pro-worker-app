<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PdfTemplate;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create role if not exists
        Role::firstOrCreate(['name' => 'super-admin']);
    }

    public function test_store_template_saves_original_filename()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        // Generate a real valid PDF using FPDF
        $fpdf = new \setasign\Fpdi\Fpdi();
        $fpdf->AddPage();
        $fpdf->SetFont('Arial', 'B', 16);
        $fpdf->Cell(40, 10, 'Hello World!');
        $pdfContent = $fpdf->Output('S');

        $file = UploadedFile::fake()->createWithContent('my-original-template.pdf', $pdfContent);

        $response = $this->actingAs($user)->post(route('admin.pdf-templates.store'), [
            'name' => 'My Template',
            'type' => 'global',
            'file' => $file,
        ]);

        if (session('errors')) {
            dump(session('errors')->all());
        }

        $response->assertRedirect();

        $template = PdfTemplate::first();
        $this->assertNotNull($template);
        $this->assertEquals('My Template', $template->name);
        $this->assertEquals('my-original-template.pdf', $template->meta_data['original_filename']);
    }

    public function test_download_template_uses_original_filename()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $file = UploadedFile::fake()->create('test-template.pdf', 100, 'application/pdf');
        $path = $file->store('pdf_templates', 'public');

        $template = PdfTemplate::create([
            'name' => 'Download Test',
            'file_path' => $path,
            'type' => 'global',
            'created_by' => $user->id,
            'meta_data' => [
                'original_filename' => 'original-file.pdf'
            ]
        ]);

        $response = $this->actingAs($user)->get(route('admin.pdf-templates.file', [
            'pdf_template' => $template->id,
            'download' => 1
        ]));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=original-file.pdf');
    }

    public function test_download_template_fallback_filename()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $file = UploadedFile::fake()->create('test-template.pdf', 100, 'application/pdf');
        $path = $file->store('pdf_templates', 'public');

        $template = PdfTemplate::create([
            'name' => 'Fallback Test',
            'file_path' => $path,
            'type' => 'global',
            'created_by' => $user->id,
            // No meta_data['original_filename']
        ]);

        $response = $this->actingAs($user)->get(route('admin.pdf-templates.file', [
            'pdf_template' => $template->id,
            'download' => 1
        ]));

        $response->assertOk();
        $header = $response->headers->get('content-disposition');
        $this->assertTrue(
            $header === 'attachment; filename="Fallback Test.pdf"' ||
            $header === 'attachment; filename=Fallback Test.pdf'
        );
    }
}
