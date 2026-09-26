<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use App\Models\ActivityLog;
use App\Support\ActivityLogPresenter;
use Illuminate\Support\Facades\Request;

/**
 * Registered automatically by Laravel's event discovery (app/Listeners).
 * reason: "manual" (the user pressed Logout) or "inactive" (signed out by
 * EnsureBrowserSessionAlive after the browser/app was closed or left idle).
 */
class LogSuccessfulLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        $user = $event->user;

        if ($user) {
            $reason = request()->attributes->get('logout_reason', 'manual');
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'logout',
                'subject_type' => get_class($user),
                'subject_id' => $user->id,
                'description' => $reason === 'inactive' ? 'ระบบออกจากระบบให้อัตโนมัติ' : 'ออกจากระบบ',
                'properties' => ['reason' => $reason, 'device' => ActivityLogPresenter::device(Request::userAgent())],
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        }
    }
}
