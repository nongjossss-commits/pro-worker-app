<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ProductionItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Combined appointment reminder — sums pending appointments across the 3
 * places they're tracked independently: employee_appointments rows whose
 * resolution_tab_id belongs to a 'registration' ResolutionTab, the same for
 * 'renewal' (this naturally includes dual-listed employees too — each
 * dual-listed appointment is its own row scoped to that specific renewal
 * tab, see EmployeeAppointment), and ProductionItem.appointment_date
 * (Workflow, a separate model entirely). Mirrors the per-module calendar
 * logic in RegistrationController/RenewalController/WorkflowController
 * exactly (same status/tenancy-scope conditions) rather than introducing
 * new filtering rules.
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
            // Joined through employee_appointments (not Employee's own
            // resolution_tab_id) so dual-listed employees' tab-specific
            // appointments are counted too — each dual listing has its own
            // row here, scoped to the exact renewal tab it belongs to.
            $query = Employee::query()
                ->join('employee_appointments', 'employee_appointments.employee_id', '=', 'employees.id')
                ->join('resolution_tabs', 'resolution_tabs.id', '=', 'employee_appointments.resolution_tab_id')
                ->where('resolution_tabs.type', $type);
            if (auth()->user()->can('manage-tickets')) {
                $query->withoutGlobalScope('employerTenancy');
            }

            $query->select(DB::raw('DATE(employee_appointments.appointment_date) as date'), DB::raw('count(*) as count'))
                ->whereBetween('employee_appointments.appointment_date', [$start, $end])
                ->whereNull('employee_appointments.appointment_completed_at')
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
            // Same join as calendarData() — also covers dual-listed
            // employees, and employees.* is aliased over by
            // employee_appointments' own columns (including
            // resolution_tab_id) so the link below points at the exact
            // tab this appointment belongs to, not necessarily the
            // employee's home tab.
            $query = Employee::query()
                ->join('employee_appointments', 'employee_appointments.employee_id', '=', 'employees.id')
                ->join('resolution_tabs', 'resolution_tabs.id', '=', 'employee_appointments.resolution_tab_id')
                ->where('resolution_tabs.type', $type);
            if (auth()->user()->can('manage-tickets')) {
                $query->withoutGlobalScope('employerTenancy');
            }

            $query->select(
                    'employees.*',
                    'employee_appointments.resolution_tab_id as resolution_tab_id',
                    'employee_appointments.appointment_date as appointment_date',
                    'employee_appointments.appointment_location as appointment_location',
                    'employee_appointments.appointment_completed_at as appointment_completed_at'
                )
                ->whereDate('employee_appointments.appointment_date', $date)
                ->whereNull('employee_appointments.appointment_completed_at')
                ->with(['employer', 'resolutionTab'])
                ->orderBy('employee_appointments.appointment_date')
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
