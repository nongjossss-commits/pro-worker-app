<?php

namespace App\Helpers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationHelper
{
    public static function getTotalNotificationCount()
    {
        $query = Notification::with(['employee.employer', 'employer']);

        // Apply tenancy scope for Employer role to prevent data leakage
        if (Auth::check() && Auth::user()->hasRole('employer')) {
            $employerId = Auth::user()->employer->id ?? null;
            if ($employerId) {
                $query->where(function($q) use ($employerId) {
                    // 1. Notifications directly linked to the employer (e.g., employer_document_expiry)
                    $q->where('employer_id', $employerId)
                    // 2. Notifications linked to employees (respects Employee global scope)
                      ->orWhereHas('employee');
                });
            } else {
                // Fallback: If user is 'employer' but has no linked Employer record, show nothing.
                $query->whereRaw('1 = 0');
            }
        }

        // Count only active notifications (not cancelled)
        $query->where('status', '!=', 'cancelled');

        return $query->count();
    }
}
