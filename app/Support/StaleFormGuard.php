<?php

namespace App\Support;

use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Two people editing the same record (2026-10-30, "data disappeared by
 * itself" reports): A opens the edit form, B saves a new passport number,
 * then A saves — A's form still holds the old number and used to put it back,
 * so B's change silently vanished.
 *
 * The edit forms now send when they were opened (_form_loaded_at). On save,
 * every field someone else changed after that moment (found in the activity
 * log) is left as they saved it — unless this user changed it too (the value
 * sent differs from the value before the other person's change). Fields the
 * user really edited are saved as usual.
 */
class StaleFormGuard
{
    /** Hidden input the edit forms carry. */
    public const INPUT = '_form_loaded_at';

    /**
     * Drop from $data the fields changed by someone else since $loadedAt that
     * this form only re-sent unchanged. Returns the fields kept that way.
     */
    public static function protect(Model $model, array &$data, $loadedAt): array
    {
        if (!$loadedAt || !is_numeric($loadedAt) || !$model->getKey()) {
            return [];
        }
        $since = Carbon::createFromTimestamp((int) $loadedAt);

        // The value each field had before the first change made after the form was opened.
        $before = [];
        ActivityLog::where('subject_type', get_class($model))
            ->where('subject_id', $model->getKey())
            ->where('action', 'update')
            ->where('created_at', '>=', $since)
            ->orderBy('id')
            ->get(['properties'])
            ->each(function ($log) use (&$before) {
                foreach (($log->properties['old'] ?? []) as $field => $value) {
                    if (!array_key_exists($field, $before)) {
                        $before[$field] = $value;
                    }
                }
            });

        $kept = [];
        foreach ($before as $field => $oldValue) {
            if ($field === 'password' || !array_key_exists($field, $data)) {
                continue;
            }
            // Sent back exactly what was there when the form was opened → not this user's edit.
            if (self::same($data[$field], $oldValue) && !self::same($data[$field], $model->getRawOriginal($field))) {
                unset($data[$field]);
                $kept[] = $field;
            }
        }

        return $kept;
    }

    /** Same value for a form: blank = null, dates by their day, text trimmed. */
    protected static function same($a, $b): bool
    {
        $norm = function ($v) {
            if ($v instanceof \DateTimeInterface) {
                $v = $v->format('Y-m-d');
            }
            if ($v === null) {
                return '';
            }
            $v = trim((string) $v);
            if (preg_match('/^(\d{4}-\d{2}-\d{2})([ T]00:00(:00)?(\.0+)?Z?)?$/', $v, $m)) {
                return $m[1];
            }
            return $v;
        };

        return $norm($a) === $norm($b);
    }

    /** "ชื่อช่อง, ชื่อช่อง" for a message. */
    public static function labels(Model $model, array $fields): string
    {
        return implode(', ', array_map(fn ($f) => ActivityLogPresenter::fieldLabel(class_basename($model), $f), $fields));
    }
}
