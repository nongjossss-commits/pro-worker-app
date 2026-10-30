<?php

namespace Tests\Feature;

use App\Jobs\ProcessDownload;
use App\Models\DownloadTask;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use App\Support\DownloadFileName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-10-30: preview copies the name without the title; Advanced Download
 * can put each employer in its own folder (merged PDF: one PDF per employer);
 * downloaded / exported files are named "<employer or menu> (n คน) <date time>"
 * with (1), (2) … when the same name comes up again.
 */
class DownloadByEmployerAndFileNamesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private array $made = [];

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
        $this->actingAs($this->admin);
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        foreach ($this->made as $path) {
            @unlink($path);
        }
        parent::tearDown();
    }

    private function pdfBytes(string $text): string
    {
        $pdf = new \setasign\Fpdi\Fpdi();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 14);
        $pdf->Cell(0, 10, $text);

        return $pdf->Output('S');
    }

    /** Employers A (2 people) and B (1), picked A, B, A — each with a passport PDF. */
    private function employees(): array
    {
        $a = Employer::factory()->create(['employerNameTh' => 'นายจ้างเอ']);
        $b = Employer::factory()->create(['employerNameTh' => 'นายจ้างบี']);
        $list = [];
        foreach ([[$a, 'Alpha One'], [$b, 'Bravo One'], [$a, 'Alpha Two']] as [$er, $name]) {
            $path = 'employee_files/test-dl/' . str_replace(' ', '_', $name) . '.pdf';
            Storage::disk('public')->put($path, $this->pdfBytes($name));
            $list[] = Employee::factory()->create(['employer_id' => $er->id, 'employeeNameEn' => $name, 'employeeTitleEn' => 'MR.', 'employee_doc_1' => $path]);
        }

        return $list;
    }

    private function runDownload(string $type, array $employees, bool $byEmployer): array
    {
        $task = DownloadTask::create(['user_id' => $this->admin->id, 'type' => $type, 'status' => 'pending']);
        (new ProcessDownload($task->id, array_map(fn ($e) => $e->id, $employees), ['passport'], ['employer_folders' => $byEmployer]))->handle();
        $task->refresh();
        $this->assertSame('completed', $task->status, (string) $task->error_message);
        $full = storage_path('app/private/' . $task->file_path);
        $this->made[] = $full;

        $names = [];
        if (str_ends_with($full, '.zip')) {
            $zip = new \ZipArchive();
            $zip->open($full);
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $names[] = $zip->getNameIndex($i);
            }
            $zip->close();
        }

        return [$task, basename($full), $names];
    }

    public function test_zip_has_a_folder_per_employer_with_numbering_per_employer(): void
    {
        [$task, $file, $names] = $this->runDownload('zip', $this->employees(), true);

        $this->assertMatchesRegularExpression('/^' . $task->id . '__' . preg_quote(__('Employee documents'), '/') . ' \(3 .+\) \d{4}-\d\d-\d\d \d\d\.\d\d\.\d\d\.zip$/u', $file);
        $folderA = DownloadFileName::withCount('นายจ้างเอ', 2);
        $folderB = DownloadFileName::withCount('นายจ้างบี', 1);
        $this->assertCount(3, $names);
        $this->assertStringStartsWith($folderA . '/1. MR__Alpha_One_', $names[0]);
        $this->assertStringStartsWith($folderA . '/2. MR__Alpha_Two_', $names[1]);
        $this->assertStringStartsWith($folderB . '/1. MR__Bravo_One_', $names[2]);
    }

    public function test_without_folders_the_zip_stays_as_before(): void
    {
        [, , $names] = $this->runDownload('zip', $this->employees(), false);

        $this->assertStringStartsWith('1. MR__Alpha_One_', $names[0]);
        $this->assertStringStartsWith('2. MR__Bravo_One_', $names[1]);
        $this->assertStringStartsWith('3. MR__Alpha_Two_', $names[2]);
    }

    public function test_single_folder_and_individual_pdfs_go_into_employer_folders(): void
    {
        $employees = $this->employees();
        $folderA = DownloadFileName::withCount('นายจ้างเอ', 2);

        [, , $single] = $this->runDownload('zip_single', $employees, true);
        $this->assertCount(3, $single);
        $this->assertStringStartsWith($folderA . '/1. MR__Alpha_One_', $single[0]);

        [, , $individual] = $this->runDownload('pdf_individual', $employees, true);
        $this->assertCount(3, $individual);
        $this->assertMatchesRegularExpression('/^' . preg_quote($folderA, '/') . '\/2\. MR__Alpha_Two_\d+\.pdf$/u', $individual[1]);
    }

    public function test_merged_pdf_makes_one_pdf_per_employer(): void
    {
        [, , $names] = $this->runDownload('pdf', $this->employees(), true);

        $folderA = DownloadFileName::withCount('นายจ้างเอ', 2);
        $folderB = DownloadFileName::withCount('นายจ้างบี', 1);
        $this->assertSame([$folderA . '/' . $folderA . '.pdf', $folderB . '/' . $folderB . '.pdf'], $names);
    }

    public function test_one_employer_is_named_after_the_employer_and_served_without_the_task_id(): void
    {
        $employees = $this->employees();
        [$task, $file] = $this->runDownload('pdf', [$employees[0], $employees[2]], false); // both of employer A

        $this->assertStringStartsWith($task->id . '__นายจ้างเอ ', $file);
        $this->assertStringEndsWith('.pdf', $file);

        $response = $this->get(route('admin.downloads.download', $task->id))->assertOk();
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringNotContainsString($task->id . '__', rawurldecode($disposition));
        $this->assertStringContainsString(rawurlencode('นายจ้างเอ'), $disposition);
    }

    public function test_the_same_name_twice_gets_a_number(): void
    {
        $this->travelTo(now()->setTime(10, 0, 0));
        $first = DownloadFileName::make('นายจ้างเอ', 2, 'xlsx', $this->admin->id);
        $second = DownloadFileName::make('นายจ้างเอ', 2, 'xlsx', $this->admin->id);
        $third = DownloadFileName::make('นายจ้างเอ', 2, 'xlsx', $this->admin->id);

        $this->assertStringEndsWith('10.00.00.xlsx', $first);
        $this->assertStringEndsWith('10.00.00 (1).xlsx', $second);
        $this->assertStringEndsWith('10.00.00 (2).xlsx', $third);
        $this->assertSame('a_b_c', DownloadFileName::safe('a/b:c'));
    }

    public function test_advanced_export_of_one_employer_is_named_after_it(): void
    {
        $employees = $this->employees();

        $response = $this->post(route('employees.advanced_export'), [
            'employee_ids' => json_encode([$employees[0]->id, $employees[2]->id]),
            'columns' => ['employeeNameEn'],
        ])->assertOk();

        $this->assertStringContainsString(rawurlencode('นายจ้างเอ'), $response->headers->get('content-disposition'));
    }

    public function test_preview_copies_the_name_without_the_title(): void
    {
        $employee = $this->employees()[0];
        $employee->update(['employeeTitleTh' => 'นาย', 'employeeNameTh' => 'ทดสอบ ชื่อไทย']);

        $html = $this->get(route('global.preview', ['type' => 'employee', 'id' => $employee->id]))->assertOk()->getContent();

        $this->assertStringContainsString('data-copy="Alpha One"', $html);
        $this->assertStringContainsString('data-copy="ทดสอบ ชื่อไทย"', $html);
        $this->assertStringNotContainsString('data-copy="MR. Alpha One"', $html);
        $this->assertStringNotContainsString('data-copy="นาย ทดสอบ ชื่อไทย"', $html);
    }
}
