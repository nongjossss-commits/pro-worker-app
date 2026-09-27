<?php

namespace App\Console\Commands;

use App\Models\DownloadTask;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * ลบไฟล์ดาวน์โหลดที่ระบบสร้าง (ZIP/PDF จากศูนย์ดาวน์โหลด) เมื่อครบ 24 ชม.
 *  - download_tasks ที่สร้างเกิน --hours: ลบไฟล์ + ลบแถว (ไม่เก็บประวัติ —
 *    การกดดาวน์โหลดยังถูกบันทึกใน Activity Log ตามเดิม)
 *  - ไฟล์ใน downloads/ (private และ public แบบเก่า) ที่เก่ากว่า --hours
 *    แม้ไม่มีแถวอ้างอิง
 *  - โฟลเดอร์ temp_downloads/{id} ที่ค้างจากงานที่ล้มเหลว
 *
 * ไฟล์เหล่านี้สร้างใหม่ได้จากเอกสารต้นฉบับของลูกจ้างเสมอ
 * Schedule รายชั่วโมงใน routes/console.php
 */
class PruneDownloadFiles extends Command
{
    protected $signature = 'app:prune-download-files
                            {--hours=24 : ลบไฟล์ที่สร้างนานกว่ากี่ชั่วโมง}
                            {--dry-run : แสดงรายการที่จะลบ แต่ไม่ลบจริง}';

    protected $description = 'ลบไฟล์ดาวน์โหลดที่ระบบสร้าง (ศูนย์ดาวน์โหลด) เมื่อเก่ากว่า 24 ชม.';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subHours($hours);

        $files = 0;
        $bytes = 0;
        $tasks = 0;

        DownloadTask::where('created_at', '<', $cutoff)->chunkById(200, function ($chunk) use ($dryRun, &$files, &$bytes, &$tasks) {
            foreach ($chunk as $task) {
                foreach ($this->taskFilePaths($task) as $fullPath) {
                    $bytes += (int) @filesize($fullPath);
                    $files++;
                    $dryRun ? $this->line("DRY: {$fullPath}") : @unlink($fullPath);
                }
                if (!$dryRun) {
                    $task->delete();
                }
                $tasks++;
            }
        });

        // Files with no task row left (or rows deleted by hand).
        foreach ([storage_path('app/private/downloads'), storage_path('app/public/downloads')] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach (File::files($dir) as $file) {
                if ($file->getMTime() < $cutoff->timestamp) {
                    $bytes += $file->getSize();
                    $files++;
                    $dryRun ? $this->line("DRY: {$file->getPathname()}") : @unlink($file->getPathname());
                }
            }
        }

        // Working folders left behind by a failed job.
        $tempRoot = storage_path('app/temp_downloads');
        if (is_dir($tempRoot)) {
            foreach (File::directories($tempRoot) as $dir) {
                if (filemtime($dir) < $cutoff->timestamp) {
                    $dryRun ? $this->line("DRY: {$dir}/") : File::deleteDirectory($dir);
                }
            }
        }

        $verb = $dryRun ? 'จะลบ' : 'ลบแล้ว';
        $this->info(sprintf('%s ไฟล์ %d ไฟล์ (%.2f GB), รายการดาวน์โหลด %d รายการ', $verb, $files, $bytes / 1073741824, $tasks));

        return self::SUCCESS;
    }

    /** @return string[] existing files for this task (private, or legacy public location) */
    protected function taskFilePaths(DownloadTask $task): array
    {
        if (!$task->file_path) {
            return [];
        }
        // file_path is always 'downloads/<name>' — never follow anything else.
        $relative = 'downloads/' . basename(str_replace('\\', '/', $task->file_path));

        return array_values(array_filter([
            storage_path('app/private/' . $relative),
            storage_path('app/public/' . $relative),
        ], 'is_file'));
    }
}
