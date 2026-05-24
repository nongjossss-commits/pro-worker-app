<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

/**
 * ลบ activity logs เก่ากว่า N วัน เพื่อกัน table โตไม่หยุด
 *  - default: เก็บ 365 วัน (1 ปี)
 *  - แก้ผ่าน option --days=N
 *
 * Schedule รายเดือนใน Console/Kernel.php
 */
class PruneActivityLogs extends Command
{
    protected $signature = 'app:prune-activity-logs
                            {--days=365 : เก็บ logs ย้อนหลังกี่วัน (default 365)}
                            {--dry-run : นับจำนวนที่จะลบ แต่ไม่ลบจริง}';

    protected $description = 'ลบ activity logs ที่เก่ากว่า N วัน';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');

        $cutoff = now()->subDays($days);
        $query = ActivityLog::where('created_at', '<', $cutoff);

        $count = $query->count();

        if ($count === 0) {
            $this->info("ไม่มี logs เก่ากว่า {$days} วัน");
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("DRY: จะลบ {$count} logs ที่เก่ากว่า {$cutoff->format('Y-m-d')}");
            return self::SUCCESS;
        }

        // chunk ลดภาระ memory เมื่อมี logs เยอะ
        $deleted = $query->delete();
        $this->info("ลบไป {$deleted} logs (เก่ากว่า {$cutoff->format('Y-m-d')})");

        return self::SUCCESS;
    }
}
