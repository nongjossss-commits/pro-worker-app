<?php

namespace App\Console\Commands;

use App\Models\JobCheckSession;
use App\Services\JobCheckService;
use Illuminate\Console\Command;

/**
 * โหมดเช็คงาน: force-closes any session still 'active' or 'paused' when
 * this runs (scheduled right at the 05:00 business-day cutover) — e.g. the
 * user opened Job Check Mode, forgot to press "Finish", and logged off for
 * the day. Without this, that session would sit open forever, blocking a
 * fresh check pass from ever starting cleanly the next day. Runs the exact
 * same diff+export logic as the user-triggered "Finish" button, filed
 * under the business day the session actually STARTED on (not today), so
 * the export lands in the right day's history.
 */
class AutoFinishStaleJobCheckSessions extends Command
{
    protected $signature = 'app:auto-finish-stale-job-check-sessions';

    protected $description = 'Auto-finish any Job Check Mode session left active/paused past the 05:00 cutover';

    public function handle(JobCheckService $jobCheckService): int
    {
        $sessions = JobCheckSession::whereIn('status', ['active', 'paused'])->get();

        if ($sessions->isEmpty()) {
            $this->info('No stale Job Check Mode sessions to close.');
            return self::SUCCESS;
        }

        $now = now();
        foreach ($sessions as $session) {
            $result = $jobCheckService->completeSession($session, $now);
            $this->info("Auto-finished session #{$session->id} (user {$session->user_id}): {$result['moved_count']} moved, {$result['not_moved_count']} not moved.");
        }

        return self::SUCCESS;
    }
}
