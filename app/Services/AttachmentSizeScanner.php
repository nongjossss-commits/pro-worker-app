<?php

namespace App\Services;

use App\Support\ActivityLogPresenter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Finds uploaded files at or above a size limit (menu "ขนาดไฟล์แนบ") and
 * works out which record / field each one belongs to, so a user can judge
 * whether e.g. a one-page passport scan really needs to be 12 MB and
 * re-upload a smaller copy.
 *
 * Owners are found generically: every table's text columns whose name looks
 * like a file field (…_doc_…, …_path, …Photo, slip, logo, stamp, …) are
 * searched for the file's path — so new upload fields are picked up without
 * changing this class. Results are cached for 10 minutes (rescan on demand).
 * Read-only: nothing is deleted or changed here.
 */
class AttachmentSizeScanner
{
    public const CACHE_MINUTES = 10;

    /** Tables never searched for file references (logs, queues, framework tables). */
    protected const SKIP_TABLES = [
        'activity_logs', 'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs', 'migrations',
        'password_reset_tokens', 'sessions', 'model_has_permissions', 'model_has_roles', 'permissions',
        'role_has_permissions', 'roles', 'labor_audit_logs', 'notifications', 'counters',
    ];

    protected const FILE_COLUMN = '/(doc|path|photo|file|slip|logo|stamp|signature|attachment|image|avatar|receipt|scan)/i';
    protected const NOT_FILE_COLUMN = '/(_desc|_expiry|_date|_mm|_at|_type|_name|_count|_size|_width|_height|_id|_position)$/i';

    /** Records that have their own screens: label, name columns, model basename, edit route. */
    protected const OWNERS = [
        'employees' => ['ลูกจ้าง', ['employeeNameEn', 'employeeNameTh'], 'Employee', 'employees.edit', 'employee'],
        'employers' => ['นายจ้าง', ['employerNameTh', 'employerNameEn'], 'Employer', 'employers.edit', 'employer'],
        'agents' => ['ตัวแทน', ['agentNameEn'], 'Agent', 'agents.edit', 'agent'],
        'importers' => ['บริษัทนำเข้า', ['importerNameTh', 'importerNameEn'], 'Importer', 'importers.edit', 'importer'],
        'delegates' => ['ผู้แทน', ['delegateNameTh', 'delegateNameEn'], 'Delegate', 'delegates.edit', 'delegate'],
    ];

    /** Folders under storage/app/public that hold the program's own data, not uploads. */
    protected const SKIP_FOLDERS = ['data'];

    /** Field names for tables without their own label list. */
    protected const GENERIC_FIELDS = [
        'slip_path' => 'สลิปการชำระ', 'pdf_path' => 'ไฟล์ PDF', 'logo_path' => 'โลโก้', 'signature_path' => 'ลายเซ็น',
        'stamp_path' => 'ตราประทับ', 'attachment_path' => 'ไฟล์แนบ', 'receipt_path' => 'ใบเสร็จ', 'file_path' => 'ไฟล์',
        'avatar_path' => 'รูปโปรไฟล์', 'photo' => 'รูปถ่าย', 'image_path' => 'รูปภาพ', 'template_path' => 'ไฟล์แม่แบบ',
    ];

    /** Friendly names for other tables that hold files. */
    protected const TABLE_LABELS = [
        'financial_profiles' => 'ข้อมูลผู้ตั้งบิล',
        'financial_payments' => 'การเงิน — การรับชำระ',
        'financial_transactions' => 'การเงิน — บิล',
        'expenses' => 'การเงิน — รายจ่าย',
        'ledger_entries' => 'การเงิน — สมุดบัญชี',
        'company_documents' => 'เอกสารบริษัท',
        'company_profiles' => 'โปรไฟล์บริษัท',
        'pdf_templates' => 'แม่แบบ PDF',
        'users' => 'ผู้ใช้งาน',
        'chat_messages' => 'แชท',
        'chat_groups' => 'กลุ่มแชท',
        'job_tickets' => 'ใบงาน',
        'ticket_messages' => 'ใบงาน — ข้อความ',
        'sales_lead_employees' => 'การขาย — ลูกจ้าง',
        'employee_generated_documents' => 'เอกสารที่ระบบสร้างให้ลูกจ้าง',
        'download_profiles' => 'โปรไฟล์ดาวน์โหลด',
        'pro_worker_contracts' => 'สัญญา Pro Worker',
        'pro_worker_contract_templates' => 'แม่แบบสัญญา Pro Worker',
        'labor_bill_payments' => 'Pro Walker Labour — การรับชำระ',
        'labor_bills' => 'Pro Walker Labour — ใบวางบิล',
        'labor_tax_invoices' => 'Pro Walker Labour — ใบกำกับภาษี',
        'labor_book_transactions' => 'Pro Walker Labour — สมุดบัญชี',
    ];

    /**
     * @return array{files: array, total_bytes: int, scanned_files: int, scanned_bytes: int, scanned_at: string, threshold: int}
     */
    public function scan(int $minBytes, bool $fresh = false): array
    {
        $key = 'attachment_size_scan:' . $minBytes;
        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addMinutes(self::CACHE_MINUTES), fn () => $this->doScan($minBytes));
    }

    protected function doScan(int $minBytes): array
    {
        $root = storage_path('app/public');
        $big = [];
        $count = 0;
        $totalAll = 0;

        if (is_dir($root)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($it as $file) {
                /** @var \SplFileInfo $file */
                if (!$file->isFile() || $file->isLink()) {
                    continue;
                }
                $relativePath = str_replace('\\', '/', ltrim(substr($file->getPathname(), strlen($root)), '\\/'));
                if (in_array(explode('/', $relativePath)[0], self::SKIP_FOLDERS, true)) {
                    continue;
                }
                $size = $file->getSize();
                $count++;
                $totalAll += $size;
                if ($size >= $minBytes) {
                    $relative = $relativePath;
                    $big[$relative] = [
                        'path' => $relative,
                        'name' => $file->getFilename(),
                        'folder' => dirname($relative),
                        'size' => $size,
                        'modified' => date('Y-m-d H:i', $file->getMTime()),
                        'extension' => strtolower($file->getExtension()),
                        'dimensions' => null, // filled per page by withDimensions()
                        'owners' => [],
                    ];
                }
            }
        }

        if ($big) {
            $this->attachOwners($big);
        }

        uasort($big, fn ($a, $b) => $b['size'] <=> $a['size']);

        return [
            'files' => array_values($big),
            'total_bytes' => array_sum(array_column($big, 'size')),
            'scanned_files' => $count,
            'scanned_bytes' => $totalAll,
            'scanned_at' => now()->format('d/m/Y H:i'),
            'threshold' => $minBytes,
        ];
    }

    protected function imageDimensions(string $path): ?string
    {
        try {
            $info = @getimagesize($path);
            return $info ? $info[0] . ' × ' . $info[1] . ' px' : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Fill $big[path]['owners'] with the records that point at each file.
     *
     * One pass per table: read only the id + file columns in chunks and match
     * the values against the big-file list in PHP, then load the full rows of
     * the few matches. (Asking every table "is any column IN (…all paths…)"
     * batch by batch meant a full table scan per batch — the slow part of the
     * first visit.)
     */
    protected function attachOwners(array &$big): void
    {
        foreach ($this->fileColumns() as $table => $columns) {
            $hasId = Schema::hasColumn($table, 'id');
            $matches = []; // [rowKey => [column, path][]]

            $collect = function ($rows) use ($columns, $big, &$matches) {
                foreach ($rows as $row) {
                    $row = (array) $row;
                    foreach ($columns as $column) {
                        $path = $this->normalisePath($row[$column] ?? null);
                        if ($path !== null && isset($big[$path])) {
                            $matches[$row['id'] ?? spl_object_id((object) $row)][] = [$column, $path, $row];
                        }
                    }
                }
            };

            $query = DB::table($table)->where(function ($q) use ($columns) {
                foreach ($columns as $column) {
                    $q->orWhere(fn ($w) => $w->whereNotNull($column)->where($column, '!=', ''));
                }
            });

            if ($hasId) {
                $query->select(array_merge(['id'], $columns))->chunkById(2000, $collect);
            } else {
                $collect($query->get()); // pivot-style tables without an id are small
            }

            if (!$matches) {
                continue;
            }

            // Full rows (names, deleted_at, descriptions) only for the matches.
            $fullRows = $hasId
                ? DB::table($table)->whereIn('id', array_keys($matches))->get()->keyBy('id')
                : collect();

            foreach ($matches as $key => $hits) {
                foreach ($hits as [$column, $path, $partial]) {
                    $row = $hasId && isset($fullRows[$key]) ? (array) $fullRows[$key] : $partial;
                    $big[$path]['owners'][] = $this->describeOwner($table, $column, $row);
                }
            }
        }
    }

    /** Stored values may carry a "storage/" or "/storage/" prefix. */
    protected function normalisePath($value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        $value = str_replace('\\', '/', $value);
        foreach (['/storage/', 'storage/'] as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return substr($value, strlen($prefix));
            }
        }
        return $value;
    }

    /** Pixel size for the image files on the page being shown (read lazily, not during the scan). */
    public function withDimensions(array $files): array
    {
        $root = storage_path('app/public') . DIRECTORY_SEPARATOR;
        foreach ($files as &$f) {
            if ($f['dimensions'] === null && in_array($f['extension'], ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'tif', 'tiff'], true)) {
                $f['dimensions'] = $this->imageDimensions($root . $f['path']);
            }
        }
        return $files;
    }

    /** table => [file-like text columns] for every table that has any (cached — reading the schema is slow on MySQL). */
    protected function fileColumns(): array
    {
        return Cache::remember('attachment_size_scan:file_columns', now()->addHours(6), fn () => $this->readFileColumns());
    }

    protected function readFileColumns(): array
    {
        $result = [];
        foreach (Schema::getTables() as $t) {
            $table = $t['name'];
            if (in_array($table, self::SKIP_TABLES, true)) {
                continue;
            }
            $cols = [];
            foreach (Schema::getColumns($table) as $col) {
                $type = strtolower($col['type_name'] ?? $col['type'] ?? '');
                if (!preg_match('/char|text|string/', $type)) {
                    continue;
                }
                if (preg_match(self::FILE_COLUMN, $col['name']) && !preg_match(self::NOT_FILE_COLUMN, $col['name'])) {
                    $cols[] = $col['name'];
                }
            }
            if ($cols) {
                $result[$table] = $cols;
            }
        }
        return $result;
    }

    protected function describeOwner(string $table, string $column, array $row): array
    {
        if (isset(self::OWNERS[$table])) {
            [$label, $nameCols, $model, $editRoute, $previewType] = self::OWNERS[$table];
            $name = '';
            foreach ($nameCols as $c) {
                if (!empty($row[$c])) {
                    $name = $row[$c];
                    break;
                }
            }
            return [
                'type' => $label,
                'name' => $name ?: '-',
                'id' => $row['id'] ?? null,
                'field' => ActivityLogPresenter::fieldLabel($model, $column, $row),
                'edit_url' => isset($row['id']) && \Route::has($editRoute) ? route($editRoute, $row['id']) : null,
                'preview_type' => $previewType,
                'deleted' => !empty($row['deleted_at']),
            ];
        }

        return [
            'type' => self::TABLE_LABELS[$table] ?? Str::headline($table),
            'name' => $row['name'] ?? $row['title'] ?? $row['bill_no'] ?? $row['description'] ?? '',
            'id' => $row['id'] ?? null,
            'field' => self::GENERIC_FIELDS[$column] ?? ActivityLogPresenter::fieldLabel('', $column),
            'edit_url' => null,
            'preview_type' => null,
            'deleted' => !empty($row['deleted_at']),
        ];
    }
}
