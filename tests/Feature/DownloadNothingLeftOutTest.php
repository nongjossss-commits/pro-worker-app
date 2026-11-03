<?php

namespace Tests\Feature;

use App\Jobs\ProcessDownload;
use App\Models\DownloadTask;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-03 report: Advanced Download (merge into PDF) left out some
 * people's passport / visa files that the preview could open and export.
 *  - files are recognised by content (a PDF with junk bytes before "%PDF"
 *    counted as "not PDF / image" and was skipped while marked as added);
 *  - a file that can't be merged is never dropped: the download becomes a
 *    ZIP with the merged PDF + the original, a notice page marks its place,
 *    and the summary lists it;
 *  - selected employees that can't be loaded are listed.
 */
class DownloadNothingLeftOutTest extends TestCase
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

    private function pngBytes(): string
    {
        $img = imagecreatetruecolor(40, 30);
        imagefilledrectangle($img, 0, 0, 40, 30, imagecolorallocate($img, 200, 50, 50));
        ob_start();
        imagepng($img);
        imagedestroy($img);

        return ob_get_clean();
    }

    private function employee(Employer $er, string $name, string $file, string $bytes): Employee
    {
        $path = 'employee_files/test-dl/' . $file;
        Storage::disk('public')->put($path, $bytes);

        return Employee::factory()->create(['employer_id' => $er->id, 'employeeNameEn' => $name, 'employee_doc_1' => $path]);
    }

    private function runDownload(string $type, array $ids): DownloadTask
    {
        $task = DownloadTask::create(['user_id' => $this->admin->id, 'type' => $type, 'status' => 'pending']);
        (new ProcessDownload($task->id, $ids, ['passport'], []))->handle();
        $task->refresh();
        $this->assertSame('completed', $task->status, (string) $task->error_message);
        $this->made[] = storage_path('app/private/' . $task->file_path);

        return $task;
    }

    public function test_readable_files_are_merged_and_unreadable_ones_ship_as_originals(): void
    {
        $er = Employer::factory()->create();
        $normal = $this->employee($er, 'Normal Pdf', 'normal.pdf', $this->pdfBytes('normal'));
        $junk = $this->employee($er, 'Junk Prefix', 'junk.pdf', "\xEF\xBB\xBF\r\n" . $this->pdfBytes('junk'));
        $image = $this->employee($er, 'Image Png', 'image.png', $this->pngBytes());
        $broken = $this->employee($er, 'Broken Pdf', 'broken.pdf', 'this is not really a pdf');
        $heic = $this->employee($er, 'Iphone Heic', 'photo.heic', random_bytes(64));
        $gone = Employee::factory()->create(['employer_id' => $er->id]);
        $goneId = $gone->id;
        $gone->delete(); // moved to the trash after being selected

        $task = $this->runDownload('pdf', [$normal->id, $junk->id, $image->id, $broken->id, $heic->id, $goneId]);

        // something couldn't be merged → ZIP: merged PDF + originals
        $this->assertStringEndsWith('.zip', $task->file_path);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open(storage_path('app/private/' . $task->file_path)) === true);
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $pdfs = array_values(array_filter($names, fn ($n) => !str_contains($n, ProcessDownload::UNMERGED_FOLDER) && str_ends_with($n, '.pdf')));
        $this->assertCount(1, $pdfs, implode(', ', $names));
        $originals = array_values(array_filter($names, fn ($n) => str_contains($n, ProcessDownload::UNMERGED_FOLDER)));
        $this->assertCount(2, $originals, implode(', ', $names));
        $this->assertTrue(collect($originals)->contains(fn ($n) => str_ends_with($n, 'broken.pdf')));
        $this->assertTrue(collect($originals)->contains(fn ($n) => str_ends_with($n, 'photo.heic')));

        // merged PDF: normal + junk-prefixed + image + 2 notice pages = 5 pages
        $merged = tempnam(sys_get_temp_dir(), 'merged_') . '.pdf';
        file_put_contents($merged, $zip->getFromName($pdfs[0]));
        $zip->close();
        $this->made[] = $merged;
        $this->assertSame(5, (new \setasign\Fpdi\Fpdi())->setSourceFile($merged));

        // the summary names what was not merged and who was not found
        $this->assertStringContainsString('Broken Pdf', $task->error_message);
        $this->assertStringContainsString('Iphone Heic', $task->error_message);
        $this->assertStringNotContainsString('Junk Prefix', $task->error_message);
        $this->assertStringContainsString('#' . $goneId, $task->error_message);
    }

    public function test_all_readable_still_gives_a_single_pdf(): void
    {
        $er = Employer::factory()->create();
        $a = $this->employee($er, 'Alpha', 'a.pdf', $this->pdfBytes('a'));
        $b = $this->employee($er, 'Bravo', 'b.pdf', "%junk\n" . $this->pdfBytes('b'));

        $task = $this->runDownload('pdf', [$a->id, $b->id]);

        $this->assertStringEndsWith('.pdf', $task->file_path);
        $this->assertSame(2, (new \setasign\Fpdi\Fpdi())->setSourceFile(storage_path('app/private/' . $task->file_path)));
        $this->assertNull($task->error_message);
    }

    public function test_download_center_shows_a_visible_link_to_the_details(): void
    {
        $blade = file_get_contents(resource_path('views/components/download-modals.blade.php'));
        $this->assertStringContainsString("__('Some files were not included — see details')", $blade);
        $this->assertStringContainsString('window.downloadTaskNotes', $blade);
    }
}
