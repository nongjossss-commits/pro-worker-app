<?php

namespace Tests\Feature;

use App\Jobs\ProcessDownload;
use App\Models\DownloadTask;
use App\Models\Employee;
use App\Models\User;
use App\Services\PdfGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use setasign\Fpdi\Fpdi;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Advanced download ("merge into one PDF" / "one PDF per person"): PDF
 * attachments saved in the modern format (PDF 1.5+ object streams — most
 * scanners / phone apps) used to be skipped silently, so only the photo came
 * out. ProcessDownload::openPdfSource() now converts them first, and reports
 * (or, per person, ships the original of) any it still can't read.
 */
class AdvancedDownloadPdfTest extends TestCase
{
    use RefreshDatabase;

    private string $dir = 'test-advanced-download';
    private array $outputs = [];

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/public/' . $this->dir));
        foreach ($this->outputs as $f) {
            @unlink($f);
        }
        parent::tearDown();
    }

    private function employeeWithFiles(): Employee
    {
        File::ensureDirectoryExists(storage_path('app/public/' . $this->dir));
        $im = imagecreatetruecolor(300, 400);
        imagejpeg($im, storage_path("app/public/{$this->dir}/photo.jpg"));
        imagedestroy($im);
        copy(base_path('tests/Fixtures/pdf-object-streams.pdf'), storage_path("app/public/{$this->dir}/passport.pdf"));

        return Employee::factory()->create([
            'employeePhoto' => "{$this->dir}/photo.jpg",
            'employee_doc_1' => "{$this->dir}/passport.pdf",
        ]);
    }

    private function download(string $type, Employee $employee): DownloadTask
    {
        Role::findOrCreate('staff');
        $user = User::factory()->create();
        $user->assignRole('staff');
        $this->actingAs($user);

        $task = DownloadTask::create(['user_id' => $user->id, 'type' => $type, 'status' => 'pending']);
        ProcessDownload::dispatchSync($task->id, [$employee->id], ['photo', 'passport'], []);
        $task->refresh();
        $this->outputs[] = storage_path('app/private/' . $task->file_path);
        return $task;
    }

    private function zipEntries(DownloadTask $task): array
    {
        $zip = new \ZipArchive();
        $zip->open(storage_path('app/private/' . $task->file_path));
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();
        return $names;
    }

    public function test_free_fpdi_parser_cannot_read_the_fixture_on_its_own(): void
    {
        $this->expectException(\Throwable::class);
        (new Fpdi())->setSourceFile(base_path('tests/Fixtures/pdf-object-streams.pdf'));
    }

    public function test_modern_pdf_is_converted_and_merged(): void
    {
        if (!is_dir(base_path('node_modules/pdf-lib'))) {
            $this->markTestSkipped('No PDF converter (node pdf-lib) on this machine.');
        }

        $task = $this->download('pdf', $this->employeeWithFiles());

        $this->assertSame('completed', $task->status);
        // photo (1 page) + passport (2 pages)
        $this->assertSame(3, (new Fpdi())->setSourceFile(storage_path('app/private/' . $task->file_path)));
        $this->assertNull($task->error_message);
    }

    public function test_without_a_converter_the_original_is_shipped_and_reported(): void
    {
        $this->app->instance(PdfGeneratorService::class, new class extends PdfGeneratorService {
            public function __construct() {}
            public function tryNormalizePdf($inputPath) { throw new \Exception('no converter'); }
        });
        $employee = $this->employeeWithFiles();

        $perPerson = $this->download('pdf_individual', $employee);
        $entries = $this->zipEntries($perPerson);
        $this->assertCount(2, $entries, 'person PDF (photo) + original passport PDF');
        $this->assertTrue(collect($entries)->contains(fn ($n) => str_contains($n, '_passport_passport.pdf')));
        $this->assertStringContainsString('ใส่ไฟล์ต้นฉบับไว้ใน ZIP', (string) $perPerson->error_message);
        $this->assertStringContainsString('พาสปอร์ต', (string) $perPerson->error_message);

        $merged = $this->download('pdf', $employee);
        $this->assertStringContainsString('กรุณาดาวน์โหลดแบบ ZIP', (string) $merged->error_message);
    }

    public function test_zip_download_contains_the_original_files(): void
    {
        $task = $this->download('zip', $this->employeeWithFiles());

        $entries = $this->zipEntries($task);
        $this->assertTrue(collect($entries)->contains(fn ($n) => str_ends_with($n, 'photo_photo.jpg')));
        $this->assertTrue(collect($entries)->contains(fn ($n) => str_ends_with($n, 'passport_passport.pdf')));
    }

    public function test_start_answers_at_once_and_the_file_is_built_after_the_response(): void
    {
        $employee = $this->employeeWithFiles();
        Role::findOrCreate('staff');
        $user = User::factory()->create();
        $user->assignRole('staff');

        $res = $this->actingAs($user)->postJson(route('admin.downloads.initiate'), [
            'employee_ids' => [$employee->id], 'selected_files' => ['photo', 'passport'], 'type' => 'zip',
        ])->assertOk()->json();

        $this->assertTrue($res['success']);
        $task = DownloadTask::findOrFail($res['task_id']);
        $this->outputs[] = storage_path('app/private/' . $task->file_path);
        // The test client runs the after-response job when the request ends.
        $this->assertSame('completed', $task->status);
        $this->assertCount(2, $this->zipEntries($task));
    }

    public function test_download_center_shows_progress_and_fails_stuck_tasks(): void
    {
        $user = User::factory()->create();
        $running = DownloadTask::create(['user_id' => $user->id, 'type' => 'pdf', 'status' => 'processing']);
        \Illuminate\Support\Facades\Cache::put('download_progress:' . $running->id, ['done' => 12, 'total' => 80], 600);
        $stuck = DownloadTask::create(['user_id' => $user->id, 'type' => 'pdf', 'status' => 'processing']);
        DownloadTask::whereKey($stuck->id)->update(['updated_at' => now()->subHours(2)]);

        $tasks = collect($this->actingAs($user)->getJson(route('admin.downloads.index'))->assertOk()->json())->keyBy('id');

        $this->assertSame(['done' => 12, 'total' => 80], $tasks[$running->id]['progress']);
        $this->assertSame('failed', $tasks[$stuck->id]['status']);
        $this->assertStringContainsString('หมดเวลา', $tasks[$stuck->id]['error_message']);
    }

    public function test_files_with_the_same_name_do_not_overwrite_each_other_in_the_zip(): void
    {
        File::ensureDirectoryExists(storage_path("app/public/{$this->dir}/a"));
        File::ensureDirectoryExists(storage_path("app/public/{$this->dir}/b"));
        file_put_contents(storage_path("app/public/{$this->dir}/a/scan.pdf"), '%PDF-1.4 a');
        file_put_contents(storage_path("app/public/{$this->dir}/b/scan.pdf"), '%PDF-1.4 b');
        $employee = Employee::factory()->create([
            'insurance_document_path' => "{$this->dir}/a/scan.pdf",
            'insurance_document_path_private' => "{$this->dir}/b/scan.pdf",
        ]);

        Role::findOrCreate('staff');
        $user = User::factory()->create();
        $this->actingAs($user);
        $task = DownloadTask::create(['user_id' => $user->id, 'type' => 'zip', 'status' => 'pending']);
        ProcessDownload::dispatchSync($task->id, [$employee->id], ['insurance'], []);
        $task->refresh();
        $this->outputs[] = storage_path('app/private/' . $task->file_path);

        $entries = $this->zipEntries($task);
        $this->assertCount(2, $entries, implode(', ', $entries));
    }

    public function test_huge_merge_is_split_into_parts_instead_of_crashing(): void
    {
        // Three employees, and a pretend memory limit so small that every
        // employee forces a new part.
        $employees = collect([$this->employeeWithFiles(), Employee::factory()->create(), Employee::factory()->create()]);
        $employees[1]->forceFill(['employeePhoto' => "{$this->dir}/photo.jpg"])->save();
        $employees[2]->forceFill(['employeePhoto' => "{$this->dir}/photo.jpg"])->save();

        $user = User::factory()->create();
        $this->actingAs($user);
        $task = DownloadTask::create(['user_id' => $user->id, 'type' => 'pdf', 'status' => 'pending']);
        $job = new class($task->id, $employees->pluck('id')->all(), ['photo'], []) extends ProcessDownload {
            protected function memoryLimitBytes(): int { return 1; }
        };
        $job->handle();
        $task->refresh();
        $this->outputs[] = storage_path('app/private/' . $task->file_path);

        $this->assertSame('completed', $task->status);
        $this->assertStringEndsWith('.zip', $task->file_path);
        $entries = $this->zipEntries($task);
        sort($entries);
        $this->assertSame(['merged_part_1_of_3.pdf', 'merged_part_2_of_3.pdf', 'merged_part_3_of_3.pdf'], $entries);
        $this->assertStringContainsString('แบ่งเป็น 3 ไฟล์', (string) $task->error_message);
    }

    public function test_normal_merge_is_still_one_pdf(): void
    {
        $task = $this->download('pdf', $this->employeeWithFiles());

        $this->assertStringEndsWith('.pdf', $task->file_path);
        $this->assertStringNotContainsString('แบ่งเป็น', (string) $task->error_message);
    }

    public function test_big_photos_are_shrunk_for_pdf_output_only(): void
    {
        File::ensureDirectoryExists(storage_path('app/public/' . $this->dir));
        $path = storage_path("app/public/{$this->dir}/big.jpg");
        $im = imagecreatetruecolor(4000, 3000);
        imagejpeg($im, $path);
        imagedestroy($im);

        $job = new ProcessDownload(1, [], []);
        $m = new \ReflectionMethod($job, 'normalizeImage');
        $m->setAccessible(true);
        $out = $m->invoke($job, $path);
        $this->outputs[] = $out;

        [$w, $h] = getimagesize($out);
        $this->assertSame([2480, 1860], [$w, $h]);
        $this->assertSame([4000, 3000], array_slice(getimagesize($path), 0, 2), 'original untouched');
    }
}
