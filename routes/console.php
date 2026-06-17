<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule Daily Expiry Report
// Note: In Laravel 11/12, we typically use the Schedule facade in bootstrap/app.php or here if using the new structure.
// Assuming standard Scheduler setup picks up commands or we define schedule here using Schedule facade.
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:send-daily-expiry-report')->dailyAt('06:00');
Schedule::command('app:check-expiries')->dailyAt('01:00'); // Run check before report just in case

// Workflow MOU auto-apply: 24h after "Finish" pushes the admin-configured
// MOU group (and optional expiry) onto the linked employee. The 24h delay
// is the safety window so users can `restoreItem` to undo a finalize.
Schedule::command('app:apply-workflow-settings')->hourly();
