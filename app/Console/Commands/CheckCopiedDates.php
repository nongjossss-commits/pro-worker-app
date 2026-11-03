<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;

/**
 * Read-only check after the 2026-11-03 report: adding employees one after
 * another from Workflow → "Add Employee" → New saved the previous employee's
 * dates into the next one (the date boxes looked empty). Changes nothing.
 *
 * Lists employees whose dates are identical to the employee created just
 * before them (within --hours), in --min or more date fields — the pattern
 * that bug leaves. Two real people rarely share several of these dates.
 * Check each pair and correct the dates by hand where they are wrong.
 *
 *   php artisan app:check-copied-dates
 *   php artisan app:check-copied-dates --days=365 --min=2
 */
class CheckCopiedDates extends Command
{
    protected $signature = 'app:check-copied-dates
                            {--days=180 : How far back to look (by the date the employee was created)}
                            {--hours=3 : Two employees count as "added one after another" within this many hours}
                            {--min=3 : How many identical dates make a pair suspicious}';

    protected $description = 'หาลูกจ้างที่วันที่ต่าง ๆ ซ้ำกับลูกจ้างที่สร้างก่อนหน้า (บั๊กฟอร์มเพิ่มลูกจ้างใน Workflow) — อ่านอย่างเดียว ไม่แก้ข้อมูล';

    public const DATE_FIELDS = [
        'employeeDob', 'passport_issue_date', 'passportExpiryDate', 'visaExpiryDate', 'visaEndorsementDate',
        'workPermitIssueDate', 'workPermitExpiryDate', 'ninetyDayReportDate', 'startDate',
        'sso_issue_date', 'sso_expiry_date', 'insurance_expiry_date_hospital', 'insurance_expiry_date_private',
    ];

    public function handle(): int
    {
        $since = now()->subDays(max(1, (int) $this->option('days')));
        $hours = max(1, (int) $this->option('hours'));
        $min = max(1, (int) $this->option('min'));

        $columns = array_merge(['id', 'employer_id', 'employeeNameEn', 'employeeNameTh', 'employeePassport', 'created_at'], self::DATE_FIELDS);
        $employees = Employee::withoutGlobalScopes()
            ->where('created_at', '>=', $since)
            ->orderBy('created_at')->orderBy('id')
            ->get($columns);

        $rows = [];
        $previous = null;
        foreach ($employees as $e) {
            if ($previous && $previous->created_at && $e->created_at
                && $previous->created_at->diffInHours($e->created_at) <= $hours) {
                $same = [];
                foreach (self::DATE_FIELDS as $f) {
                    $a = $this->day($previous->getRawOriginal($f));
                    $b = $this->day($e->getRawOriginal($f));
                    if ($a !== null && $a === $b) {
                        $same[] = $f;
                    }
                }
                if (count($same) >= $min) {
                    $rows[] = [
                        $e->id,
                        $this->name($e),
                        $e->created_at->format('d/m/Y H:i'),
                        $previous->id . ' ' . $this->name($previous),
                        count($same) . ': ' . implode(', ', $same),
                    ];
                }
            }
            $previous = $e;
        }

        $this->info('ตรวจลูกจ้างที่สร้างตั้งแต่ ' . $since->format('d/m/Y') . ' (' . $employees->count() . ' ราย) · สร้างต่อกันภายใน ' . $hours . ' ชม. · วันที่ซ้ำ ≥ ' . $min . ' ช่อง');
        if (! $rows) {
            $this->info('ไม่พบลูกจ้างที่น่าสงสัย');
            return self::SUCCESS;
        }

        $this->warn('พบ ' . count($rows) . ' ราย — ตรวจและแก้วันที่ที่ผิดด้วยมือ (คำสั่งนี้ไม่แก้ข้อมูล)');
        $this->table(['ID', 'ลูกจ้าง', 'สร้างเมื่อ', 'ซ้ำกับลูกจ้างก่อนหน้า', 'วันที่ที่ซ้ำ'], $rows);

        return self::SUCCESS;
    }

    private function day($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return substr((string) $value, 0, 10);
    }

    private function name(Employee $e): string
    {
        return trim(($e->employeeNameEn ?: $e->employeeNameTh ?: '-') . ($e->employeePassport ? ' (' . $e->employeePassport . ')' : ''));
    }
}
