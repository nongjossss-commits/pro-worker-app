<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
     * Renew a notification's due date.
     */
    public function renew(Request $request, Notification $notification)
    {
        $request->validate(['new_due_date' => 'required|date']);
        $newDate = $request->input('new_due_date');

        // Update the notification itself
        $notification->update(['due_date' => $newDate]);

        // Update the corresponding field on the employee record
        $employee = $notification->employee;
        if ($employee) {
            $updateField = null;
            switch ($notification->type) {
                case 'passport_expiry':
                case 'ci_renewal':
                    $updateField = 'passportExpiryDate';
                    break;
                case 'visa_expiry':
                    $updateField = 'visaExpiryDate';
                    break;
                case 'work_permit_expiry':
                case 'resolution_renewal':
                    $updateField = 'workPermitExpiryDate';
                    break;
                case 'ninety_day_report':
                    $updateField = 'ninetyDayReportDate';
                    break;
            }

            if ($updateField) {
                // Use the correct camelCase field name to update
                $employee->update([$updateField => $newDate]);
            }
        }

        return back()->with('success', 'ต่ออายุสำเร็จ');
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
     * Permanently delete a notification.
     */
    public function forceDelete(Notification $notification)
    {
        $notification->delete();

        return back()->with('success', 'รายการถูกลบอย่างถาวร');
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
                $query->where('type', 'work_permit_expiry')
                      ->whereDate('due_date', '<', now())
                      ->where('status', 'unread');
                break;
            case 'work_permit_expiry':
                $query->where('type', 'work_permit_expiry')
                      ->whereDate('due_date', '>=', now())
                      ->where('status', 'unread');
                break;
            default:
                $query->where('status', 'unread');
                if ($type !== 'permits') { // 'permits' is a tab group, not a notification type
                    $query->where('type', $type);
                }
        }

        // Apply filters
        if ($searchTerm = $request->input("search_{$prefix}")) {
            $query->whereHas('employee', function ($q) use ($searchTerm) {
                $q->where(function ($q) use ($searchTerm) {
                    $q->where('employeeNameTh', 'like', "%{$searchTerm}%")
                      ->orWhere('employeeNameEn', 'like', "%{$searchTerm}%")
                      ->orWhere('companyWorkerId', 'like', "%{$searchTerm}%");
                });
            });
        }

        if ($nationality = $request->input("nationality_{$prefix}")) {
            $query->whereHas('employee', function ($q) use ($nationality) {
                $q->where('employeeNationality', $nationality);
            });
        }

        if ($mouGroup = $request->input("mou_{$prefix}")) {
            $query->whereHas('employee', function ($q) use ($mouGroup) {
                $q->where('workPermitMOUGroup', $mouGroup);
            });
        }

        if ($month = $request->input("month_{$prefix}")) {
            if (is_numeric($month)) {
                $query->whereMonth('due_date', '=', (int)$month + 1);
            }
        }

        if ($type === 'resolution_renew' && ($step = $request->input("step_{$prefix}"))) {
            $query->whereHas('employee', function($q) use ($step) {
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

    /**
     * Handles the renewal of an employee's document from a notification.
     * This function fixes the issue where the employee's record was not updated.
     */
    public function renewDocument(Request $request, $notificationId)
    {
        $notification = Auth::user()->notifications()->findOrFail($notificationId);

        // Extract data from the notification's 'data' column
        $employeeId = $notification->data['employee_id'] ?? null;
        $documentTypeSnake = $notification->data['document_type'] ?? null; // e.g., 'visa_expiry_date'

        if (!$employeeId || !$documentTypeSnake) {
            return back()->with('error', 'Notification data is invalid.');
        }

        $employee = Employee::find($employeeId);
        if (!$employee) {
            return back()->with('error', 'Employee not found.');
        }

        // Set the new expiry date (e.g., 1 year from now)
        $newExpiryDate = Carbon::now()->addYear();

        // The documentTypeSnake should be a snake_case column name like 'visa_expiry_date',
        // which matches the database schema.
        $employee->update([
            $documentTypeSnake => $newExpiryDate
        ]);

        // Mark the notification as read after successful renewal
        $notification->markAsRead();

        // Redirect back to the employer's page and add a fragment for scrolling
        return redirect()->route('employers.edit', $employee->employer_id)
            ->with('success', 'Document for ' . $employee->name_en . ' has been renewed.')
            ->withFragment('employee-card-' . $employee->id);
    }

    /**
     * Redirects to the employer's page and highlights the specific employee.
     * This function fixes the "View Info" button functionality.
     */
    public function viewEmployee($notificationId)
    {
        $notification = Auth::user()->notifications()->findOrFail($notificationId);

        $employeeId = $notification->data['employee_id'] ?? null;
        if (!$employeeId) {
            return back()->with('error', 'Notification data is invalid.');
        }

        $employee = Employee::findOrFail($employeeId);

        // Mark as read when the user views the employee info
        $notification->markAsRead();

        // Redirect to the employer's edit page with a fragment identifier
        // The fragment will be used by JavaScript to scroll and highlight.
        return redirect()->route('employers.edit', ['employer' => $employee->employer_id])
            ->withFragment('employee-card-' . $employeeId);
    }
}
