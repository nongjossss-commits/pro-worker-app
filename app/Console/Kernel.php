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
