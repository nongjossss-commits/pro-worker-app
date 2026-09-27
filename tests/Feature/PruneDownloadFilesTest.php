<?php

namespace Tests\Feature;

use App\Models\DownloadTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Download Center files are kept 24 hours and are only reachable through
 * DownloadController — see app:prune-download-files.
 */
class PruneDownloadFilesTest extends TestCase
{
    use RefreshDatabase;

    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();
        // Isolated storage so the test never touches real download files.
        $this->storage = sys_get_temp_dir() . '/pw_prune_dl_' . uniqid();
        File::makeDirectory($this->storage . '/app/private/downloads', 0755, true);
        File::makeDirectory($this->storage . '/app/public/downloads', 0755, true);
        $this->app->useStoragePath($this->storage);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);
        parent::tearDown();
    }

    private function task(User $user, string $file, string $where, int $hoursAgo): DownloadTask
    {
        File::put("{$this->storage}/app/{$where}/downloads/{$file}", 'zip-bytes');
        $task = DownloadTask::create(['user_id' => $user->id, 'type' => 'zip', 'status' => 'completed', 'file_path' => 'downloads/' . $file]);
        $task->forceFill(['created_at' => now()->subHours($hoursAgo)])->save();

        return $task;
    }

    public function test_files_and_rows_older_than_24_hours_are_removed(): void
    {
        $user = User::factory()->create();
        $old = $this->task($user, 'old.zip', 'private', 25);
        $legacy = $this->task($user, 'legacy.zip', 'public', 30);
        $fresh = $this->task($user, 'fresh.zip', 'private', 2);

        $orphan = "{$this->storage}/app/public/downloads/orphan.zip";
        File::put($orphan, 'x');
        touch($orphan, now()->subDays(40)->timestamp);
        File::makeDirectory("{$this->storage}/app/temp_downloads/99", 0755, true);
        touch("{$this->storage}/app/temp_downloads/99", now()->subDays(2)->timestamp);

        $this->artisan('app:prune-download-files')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelMissing($legacy);
        $this->assertModelExists($fresh);
        $this->assertFileDoesNotExist("{$this->storage}/app/private/downloads/old.zip");
        $this->assertFileDoesNotExist("{$this->storage}/app/public/downloads/legacy.zip");
        $this->assertFileDoesNotExist($orphan);
        $this->assertDirectoryDoesNotExist("{$this->storage}/app/temp_downloads/99");
        $this->assertFileExists("{$this->storage}/app/private/downloads/fresh.zip");
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $task = $this->task(User::factory()->create(), 'old.zip', 'private', 48);

        $this->artisan('app:prune-download-files --dry-run')->assertSuccessful();

        $this->assertModelExists($task);
        $this->assertFileExists("{$this->storage}/app/private/downloads/old.zip");
    }

    public function test_owner_downloads_private_and_legacy_files_others_cannot(): void
    {
        $owner = User::factory()->create();
        $private = $this->task($owner, 'mine.zip', 'private', 1);
        $legacy = $this->task($owner, 'before.zip', 'public', 1);

        $this->actingAs($owner)->get(route('admin.downloads.download', $private))->assertOk();
        $this->actingAs($owner)->get(route('admin.downloads.download', $legacy))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('admin.downloads.download', $private))->assertForbidden();
    }

    public function test_pruning_is_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('app:prune-download-files')
            ->expectsOutputToContain('app:prune-orphan-files')
            ->assertSuccessful();
    }
}
