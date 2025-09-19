<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Employee;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $currentView = $request->input('view', 'card');
        $perPageOptions = ($currentView === 'card') ? [10, 15, 20] : [25, 50, 100];
        $perPage = $request->input('per_page', $perPageOptions[0]);

        $tabs = [
            'ninety_day_report' => 'รายงานตัว 90 วัน',
            'passport_expiry' => 'Passport',
            'work_permit_mou' => 'ใบอนุญาตทำงาน (MOU)',
            'visa_expiry' => 'วีซ่า',
            'ci_renewal' => 'ต่ออายุ CI',
            'resolution_renewal' => 'ต่ออายุมติ',
            'new_registration_renewal' => 'มติขึ้นทะเบียนใหม่', // New Tab
        ];

        $notificationsData = [];
        $counts = [];

        foreach ($tabs as $type => $title) {
            $query = $this->getFilteredQuery($request, $type);
            $counts[$type] = (clone $query)->count();
            // For the special tab, we don't paginate here. We pass the whole collection.
            if ($type === 'work_permit_mou') {
                $notificationsData[$type] = $query->get();
            } else {
                $notificationsData[$type] = $query->paginate($perPage, ['*'], $type . '_page')->withQueryString();
            }
        }

        $cancelledQuery = $this->getFilteredQuery($request, 'cancelled');
        $counts['cancelled'] = (clone $cancelledQuery)->count();
        $notificationsData['cancelled'] = $cancelledQuery->paginate($perPage, ['*'], 'cancelled_page')->withQueryString();

        return view('notifications.index', compact('notificationsData', 'counts', 'tabs', 'currentView', 'perPageOptions'));
    }

    private function getFilteredQuery(Request $request, string $type)
    {
        $query = \App\Models\Notification::with('employee.employer');

        if ($type === 'cancelled') {
            $query->where('status', 'cancelled')->latest('updated_at');
        } else {
            $query->where('status', '!=', 'cancelled')->where('type', $type)->latest('due_date');
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('employeeNameTh', 'like', "%{$search}%")
                  ->orWhere('employeeNameEn', 'like', "%{$search}%");
            });
        }
        if ($request->filled('nationality')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('employeeNationality', $request->input('nationality'));
            });
        }
        if ($request->filled('mou_type')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('workPermitMOUGroup', $request->input('mou_type'));
            });
        }
        return $query;
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

    // Add this new method to the controller
    public function renew(Request $request, \App\Models\Notification $notification)
    {
        $request->validate(['new_due_date' => 'required|date']);

        $employee = $notification->employee;
        $fieldToUpdate = '';

        switch ($notification->type) {
            case 'passport_expiry':
                $fieldToUpdate = 'passportExpiryDate';
                break;
            case 'work_permit_expiry':
            case 'work_permit_mou': // Added to handle new type
            case 'resolution_renewal': // Added to handle new type
                $fieldToUpdate = 'workPermitExpiryDate';
                break;
            case 'visa_expiry':
                $fieldToUpdate = 'visaExpiryDate';
                break;
            case 'ninety_day_report':
                $fieldToUpdate = 'ninetyDayReportDate';
                break;
        }

        if ($fieldToUpdate && $employee) {
            $employee->{$fieldToUpdate} = $request->new_due_date;
            $employee->save();
        }

        $notification->delete(); // Remove the notification after handling

        return redirect()->route('notifications.index')->with('success', 'ต่ออายุข้อมูลเรียบร้อยแล้ว');
    }

    // Add this new method to the controller
    public function cancel(\App\Models\Notification $notification)
    {
        // Here you can add logic to flag the employee or notification
        // For now, we will just delete it as a simple cancel action.
        $notification->update(['status' => 'cancelled']);

        return redirect()->route('notifications.index')->with('success', 'ยกเลิกการแจ้งเตือนเรียบร้อยแล้ว');
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
     * This simplified function fixes the SQL error.
     */
    public function viewEmployee($notificationId)
    {
        $notification = \App\Models\Notification::findOrFail($notificationId);
        $employee = $notification->employee;
        $employer = $employee->employer;

        if (!$employer) {
            return redirect()->back()->with('error', 'ไม่พบข้อมูลนายจ้าง');
        }

        // Add the URL hash to redirect and trigger the highlight
        $url = route('employers.edit', $employer) . '#employee-card-' . $employee->id;
        return redirect($url);
    }
}
