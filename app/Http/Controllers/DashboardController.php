<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\EmployeeAppointment;
use App\Models\EmployeeRenewalLink;
use App\Models\Employer;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\ResolutionTab;
use App\Models\WorkType;
use App\Services\SuperAdminService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Executive dashboard (showcase page): workforce, hires vs notified out,
 * work pipeline, monthly output, upcoming appointments, 90-day reports,
 * document expiry, nationality / top employers.
 *
 * No money figures on purpose — any account with the dashboard menu sees
 * this page (finance stays in the Finance menu, behind its own permission
 * and menu password).
 *
 * Every number is counted the same way as the menu it comes from:
 *  - workforce = employees not deleted, not notified out (terminated_at),
 *    and not in a cancelled resolution status;
 *  - appointments = Registration / Renewal tab appointments + Workflow item
 *    appointments that are not marked done.
 * Date grouping is done in PHP (no MySQL-only date functions; dev = SQLite).
 */
class DashboardController extends Controller
{
    private const CANCELLED = ['registration_cancelled', 'renewal_cancelled'];

    public function index(SuperAdminService $menus)
    {
        $now = Carbon::now();
        $today = Carbon::today();
        $monthStart = $now->copy()->startOfMonth();
        $prevMonthStart = $monthStart->copy()->subMonth();
        $months = collect(range(11, 0))->map(fn ($i) => $monthStart->copy()->subMonths($i)); // last 12 months, oldest first
        $monthKeys = $months->map(fn ($m) => $m->format('Y-m'))->all();
        $monthLabels = $months->map(fn ($m) => $this->monthLabel($m, true))->all();
        $rangeStart = $months->first()->copy();

        $workforce = fn () => Employee::query()->whereNull('terminated_at')->whereNotIn('status', self::CANCELLED);

        // ---------- 1. Workforce ----------
        $workforceCount = $workforce()->count();
        $workforceByStatus = $workforce()->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status');
        $newThisMonth = $workforce()->where('created_at', '>=', $monthStart)->count();
        $newPrevMonth = Employee::query()->whereBetween('created_at', [$prevMonthStart, $monthStart->copy()->subSecond()])->count();
        $leftThisMonth = Employee::query()->where('terminated_at', '>=', $monthStart)->count();

        // headcount at the end of each of the last 6 months (sparkline)
        $lifespans = Employee::query()->whereNotIn('status', self::CANCELLED)->get(['created_at', 'terminated_at']);
        $headcountTrend = $months->slice(-6)->map(function ($m) use ($lifespans) {
            $end = $m->copy()->endOfMonth();
            return $lifespans->filter(fn ($e) => $e->created_at && $e->created_at->lte($end)
                && (! $e->terminated_at || Carbon::parse($e->terminated_at)->gt($end)))->count();
        })->values()->all();

        // ---------- 2. Employers ----------
        $employerCount = Employer::query()->count();
        $employersWithWorkers = $workforce()->distinct()->count('employer_id');
        $newEmployersThisMonth = Employer::query()->where('created_at', '>=', $monthStart)->count();

        // ---------- 3. Work in progress (pipeline per tab) ----------
        $pipeline = [];
        $wfPending = ProductionItem::query()->where('production_items.status', 'pending')
            ->whereHas('order', fn ($o) => $o->where('status', 'active')->whereNotNull('work_type_id'))
            ->join('production_orders', 'production_orders.id', '=', 'production_items.production_order_id')
            ->select('production_orders.work_type_id', DB::raw('count(*) as c'))
            ->groupBy('production_orders.work_type_id')->pluck('c', 'work_type_id');
        foreach (WorkType::query()->orderBy('order')->get() as $wt) {
            $pipeline[] = ['group' => 'workflow', 'name' => $wt->name, 'count' => (int) ($wfPending[$wt->id] ?? 0),
                'url' => $menus->isVisible('workflow') ? route('workflow.index', ['tab' => $wt->slug]) : null];
        }
        $preProduction = ProductionOrder::query()->where('status', 'pre_production')->count();

        $regPending = Employee::query()->where('status', 'registration_pending')
            ->select('resolution_tab_id', DB::raw('count(*) as c'))->groupBy('resolution_tab_id')->pluck('c', 'resolution_tab_id');
        $renPending = Employee::query()->where('status', 'renewal_pending')
            ->select('resolution_tab_id', DB::raw('count(*) as c'))->groupBy('resolution_tab_id')->pluck('c', 'resolution_tab_id');
        $renLinkPending = EmployeeRenewalLink::query()->where('status', 'renewal_pending')
            ->whereHas('employee', fn ($e) => $e->whereNull('terminated_at'))
            ->select('resolution_tab_id', DB::raw('count(*) as c'))->groupBy('resolution_tab_id')->pluck('c', 'resolution_tab_id');
        foreach (ResolutionTab::query()->orderBy('id')->get() as $tab) {
            $isReg = $tab->type === 'registration';
            $count = $isReg ? ($regPending[$tab->id] ?? 0) : (($renPending[$tab->id] ?? 0) + ($renLinkPending[$tab->id] ?? 0));
            $menu = $isReg ? 'registration_resolution' : 'renewal_resolution';
            $pipeline[] = ['group' => $isReg ? 'registration' : 'renewal', 'name' => $tab->name, 'count' => (int) $count,
                'url' => $menus->isVisible($menu) ? route($isReg ? 'production.registration.operations' : 'production.renewal.operations', $tab->id) : null];
        }
        $inProgressTotal = array_sum(array_column($pipeline, 'count'));

        // ---------- 4. Finished work per month (12 months) ----------
        $wfDone = ProductionItem::query()->where('status', 'completed')->where('completed_at', '>=', $rangeStart)
            ->whereHas('order')->pluck('completed_at');
        $regDone = Employee::query()->where('status', 'registration_completed')->where('resolution_completed_at', '>=', $rangeStart)->pluck('resolution_completed_at');
        $renDone = Employee::query()->where('status', 'renewal_completed')->where('resolution_completed_at', '>=', $rangeStart)->pluck('resolution_completed_at')
            ->concat(EmployeeRenewalLink::query()->where('status', 'renewal_completed')->where('resolution_completed_at', '>=', $rangeStart)
                ->whereHas('employee')->pluck('resolution_completed_at'));
        $output = [
            'workflow' => $this->perMonth($wfDone, $monthKeys),
            'registration' => $this->perMonth($regDone, $monthKeys),
            'renewal' => $this->perMonth($renDone, $monthKeys),
        ];
        $doneThisMonth = $output['workflow'][11] + $output['registration'][11] + $output['renewal'][11];
        $donePrevMonth = $output['workflow'][10] + $output['registration'][10] + $output['renewal'][10];

        // ---------- 5. Document expiry (workforce) ----------
        $expiry = [];
        foreach (['visaExpiryDate' => __('Visa'), 'workPermitExpiryDate' => __('Work Permit'), 'passportExpiryDate' => __('Passport')] as $col => $label) {
            $d = fn ($days) => $today->copy()->addDays($days)->toDateString();
            $expiry[] = [
                'label' => $label,
                'expired' => $workforce()->whereNotNull($col)->where($col, '<', $today->toDateString())->count(),
                'd30' => $workforce()->whereBetween($col, [$today->toDateString(), $d(30)])->count(),
                'd60' => $workforce()->where($col, '>', $d(30))->where($col, '<=', $d(60))->count(),
                'd90' => $workforce()->where($col, '>', $d(60))->where($col, '<=', $d(90))->count(),
            ];
        }
        $expiringSoon = array_sum(array_column($expiry, 'd30'));

        // ---------- 6. Nationality / top employers ----------
        $nationalityNames = ['เมียนมา' => 'Myanmar', 'ลาว' => 'Laos', 'กัมพูชา' => 'Cambodia', 'เวียดนาม' => 'Vietnam'];
        $nationalities = $workforce()->select('employeeNationality', DB::raw('count(*) as c'))->groupBy('employeeNationality')
            ->orderByDesc('c')->get()
            ->map(fn ($r) => ['label' => $r->employeeNationality ? __($nationalityNames[$r->employeeNationality] ?? $r->employeeNationality) : __('Not specified'), 'count' => (int) $r->c])
            ->values()->all();

        $topRows = $workforce()->select('employer_id', DB::raw('count(*) as c'))->whereNotNull('employer_id')
            ->groupBy('employer_id')->orderByDesc('c')->limit(8)->get();
        $topNames = Employer::query()->whereIn('id', $topRows->pluck('employer_id'))->get()->keyBy('id');
        $topEmployers = $topRows->map(function ($r) use ($topNames) {
            $er = $topNames->get($r->employer_id);
            $name = $er ? (app()->getLocale() === 'th' ? ($er->employerNameTh ?: $er->employerNameEn) : ($er->employerNameEn ?: $er->employerNameTh)) : '#' . $r->employer_id;
            return ['label' => trim($name . ($er && $er->name_suffix ? ' ' . $er->name_suffix : '')), 'count' => (int) $r->c];
        })->values()->all();

        // ---------- 7. Hires vs notified out per month (12 months) ----------
        $movement = [
            'hired' => $this->perMonth(Employee::query()->whereNotIn('status', self::CANCELLED)->where('created_at', '>=', $rangeStart)->pluck('created_at'), $monthKeys),
            'left' => $this->perMonth(Employee::query()->where('terminated_at', '>=', $rangeStart)->pluck('terminated_at'), $monthKeys),
        ];

        // ---------- 8. Upcoming appointments (not done) ----------
        // Resolution tab appointments + Workflow item appointments.
        $apptDates = EmployeeAppointment::query()->whereNotNull('appointment_date')->whereNull('appointment_completed_at')
            ->where('appointment_date', '<', $today->copy()->addDays(7)->endOfDay())
            ->whereHas('employee', fn ($e) => $e->whereNull('terminated_at'))
            ->pluck('appointment_date')
            ->concat(ProductionItem::query()->whereNotNull('appointment_date')->whereNull('appointment_completed_at')
                ->where('status', 'pending')
                ->where('appointment_date', '<', $today->copy()->addDays(7)->endOfDay())
                ->whereHas('order', fn ($o) => $o->where('status', 'active'))
                ->pluck('appointment_date'))
            ->map(fn ($d) => Carbon::parse($d));
        $appointments = [
            'overdue' => $apptDates->filter(fn ($d) => $d->lt($today))->count(),
            'today' => $apptDates->filter(fn ($d) => $d->isSameDay($today))->count(),
            'tomorrow' => $apptDates->filter(fn ($d) => $d->isSameDay($today->copy()->addDay()))->count(),
            'week' => $apptDates->filter(fn ($d) => $d->gte($today))->count(), // today … +7 days
        ];

        // ---------- 9. 90-day reports (workforce) ----------
        $ninety = fn ($from, $to) => $workforce()->whereBetween('ninetyDayReportDate', [$from->toDateString(), $to->toDateString()])->count();
        $ninetyDay = [
            'overdue' => $workforce()->whereNotNull('ninetyDayReportDate')->where('ninetyDayReportDate', '<', $today->toDateString())
                ->where('ninetyDayReportDate', '>=', $today->copy()->subDays(30)->toDateString())->count(), // missed in the last 30 days
            'd7' => $ninety($today, $today->copy()->addDays(7)),
            'd15' => $ninety($today, $today->copy()->addDays(15)),
        ];
        $expiry[] = [
            'label' => __('90-Day Report'),
            'expired' => $ninetyDay['overdue'],
            'd30' => $ninety($today, $today->copy()->addDays(30)),
            'd60' => $ninety($today->copy()->addDays(31), $today->copy()->addDays(60)),
            'd90' => $ninety($today->copy()->addDays(61), $today->copy()->addDays(90)),
        ];

        // ---------- 10. Activity ----------
        $activity = ActivityLog::query()->where('created_at', '>=', $today->copy()->subDays(13))->pluck('created_at')
            ->countBy(fn ($d) => Carbon::parse($d)->toDateString());
        $activityDays = collect(range(13, 0))->map(fn ($i) => $today->copy()->subDays($i));
        $activityLabels = $activityDays->map(fn ($d) => $d->translatedFormat('j M'))->all();
        $activityCounts = $activityDays->map(fn ($d) => (int) ($activity[$d->toDateString()] ?? 0))->all();
        $recentActivities = ActivityLog::with('user')->latest()->take(6)->get();

        return view('dashboard', compact(
            'workforceCount', 'workforceByStatus', 'newThisMonth', 'newPrevMonth', 'leftThisMonth', 'headcountTrend',
            'employerCount', 'employersWithWorkers', 'newEmployersThisMonth',
            'pipeline', 'preProduction', 'inProgressTotal',
            'monthLabels', 'output', 'doneThisMonth', 'donePrevMonth',
            'expiry', 'expiringSoon', 'nationalities', 'topEmployers',
            'movement', 'appointments', 'ninetyDay',
            'activityLabels', 'activityCounts', 'recentActivities'
        ));
    }

    /** Count dates into the given 'Y-m' buckets (in order). */
    private function perMonth($dates, array $monthKeys): array
    {
        $counts = array_fill_keys($monthKeys, 0);
        foreach ($dates as $d) {
            if (! $d) continue;
            $k = Carbon::parse($d)->format('Y-m');
            if (isset($counts[$k])) $counts[$k]++;
        }
        return array_values($counts);
    }

    /** "ต.ค. 69" / "Oct 26" — Buddhist year in Thai. */
    private function monthLabel(Carbon $m, bool $short = false): string
    {
        $locale = app()->getLocale();
        $year = $locale === 'th' ? $m->year + 543 : $m->year;
        $name = $m->copy()->locale($locale)->translatedFormat($short ? 'M' : 'F');
        return $name . ' ' . ($short ? substr((string) $year, -2) : $year);
    }
}
