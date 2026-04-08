<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
}
