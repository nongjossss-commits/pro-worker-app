<?php

namespace App\Helpers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationHelper
{
    public static function getTotalNotificationCount()
    {
        $query = Notification::query();

        // Apply filtering based on relationships to enforce Global Scopes of related models (Employer/Employee).
        // This handles 'employer' tenancy AND 'caretaker' (delegated admin) tenancy automatically via the models' global scopes.
        if (Auth::check()) {
             $query->where(function($q) {
                // 1. Notifications directly linked to the employer (e.g., employer_document_expiry)
                // This respects the Employer global scope (e.g. assigned_staff_id check)
                $q->whereHas('employer')
                // 2. Notifications linked to employees
                // This respects the Employee global scope (e.g. employer.assigned_staff_id check)
                  ->orWhereHas('employee');
            });
        }

        // Count only active notifications (not cancelled)
        $query->where('status', '!=', 'cancelled');

        return $query->count();
    }
}
