<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\CheckExpiries::class,
        \App\Console\Commands\PruneSoftDeletes::class,
        \App\Console\Commands\ProcessEmployeeTransfers::class,
        \App\Console\Commands\UpdateResolutionData::class,
        \App\Console\Commands\PruneOrphanFiles::class,
        \App\Console\Commands\PruneActivityLogs::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('app:check-expiries')->daily()->timezone('Asia/Bangkok');
        $schedule->command('app:prune-soft-deletes')->daily();
        $schedule->command('app:process-employee-transfers')->hourly();
        $schedule->command('app:update-resolution-data')->hourly();
        $schedule->command('resolution-tabs:purge')->daily()->timezone('Asia/Bangkok');
        // ลบ orphan/temp files รายวัน — ไฟล์ใน temp_uploads/, temp/batches/, temp/
        $schedule->command('app:prune-orphan-files')->daily()->timezone('Asia/Bangkok');
        // ลบ activity logs เก่ากว่า 365 วัน รายเดือน — กัน table โตไม่หยุด
        $schedule->command('app:prune-activity-logs')->monthly()->timezone('Asia/Bangkok');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
