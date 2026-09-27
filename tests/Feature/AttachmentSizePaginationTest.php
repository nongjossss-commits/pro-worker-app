<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Services\AttachmentSizeScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Menu "ขนาดไฟล์แนบ": the list is paged (20/30/50/100 per page) and split
 * into "attached to records" / "not linked" tabs — AttachmentSizeController@list.
 */
class AttachmentSizePaginationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('super-admin');
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        return $user;
    }

    /** Stand-in scan result: $owned files with an owner, $orphans without, biggest first. */
    private function fakeScan(int $owned, int $orphans): void
    {
        $files = [];
        $n = $owned + $orphans;
        for ($i = 0; $i < $n; $i++) {
            $files[] = [
                'path' => "employee_files/x/file-{$i}.pdf", 'name' => "file-{$i}.pdf", 'folder' => 'employee_files/x',
                'size' => (1000 - $i) * 1048576, 'modified' => '2026-09-01 10:00', 'extension' => 'pdf', 'dimensions' => null,
                'owners' => $i < $owned ? [['type' => 'ลูกจ้าง', 'name' => "Owner {$i}", 'id' => $i + 1, 'field' => 'ไฟล์ 1. พาสปอร์ต', 'edit_url' => null, 'preview_type' => null, 'deleted' => false]] : [],
            ];
        }
        // Owned first then orphans is fine — the controller splits them.
        $result = ['files' => $files, 'total_bytes' => array_sum(array_column($files, 'size')), 'scanned_files' => $n, 'scanned_bytes' => 1, 'scanned_at' => '27/09/2026 10:00', 'threshold' => 10485760];

        $this->app->instance(AttachmentSizeScanner::class, new class($result) extends AttachmentSizeScanner {
            public function __construct(private array $fake) {}
            public function scan(int $minBytes, bool $fresh = false): array { return $this->fake; }
            public function withDimensions(array $files): array { return $files; }
        });
    }

    public function test_default_page_shows_20_files_and_the_page_links(): void
    {
        $this->fakeScan(45, 3);

        $html = $this->actingAs($this->admin())->get(route('admin.attachment-sizes.list'))->assertOk()->getContent();

        $this->assertStringContainsString('file-0.pdf', $html);
        $this->assertStringContainsString('file-19.pdf', $html);
        $this->assertStringNotContainsString('file-20.pdf', $html);
        $this->assertStringContainsString('per_page=20', $html);
        $this->assertStringContainsString('page=3', $html);          // 45 / 20 → 3 pages
        $this->assertStringContainsString('1–20', $html);
        $this->assertStringContainsString('>45<', $html);            // tab count
        $this->assertStringContainsString('>3<', $html);             // orphan tab count
    }

    public function test_user_picks_page_size_and_page(): void
    {
        $this->fakeScan(45, 0);

        $html = $this->actingAs($this->admin())->get(route('admin.attachment-sizes.list', ['per_page' => 30, 'page' => 2]))->assertOk()->getContent();

        $this->assertStringContainsString('file-30.pdf', $html);
        $this->assertStringContainsString('file-44.pdf', $html);
        $this->assertStringNotContainsString('file-29.pdf', $html);
        $this->assertStringContainsString('31–45', $html);
    }

    public function test_orphan_tab_and_invalid_options_fall_back(): void
    {
        $this->fakeScan(2, 25);

        $html = $this->actingAs($this->admin())->get(route('admin.attachment-sizes.list', ['view' => 'orphans', 'per_page' => 7, 'page' => 99]))->assertOk()->getContent();

        // per_page 7 is not allowed → 20; page 99 → last page (2)
        $this->assertStringContainsString('data-view="orphans"', $html);
        $this->assertStringContainsString('21–25', $html);
        $this->assertStringContainsString('file-26.pdf', $html);
        $this->assertStringNotContainsString('Owner 0', $html);
    }

    public function test_owner_is_found_when_the_path_is_stored_with_a_storage_prefix(): void
    {
        $dir = storage_path('app/public/employee_files/test-size-prefix');
        File::ensureDirectoryExists($dir);
        $path = 'employee_files/test-size-prefix/prefixed.pdf';
        file_put_contents(storage_path('app/public/' . $path), str_repeat('x', 6 * 1024 * 1024));

        try {
            $employee = Employee::factory()->create(['employee_doc_2' => '/storage/' . $path]);

            $file = collect(app(AttachmentSizeScanner::class)->scan(5 * 1024 * 1024, true)['files'])->firstWhere('path', $path);

            $this->assertNotNull($file);
            $this->assertSame($employee->id, $file['owners'][0]['id']);
        } finally {
            File::deleteDirectory($dir);
        }
    }
}
