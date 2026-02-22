<?php

namespace Tests\Feature\Pdf;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\PdfTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PdfRedirectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup permissions
        Permission::firstOrCreate(['name' => 'view-pdf-templates']);
        Permission::firstOrCreate(['name' => 'manage-tickets']);
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo(['view-pdf-templates', 'manage-tickets']);

        $this->user = User::factory()->create();
        $this->user->assignRole('admin');

        $this->actingAs($this->user);
    }

    public function test_modal_receives_redirect_url()
    {
        $redirectUrl = 'http://example.com/previous-page';
        $employee = Employee::factory()->create();

        $response = $this->post(route('admin.pdf-templates.generate.modal'), [
            'employees' => [$employee->id],
            'redirect_url' => $redirectUrl
        ]);

        // If Vite fails, we catch it? No, it's a fatal error or exception.
        // We will handle Vite in layout file.
        $response->assertStatus(200);
        $response->assertViewHas('redirect_url', $redirectUrl);
    }

    public function test_modal_defaults_to_previous_url_if_missing()
    {
        $employee = Employee::factory()->create();
        $previousUrl = 'http://example.com/fallback';

        $response = $this->from($previousUrl)->post(route('admin.pdf-templates.generate.modal'), [
            'employees' => [$employee->id]
        ]);

        $response->assertStatus(200);
        $response->assertViewHas('redirect_url', $previousUrl);
    }

    public function test_process_save_to_slot_redirects_to_custom_url()
    {
        Storage::fake('public');

        $employee = Employee::factory()->create();
        $template = new PdfTemplate();
        $template->name = 'Test Template';
        $template->type = 'global';
        $template->file_path = 'templates/dummy.pdf';
        $template->save();

        $redirectUrl = 'http://example.com/custom-redirect';

        Storage::disk('public')->put($template->file_path, 'dummy content');

        // Create a minimal valid PDF content to avoid Fpdi errors if service actually parses it
        $pdfContent = "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n3 0 obj\n<<\n/Type /Page\n/MediaBox [0 0 612 792]\n/Resources <<\n/Font <<\n/F1 4 0 R\n>>\n>>\n/Contents 5 0 R\n/Parent 2 0 R\n>>\nendobj\n4 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica\n>>\nendobj\n5 0 obj\n<<\n/Length 44\n>>\nstream\nBT\n/F1 12 Tf\n72 712 Td\n(Hello World) Tj\nET\nendstream\nendobj\nxref\n0 6\n0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n0000000117 00000 n \n0000000244 00000 n \n0000000331 00000 n \ntrailer\n<<\n/Size 6\n/Root 1 0 R\n>>\nstartxref\n425\n%%EOF";
        Storage::disk('public')->put($template->file_path, $pdfContent);

        $response = $this->post(route('admin.pdf-templates.generate.process'), [
            'employees' => [$employee->id],
            'template_id' => $template->id,
            'output_type' => 'save_to_slot',
            'slot_name' => 'employee_doc_9',
            'redirect_url' => $redirectUrl
        ]);

        $response->assertRedirect($redirectUrl);
    }

    public function test_process_error_redirects_to_custom_url()
    {
        $employee = Employee::factory()->create();
        $template = new PdfTemplate();
        $template->name = 'Test Template';
        $template->type = 'global';
        $template->file_path = 'templates/dummy_error.pdf'; // Valid path needed for DB constraint
        $template->save();

        $redirectUrl = 'http://example.com/failure-redirect';

        // Mock PdfGeneratorService to throw exception
        $this->mock(\App\Services\PdfGeneratorService::class, function ($mock) {
            $mock->shouldReceive('generateSinglePdf')->andThrow(new \Exception('Mocked Failure'));
            $mock->shouldReceive('generateFilename')->andReturn('test.pdf');
        });

        // Trigger download mode (which calls generateSinglePdf in loop)
        $response = $this->post(route('admin.pdf-templates.generate.process'), [
            'employees' => [$employee->id],
            'template_id' => $template->id,
            'output_type' => 'download',
            'redirect_url' => $redirectUrl
        ]);

        $response->assertRedirect($redirectUrl);
        $response->assertSessionHas('danger');
    }
}
