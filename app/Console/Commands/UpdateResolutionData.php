<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Auto-apply Registration / Renewal Resolution settings.
 *
 * After a user clicks "เสร็จสิ้น" on an employee in the Registration or
 * Renewal Resolution menu, we wait 24 hours (safety window for `restore`)
 * and then push the admin-configured auto values onto the employee:
 *   - workPermitExpiryDate  ← {group}_auto_work_permit_expiry
 *   - visaExpiryDate        ← {group}_auto_visa_expiry
 *   - workPermitMOUGroup    ← {group}_auto_mou_group
 *
 * Setting key format (per-tab preferred, global as fallback):
 *   {group}_auto_visa_expiry__tab_{tabId}     ← preferred (per-tab)
 *   {group}_auto_visa_expiry                  ← legacy global fallback
 *   (same pattern for auto_work_permit_expiry and auto_mou_group)
 *
 * Uses updateQuietly() to bypass EmployeeObserver — otherwise the new
 * dates could trigger syncRenewalStatus and immediately re-add the
 * employee to another resolution tab.
 *
 * Scheduled hourly from routes/console.php.
 */
class UpdateResolutionData extends Command
{
    protected $signature = 'app:update-resolution-data';

    protected $description = 'Apply admin-configured Auto Settings (visa/WP expiry, MOU group) to employees whose Resolution was finalized > 24 hours ago.';

    public function handle(): int
    {
        $this->info('UpdateResolutionData: starting…');

        $totalApplied = 0;
        foreach (['registration', 'renewal'] as $group) {
            $totalApplied += $this->processGroup($group);
        }

        $msg = "UpdateResolutionData: total applied = {$totalApplied}";
        $this->info($msg);
        Log::info($msg);

        return self::SUCCESS;
    }

    protected function processGroup(string $group): int
    {
        $status = "{$group}_completed";
        $this->info("Processing group: {$group} (status={$status})");

        // Pre-load all auto-settings for this group in one query, keyed by suffix.
        // - "__tab_{id}" suffix → per-tab
        // - "" (no suffix)     → legacy global
        $rows = SystemSetting::where('group', $group)
            ->where(function ($q) use ($group) {
                $q->where('key', 'like', "{$group}_auto_visa_expiry%")
                  ->orWhere('key', 'like', "{$group}_auto_work_permit_expiry%")
                  ->orWhere('key', 'like', "{$group}_auto_mou_group%");
            })
            ->get();

        $perTab = [];    // [tabId => ['visa'=>…, 'wp'=>…, 'mou'=>…]]
        $global = [];    // ['visa'=>…, 'wp'=>…, 'mou'=>…]

        foreach ($rows as $row) {
            $key = $row->key;
            $value = $row->value;
            if (!$value) continue; // ignore blanks — they mean "no auto-update"

            $field = null;
            $rest = null;
            foreach (['visa_expiry' => 'visa', 'work_permit_expiry' => 'wp', 'mou_group' => 'mou'] as $needle => $short) {
                $prefix = "{$group}_auto_{$needle}";
                if (str_starts_with($key, $prefix)) {
                    $field = $short;
                    $rest = substr($key, strlen($prefix));
                    break;
                }
            }
            if (!$field) continue;

            if ($rest === '') {
                $global[$field] = $value;
            } elseif (preg_match('/^__tab_(\d+)$/', $rest, $m)) {
                $perTab[(int) $m[1]][$field] = $value;
            }
        }

        if (empty($perTab) && empty($global)) {
            $this->info("  No auto-settings configured for {$group}. Skipping.");
            return 0;
        }

        $employees = Employee::where('status', $status)
            ->whereNotNull('resolution_completed_at')
            ->where('resolution_completed_at', '<=', now()->subHours(24))
            ->where(function ($q) {
                $q->whereNull('resolution_settings_applied')
                  ->orWhere('resolution_settings_applied', false);
            })
            ->get();

        if ($employees->isEmpty()) {
            $this->info("  No employees pending settings application for {$group}.");
            return 0;
        }

        $applied = 0;
        foreach ($employees as $employee) {
            // Per-tab settings win; fall back to global for any missing field.
            $tabId = $employee->resolution_tab_id;
            $cfg = $perTab[$tabId] ?? [];
            $cfg = array_merge($global, $cfg); // per-tab overrides global

            $updates = [];
            if (!empty($cfg['wp']))   $updates['workPermitExpiryDate'] = $cfg['wp'];
            if (!empty($cfg['visa'])) $updates['visaExpiryDate']       = $cfg['visa'];
            if (!empty($cfg['mou']))  $updates['workPermitMOUGroup']   = $cfg['mou'];

            // Always flag as applied so we don't re-check the same row every hour,
            // even if the tab has no config (nothing to apply) — otherwise the query
            // keeps re-fetching this employee forever.
            $updates['resolution_settings_applied'] = true;

            // updateQuietly bypasses EmployeeObserver → prevents auto re-pull
            // into another resolution tab when the new dates happen to match.
            $employee->updateQuietly($updates);
            $applied++;
        }

        $this->info("  Applied to {$applied} employee(s) in {$group}.");
        Log::info("UpdateResolutionData: applied to {$applied} employee(s) in {$group}.");
        return $applied;
    }
}
