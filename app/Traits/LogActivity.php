<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogActivity
{
    /**
     * Get the most recent activity log entry (create/update) for this model.
     */
    public function lastEditedActivity()
    {
        return $this->morphOne(\App\Models\ActivityLog::class, 'subject')
            ->whereIn('action', ['create', 'update'])
            ->latest('created_at');
    }

    protected static function bootLogActivity()
    {
        static::created(function (Model $model) {
            static::logChange($model, 'create');
        });

        static::updated(function (Model $model) {
            static::logChange($model, 'update');
        });

        static::deleted(function (Model $model) {
            static::logChange($model, 'delete');
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                static::logChange($model, 'restore');
            });
        }

        if (method_exists(static::class, 'forceDeleted')) {
            static::forceDeleted(function (Model $model) {
                static::logChange($model, 'force_delete');
            });
        }
    }

    protected static function logChange(Model $model, string $action)
    {
        $user = Auth::user();

        // Skip logging if no user (e.g. seeder or system job) unless strictly required.
        // However, the user requested "black box of each ID", so we should record everything.
        // If no auth user, we might record as null or system.

        $properties = [];

        if ($action === 'update') {
            // Only log changed attributes
            $original = $model->getOriginal();
            $attributes = $model->getAttributes();
            $changes = $model->getChanges(); // Keys of changed attributes

            $old = [];
            $new = [];

            foreach ($changes as $key => $value) {
                // Skip timestamps or hidden fields if needed
                if (in_array($key, ['updated_at', 'password', 'remember_token'])) {
                    continue;
                }

                $old[$key] = $original[$key] ?? null;
                $new[$key] = $attributes[$key] ?? null;
            }

            if (empty($new)) {
                return; // No meaningful changes
            }

            $properties = [
                'old' => $old,
                'attributes' => $new,
            ];
        } elseif ($action === 'create') {
            $attributes = $model->getAttributes();
            // Mask password
            if (isset($attributes['password'])) {
                $attributes['password'] = '********';
            }
            $properties = ['attributes' => $attributes];
        }

        ActivityLog::create([
            'user_id' => $user ? $user->id : null,
            'action' => $action,
            'subject_type' => get_class($model),
            'subject_id' => $model->id,
            'description' => ucfirst($action) . ' ' . class_basename($model),
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
