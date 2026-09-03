<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ProductionItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Combined appointment reminder — sums pending appointments across the 3
 * places they're tracked independently: Employee.appointment_date scoped to
 * a 'registration' ResolutionTab, the same scoped to 'renewal', and
 * ProductionItem.appointment_date (Workflow, a separate model entirely).
 * Mirrors the per-module calendar logic in RegistrationController/
 * RenewalController/WorkflowController exactly (same status/tenancy-scope
 * conditions) rather than introducing new filtering rules.
 */
class AppointmentReminderController extends Controller
{
    public function calendarData(Request $request)
    {
        abort_unless(auth()->user()->can('edit-employees'), 403);

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $counts = [];

        foreach (['registration', 'renewal'] as $type) {
            $query = Employee::query()
                ->whereHas('resolutionTab', fn ($q) => $q->where('type', $type));
            if (auth()->user()->can('manage-tickets')) {
                $query->withoutGlobalScope('employerTenancy');
            }

            $query->select(DB::raw('DATE(appointment_date) as date'), DB::raw('count(*) as count'))
                ->whereBetween('appointment_date', [$start, $end])
                ->whereNull('appointment_completed_at')
                ->groupBy('date')
                ->get()
                ->each(function ($row) use (&$counts) {
                    $counts[$row->date] = ($counts[$row->date] ?? 0) + $row->count;
                });
        }

        $workflowQuery = ProductionItem::select(DB::raw('DATE(appointment_date) as date'), DB::raw('count(*) as count'))
            ->whereBetween('appointment_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'completed')
            ->whereNull('appointment_completed_at');

        // ProductionItem has no automatic tenancy scope of its own (unlike
        // Employee, used above) — see ProductionItem::scopeVisibleToUser().
        if (!auth()->user()->can('manage-tickets')) {
            $workflowQuery->visibleToUser();
        }

        $workflowQuery
            ->groupBy('date')
            ->get()
            ->each(function ($row) use (&$counts) {
                $counts[$row->date] = ($counts[$row->date] ?? 0) + $row->count;
            });

        return response()->json($counts);
    }

    public function appointmentsByDate(Request $request)
    {
        abort_unless(auth()->user()->can('edit-employees'), 403);

        $request->validate(['date' => 'required|date']);
        $date = Carbon::parse($request->date);

        $items = collect();

        foreach (['registration' => 'มติลงทะเบียน', 'renewal' => 'มติต่ออายุ'] as $type => $label) {
            $query = Employee::query()
                ->whereHas('resolutionTab', fn ($q) => $q->where('type', $type));
            if (auth()->user()->can('manage-tickets')) {
                $query->withoutGlobalScope('employerTenancy');
            }

            $query->whereDate('appointment_date', $date)
                ->whereNull('appointment_completed_at')
                ->with(['employer', 'resolutionTab'])
                ->orderBy('appointment_date')
                ->get()
                ->each(function ($employee) use (&$items, $type, $label) {
                    $items->push((object) [
                        'source' => $type,
                        'source_label' => $label,
                        'employee_id' => $employee->id,
                        'employer_id' => $employee->employer_id,
                        'name_th' => $employee->employeeNameTh,
                        'name_en' => $employee->employeeNameEn,
                        'title_th' => $employee->employeeTitleTh,
                        'title_en' => $employee->employeeTitleEn,
                        'passport' => $employee->employeePassport,
                        'photo_url' => $employee->photo_url,
                        'company' => $employee->employer->employerNameTh ?? '-',
                        'appointment_date' => $employee->appointment_date,
                        'appointment_location' => $employee->appointment_location,
                        'link' => route($type === 'registration' ? 'production.registration.index' : 'production.renewal.index', ['resolutionTab' => $employee->resolution_tab_id]),
                    ]);
                });
        }

        $workflowItemsQuery = ProductionItem::whereDate('appointment_date', $date)
            ->where('status', '!=', 'cancelled')
            ->whereNull('appointment_completed_at');

        if (!auth()->user()->can('manage-tickets')) {
            $workflowItemsQuery->visibleToUser();
        }

        $workflowItemsQuery
            ->with(['employee', 'order.employer'])
            ->get()
            ->each(function ($item) use (&$items) {
                $employee = $item->employee;
                $items->push((object) [
                    'source' => 'workflow',
                    'source_label' => 'Workflow',
                    'employee_id' => $employee->id ?? null,
                    'employer_id' => $item->order->employer_id ?? null,
                    'name_th' => $employee->employeeNameTh ?? null,
                    'name_en' => $employee->employeeNameEn ?? null,
                    'title_th' => $employee->employeeTitleTh ?? null,
                    'title_en' => $employee->employeeTitleEn ?? null,
                    'passport' => $employee->employeePassport ?? null,
                    'photo_url' => $employee->photo_url ?? null,
                    'company' => $item->order->employer->employerNameTh ?? '-',
                    'appointment_date' => $item->appointment_date,
                    'appointment_location' => $item->appointment_location,
                    'link' => route('workflow.index'),
                ]);
            });

        $items = $items->sortBy('appointment_date')->values();

        $html = view('partials.appointment_reminder.day_list', compact('items'))->render();

        return response()->json(['html' => $html]);
    }
}
