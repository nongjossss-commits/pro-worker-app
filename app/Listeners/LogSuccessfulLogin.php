<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Models\ActivityLog;
use App\Support\ActivityLogPresenter;
use Illuminate\Support\Facades\Request;

/**
 * Registered automatically by Laravel's event discovery (app/Listeners) —
 * do not also Event::listen() it, or every login is written twice.
 */
class LogSuccessfulLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'subject_type' => get_class($user),
            'subject_id' => $user->id,
            'description' => 'เข้าสู่ระบบสำเร็จ',
            'properties' => ['device' => ActivityLogPresenter::device(Request::userAgent())],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
