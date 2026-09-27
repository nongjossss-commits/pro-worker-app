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

// โหมดเช็คงาน: force-close any session still active/paused right at the
// 05:00 business-day cutover (user forgot to press "Finish"), then, a few
// minutes later, purge completed check-session reports older than the
// last 7 business days.
Schedule::command('app:auto-finish-stale-job-check-sessions')->dailyAt('05:00');
Schedule::command('app:cleanup-job-check-sessions')->dailyAt('05:15');

// Download Center files (ZIP/PDF built from employee documents) are only kept
// 24 hours — users rebuild them every time. Hourly, so each file goes about
// 24h after it was made. Also sweep temp_uploads/ and temp/ leftovers.
Schedule::command('app:prune-download-files')->hourly();

// แจ้งเข้า / เปลี่ยนนายจ้าง: 24h after a job is completed, move the employee to
// the job's employer. Was only in app/Console/Kernel.php, which Laravel 12
// does not load — so it never ran. Only jobs completed in the last 7 days
// (see the command).
Schedule::command('app:process-employee-transfers')->hourly();
Schedule::command('app:prune-orphan-files')->dailyAt('03:30');
