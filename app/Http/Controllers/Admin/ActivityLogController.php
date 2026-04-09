<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    public function index()
    {
        // Get distinct years from created_at
        $years = ActivityLog::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('admin.activity_logs.index', compact('years'));
    }

    public function showYear($year)
    {
        // Get months that have activity in this year
        $months = ActivityLog::whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month')
            ->distinct()
            ->orderBy('month', 'desc')
            ->pluck('month');

        return view('admin.activity_logs.year', compact('year', 'months'));
    }

    public function showMonth($year, $month)
    {
        // Get days that have activity in this month
        $days = ActivityLog::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->selectRaw('DAY(created_at) as day')
            ->distinct()
            ->orderBy('day', 'desc')
            ->pluck('day');

        return view('admin.activity_logs.month', compact('year', 'month', 'days'));
    }

    public function showDay(Request $request, $year, $month, $day)
    {
        $date = Carbon::createFromDate($year, $month, $day)->format('Y-m-d');

        // Filter parameters
        $userId = $request->query('user_id');
        $actionFilter = $request->query('action');
        $search = $request->query('search');

        $query = ActivityLog::with(['user', 'subject'])
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($actionFilter) {
            $query->where('action', $actionFilter);
        }

        // Search: support ID:123 format and text search
        if ($search) {
            $searchTerm = trim($search);
            if (preg_match('/^ID:\s*(\d+)$/i', $searchTerm, $matches)) {
                // Search by subject ID
                $query->where('subject_id', (int) $matches[1]);
            } else {
                // Search by description or subject name (via properties or description text)
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('description', 'like', '%' . $searchTerm . '%')
                      ->orWhere('subject_id', 'like', '%' . $searchTerm . '%');
                });
            }
        }

        $logs = $query->get();

        // Get list of users who had activity on this day for the filter dropdown
        $activeUserIds = ActivityLog::whereDate('created_at', $date)
            ->distinct()
            ->pluck('user_id');

        $users = User::whereIn('id', $activeUserIds)->get();

        // Get distinct action types on this day for the filter dropdown
        $actions = ActivityLog::whereDate('created_at', $date)
            ->distinct()
            ->pluck('action')
            ->sort()
            ->values();

        return view('admin.activity_logs.day', compact('logs', 'date', 'users', 'userId', 'actionFilter', 'actions', 'search', 'year', 'month', 'day'));
    }

    public function search(Request $request)
    {
        $date = $request->input('date');
        if (!$date) {
            return back()->with('error', 'Please select a date');
        }

        $d = Carbon::parse($date);
        return redirect()->route('admin.activity-logs.day', [
            'year' => $d->year,
            'month' => $d->month,
            'day' => $d->day,
        ]);
    }

    /**
     * AJAX: ค้นหาลูกจ้าง/นายจ้าง สำหรับ autocomplete
     */
    public function searchSubject(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $results = [];

        // ค้นหาลูกจ้าง
        $employees = Employee::where(function ($query) use ($q) {
                $query->where('employeeNameTh', 'like', "%{$q}%")
                      ->orWhere('employeeNameEn', 'like', "%{$q}%")
                      ->orWhere('name_suffix', 'like', "%{$q}%")
                      ->orWhere('employeePassport', 'like', "%{$q}%")
                      ->orWhere('employee_reference_id', 'like', "%{$q}%")
                      ->orWhere('request_number', 'like', "%{$q}%")
                      ->orWhere('registration_request_number', 'like', "%{$q}%")
                      ->orWhere('renewal_request_number', 'like', "%{$q}%")
                      ->orWhere('id', $q);
            })
            ->with('employer:id,employerNameTh,employerNameEn')
            ->limit(15)
            ->get(['id', 'employeeNameTh', 'employeeNameEn', 'employeePassport', 'employee_reference_id', 'employer_id']);

        foreach ($employees as $emp) {
            $results[] = [
                'type' => 'employee',
                'type_label' => 'ลูกจ้าง',
                'id' => $emp->id,
                'subject_type' => 'App\\Models\\Employee',
                'name' => $emp->employeeNameEn ?: $emp->employeeNameTh ?: '-',
                'name_sub' => $emp->employeeNameTh ?: '',
                'detail' => implode(' | ', array_filter([
                    $emp->employeePassport ? "PP: {$emp->employeePassport}" : null,
                    $emp->employee_reference_id ? "Ref: {$emp->employee_reference_id}" : null,
                ])),
                'employer' => $emp->employer->employerNameTh ?? $emp->employer->employerNameEn ?? '',
            ];
        }

        // ค้นหานายจ้าง
        $employers = Employer::where(function ($query) use ($q) {
                $query->where('employerNameTh', 'like', "%{$q}%")
                      ->orWhere('employerNameEn', 'like', "%{$q}%")
                      ->orWhere('name_suffix', 'like', "%{$q}%")
                      ->orWhere('id', $q);
            })
            ->limit(10)
            ->get(['id', 'employerNameTh', 'employerNameEn']);

        foreach ($employers as $er) {
            $results[] = [
                'type' => 'employer',
                'type_label' => 'นายจ้าง',
                'id' => $er->id,
                'subject_type' => 'App\\Models\\Employer',
                'name' => $er->employerNameTh ?: $er->employerNameEn ?: '-',
                'name_sub' => $er->employerNameEn ?: '',
                'detail' => "ID: {$er->id}",
                'employer' => '',
            ];
        }

        return response()->json($results);
    }

    /**
     * แสดงประวัติการทำงานทั้งหมดของลูกจ้าง/นายจ้างรายนั้น
     */
    public function subjectHistory(Request $request)
    {
        $subjectType = $request->input('subject_type');
        $subjectId = $request->input('subject_id');

        if (!$subjectType || !$subjectId) {
            return redirect()->route('admin.activity-logs.index')->with('error', 'กรุณาเลือกลูกจ้างหรือนายจ้างก่อน');
        }

        // โหลดข้อมูล subject
        $subject = null;
        $subjectLabel = '';
        if ($subjectType === 'App\\Models\\Employee') {
            $subject = Employee::with('employer:id,employerNameTh,employerNameEn')->find($subjectId);
            if ($subject) {
                $subjectLabel = ($subject->employeeNameEn ?: $subject->employeeNameTh ?: 'ลูกจ้าง') . " (ID: {$subject->id})";
            }
        } elseif ($subjectType === 'App\\Models\\Employer') {
            $subject = Employer::find($subjectId);
            if ($subject) {
                $subjectLabel = ($subject->employerNameTh ?: $subject->employerNameEn ?: 'นายจ้าง') . " (ID: {$subject->id})";
            }
        }

        if (!$subject) {
            return redirect()->route('admin.activity-logs.index')->with('error', 'ไม่พบข้อมูลที่ต้องการ');
        }

        // Filter parameters
        $userId = $request->query('user_id');
        $actionFilter = $request->query('action');
        $categoryFilter = $request->query('category');

        $query = ActivityLog::with(['user'])
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderBy('created_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($actionFilter) {
            $query->where('action', $actionFilter);
        }

        // Category filter: group actions into categories
        if ($categoryFilter === 'changes') {
            // แก้ไข/เปลี่ยนแปลง/ดาวน์โหลด/อัพโหลด
            $query->whereIn('action', ['create', 'update', 'delete', 'restore', 'force_delete', 'download', 'upload', 'export', 'import', 'generate_document', 'print']);
        } elseif ($categoryFilter === 'movement') {
            // แจ้งออก/ย้ายนายจ้าง/คืนสภาพ/เปลี่ยนสถานะ/ดำเนินการ
            $query->whereIn('action', ['terminate', 'reinstate', 'transfer', 'status_change', 'workflow_process', 'bulk_action']);
        }

        $logs = $query->paginate(50)->withQueryString();

        // รายชื่อผู้ใช้ที่มี activity กับ subject นี้
        $activeUserIds = ActivityLog::where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->distinct()->pluck('user_id');
        $users = User::whereIn('id', $activeUserIds)->get();

        // ประเภท action ทั้งหมดของ subject นี้
        $actions = ActivityLog::where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->distinct()->pluck('action')->sort()->values();

        return view('admin.activity_logs.subject_history', compact(
            'logs', 'subject', 'subjectType', 'subjectId', 'subjectLabel',
            'users', 'userId', 'actions', 'actionFilter', 'categoryFilter'
        ));
    }
}
