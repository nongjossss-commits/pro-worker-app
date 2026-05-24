<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * ลบ temp/orphan files ที่ค้างใน storage รายวัน
 *  - temp_uploads/ — ไฟล์ที่ user upload แต่ไม่ได้ submit form (เก่ากว่า 24 ชม.)
 *  - temp/batches/ — PDF batch folders ที่สร้างแล้วแต่ไม่ download (เก่ากว่า 7 วัน)
 *  - temp/ — temp file ของ PDF normalization/streaming ที่ค้าง (เก่ากว่า 1 วัน)
 *
 * Schedule รายวันใน Console/Kernel.php
 */
class PruneOrphanFiles extends Command
{
    protected $signature = 'app:prune-orphan-files
                            {--dry-run : แสดงรายชื่อไฟล์ที่จะลบ แต่ไม่ลบจริง}';

    protected $description = 'ลบ temp/orphan files ที่ค้างใน storage';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $disk = Storage::disk('public');

        $totalDeleted = 0;
        $totalDeleted += $this->pruneDirectory($disk, 'temp_uploads', 24, $dryRun);
        $totalDeleted += $this->pruneDirectory($disk, 'temp/batches', 24 * 7, $dryRun);
        $totalDeleted += $this->pruneDirectory($disk, 'temp', 24, $dryRun);

        $verb = $dryRun ? 'จะลบ' : 'ลบแล้ว';
        $this->info("{$verb} ไฟล์ทั้งหมด {$totalDeleted} ไฟล์");

        return self::SUCCESS;
    }

    /**
     * ลบไฟล์ใน directory ที่ lastmod เก่ากว่า $maxAgeHours
     * คืนจำนวนไฟล์ที่ถูกลบ
     */
    private function pruneDirectory($disk, string $dir, int $maxAgeHours, bool $dryRun): int
    {
        if (!$disk->exists($dir)) return 0;

        $cutoff = now()->subHours($maxAgeHours)->timestamp;
        $deleted = 0;

        foreach ($disk->allFiles($dir) as $path) {
            if ($disk->lastModified($path) < $cutoff) {
                if ($dryRun) {
                    $this->line("DRY: $path");
                } else {
                    $disk->delete($path);
                }
                $deleted++;
            }
        }

        return $deleted;
    }
}
