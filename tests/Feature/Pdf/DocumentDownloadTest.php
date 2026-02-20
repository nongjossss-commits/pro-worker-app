<?php

namespace Tests\Feature\Pdf;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\JobOwner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;

class DocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed permissions
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        // Ensure manage-tickets exists if not seeded
        if (!Permission::where('name', 'manage-tickets')->exists()) {
             Permission::create(['name' => 'manage-tickets']);
        }
    }

    public function test_employee_document_download_disposition_inline()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('document.pdf', 100);
        $path = $file->store('employee_files', 'public');

        $jobOwner = JobOwner::factory()->create();
        $employer = Employer::factory()->create(['job_owner_id' => $jobOwner->id]);
        $employee = Employee::factory()->create([
            'employer_id' => $employer->id,
            'employee_doc_1' => $path,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo(['view-employees', 'manage-tickets']);

        $response = $this->actingAs($user)->get(route('employees.documents.pdf', [
            'employee' => $employee->id,
            'field' => 'employee_doc_1'
        ]));

        $response->assertStatus(200);

        // Expected Content-Disposition header
        $filename = basename($path);

        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString($filename, $response->headers->get('Content-Disposition'));
    }

    public function test_employer_document_download_disposition_inline()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('company.pdf', 100);
        $path = $file->store('employer_documents', 'public');

        $jobOwner = JobOwner::factory()->create();
        $employer = Employer::factory()->create([
            'job_owner_id' => $jobOwner->id,
            'employer_doc_company' => $path,
        ]);

        $user = User::factory()->create();
        // Assuming EmployerPolicy might also check manage-tickets or ownership
        // Let's check EmployerPolicy logic if needed. Usually view-employers is enough for middleware, but policy?
        // EmployerPolicy not checked previously, but likely similar.
        // Giving manage-tickets is safer.
        $user->givePermissionTo(['view-employers', 'manage-tickets']);

        $response = $this->actingAs($user)->get(route('employers.documents.pdf', [
            'employer' => $employer->id,
            'field' => 'employer_doc_company'
        ]));

        $response->assertStatus(200);

        $filename = basename($path);
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString($filename, $response->headers->get('Content-Disposition'));
    }
}
