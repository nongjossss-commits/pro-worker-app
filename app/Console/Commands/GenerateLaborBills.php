<?php

namespace App\Console\Commands;

use App\Models\LaborTeam;
use App\Services\LaborBillService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Auto-billing for the Pro Walker Labor module. Each team has its own
 * cadence (see labor_teams.billing_cadence/billing_day_of_week/
 * billing_day_of_month, edited on the team's own page) — this command runs
 * daily and only fires for a team when today matches its schedule.
 *
 * Period boundaries are NOT calendar week/month boxes — each new bill starts
 * the day after the previous one's period_end (or the team's creation date,
 * for its first bill) and ends today. This keeps cadence purely about *when*
 * a team gets billed, while guaranteeing bills always chain with no gaps or
 * overlaps regardless of cadence.
 */
class GenerateLaborBills extends Command
{
    protected $signature = 'app:generate-labor-bills {--dry-run : List what would be billed without generating}';

    protected $description = 'Auto-generate Labor billing statements for teams whose schedule matches today';

    public function handle(LaborBillService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $today = Carbon::today();

        $teams = LaborTeam::where('is_active', true)
            ->where('auto_billing_enabled', true)
            ->get();

        $billed = 0;

        foreach ($teams as $team) {
            if ($team->last_auto_billed_on && $team->last_auto_billed_on->isSameDay($today)) {
                continue; // already billed today — never double-fire
            }

            if (!$this->cadenceMatchesToday($team, $today)) {
                continue;
            }

            $lastBill = $team->bills()->where('status', '!=', 'void')->orderByDesc('period_end')->first();
            $periodStart = $lastBill
                ? $lastBill->period_end->copy()->addDay()
                : $team->created_at->copy()->startOfDay();

            if ($periodStart->gt($today)) {
                continue; // nothing new to bill
            }

            $this->line("Billing [{$team->name}]: {$periodStart->format('Y-m-d')} - {$today->format('Y-m-d')}");

            if (!$dryRun) {
                $service->generate($team, $periodStart, $today->copy(), null, true);
                $team->update(['last_auto_billed_on' => $today]);
            }

            $billed++;
        }

        $this->info($dryRun ? "DRY: would bill {$billed} team(s)" : "Billed {$billed} team(s)");

        return self::SUCCESS;
    }

    protected function cadenceMatchesToday(LaborTeam $team, Carbon $today): bool
    {
        return match ($team->billing_cadence) {
            'daily' => true,
            'weekly' => $team->billing_day_of_week !== null
                && $today->dayOfWeek === (int) $team->billing_day_of_week,
            'monthly' => $team->billing_day_of_month !== null
                && $this->matchesDayOfMonth($today, (int) $team->billing_day_of_month),
            default => false,
        };
    }

    /**
     * Clamp to the last day of short months — e.g. billing_day_of_month=31
     * fires on Feb 28/29, Apr/Jun/Sep/Nov 30.
     */
    protected function matchesDayOfMonth(Carbon $today, int $dayOfMonth): bool
    {
        $target = min($dayOfMonth, $today->daysInMonth);
        return $today->day === $target;
    }
}
