<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use App\Models\ActivityLog;
use App\Support\ActivityLogPresenter;
use Illuminate\Support\Facades\Request;

/**
 * A wrong email/password on the login page. user_id is the account whose
 * email was typed (when that account exists) so the attempt shows up in
 * that user's history; the password itself is never stored.
 * Registered automatically by Laravel's event discovery (app/Listeners).
 */
class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        ActivityLog::create([
            'user_id' => $event->user?->getAuthIdentifier(),
            'action' => 'login_failed',
            'subject_type' => $event->user ? get_class($event->user) : null,
            'subject_id' => $event->user?->getAuthIdentifier(),
            'description' => 'เข้าสู่ระบบไม่สำเร็จ',
            'properties' => [
                'email' => mb_substr((string) ($event->credentials['email'] ?? ''), 0, 191),
                'device' => ActivityLogPresenter::device(Request::userAgent()),
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
