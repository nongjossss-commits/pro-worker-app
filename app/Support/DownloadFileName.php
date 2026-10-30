<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Names for files people download (Advanced Export Excel / ZIP, Advanced
 * Download ZIP / PDF):
 *
 *   "<employer or menu name> (12 คน) 2026-10-30 15.36.12.xlsx"
 *
 * - the number of employees in brackets after the name
 * - date and time to the second, so two downloads rarely share a name
 * - if the same name comes up again for the same user (same second), it gets
 *   " (1)", " (2)" … like Windows does
 * - characters Windows / macOS can't use in a file name are replaced, and the
 *   name part is kept short enough for any file system
 */
class DownloadFileName
{
    /** A file / folder name part that is safe on Windows, macOS and inside a ZIP. */
    public static function safe(?string $name, int $max = 80): string
    {
        $name = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F\x7F]+/u', '_', (string) $name);
        $name = trim(preg_replace('/\s+/u', ' ', $name), " .\t");
        $name = mb_substr($name, 0, $max);

        return rtrim($name, ' .') ?: 'file';
    }

    /** "(12 คน)" in the user's language. */
    public static function people(int $count): string
    {
        return __('(:count people)', ['count' => $count]);
    }

    /** "<name> (n คน)" — an employer folder, or a file inside a ZIP. */
    public static function withCount(?string $name, int $count): string
    {
        return self::safe($name) . ' ' . self::people($count);
    }

    /**
     * The name of the file handed to the browser: name + (n คน) + date-time,
     * with " (1)", " (2)" … when this user already got the very same name.
     */
    public static function make(?string $name, int $count, string $ext, ?int $userId = null): string
    {
        $base = self::withCount($name, $count) . ' ' . now()->format('Y-m-d H.i.s');

        $key = 'download-file-name:' . ($userId ?? 0) . ':' . md5($base . '.' . $ext);
        Cache::add($key, 0, now()->addMinutes(10));
        $seen = (int) Cache::increment($key); // 1 = first time
        if ($seen > 1) {
            $base .= ' (' . ($seen - 1) . ')';
        }

        return $base . '.' . ltrim($ext, '.');
    }
}
