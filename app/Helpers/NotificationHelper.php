<?php

namespace App\Helpers;

use App\Models\Notification;
use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Auth;

class NotificationHelper
{
    /**
     * Same 11 tabs NotificationController::index() builds — duplicated
     * here (not extracted into a shared constant) to avoid touching that
     * already-large controller for this.
     */
    protected const TYPE_LABELS = [
        'ninety_day_report' => 'รายงานตัว 90 วัน',
        'passport_expiry' => 'Passport',
        'work_permit_mou' => 'ใบอนุญาตทำงาน (MOU)',
        'visa_expiry' => 'วีซ่า',
        'ci_renewal' => 'ต่ออายุ CI',
        'resolution_renewal' => 'ต่ออายุมติ',
        'new_registration_renewal' => 'มติขึ้นทะเบียนใหม่',
        'employer_document_expiry' => 'เอกสารนายจ้าง',
        'employee_insurance_expiry' => 'ประกันลูกจ้าง',
        'pink_card_missing' => 'บัตรชมพู',
        'residence_permit_missing' => 'แจ้งที่พักอาศัย',
    ];

    /**
     * Compact, grouped digest for the "pops up on the Welcome page" modal —
     * unlike getTotalNotificationCount() (a single number for the sidebar
     * badge), this returns the top 5 soonest-due items per enabled type so
     * the popup has something concrete to show, not just a count. Same
     * tenancy approach as getTotalNotificationCount() (whereHas('employer')/
     * whereHas('employee') to piggyback on those models' own global
     * scopes), plus the same NotificationSetting.is_enabled filtering
     * NotificationController::index() uses to build its tabs.
     */
    public static function getPopupSummary(): array
    {
        if (!Auth::check()) {
            return ['total' => 0, 'groups' => []];
        }

        $settings = NotificationSetting::all()->keyBy('notification_type');

        $groups = [];
        $total = 0;

        foreach (self::TYPE_LABELS as $type => $label) {
            $setting = $settings->get($type);
            if ($setting && !$setting->is_enabled) {
                continue;
            }

            $query = Notification::where('type', $type)->where('status', '!=', 'cancelled');

            if ($type === 'employer_document_expiry') {
                $query->whereHas('employer');
            } else {
                $query->whereHas('employee');
            }

            $count = (clone $query)->count();
            if ($count === 0) {
                continue;
            }

            $total += $count;

            $items = $query->with(['employee', 'employer'])
                ->orderBy('due_date', 'asc')
                ->limit(5)
                ->get()
                ->map(function (Notification $n) use ($type) {
                    $name = $type === 'employer_document_expiry'
                        ? ($n->employer->employerNameTh ?? '-')
                        : ($n->employee->employeeNameTh ?: $n->employee->employeeNameEn ?? '-');

                    return [
                        'id' => $n->id,
                        'name' => $name,
                        'due_date' => optional($n->due_date)->format('d/m/Y'),
                        'days_remaining' => $n->days_remaining,
                        // notifications.view-employee assumes a linked Employee
                        // (crashes on null for employer_document_expiry, which
                        // has no employee_id) — routed around here rather than
                        // touched, since that's pre-existing behavior outside
                        // this feature's scope.
                        'view_url' => $type === 'employer_document_expiry'
                            ? route('employers.edit', $n->employer_id)
                            : route('notifications.view-employee', $n->id),
                    ];
                })->all();

            $groups[] = [
                'type' => $type,
                'label' => $label,
                'count' => $count,
                'items' => $items,
            ];
        }

        return ['total' => $total, 'groups' => $groups];
    }

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

        // Fix: Ensure we only count notifications where the related model still exists.
        // This matches the logic in NotificationController::getFilteredQuery.
        $query->where(function($q) {
            // Case 1: Employer Document Expiry - Must have valid Employer
            $q->where(function($sub) {
                $sub->where('type', 'employer_document_expiry')
                    ->whereHas('employer');
            })
            // Case 2: All other types - Must have valid Employee
            ->orWhere(function($sub) {
                $sub->where('type', '!=', 'employer_document_expiry')
                    ->whereHas('employee');
            });
        });

        // Count only active notifications (not cancelled)
        $query->where('status', '!=', 'cancelled');

        return $query->count();
    }
}
