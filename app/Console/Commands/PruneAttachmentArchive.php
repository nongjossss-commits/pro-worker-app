<?php

namespace App\Console\Commands;

use App\Support\AttachmentArchive;
use Illuminate\Console\Command;

/**
 * Replaced / removed attachments are kept 30 days (App\Support\AttachmentArchive),
 * then deleted for good — so the archive never fills the disk.
 * Scheduled daily in routes/console.php.
 */
class PruneAttachmentArchive extends Command
{
    protected $signature = 'app:prune-attachment-archive
                            {--dry-run : แสดงรายการที่จะลบ แต่ไม่ลบจริง}';

    protected $description = 'ลบไฟล์แนบเดิมที่เก็บสำรองไว้ครบ ' . AttachmentArchive::KEEP_DAYS . ' วันแล้ว';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $count = AttachmentArchive::prune($dryRun, $dryRun ? fn ($path) => $this->line("DRY: {$path}") : null);
        $this->info(($dryRun ? 'จะลบ' : 'ลบแล้ว') . " {$count} ไฟล์ (เก็บครบ " . AttachmentArchive::KEEP_DAYS . ' วัน)');

        return self::SUCCESS;
    }
}
