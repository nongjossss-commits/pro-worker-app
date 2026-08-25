<?php

namespace App\Console\Commands;

use App\Models\JobCheckSession;
use App\Services\AccountingPeriodService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupJobCheckSessions extends Command
{
    protected $signature = 'app:cleanup-job-check-sessions';
    protected $description = 'Delete Job Check Mode (โหมดเช็คงาน) sessions and their exported Excel files once they fall outside the 7-business-day history window.';

    public function handle(): void
    {
        $cutoff = AccountingPeriodService::businessDate(now())->subDays(6);

        $old = JobCheckSession::where('status', 'completed')
            ->where('business_date', '<', $cutoff->toDateString())
            ->get();

        foreach ($old as $session) {
            Storage::disk('local')->deleteDirectory("job-check/{$session->id}");
            $session->snapshots()->delete();
            $session->delete();
        }
        $this->info("Removed {$old->count()} completed Job Check sessions older than {$cutoff->toDateString()}.");

        // Sessions the user opened and abandoned without finishing carry no
        // exported files — just prune the stale rows so they don't linger.
        $staleCancelled = JobCheckSession::where('status', 'cancelled')
            ->where('created_at', '<', now()->subDays(7))
            ->delete();
        $this->info("Removed {$staleCancelled} stale cancelled Job Check sessions.");
    }
}
