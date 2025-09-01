<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // 1. Determine the active tab and set the base status
        $activeTab = $request->input('tab', 'unread');
        $query = Notification::with('employee.employer')
                             ->where('status', $activeTab);

        // 2. Apply search filter
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->whereHas('employee', function ($q) use ($searchTerm) {
                $q->where('name_th', 'like', "%{$searchTerm}%")
                  ->orWhere('name_en', 'like', "%{$searchTerm}%")
                  ->orWhere('employee_code', 'like', "%{$searchTerm}%");
            });
        }

        // 3. Apply nationality filter
        if ($request->filled('nationality')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('nationality', $request->input('nationality'));
            });
        }

        // 4. Apply MOU type filter
        if ($request->filled('mou_type')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('mou_type', $request->input('mou_type'));
            });
        }

        // 5. Apply month filter (on due_date)
        if ($request->filled('month')) {
            $month = $request->input('month'); // Expects 'YYYY-MM' format
            if (preg_match('/^(\d{4})-(\d{2})$/', $month, $matches)) {
                $year = $matches[1];
                $month = $matches[2];
                $query->whereYear('due_date', '=', $year)
                      ->whereMonth('due_date', '=', $month);
            }
        }

        // Fetch the notifications
        $notifications = $query->orderBy('due_date', 'asc')->get();

        // Group them for the view
        $groupedNotifications = $notifications->groupBy('type');

        // Pass data to the view
        return view('notifications.index', [
            'groupedNotifications' => $groupedNotifications,
            'activeTab' => $activeTab,
            'filters' => $request->only(['search', 'nationality', 'mou_type', 'month']),
        ]);
    }

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

    public function export(Request $request)
    {
        // Reuse the same filtering logic from index
        $activeTab = $request->input('tab', 'unread');
        $query = Notification::with('employee.employer')
                             ->where('status', $activeTab);

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->whereHas('employee', function ($q) use ($searchTerm) {
                $q->where('name_th', 'like', "%{$searchTerm}%")
                  ->orWhere('name_en', 'like', "%{$searchTerm}%")
                  ->orWhere('employee_code', 'like', "%{$searchTerm}%");
            });
        }
        if ($request->filled('nationality')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('nationality', $request->input('nationality'));
            });
        }
        if ($request->filled('mou_type')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('mou_type', $request->input('mou_type'));
            });
        }
        if ($request->filled('month')) {
            $month = $request->input('month');
            if (preg_match('/^(\d{4})-(\d{2})$/', $month, $matches)) {
                $year = $matches[1];
                $month = $matches[2];
                $query->whereYear('due_date', '=', $year)
                      ->whereMonth('due_date', '=', $month);
            }
        }

        $notifications = $query->orderBy('due_date', 'asc')->get();

        $fileName = "notifications_{$activeTab}_" . date('Y-m-d') . ".csv";
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
            // Add BOM to support UTF-8 in Excel
            fputs($file, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
            fputcsv($file, $columns);

            foreach ($notifications as $notification) {
                $row = [
                    $notification->id,
                    $notification->employee->employee_code,
                    $notification->employee->name_th . ' / ' . $notification->employee->name_en,
                    $notification->employee->employer->name,
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
}
