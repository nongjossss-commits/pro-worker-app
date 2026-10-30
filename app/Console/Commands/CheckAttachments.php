<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Employer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Read-only check after "data / files went missing" reports (2026-10-30).
 * Changes nothing — run it on the server to see:
 *
 *  1. Attachments the database still points to but that are NOT on the
 *     server (employees and employers). A file recorded but missing is a
 *     storage / system problem, not "never attached".
 *  2. With --sso: employees whose social-security issue / expiry date was
 *     blanked by a work-card quick edit (the bug fixed 2026-10-30) — found
 *     in the activity log, with the date and the value it had before.
 *
 *   php artisan app:check-attachments
 *   php artisan app:check-attachments --sso --days=365
 *
 * Records in the trash are checked too (withoutGlobalScopes()).
 */
class CheckAttachments extends Command
{
    protected $signature = 'app:check-attachments
                            {--sso : Also list social-security dates blanked by quick edits (from the activity log)}
                            {--days=180 : How far back to look in the activity log for --sso}';

    protected $description = 'ตรวจไฟล์แนบที่ฐานข้อมูลบันทึกไว้แต่ไม่มีบนเซิร์ฟเวอร์ (อ่านอย่างเดียว ไม่แก้ข้อมูล)';

    public const EMPLOYEE_FILE_FIELDS = [
        'employeePhoto', 'insurance_document_path', 'insurance_document_path_private', 'medical_certificate_path',
        'employee_doc_1', 'employee_doc_2', 'employee_doc_3', 'employee_doc_4', 'employee_doc_5', 'employee_doc_6',
        'employee_doc_7', 'employee_doc_8', 'employee_doc_9', 'employee_doc_10', 'employee_doc_11', 'employee_doc_12',
        'employee_doc_13', 'employee_doc_14', 'employee_doc_15', 'employee_doc_16', 'employee_doc_17', 'employee_doc_18',
    ];

    public const EMPLOYER_FILE_FIELDS = [
        'employer_doc_company', 'employer_doc_lease', 'employer_doc_construction',
        'employer_doc_other_1', 'employer_doc_other_2', 'employer_doc_other_3',
        'signature_1_path', 'signature_2_path', 'employer_stamp_path',
    ];

    public function handle(): int
    {
        $rows = [];
        $checked = 0;

        $scan = function ($query, array $fields, string $kind, callable $name) use (&$rows, &$checked) {
            $query->withoutGlobalScopes()->select(array_merge(['id', 'deleted_at'], $this->namesFor($kind), $fields))
                ->chunkById(500, function ($models) use ($fields, $kind, $name, &$rows, &$checked) {
                    foreach ($models as $model) {
                        foreach ($fields as $field) {
                            $path = $model->getRawOriginal($field);
                            if (!$path) {
                                continue;
                            }
                            $checked++;
                            if (!Storage::disk('public')->exists($path) && !Storage::disk('private')->exists($path)) {
                                $rows[] = [$kind, $model->id, $name($model) . ($model->deleted_at ? ' (ในถังขยะ)' : ''), $field, $path];
                            }
                        }
                    }
                });
        };

        $existing = fn (string $table, array $fields) => array_values(array_filter($fields, fn ($f) => \Illuminate\Support\Facades\Schema::hasColumn($table, $f)));

        $scan(Employee::query(), $existing('employees', self::EMPLOYEE_FILE_FIELDS), 'ลูกจ้าง',
            fn ($e) => $e->getRawOriginal('employeeNameEn') ?: $e->getRawOriginal('employeeNameTh'));
        $scan(Employer::query(), $existing('employers', self::EMPLOYER_FILE_FIELDS), 'นายจ้าง',
            fn ($e) => $e->getRawOriginal('employerNameTh') ?: $e->getRawOriginal('employerNameEn'));

        $this->info("ตรวจไฟล์แนบที่บันทึกในฐานข้อมูล {$checked} ไฟล์");
        if ($rows) {
            $this->warn('ไฟล์ที่ฐานข้อมูลบันทึกไว้ แต่ไม่มีบนเซิร์ฟเวอร์: ' . count($rows) . ' ไฟล์');
            $this->table(['ประเภท', 'รหัส', 'ชื่อ', 'ช่อง', 'path ที่บันทึก'], $rows);
        } else {
            $this->info('ไม่พบไฟล์ที่หายจากเซิร์ฟเวอร์ — ไฟล์ทุกไฟล์ที่บันทึกไว้ยังอยู่ครบ');
        }

        if ($this->option('sso')) {
            $this->checkSsoDates(max(1, (int) $this->option('days')));
        }

        return self::SUCCESS;
    }

    protected function namesFor(string $kind): array
    {
        return $kind === 'ลูกจ้าง' ? ['employeeNameEn', 'employeeNameTh'] : ['employerNameTh', 'employerNameEn'];
    }

    /**
     * Updates where sso_issue_date / sso_expiry_date went from a date to empty
     * while the insurance type was not part of the change — the quick-edit bug.
     */
    protected function checkSsoDates(int $days): void
    {
        $rows = [];
        ActivityLog::where('subject_type', Employee::class)
            ->where('action', 'update')
            ->where('created_at', '>=', now()->subDays($days))
            ->where(fn ($q) => $q->where('properties', 'like', '%sso_issue_date%')->orWhere('properties', 'like', '%sso_expiry_date%'))
            ->orderBy('id')
            ->chunkById(500, function ($logs) use (&$rows) {
                foreach ($logs as $log) {
                    $p = is_array($log->properties) ? $log->properties : [];
                    $old = $p['old'] ?? [];
                    $new = $p['attributes'] ?? [];
                    if (array_key_exists('insurance_type', $new)) {
                        continue; // the insurance type itself changed — an intended clear
                    }
                    foreach (['sso_issue_date', 'sso_expiry_date'] as $f) {
                        if (!empty($old[$f]) && array_key_exists($f, $new) && empty($new[$f])) {
                            $rows[] = [$log->created_at->format('d/m/Y H:i'), $log->subject_id, $f, substr((string) $old[$f], 0, 10), $log->user_id ?? 'ระบบ'];
                        }
                    }
                }
            });

        if ($rows) {
            $this->warn("วันที่ประกันสังคมที่ถูกล้างโดยการแก้ไขด่วนบนการ์ด (บั๊กที่แก้แล้ว) ใน {$days} วันล่าสุด: " . count($rows) . ' รายการ');
            $this->table(['เมื่อ', 'รหัสลูกจ้าง', 'ช่อง', 'ค่าเดิม', 'ผู้ใช้ (id)'], $rows);
        } else {
            $this->info("ไม่พบวันที่ประกันสังคมที่ถูกล้างโดยการแก้ไขด่วนใน {$days} วันล่าสุด");
        }
    }
}
