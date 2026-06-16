<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\SalesLead;
use App\Models\SalesLeadEmployee;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

/**
 * Repairs legacy rows where the suffix accessor wrote " (suffix)" back into
 * the raw name column on save (pre-63b3e423 bug). For each row whose name
 * column ends with one or more "(suffix)" segments we strip them out until
 * the column holds the bare name again.
 *
 * Defaults to dry-run. Pass --apply to commit.
 *
 *   php artisan finance-data:cleanup-name-suffix-duplicates
 *   php artisan finance-data:cleanup-name-suffix-duplicates --apply
 */
class CleanupNameSuffixDuplicates extends Command
{
    protected $signature = 'finance-data:cleanup-name-suffix-duplicates
                            {--apply : commit the cleanup (default: dry-run)}
                            {--limit=20 : how many before/after samples to print per table}';

    protected $description = 'Strip duplicated " (suffix)" segments left behind in name columns by the pre-fix accessor bug.';

    /**
     * Each entry: [model class, raw name column, label for the report]
     */
    private const TARGETS = [
        [Employee::class,           'employeeNameEn',  'Employees'],
        [Employer::class,           'employerNameTh',  'Employers'],
        [SalesLead::class,          'employerNameTh',  'Sales Leads (Employer)'],
        [SalesLeadEmployee::class,  'employeeNameEn',  'Sales Lead Employees'],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $sampleLimit = (int) $this->option('limit');

        if ($apply) {
            $this->warn('APPLY MODE — changes will be committed to the database.');
        } else {
            $this->info('DRY-RUN MODE — no rows will be modified. Use --apply to commit.');
        }
        $this->newLine();

        $grandTotal = 0;

        foreach (self::TARGETS as [$modelClass, $column, $label]) {
            $count = $this->processTable($modelClass, $column, $label, $apply, $sampleLimit);
            $grandTotal += $count;
        }

        $this->newLine();
        $this->info("=== TOTAL ROWS " . ($apply ? 'CLEANED' : 'TO CLEAN') . ": {$grandTotal} ===");

        return self::SUCCESS;
    }

    protected function processTable(string $modelClass, string $column, string $label, bool $apply, int $sampleLimit): int
    {
        $this->line("--- {$label} ({$column}) ---");

        // Pull only rows that could be affected — has a suffix, and the column already
        // ends with the suffix in parentheses. Filtering in PHP because the pattern
        // depends on each row's own suffix value, which is awkward in pure SQL across
        // both MySQL and SQLite test setups.
        $candidates = $modelClass::query()
            ->whereNotNull('name_suffix')
            ->where('name_suffix', '!=', '')
            ->get()
            ->filter(function (Model $row) use ($column) {
                $raw = $row->getRawOriginal($column);
                $suffix = $row->getAttribute('name_suffix');
                if (!$raw || !$suffix) return false;
                return $this->stripTrailingSuffixes($raw, $suffix) !== $raw;
            })
            ->values();

        if ($candidates->isEmpty()) {
            $this->line("  No corrupted rows.");
            $this->newLine();
            return 0;
        }

        $this->line("  Found <fg=yellow>{$candidates->count()}</> corrupted row(s).");

        // Show samples (before → after)
        $samples = $candidates->take($sampleLimit);
        $this->table(
            ['ID', 'Suffix', 'BEFORE', 'AFTER'],
            $samples->map(function (Model $row) use ($column) {
                $raw = $row->getRawOriginal($column);
                $sfx = $row->getAttribute('name_suffix');
                return [
                    $row->getKey(),
                    $sfx,
                    $this->truncate($raw),
                    $this->truncate($this->stripTrailingSuffixes($raw, $sfx)),
                ];
            })->toArray()
        );

        if ($candidates->count() > $sampleLimit) {
            $this->line("  …(+ " . ($candidates->count() - $sampleLimit) . " more not shown)");
        }

        if (!$apply) {
            $this->newLine();
            return $candidates->count();
        }

        // Apply — write the cleaned value back using updateQuietly so we don't
        // bump updated_at and don't accidentally trigger any observer-side
        // accessor evaluation.
        $cleaned = 0;
        foreach ($candidates as $row) {
            $raw = $row->getRawOriginal($column);
            $sfx = $row->getAttribute('name_suffix');
            $fixed = $this->stripTrailingSuffixes($raw, $sfx);
            if ($fixed !== $raw) {
                // Direct DB write — bypass model accessors / mutators / events.
                $modelClass::query()
                    ->where($row->getKeyName(), $row->getKey())
                    ->update([$column => $fixed]);
                $cleaned++;
            }
        }
        $this->info("  Cleaned {$cleaned} row(s) in {$label}.");
        $this->newLine();
        return $cleaned;
    }

    /**
     * Repeatedly trim " (suffix)" off the end until no more match.
     * Handles double / triple / quadruple appends from the legacy bug.
     */
    protected function stripTrailingSuffixes(string $value, string $suffix): string
    {
        $needle = '(' . $suffix . ')';
        $trimmed = rtrim($value);
        while (str_ends_with($trimmed, $needle)) {
            $trimmed = rtrim(substr($trimmed, 0, -strlen($needle)));
        }
        return $trimmed;
    }

    protected function truncate(string $s, int $max = 60): string
    {
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max - 1) . '…' : $s;
    }
}
