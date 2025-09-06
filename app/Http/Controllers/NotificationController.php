<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\Builder;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $notificationTypes = [
            'ninety_day_report',
            'passport_expiry',
            'work_permit_expiry',
            'work_permit_expired',
            'visa_expiry',
            'ci_renewal',
            'resolution_renewal',
            'cancelled'
        ];

        $groupedNotifications = collect();

        foreach ($notificationTypes as $type) {
            $formPrefix = $type;
            if (in_array($type, ['work_permit_expiry', 'work_permit_expired', 'visa_expiry'])) {
                $formPrefix = 'permits';
            }

            $query = $this->getFilteredNotificationsQuery($request, $type, $formPrefix);
            $pageName = str_replace('_', '', $type) . '_page';
            $notifications = $query->with('employee.employer')->paginate(10, ['*'], $pageName)->withQueryString();
            $groupedNotifications->put($type, $notifications);
        }

        return view('notifications.index', ['groupedNotifications' => $groupedNotifications]);
    }

    /**
     * Restore a cancelled notification.
     */
    public function restore(Notification $notification)
    {
        $notification->update([
            'status' => 'unread',
            'cancellation_reason' => null,
            'cancelled_at' => null,
        ]);

        return back()->with('success', 'รายการถูกนำกลับมาเรียบร้อยแล้ว');
    }

    /**
     * Cancel a notification.
     */
    public function cancel(Request $request, Notification $notification)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:1000',
        ]);

        $notification->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->input('cancellation_reason'),
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'การต่ออายุถูกยกเลิกสำเร็จ');
    }

    /**
     * Handle the export of notifications to CSV.
     */
    public function export(Request $request)
    {
        $exportType = $request->input('export_type', '90day');

        $query = $this->getFilteredNotificationsQuery($request, $exportType, $request->input('tab'));

        $notifications = $query->get();

        $fileName = "notifications_{$exportType}_" . date('Y-m-d') . ".csv";
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'รหัสพนักงาน', 'ชื่อพนักงาน', 'นายจ้าง', 'ประเภทการแจ้งเตือน', 'วันที่ครบกำหนด', 'สถานะ'];

        $callback = function() use($notifications, $columns) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
            fputcsv($file, $columns);

            foreach ($notifications as $notification) {
                $row = [
                    $notification->id,
                    $notification->employee->companyWorkerId ?? 'N/A',
                    ($notification->employee->employeeNameTh ?? 'N/A') . ' / ' . ($notification->employee->employeeNameEn ?? 'N/A'),
                    $notification->employee->employer->employerNameTh ?? 'N/A',
                    $notification->type,
                    $notification->due_date,
                    $notification->status,
                ];
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Reusable private method to get filtered notification queries.
     */
    private function getFilteredNotificationsQuery(Request $request, string $type, ?string $formPrefix = null): Builder
    {
        $prefix = $formPrefix ?? $type;

        $query = Notification::with('employee.employer')->orderBy('due_date', 'asc');

        // Base query conditions based on type
        switch ($type) {
            case 'cancelled':
                $query->where('status', 'cancelled');
                break;
            case 'work_permit_expired':
                $query->where('type', 'work_permit_expiry')->whereDate('due_date', '<', now());
                break;
            default:
                $query->where('status', 'unread');
                if ($type !== 'permits') {
                     $query->where('type', $type);
                }
        }

        // Apply filters
        if ($request->filled("search_{$prefix}")) {
            $searchTerm = $request->input("search_{$prefix}");
            $query->whereHas('employee', function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', "%{$searchTerm}%")
                  ->orWhere('employeeNameEn', 'like', "%{$searchTerm}%")
                  ->orWhere('companyWorkerId', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled("nationality_{$prefix}")) {
            $query->whereHas('employee', function ($q) use ($request, $prefix) {
                $q->where('employeeNationality', $request->input("nationality_{$prefix}"));
            });
        }

        if ($request->filled("mou_{$prefix}")) {
            $query->whereHas('employee', function ($q) use ($request, $prefix) {
                $q->where('workPermitMOUGroup', $request->input("mou_{$prefix}"));
            });
        }

        if ($request->filled("month_{$prefix}")) {
            $month = $request->input("month_{$prefix}");
            $query->whereMonth('due_date', '=', (int)$month + 1);
        }

        if ($type === 'resolution_renew' && $request->filled("step_{$prefix}")) {
            // This requires a more complex condition based on employee's JSON data
            // Assuming 'task_tracking_data' is a JSON field on the employee model
            $step = $request->input("step_{$prefix}");
            $query->whereHas('employee', function($q) use ($step) {
                // Example logic, may need adjustment based on actual JSON structure
                switch($step) {
                    case 'not_started':
                        $q->whereJsonContains('task_tracking_data->step1_checked', false);
                        break;
                    case 'step1':
                        $q->whereJsonContains('task_tracking_data->step1_checked', true)
                          ->whereJsonContains('task_tracking_data->step2_checked', false);
                        break;
                    // ... and so on for other steps
                }
            });
        }

        return $query;
    }
}
