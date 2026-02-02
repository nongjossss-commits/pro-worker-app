<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Employee;
use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $currentView = $request->input('view', 'card');
        $perPageOptions = ($currentView === 'card') ? [10, 15, 20] : [25, 50, 100];
        $perPage = $request->input('per_page', $perPageOptions[0]);

        // Fetch all settings once
        $allSettings = NotificationSetting::all()->keyBy('notification_type');

        $allPossibleTabs = [
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

        // Filter tabs based on whether they are enabled in settings.
        $tabs = [];
        foreach ($allPossibleTabs as $type => $title) {
            $setting = $allSettings->get($type);
            // If a setting doesn't exist for a tab, default to showing it for backward compatibility.
            // Only hide if the setting exists AND is_enabled is explicitly false (or 0).
            if (!$setting || $setting->is_enabled) {
                $tabs[$type] = $title;
            }
        }


        $notificationsData = [];
        $counts = [];

        foreach ($tabs as $type => $title) {
            $query = $this->getFilteredQuery($request, $type);

            // --- NEW: Gender Counting Logic ---
            if ($type === 'employer_document_expiry') {
                $counts[$type] = ['total' => (clone $query)->count(), 'male' => 0, 'female' => 0];
            } else {
                $employeeIds = (clone $query)->pluck('employee_id')->filter();
                if ($employeeIds->isNotEmpty()) {
                    $male_count = Employee::whereIn('id', $employeeIds)->where('employeeTitleTh', 'นาย')->count();
                    $female_count = Employee::whereIn('id', $employeeIds)->whereIn('employeeTitleTh', ['นางสาว', 'นาง'])->count();
                    $total_count = $employeeIds->count();
                } else {
                    $male_count = $female_count = $total_count = 0;
                }

                $counts[$type] = [
                    'total' => $total_count,
                    'male' => $male_count,
                    'female' => $female_count,
                ];
            }
            // --- END: Gender Counting Logic ---

            if ($type === 'work_permit_mou') {
                $notificationsData[$type] = $query->get();
            } else {
                $notificationsData[$type] = $query->paginate($perPage, ['*'], $type . '_page')
                    ->appends(array_merge($request->query(), ['active_tab' => $type]));
            }
        }

        $cancelledQuery = $this->getFilteredQuery($request, 'cancelled');
        $counts['cancelled'] = (clone $cancelledQuery)->count();
        $notificationsData['cancelled'] = $cancelledQuery->paginate($perPage, ['*'], 'cancelled_page')
            ->appends(array_merge($request->query(), ['active_tab' => 'cancelled']));

        return view('notifications.index', compact('notificationsData', 'counts', 'tabs', 'currentView', 'perPageOptions'));
    }

    private function getFilteredQuery(Request $request, string $type)
    {
        $query = Notification::with(['employee.employer', 'employer']);

        // Filter out notifications where the related model is missing (e.g. soft deleted)
        if ($type === 'employer_document_expiry') {
            $query->whereHas('employer');
        } else {
            $query->whereHas('employee');
        }

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

        if ($type === 'cancelled') {
            $query->where('status', 'cancelled')->orderBy('updated_at', 'desc');
        } else {
            // Modified sorting: Sort by due_date ASC (Earliest expiry first, expired items at top)
            // For missing data types, due_date might be null or irrelevant, but we keep sorting for consistency
            $query->where('status', '!=', 'cancelled')->where('type', $type)->orderBy('due_date', 'asc');
        }

        // Filter by month if provided
        if ($request->filled('month')) {
            $query->whereMonth('due_date', $request->input('month'));
        }

        // --- NEW: Enhanced Search Logic ---
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search, $type) {
                if ($type === 'employer_document_expiry') {
                    $q->whereHas('employer', function ($q_employer) use ($search) {
                        $q_employer->where('employerNameTh', 'like', "%{$search}%")
                                   ->orWhere('employerNameEn', 'like', "%{$search}%")
                                   ->orWhere(function($addrQ) use ($search) {
                                       $addrQ->filterByAddress($search);
                                   });
                    });
                } else {
                    $q->whereHas('employee', function ($q_employee) use ($search) {
                        $q_employee->where('employeeNameTh', 'like', "%{$search}%")
                                   ->orWhere('employeeNameEn', 'like', "%{$search}%")
                                   ->orWhere('employeePassport', 'like', "%{$search}%")
                                   ->orWhere('companyWorkerId', 'like', "%{$search}%") // Added companyWorkerId
                                   ->orWhereHas('employer', function ($q_employer) use ($search) {
                                       $q_employer->where('employerNameTh', 'like', "%{$search}%")
                                                  ->orWhere('employerNameEn', 'like', "%{$search}%") // Added employerNameEn
                                                  ->orWhere(function($addrQ) use ($search) {
                                                      $addrQ->filterByAddress($search);
                                                  });
                                   });
                    });
                }
            });
        }
        // --- END: Enhanced Search Logic ---

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

        if ($request->filled('insurance_type')) {
            $type = $request->input('insurance_type');
            // Only apply to notifications linked to employees
            $query->whereHas('employee', function ($q) use ($type) {
                if ($type === 'none') {
                    $q->whereNull('insurance_type')->orWhere('insurance_type', '=', '');
                } else {
                    $q->where('insurance_type', $type);
                }
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

    /**
     * Manually trigger the document expiry check.
     */
    public function checkExpiries()
    {
        Artisan::call('app:check-expiries');
        return back()->with('success', 'ระบบกำลังตรวจสอบเอกสารหมดอายุใหม่อีกครั้ง กรุณารอสักครู่...');
    }

    // Add this new method to the controller
    public function renew(Request $request, \App\Models\Notification $notification)
    {
        // Validation rules adjusted for new types
        $rules = [];

        if (in_array($notification->type, ['pink_card_missing', 'residence_permit_missing'])) {
            // For update action
             $rules['attachment'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:10240'; // Attachment required
             if ($notification->type === 'pink_card_missing') {
                 $rules['pink_card_number'] = 'required|string|max:255';
             }
        } else {
            // For renewal action
            $rules['new_due_date'] = 'required|date';
            $rules['attachment'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240';
        }

        $request->validate($rules);

        $employee = $notification->employee;
        $employer = $notification->employer; // Get employer directly
        $fieldToUpdate = '';
        $fileField = '';
        $targetModel = null;

        // Determine active tab for redirect early to prevent loss after delete
        $activeTab = $notification->type;
        if ($activeTab === 'work_permit_expiry') {
            $activeTab = 'work_permit_mou';
        }

        switch ($notification->type) {
            case 'passport_expiry':
            case 'ci_renewal': // Handle CI renewal as passport update
                $fieldToUpdate = 'passportExpiryDate';
                // 1. พาสปอร์ต -> employee_doc_1
                $fileField = 'employee_doc_1';
                $targetModel = $employee;
                break;
            case 'work_permit_expiry':
            case 'work_permit_mou':
            case 'resolution_renewal':
            case 'new_registration_renewal':
                $fieldToUpdate = 'workPermitExpiryDate';
                // 3. ใบอนุญาต Work Permit -> employee_doc_3
                $fileField = 'employee_doc_3';
                $targetModel = $employee;
                break;
            case 'visa_expiry':
                $fieldToUpdate = 'visaExpiryDate';
                // 2. วีซ่า -> employee_doc_2
                $fileField = 'employee_doc_2';
                $targetModel = $employee;
                break;
            case 'ninety_day_report':
                $fieldToUpdate = 'ninetyDayReportDate';
                // 6. รายงานตัว 90 วัน -> employee_doc_6
                $fileField = 'employee_doc_6';
                $targetModel = $employee;
                break;
            case 'employee_insurance_expiry':
                $targetModel = $employee;
                // Determine which date field and file field to update based on insurance type
                if ($employee->insurance_type === 'ประกันเอกชน') {
                    $fieldToUpdate = 'insurance_expiry_date_private';
                    // Use private insurance document path
                    $fileField = 'insurance_document_path_private';
                } elseif ($employee->insurance_type === 'ประกันโรงพยาบาล') {
                    $fieldToUpdate = 'insurance_expiry_date_hospital';
                    // User instruction: Use same file field as private insurance
                    $fileField = 'insurance_document_path_private';
                } else {
                    $fieldToUpdate = 'insurance_expiry_date';
                    $fileField = 'insurance_document_path';
                }
                break;
            case 'employer_document_expiry':
                $fieldToUpdate = 'employer_doc_company_expiry';
                // 1. หนังสือรับรองบริษัท / บัตรประชาชน -> employer_doc_company
                $fileField = 'employer_doc_company';
                $targetModel = $employer;
                break;

            // --- New Types ---
            case 'pink_card_missing':
                // Updates pinkCardNo and employee_doc_4 (Pink Card File)
                if ($request->filled('pink_card_number')) {
                    $employee->pinkCardNo = $request->input('pink_card_number');
                }
                $fileField = 'employee_doc_4';
                $targetModel = $employee;
                // No date field to update for this type
                break;
            case 'residence_permit_missing':
                // Updates employee_doc_7 (Residence Notification File)
                $fileField = 'employee_doc_7';
                $targetModel = $employee;
                // No date field to update for this type
                break;
        }

        if ($targetModel) {
            if ($fieldToUpdate && $request->has('new_due_date')) {
                $targetModel->{$fieldToUpdate} = $request->new_due_date;
            }

            // Handle file upload
            if ($request->hasFile('attachment') && $fileField) {
                // REVERTED: Use 'public' disk for all uploads to prevent 403 errors and ensure consistency.
                $disk = 'public';

                // Delete old file if exists
                // Note: For unified public disk, we check public first.
                // We also check private just in case a legacy file exists there to clean it up.
                if ($targetModel->{$fileField}) {
                     if (\Illuminate\Support\Facades\Storage::disk('public')->exists($targetModel->{$fileField})) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($targetModel->{$fileField});
                     }
                     if (\Illuminate\Support\Facades\Storage::disk('private')->exists($targetModel->{$fileField})) {
                        \Illuminate\Support\Facades\Storage::disk('private')->delete($targetModel->{$fileField});
                     }
                }

                $file = $request->file('attachment');
                $filename = \Illuminate\Support\Str::random(20) . '.' . $file->getClientOriginalExtension();

                // Determine storage path based on model type and disk
                if ($targetModel instanceof \App\Models\Employee) {
                    // Always use public folder structure
                    $folder = "employee_files/{$targetModel->employer_id}";
                    $path = $file->storeAs($folder, $filename, $disk);
                } else {
                     // Employer documents
                    $path = $file->storeAs("employer_documents", $filename, $disk);
                }

                $targetModel->{$fileField} = $path;
            }

            $targetModel->save();
        }

        $notification->delete(); // Remove the notification after handling

        // Customize success message
        $msg = 'ดำเนินการเรียบร้อยแล้ว';
        if (in_array($notification->type, ['pink_card_missing', 'residence_permit_missing'])) {
            $msg = 'อัพเดตข้อมูลและบันทึกเอกสารเรียบร้อยแล้ว';
        } else {
            $msg = 'ต่ออายุข้อมูลและบันทึกเอกสารเรียบร้อยแล้ว';
        }

        return redirect()->route('notifications.index', ['active_tab' => $activeTab])->with('success', $msg);
    }

    // Add this new method to the controller
    public function cancel(\App\Models\Notification $notification)
    {
        // Here you can add logic to flag the employee or notification
        // For now, we will just delete it as a simple cancel action.
        $notification->update(['status' => 'cancelled']);

        // Determine active tab for redirect
        $activeTab = $notification->type;
        if ($activeTab === 'work_permit_expiry') {
            $activeTab = 'work_permit_mou';
        }

        return redirect()->route('notifications.index', ['active_tab' => $activeTab])->with('success', 'ยกเลิกการแจ้งเตือนเรียบร้อยแล้ว');
    }

    public function export(Request $request)
    {
        // Prioritize 'export_type' from the new buttons, fallback to 'active_tab' for compatibility.
        $exportType = $request->input('export_type', $request->input('active_tab', 'ninety_day_report'));

        // Use the existing filter logic
        $query = $this->getFilteredQuery($request, $exportType);
        $notifications = $query->get(); // Get all matching records, not paginated

        $fileName = "notifications_{$exportType}_" . date('Y-m-d') . ".csv";
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"$fileName\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['#', 'ชื่อลูกจ้าง (TH)', 'ชื่อลูกจ้าง (EN)', 'Passport', 'สัญชาติ', 'นายจ้าง', 'วันที่ครบกำหนด', 'ประเภท'];

        $callback = function() use($notifications, $columns) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // Add BOM for UTF-8
            fputcsv($file, $columns);

            foreach ($notifications as $index => $notification) {
                $employee = $notification->employee;
                if (!$employee) continue; // Skip if no employee data

                $row = [
                    $index + 1,
                    $employee->employeeNameTh,
                    $employee->employeeNameEn,
                    $employee->employeePassport,
                    $employee->employeeNationality,
                    $employee->employer->employerNameTh ?? 'N/A',
                    $notification->due_date ? \Carbon\Carbon::parse($notification->due_date)->format('d/m/Y') : '-',
                    $notification->type,
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

        // This method is proven to trigger the highlight on the employers.edit page
        return redirect()->route('employers.edit', $employer)
                     ->with('highlight_employee', $employee->id);
    }
}
