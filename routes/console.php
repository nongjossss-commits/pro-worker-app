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

// Registration/Renewal Resolution auto-apply: 24h after "เสร็จสิ้น" pushes
// the admin-configured Auto Settings (visa/WP expiry + MOU group) onto the
// employee. Same 24h safety window as the workflow command above so users
// can `restore` to undo a finalize. Supports per-tab and legacy global keys.
Schedule::command('app:update-resolution-data')->hourly();

// โหมดเช็คงาน: keep only the last 7 business days (05:00 cutoff) of
// completed check-session reports; runs shortly after the cutoff moment.
Schedule::command('app:cleanup-job-check-sessions')->dailyAt('05:15');
