<?php

namespace App\Traits;

use Illuminate\Http\Request;
use App\Models\Employee;

trait DailyCheckTrait
{
    public function toggleDailyCheck(Request $request, Employee $employee)
    {
        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        $employee->daily_check_enabled = !$employee->daily_check_enabled;
        $employee->save();

        return response()->json([
            'success' => true,
            'enabled' => $employee->daily_check_enabled,
            'message' => $employee->daily_check_enabled ? 'Daily check enabled' : 'Daily check disabled',
            'pending' => $employee->is_daily_check_pending
        ]);
    }

    public function checkDaily(Request $request, Employee $employee)
    {
        if (!auth()->user()->can('edit-employees')) {
            abort(403);
        }

        if (!$employee->daily_check_enabled) {
            return response()->json(['success' => false, 'message' => 'Daily check is disabled for this employee.'], 400);
        }

        $employee->last_daily_checked_at = now();
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Checked successfully',
            'last_checked_at' => $employee->last_daily_checked_at->format('d/m/Y H:i')
        ]);
    }
}
