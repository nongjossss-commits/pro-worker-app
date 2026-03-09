<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PdfTemplate;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfTemplateUpdateNameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create role if not exists
        Role::firstOrCreate(['name' => 'super-admin']);
    }

    public function test_can_update_template_name()
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $template = PdfTemplate::create([
            'name' => 'Old Name',
            'file_path' => 'fake_path.pdf',
            'type' => 'global',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->putJson(route('admin.pdf-templates.update', $template), [
            'name' => 'New Awesome Name',
            'field_mapping' => [],
            'meta_data' => []
        ]);

        $response->assertOk();

        $template->refresh();
        $this->assertEquals('New Awesome Name', $template->name);
    }
}
