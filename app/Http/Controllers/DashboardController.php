<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Employee Stats
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', '!=', 'terminated')->count();
        $resignedEmployees = Employee::onlyTrashed()->count() + Employee::where('status', 'terminated')->count();

        // 2. Production/Workflow Stats
        $preProductionOrders = ProductionOrder::where('status', 'pre_production')->count();
        $activeOrders = ProductionOrder::where('status', 'active')->count();
        $completedOrders = ProductionOrder::where('status', 'completed')->count();

        // 3. Item Status Breakdown
        $itemStats = ProductionItem::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // 4. Activity Logs (Last 14 Days)
        $activityData = ActivityLog::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subDays(14))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $activityLabels = $activityData->pluck('date');
        $activityCounts = $activityData->pluck('count');

        // 5. Recent Activity
        $recentActivities = ActivityLog::with('user')->latest()->take(5)->get();

        return view('dashboard', compact(
            'totalEmployees',
            'activeEmployees',
            'resignedEmployees',
            'preProductionOrders',
            'activeOrders',
            'completedOrders',
            'itemStats',
            'activityLabels',
            'activityCounts',
            'recentActivities'
        ));
    }
}
