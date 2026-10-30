<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Replaced or removed attachments are kept, not deleted (2026-10-30, after
 * reports of "lost" files): the old file is moved to the private disk under
 *
 *   attachment_archive/{employees|employers}/{id}/{YYYYmmdd_His}_{field}_{name}
 *
 * so it can always be recovered, and the activity log can say "the old file
 * was kept" (LogActivity picks up what was archived for the model being saved
 * and stores it with the update — see pending()).
 *
 * The private disk is not reachable from the web; restoring a file is done
 * by an admin on the server.
 */
class AttachmentArchive
{
    /** [model key => [field => archived path]] for saves still to come in this request. */
    protected static array $pending = [];

    /**
     * Move $path (public disk, or private as a fallback) into the archive.
     * Returns the archived path, or null when there was nothing to keep.
     * Never throws: a failure is logged and the old file is left where it is.
     */
    public static function keep(?string $path, string $owner, int $id, string $field): ?string
    {
        if (!$path) {
            return null;
        }

        try {
            $source = Storage::disk('public')->exists($path) ? 'public'
                : (Storage::disk('private')->exists($path) ? 'private' : null);
            if ($source === null) {
                return null; // already gone — nothing to keep
            }

            $archived = 'attachment_archive/' . $owner . '/' . $id . '/' . now()->format('Ymd_His') . '_' . $field . '_' . basename($path);
            for ($n = 2; Storage::disk('private')->exists($archived); $n++) {
                $archived = preg_replace('/(\.[^.\/]+)?$/', "_{$n}$1", $archived, 1);
            }

            $stream = Storage::disk($source)->readStream($path);
            Storage::disk('private')->writeStream($archived, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            if (!Storage::disk('private')->exists($archived)) {
                Log::warning("AttachmentArchive: could not write {$archived}; original kept at {$path}");
                return null;
            }
            Storage::disk($source)->delete($path);

            self::$pending[$owner . ':' . $id][$field] = $archived;

            return $archived;
        } catch (\Throwable $e) {
            Log::warning("AttachmentArchive: keeping {$path} failed — original left in place: " . $e->getMessage());
            return null;
        }
    }

    /** Archived files waiting for this model's activity-log entry (taken once). */
    public static function pending(string $owner, int $id): array
    {
        $key = $owner . ':' . $id;
        $files = self::$pending[$key] ?? [];
        unset(self::$pending[$key]);

        return $files;
    }

    /** "employees" / "employers" / … for a model class. */
    public static function ownerFor(object|string $model): string
    {
        return \Illuminate\Support\Str::plural(strtolower(class_basename($model)));
    }

    /** Kept files are deleted for good after this many days (app:prune-attachment-archive). */
    public const KEEP_DAYS = 30;

    /** Owners that have a viewer page (route segment => model class). */
    public const OWNERS = ['employees' => \App\Models\Employee::class, 'employers' => \App\Models\Employer::class];

    /**
     * The kept files of one record, newest first:
     * [path, field, archived_at (Carbon), deleted_on (Carbon), name, size].
     */
    public static function files(string $owner, int $id): array
    {
        $files = [];
        foreach (Storage::disk('private')->files("attachment_archive/{$owner}/{$id}") as $path) {
            $files[] = self::describe($path);
        }
        usort($files, fn ($a, $b) => $b['archived_at'] <=> $a['archived_at']);

        return $files;
    }

    /** True when $path is one of this record's kept files (no "../" tricks). */
    public static function belongsTo(string $path, string $owner, int $id): bool
    {
        return isset(self::OWNERS[$owner])
            && !str_contains($path, '..')
            && dirname($path) === "attachment_archive/{$owner}/{$id}"
            && Storage::disk('private')->exists($path);
    }

    /** Read "{YmdHis}_{field}_{original name}" back. */
    protected static function describe(string $path): array
    {
        $base = basename($path);
        $archivedAt = null;
        $field = '';
        $name = $base;
        if (preg_match('/^(\d{8}_\d{6})_(.+)$/', $base, $m)) {
            try {
                $archivedAt = \Carbon\Carbon::createFromFormat('Ymd_His', $m[1]);
            } catch (\Throwable $e) {
            }
            // The field is one of the known file columns (longest first: employee_doc_10 before employee_doc_1).
            $known = array_merge(\App\Console\Commands\CheckAttachments::EMPLOYEE_FILE_FIELDS, \App\Console\Commands\CheckAttachments::EMPLOYER_FILE_FIELDS);
            usort($known, fn ($a, $b) => strlen($b) <=> strlen($a));
            foreach ($known as $column) {
                if (str_starts_with($m[2], $column . '_')) {
                    $field = $column;
                    $name = substr($m[2], strlen($column) + 1);
                    break;
                }
            }
        }
        $archivedAt ??= \Carbon\Carbon::createFromTimestamp(Storage::disk('private')->lastModified($path));

        return [
            'path' => $path,
            'field' => $field,
            'archived_at' => $archivedAt,
            'deleted_on' => $archivedAt->copy()->addDays(self::KEEP_DAYS),
            'name' => $name,
            'size' => Storage::disk('private')->size($path),
        ];
    }

    /**
     * Delete kept files older than KEEP_DAYS (and empty folders). Returns how
     * many files were deleted.
     */
    public static function prune(bool $dryRun = false, ?callable $report = null): int
    {
        $disk = Storage::disk('private');
        if (!$disk->exists('attachment_archive')) {
            return 0;
        }
        $cutoff = now()->subDays(self::KEEP_DAYS);
        $deleted = 0;
        foreach ($disk->allFiles('attachment_archive') as $path) {
            if (self::describe($path)['archived_at']->lt($cutoff)) {
                $report && $report($path);
                if (!$dryRun) {
                    $disk->delete($path);
                }
                $deleted++;
            }
        }
        if (!$dryRun) {
            foreach (array_reverse($disk->allDirectories('attachment_archive')) as $dir) {
                if (!$disk->allFiles($dir)) {
                    $disk->deleteDirectory($dir);
                }
            }
        }

        return $deleted;
    }
}
