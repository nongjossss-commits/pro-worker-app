@extends('layouts.app')

@section('title', __('Statistics Dashboard'))

@section('content')
<x-help-button manual="dashboard" title="{{ __('Dashboard') }}" />
@php
    $pct = fn ($now, $prev) => $prev > 0 ? round(($now - $prev) / $prev * 100) : null;
    $statusNames = [
        'active' => __('Working normally'),
        'registration_pending' => __('In registration resolution'),
        'registration_completed' => __('Registration finished'),
        'renewal_pending' => __('In renewal resolution'),
        'renewal_completed' => __('Renewal finished'),
    ];
    $groupSum = fn ($g) => collect($pipeline)->where('group', $g)->sum('count');
    $outputLast = fn ($k) => $output[$k][11];
    // Everything the charts need, in one object (single-line @json below).
    $dash = [
        'months' => $monthLabels,
        'output' => $output,
        'headcount' => $headcountTrend,
        'pipeline' => array_values(array_filter($pipeline, fn ($p) => $p['count'] > 0 || $p['group'] !== 'workflow')),
        'nationalities' => $nationalities,
        'expiry' => $expiry,
        'topEmployers' => $topEmployers,
        'activity' => ['labels' => $activityLabels, 'counts' => $activityCounts],
        'movement' => $movement,
        't' => [
            'workflow' => __('Workflow'), 'registration' => __('Registration Resolution'), 'renewal' => __('Renewal Resolution'),
            'people' => __('people'), 'items' => __('items'), 'actions' => __('Actions Per Day'), 'total' => __('Total'),
            'expired' => __('Expired'), 'd30' => __('Within 30 days'), 'd60' => __('31–60 days'), 'd90' => __('61–90 days'),
            'hired' => __('New employees'), 'left' => __('Notified out'), 'net' => __('Net change'), 'clickToOpen' => __('Click to open this tab'), 'share' => __('of all'),
            'headcount' => __('Workforce'),
        ],
    ];
@endphp

<div class="container-fluid py-3 exec-dash">
    {{-- ===== Hero ===== --}}
    <div class="ed-hero mb-4">
        <div class="ed-hero-shapes" aria-hidden="true"><span></span><span></span><span></span></div>
        <div class="position-relative d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <div class="ed-hero-kicker"><i class="bi bi-stars me-1"></i>{{ __('Executive Overview') }}</div>
                <h1 class="ed-hero-title mb-1">{{ __('Business at a glance') }}</h1>
                <div class="ed-hero-sub">{{ __('Live figures from every menu — hover any number or chart for the details.') }}</div>
            </div>
            <div class="text-end">
                <div class="ed-hero-date">{{ now()->locale(app()->getLocale())->translatedFormat('l j F') }} {{ app()->getLocale() === 'th' ? now()->year + 543 : now()->year }}</div>
                <div class="ed-hero-time"><i class="bi bi-arrow-repeat me-1"></i>{{ __('Updated') }} {{ now()->format('H:i') }}</div>
            </div>
        </div>
        <div class="position-relative ed-hero-strip mt-3">
            <div><span>{{ number_format(array_sum($output['workflow']) + array_sum($output['registration']) + array_sum($output['renewal'])) }}</span>{{ __('Finished in the last 12 months') }}</div>
            <div><span>{{ count(array_filter($nationalities, fn ($n) => $n['count'] > 0 && $n['label'] !== __('Not specified'))) }}</span>{{ __('Nationalities') }}</div>
            <div><span>{{ collect($pipeline)->where('count', '>', 0)->count() }}</span>{{ __('Tabs with work in progress') }}</div>
            <div><span>{{ number_format(array_sum($activityCounts)) }}</span>{{ __('Actions in the last 14 days') }}</div>
        </div>
    </div>

    {{-- ===== KPI cards ===== --}}
    <div class="ed-kpis mb-4">
        @php
            $kpis = [
                ['key' => 'workforce', 'icon' => 'bi-people-fill', 'tone' => 'primary', 'label' => __('Workforce'), 'value' => $workforceCount,
                 'sub' => __('+:n new this month', ['n' => number_format($newThisMonth)]), 'delta' => null, 'spark' => 'headcount',
                 'detail' => view()->make('dashboard._kpi_detail', ['title' => __('Workforce'), 'note' => __('Employees not notified out, not deleted and not cancelled.'),
                    'rows' => collect($statusNames)->map(fn ($l, $s) => [$l, (int) ($workforceByStatus[$s] ?? 0)])->values()->concat([[__('New this month'), $newThisMonth], [__('Notified out this month'), $leftThisMonth]])->all()])->render()],
                ['key' => 'employers', 'icon' => 'bi-buildings-fill', 'tone' => 'violet', 'label' => __('Employers'), 'value' => $employerCount,
                 'sub' => __(':n with workers now', ['n' => number_format($employersWithWorkers)]), 'delta' => null, 'spark' => null,
                 'detail' => view()->make('dashboard._kpi_detail', ['title' => __('Employers'), 'note' => __('All employers in the system (not deleted).'),
                    'rows' => [[__('Total employers'), $employerCount], [__('With workers now'), $employersWithWorkers], [__('New this month'), $newEmployersThisMonth]]])->render()],
                ['key' => 'progress', 'icon' => 'bi-hourglass-split', 'tone' => 'amber', 'label' => __('In progress'), 'value' => $inProgressTotal,
                 'sub' => __('Across :n tabs', ['n' => collect($pipeline)->where('count', '>', 0)->count()]), 'delta' => null, 'spark' => null,
                 'detail' => view()->make('dashboard._kpi_detail', ['title' => __('In progress'), 'note' => __('Employees still being worked on in each menu.'),
                    'rows' => [[__('Workflow'), $groupSum('workflow')], [__('Registration Resolution'), $groupSum('registration')], [__('Renewal Resolution'), $groupSum('renewal')], [__('Pre-Production jobs'), $preProduction]]])->render()],
                ['key' => 'done', 'icon' => 'bi-patch-check-fill', 'tone' => 'emerald', 'label' => __('Finished this month'), 'value' => $doneThisMonth,
                 'sub' => __('Last month: :n', ['n' => number_format($donePrevMonth)]), 'delta' => $pct($doneThisMonth, $donePrevMonth), 'deltaLabel' => __('vs last month'), 'spark' => 'output',
                 'detail' => view()->make('dashboard._kpi_detail', ['title' => __('Finished this month'), 'note' => __('Counted by the date each employee was finished.'),
                    'rows' => [[__('Workflow'), $outputLast('workflow')], [__('Registration Resolution'), $outputLast('registration')], [__('Renewal Resolution'), $outputLast('renewal')], [__('Last month'), $donePrevMonth]]])->render()],
            ];
            $kpis[] = ['key' => 'appointments', 'icon' => 'bi-calendar-check-fill', 'tone' => 'teal', 'label' => __('Appointments in the next 7 days'), 'value' => $appointments['week'],
                'sub' => __('Today: :n', ['n' => number_format($appointments['today'])]), 'delta' => null, 'spark' => null,
                'detail' => view()->make('dashboard._kpi_detail', ['title' => __('Appointments in the next 7 days'), 'note' => __('Registration / Renewal resolution and Workflow appointments not marked done.'),
                    'rows' => [[__('Today'), $appointments['today']], [__('Tomorrow'), $appointments['tomorrow']], [__('Next 7 days'), $appointments['week']], [__('Past date, not done'), $appointments['overdue']]]])->render()];
            $kpis[] = ['key' => 'ninety', 'icon' => 'bi-person-check', 'tone' => 'rose', 'label' => __('90-day reports due in 15 days'), 'value' => $ninetyDay['d15'],
                'sub' => __('Within 7 days: :n', ['n' => number_format($ninetyDay['d7'])]), 'delta' => null, 'spark' => null,
                'detail' => view()->make('dashboard._kpi_detail', ['title' => __('90-day reports due in 15 days'), 'note' => __('Current workforce only.'),
                    'rows' => [[__('Within 7 days'), $ninetyDay['d7']], [__('Within 15 days'), $ninetyDay['d15']], [__('Missed (last 30 days)'), $ninetyDay['overdue']]]])->render()];
            $kpis[] = ['key' => 'expiry', 'icon' => 'bi-alarm-fill', 'tone' => 'orange', 'label' => __('Documents expiring in 30 days'), 'value' => $expiringSoon,
                'sub' => __('Visa / work permit / passport'), 'delta' => null, 'spark' => null,
                'detail' => view()->make('dashboard._kpi_detail', ['title' => __('Documents expiring in 30 days'), 'note' => __('Current workforce only.'),
                    'rows' => collect($expiry)->map(fn ($e) => [$e['label'], $e['d30']])->concat(collect($expiry)->map(fn ($e) => [$e['label'] . ' — ' . __('Expired'), $e['expired']]))->all()])->render()];
        @endphp
        @foreach($kpis as $k)
            <div>
                <div class="ed-kpi ed-tone-{{ $k['tone'] }} h-100" tabindex="0" data-ed-detail="kpi-detail-{{ $k['key'] }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="ed-kpi-icon"><i class="bi {{ $k['icon'] }}"></i></span>
                        @if(!is_null($k['delta']))
                            <span class="ed-delta {{ $k['delta'] >= 0 ? 'up' : 'down' }}" title="{{ $k['deltaLabel'] ?? '' }}">
                                <i class="bi {{ $k['delta'] >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>{{ abs($k['delta']) }}%
                            </span>
                        @endif
                    </div>
                    <div class="ed-kpi-label">{{ $k['label'] }}</div>
                    <div class="ed-kpi-value" data-countup="{{ $k['value'] }}">
                        {{ number_format($k['value']) }}
                    </div>
                    <div class="ed-kpi-sub">{{ $k['sub'] }}</div>
                    @if($k['spark'])
                        <div class="ed-spark"><canvas data-spark="{{ $k['spark'] }}"></canvas></div>
                    @endif
                    <i class="bi bi-info-circle ed-kpi-info" aria-hidden="true"></i>
                </div>
                <template id="kpi-detail-{{ $k['key'] }}">{!! $k['detail'] !!}</template>
            </div>
        @endforeach
    </div>

    {{-- ===== Output trend + hires vs notified out ===== --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="ed-card h-100">
                <div class="ed-card-head">
                    <div>
                        <h6><i class="bi bi-bar-chart-line-fill me-2"></i>{{ __('Work finished per month') }}</h6>
                        <div class="ed-card-sub">{{ __('Last 12 months · by menu') }}</div>
                    </div>
                    <div class="ed-pill">{{ __('12-month total') }} <strong>{{ number_format(array_sum($output['workflow']) + array_sum($output['registration']) + array_sum($output['renewal'])) }}</strong></div>
                </div>
                <div class="ed-chart" style="height: 320px;"><canvas id="edOutput"></canvas></div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="ed-card h-100">
                <div class="ed-card-head">
                    <div>
                        <h6><i class="bi bi-arrow-left-right me-2"></i>{{ __('New employees vs notified out') }}</h6>
                        <div class="ed-card-sub">{{ __('Last 12 months · by month') }}</div>
                    </div>
                    <div class="ed-pill">{{ __('Net change') }} <strong>{{ (array_sum($movement['hired']) - array_sum($movement['left'])) >= 0 ? '+' : '' }}{{ number_format(array_sum($movement['hired']) - array_sum($movement['left'])) }}</strong></div>
                </div>
                <div class="ed-chart" style="height: 320px;"><canvas id="edMovement"></canvas></div>
            </div>
        </div>
    </div>

    {{-- ===== Pipeline + nationality ===== --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="ed-card h-100">
                <div class="ed-card-head">
                    <div>
                        <h6><i class="bi bi-kanban-fill me-2"></i>{{ __('Work in progress by tab') }}</h6>
                        <div class="ed-card-sub">{{ __('Click a bar to open that tab') }}</div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap small">
                        <span class="ed-legend"><i style="background:var(--ed-c1)"></i>{{ __('Workflow') }}</span>
                        <span class="ed-legend"><i style="background:var(--ed-c2)"></i>{{ __('Registration Resolution') }}</span>
                        <span class="ed-legend"><i style="background:var(--ed-c3)"></i>{{ __('Renewal Resolution') }}</span>
                    </div>
                </div>
                <div class="ed-chart" style="height: {{ max(260, count($dash['pipeline']) * 34) }}px;"><canvas id="edPipeline"></canvas></div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="ed-card h-100">
                <div class="ed-card-head">
                    <div>
                        <h6><i class="bi bi-globe-asia-australia me-2"></i>{{ __('Workforce by nationality') }}</h6>
                        <div class="ed-card-sub">{{ __('Current workforce') }}</div>
                    </div>
                </div>
                <div class="ed-chart ed-donut" style="height: 300px;">
                    <canvas id="edNationality"></canvas>
                    <div class="ed-donut-center"><strong>{{ number_format($workforceCount) }}</strong><span>{{ __('people') }}</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Expiry + top employers ===== --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="ed-card h-100">
                <div class="ed-card-head">
                    <div>
                        <h6><i class="bi bi-calendar2-x-fill me-2"></i>{{ __('Document expiry outlook') }}</h6>
                        <div class="ed-card-sub">{{ __('Current workforce · next 90 days') }}</div>
                    </div>
                </div>
                <div class="ed-chart" style="height: 260px;"><canvas id="edExpiry"></canvas></div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="ed-card h-100">
                <div class="ed-card-head">
                    <div>
                        <h6><i class="bi bi-trophy-fill me-2"></i>{{ __('Top employers by workforce') }}</h6>
                        <div class="ed-card-sub">{{ __('Top 8') }}</div>
                    </div>
                </div>
                <div class="ed-chart" style="height: 260px;"><canvas id="edTop"></canvas></div>
            </div>
        </div>
    </div>

    {{-- ===== Activity ===== --}}
    <div class="row g-4">
        <div class="col-xl-7">
            <div class="ed-card h-100">
                <div class="ed-card-head">
                    <div>
                        <h6><i class="bi bi-activity me-2"></i>{{ __('System Activity (Last 14 Days)') }}</h6>
                        <div class="ed-card-sub">{{ __('Actions recorded by all users') }}</div>
                    </div>
                    <div class="ed-pill">{{ __('Total') }} <strong>{{ number_format(array_sum($activityCounts)) }}</strong></div>
                </div>
                <div class="ed-chart" style="height: 260px;"><canvas id="edActivity"></canvas></div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="ed-card h-100">
                <div class="ed-card-head">
                    <h6><i class="bi bi-clock-history me-2"></i>{{ __('Recent Activities') }}</h6>
                    <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-sm btn-link text-decoration-none">{{ __('View All') }}</a>
                </div>
                <div class="ed-timeline">
                    @forelse($recentActivities as $log)
                        <div class="ed-tl-item">
                            <span class="ed-tl-dot"></span>
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate">{{ $log->description }}</div>
                                <div class="small text-body-secondary"><i class="bi bi-person-circle me-1"></i>{{ $log->user->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-body-secondary">{{ __('No recent activity.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .exec-dash {
        --ed-c1: var(--bs-primary);
        --ed-c2: #10b981;
        --ed-c3: #8b5cf6;
        --ed-c4: #f59e0b;
        --ed-c5: #ef4444;
        --ed-c6: #06b6d4;
        --ed-radius: 1.1rem;
    }
    .exec-dash .ed-hero {
        position: relative; overflow: hidden; border-radius: 1.4rem; padding: 1.75rem 1.75rem 1.25rem; color: #fff;
        background: radial-gradient(120% 140% at 0% 0%, rgba(255,255,255,.18) 0%, transparent 45%),
                    linear-gradient(120deg, var(--bs-primary) 0%, color-mix(in srgb, var(--bs-primary) 55%, #1e1b4b) 100%);
        box-shadow: 0 18px 40px -18px color-mix(in srgb, var(--bs-primary) 70%, transparent);
    }
    .exec-dash .ed-hero-shapes span { position: absolute; border-radius: 50%; background: rgba(255,255,255,.08); }
    .exec-dash .ed-hero-shapes span:nth-child(1) { width: 260px; height: 260px; right: -60px; top: -110px; }
    .exec-dash .ed-hero-shapes span:nth-child(2) { width: 140px; height: 140px; right: 180px; bottom: -70px; background: rgba(255,255,255,.06); }
    .exec-dash .ed-hero-shapes span:nth-child(3) { width: 70px; height: 70px; left: 45%; top: 18px; background: rgba(255,255,255,.05); }
    .exec-dash .ed-hero-kicker { font-size: .78rem; letter-spacing: .08em; text-transform: uppercase; opacity: .85; }
    .exec-dash .ed-hero-title { font-size: clamp(1.4rem, 2.6vw, 2.1rem); font-weight: 800; }
    .exec-dash .ed-hero-sub { opacity: .85; font-size: .92rem; }
    .exec-dash .ed-hero-date { font-weight: 700; font-size: 1.05rem; }
    .exec-dash .ed-hero-time { font-size: .8rem; opacity: .8; }
    .exec-dash .ed-hero-strip { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
    .exec-dash .ed-hero-strip > div { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.16); border-radius: .9rem; padding: .6rem .8rem; font-size: .8rem; backdrop-filter: blur(4px); }
    .exec-dash .ed-hero-strip span { display: block; font-size: 1.35rem; font-weight: 800; line-height: 1.2; }
    @media (max-width: 767.98px) { .exec-dash .ed-hero-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); } .exec-dash .ed-hero { padding: 1.25rem 1rem 1rem; } }

    .exec-dash .ed-kpis { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(175px, 1fr)); }
    @media (max-width: 575.98px) { .exec-dash .ed-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; } }
    .exec-dash .ed-kpi {
        --tone: var(--bs-primary);
        position: relative; overflow: hidden; border-radius: var(--ed-radius); padding: 1rem 1rem .75rem; cursor: default;
        background: var(--bs-body-bg); border: 1px solid var(--bs-border-color-translucent);
        box-shadow: 0 6px 20px -12px rgba(15, 23, 42, .25); transition: transform .18s ease, box-shadow .18s ease;
    }
    .exec-dash .ed-kpi::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 4px; background: var(--tone); }
    .exec-dash .ed-kpi:hover, .exec-dash .ed-kpi:focus { transform: translateY(-3px); box-shadow: 0 14px 30px -14px color-mix(in srgb, var(--tone) 55%, transparent); outline: none; }
    .exec-dash .ed-tone-primary { --tone: var(--bs-primary); }
    .exec-dash .ed-tone-violet { --tone: #8b5cf6; }
    .exec-dash .ed-tone-amber { --tone: #f59e0b; }
    .exec-dash .ed-tone-emerald { --tone: #10b981; }
    .exec-dash .ed-tone-teal { --tone: #0d9488; }
    .exec-dash .ed-tone-rose { --tone: #e11d48; }
    .exec-dash .ed-tone-orange { --tone: #ea580c; }
    .exec-dash .ed-kpi-icon {
        display: inline-flex; align-items: center; justify-content: center; width: 2.4rem; height: 2.4rem; border-radius: .8rem; font-size: 1.15rem;
        color: var(--tone); background: color-mix(in srgb, var(--tone) 14%, transparent);
    }
    .exec-dash .ed-kpi-label { margin-top: .6rem; font-size: .82rem; color: var(--bs-secondary-color); font-weight: 600; }
    .exec-dash .ed-kpi-value { font-size: clamp(1.35rem, 2.1vw, 1.85rem); font-weight: 800; letter-spacing: -.01em; color: var(--bs-emphasis-color); white-space: nowrap; }
    .exec-dash .ed-kpi-sub { font-size: .76rem; color: var(--bs-secondary-color); }
    .exec-dash .ed-kpi-info { position: absolute; right: .7rem; bottom: .55rem; font-size: .8rem; color: var(--bs-secondary-color); opacity: .6; }
    .exec-dash .ed-spark { height: 38px; margin-top: .35rem; }
    .exec-dash .ed-delta { font-size: .72rem; font-weight: 700; border-radius: 999px; padding: .15rem .5rem; }
    .exec-dash .ed-delta.up { color: #059669; background: rgba(16,185,129,.14); }
    .exec-dash .ed-delta.down { color: #dc2626; background: rgba(239,68,68,.12); }

    .exec-dash .ed-card {
        border-radius: var(--ed-radius); padding: 1.1rem 1.2rem; background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color-translucent); box-shadow: 0 6px 20px -14px rgba(15, 23, 42, .3);
    }
    .exec-dash .ed-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; flex-wrap: wrap; margin-bottom: .75rem; }
    .exec-dash .ed-card-head h6 { font-weight: 800; margin: 0; }
    .exec-dash .ed-card-sub { font-size: .76rem; color: var(--bs-secondary-color); }
    .exec-dash .ed-pill { font-size: .78rem; border-radius: 999px; padding: .25rem .7rem; background: var(--bs-tertiary-bg); color: var(--bs-secondary-color); white-space: nowrap; }
    .exec-dash .ed-pill strong { color: var(--bs-emphasis-color); margin-left: .25rem; }
    .exec-dash .ed-legend { display: inline-flex; align-items: center; gap: .35rem; color: var(--bs-secondary-color); }
    .exec-dash .ed-legend i { width: .65rem; height: .65rem; border-radius: .2rem; display: inline-block; }
    .exec-dash .ed-chart { position: relative; width: 100%; }
    .exec-dash .ed-donut-center { position: absolute; left: 0; right: 0; top: 50%; transform: translateY(-58%); text-align: center; pointer-events: none; }
    .exec-dash .ed-donut-center strong { display: block; font-size: 1.8rem; font-weight: 800; color: var(--bs-emphasis-color); line-height: 1; }
    .exec-dash .ed-donut-center span { font-size: .78rem; color: var(--bs-secondary-color); }
    .exec-dash .ed-timeline { position: relative; padding-left: .35rem; }
    .exec-dash .ed-tl-item { display: flex; gap: .75rem; padding: .55rem 0; border-bottom: 1px dashed var(--bs-border-color-translucent); }
    .exec-dash .ed-tl-item:last-child { border-bottom: 0; }
    .exec-dash .ed-tl-dot { flex: 0 0 auto; width: .6rem; height: .6rem; margin-top: .45rem; border-radius: 50%; background: var(--bs-primary); box-shadow: 0 0 0 4px color-mix(in srgb, var(--bs-primary) 18%, transparent); }
    .exec-dash .min-w-0 { min-width: 0; }

    /* hover detail (popover) */
    .ed-popover { --bs-popover-max-width: 320px; --bs-popover-border-radius: .9rem; box-shadow: 0 18px 40px -16px rgba(15,23,42,.45); }
    .ed-popover .popover-body { padding: .8rem .95rem; }
    .ed-detail-title { font-weight: 800; margin-bottom: .35rem; }
    .ed-detail-row { display: flex; justify-content: space-between; gap: 1rem; font-size: .85rem; padding: .18rem 0; border-bottom: 1px dashed var(--bs-border-color-translucent); }
    .ed-detail-row:last-of-type { border-bottom: 0; }
    .ed-detail-row strong { white-space: nowrap; }
    .ed-detail-note { font-size: .72rem; color: var(--bs-secondary-color); margin-top: .45rem; }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const D = @json($dash);
    const T = D.t;
    const root = document.querySelector('.exec-dash');
    const css = (v) => getComputedStyle(root).getPropertyValue(v).trim();
    const C = () => ({
        c1: css('--ed-c1') || '#0d6efd', c2: css('--ed-c2'), c3: css('--ed-c3'), c4: css('--ed-c4'), c5: css('--ed-c5'), c6: css('--ed-c6'),
        text: css('--bs-secondary-color') || '#6c757d', grid: css('--bs-border-color-translucent') || 'rgba(0,0,0,.08)',
        body: css('--bs-body-bg') || '#fff', strong: css('--bs-emphasis-color') || '#000',
    });
    const fmt = (n) => Number(n || 0).toLocaleString();
    const alpha = (hex, a) => {
        const el = document.createElement('div'); el.style.color = hex; document.body.appendChild(el);
        const rgb = getComputedStyle(el).color.match(/\d+/g) || [13, 110, 253]; el.remove();
        return `rgba(${rgb[0]}, ${rgb[1]}, ${rgb[2]}, ${a})`;
    };
    const gradient = (ctx, color, h = 300) => {
        const g = ctx.createLinearGradient(0, 0, 0, h);
        g.addColorStop(0, alpha(color, .35)); g.addColorStop(1, alpha(color, 0));
        return g;
    };

    // --- count-up numbers ---
    document.querySelectorAll('[data-countup]').forEach(el => {
        const target = parseFloat(el.dataset.countup) || 0;
        const show = (v) => el.textContent = Math.round(v).toLocaleString();
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return show(target);
        const t0 = performance.now(), dur = 1100;
        const step = (t) => { const p = Math.min(1, (t - t0) / dur); show(target * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(step); };
        requestAnimationFrame(step);
    });

    // --- hover details on KPI cards ---
    document.querySelectorAll('[data-ed-detail]').forEach(card => {
        const tpl = document.getElementById(card.dataset.edDetail);
        if (!tpl || !window.bootstrap) return;
        new bootstrap.Popover(card, {
            trigger: 'hover focus', html: true, placement: 'bottom', customClass: 'ed-popover',
            content: () => tpl.innerHTML, sanitize: true,
        });
    });

    if (!window.Chart) return;
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    const charts = [];
    const tooltip = () => ({
        backgroundColor: 'rgba(15, 23, 42, .92)', titleColor: '#fff', bodyColor: '#e2e8f0', padding: 12, cornerRadius: 10,
        boxPadding: 6, usePointStyle: true, titleFont: { weight: '700' },
    });

    function build() {
        charts.splice(0).forEach(c => c.destroy());
        const c = C();
        Chart.defaults.color = c.text;
        Chart.defaults.borderColor = c.grid;

        // sparklines
        const sparkData = { headcount: D.headcount, output: D.months.map((_, i) => D.output.workflow[i] + D.output.registration[i] + D.output.renewal[i]).slice(-6) };
        document.querySelectorAll('canvas[data-spark]').forEach(cv => {
            const tone = getComputedStyle(cv.closest('.ed-kpi')).getPropertyValue('--tone').trim() || c.c1;
            const data = sparkData[cv.dataset.spark] || [];
            charts.push(new Chart(cv, {
                type: 'line',
                data: { labels: data.map((_, i) => D.months[D.months.length - data.length + i]), datasets: [{ data, borderColor: tone, borderWidth: 2, tension: .4, pointRadius: 0, fill: true, backgroundColor: gradient(cv.getContext('2d'), tone, 40) }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { ...tooltip(), callbacks: { label: (x) => fmt(x.parsed.y) } } },
                    scales: { x: { display: false }, y: { display: false, beginAtZero: true } }, interaction: { intersect: false, mode: 'index' } },
            }));
        });

        // work finished per month (stacked)
        const out = document.getElementById('edOutput');
        if (out) charts.push(new Chart(out, {
            type: 'bar',
            data: { labels: D.months, datasets: [
                { label: T.workflow, data: D.output.workflow, backgroundColor: c.c1, borderRadius: 6, maxBarThickness: 34 },
                { label: T.registration, data: D.output.registration, backgroundColor: c.c2, borderRadius: 6, maxBarThickness: 34 },
                { label: T.renewal, data: D.output.renewal, backgroundColor: c.c3, borderRadius: 6, maxBarThickness: 34 },
            ] },
            options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'rectRounded' } },
                    tooltip: { ...tooltip(), callbacks: { label: (x) => ` ${x.dataset.label}: ${fmt(x.parsed.y)} ${T.people}`,
                        footer: (items) => `${T.total}: ${fmt(items.reduce((s, x) => s + x.parsed.y, 0))} ${T.people}` } } },
                scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } } },
        }));

        // new employees vs notified out
        const mov = document.getElementById('edMovement');
        if (mov) charts.push(new Chart(mov, {
            type: 'line',
            data: { labels: D.months, datasets: [
                { label: T.hired, data: D.movement.hired, borderColor: c.c2, backgroundColor: gradient(mov.getContext('2d'), c.c2, 320), fill: true, borderWidth: 3, tension: .35, pointRadius: 3, pointHoverRadius: 7, pointBackgroundColor: c.body, pointBorderWidth: 2 },
                { label: T.left, data: D.movement.left, borderColor: c.c5, backgroundColor: 'transparent', borderDash: [6, 4], borderWidth: 2, tension: .35, pointRadius: 3, pointHoverRadius: 7, pointBackgroundColor: c.body, pointBorderWidth: 2 },
            ] },
            options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } },
                    tooltip: { ...tooltip(), callbacks: { label: (x) => ` ${x.dataset.label}: ${fmt(x.parsed.y)} ${T.people}`,
                        footer: (items) => { const n = (items[0]?.parsed.y || 0) - (items[1]?.parsed.y || 0); return `${T.net}: ${n >= 0 ? '+' : ''}${fmt(n)}`; } } } },
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } } },
        }));

        // pipeline per tab
        const pipe = document.getElementById('edPipeline');
        if (pipe) {
            const color = (g) => g === 'workflow' ? c.c1 : g === 'registration' ? c.c2 : c.c3;
            charts.push(new Chart(pipe, {
                type: 'bar',
                data: { labels: D.pipeline.map(p => p.name), datasets: [{ data: D.pipeline.map(p => p.count), backgroundColor: D.pipeline.map(p => alpha(color(p.group), .85)),
                    hoverBackgroundColor: D.pipeline.map(p => color(p.group)), borderRadius: 8, barThickness: 18 }] },
                options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    onHover: (e, el) => { e.native.target.style.cursor = el.length && D.pipeline[el[0].index].url ? 'pointer' : 'default'; },
                    onClick: (e, el) => { if (el.length && D.pipeline[el[0].index].url) window.location = D.pipeline[el[0].index].url; },
                    plugins: { legend: { display: false }, tooltip: { ...tooltip(), callbacks: {
                        title: (x) => x[0].label,
                        label: (x) => ` ${T[D.pipeline[x.dataIndex].group]}: ${fmt(x.parsed.x)} ${T.people}`,
                        footer: (x) => D.pipeline[x[0].dataIndex].url ? '↗ ' + T.clickToOpen : '' } } },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } } },
            }));
        }

        // nationality donut
        const nat = document.getElementById('edNationality');
        if (nat) {
            const total = D.nationalities.reduce((s, n) => s + n.count, 0);
            charts.push(new Chart(nat, {
                type: 'doughnut',
                data: { labels: D.nationalities.map(n => n.label), datasets: [{ data: D.nationalities.map(n => n.count),
                    backgroundColor: [c.c1, c.c2, c.c3, c.c4, c.c6, c.c5, '#94a3b8'], borderColor: c.body, borderWidth: 3, hoverOffset: 10 }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '68%',
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 14 } },
                        tooltip: { ...tooltip(), callbacks: { label: (x) => ` ${x.label}: ${fmt(x.parsed)} ${T.people} (${total ? Math.round(x.parsed / total * 100) : 0}%)` } } } },
            }));
        }

        // expiry outlook (stacked horizontal)
        const exp = document.getElementById('edExpiry');
        if (exp) charts.push(new Chart(exp, {
            type: 'bar',
            data: { labels: D.expiry.map(e => e.label), datasets: [
                { label: T.expired, data: D.expiry.map(e => e.expired), backgroundColor: c.c5, borderRadius: 6 },
                { label: T.d30, data: D.expiry.map(e => e.d30), backgroundColor: '#f97316', borderRadius: 6 },
                { label: T.d60, data: D.expiry.map(e => e.d60), backgroundColor: c.c4, borderRadius: 6 },
                { label: T.d90, data: D.expiry.map(e => e.d90), backgroundColor: c.c6, borderRadius: 6 },
            ] },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'rectRounded' } },
                    tooltip: { ...tooltip(), callbacks: { label: (x) => ` ${x.dataset.label}: ${fmt(x.parsed.x)} ${T.people}` } } },
                scales: { x: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }, y: { stacked: true, grid: { display: false } } } },
        }));

        // top employers
        const top = document.getElementById('edTop');
        if (top) {
            const ctx = top.getContext('2d');
            const g = ctx.createLinearGradient(0, 0, top.parentElement.clientWidth || 600, 0);
            g.addColorStop(0, alpha(c.c3, .9)); g.addColorStop(1, alpha(c.c1, .9));
            charts.push(new Chart(top, {
                type: 'bar',
                data: { labels: D.topEmployers.map(e => e.label.length > 34 ? e.label.slice(0, 33) + '…' : e.label),
                    datasets: [{ data: D.topEmployers.map(e => e.count), backgroundColor: g, borderRadius: 8, barThickness: 16 }] },
                options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { ...tooltip(), callbacks: {
                        title: (x) => D.topEmployers[x[0].dataIndex].label,
                        label: (x) => ` ${T.headcount}: ${fmt(x.parsed.x)} ${T.people}` } } },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } } },
            }));
        }

        // activity
        const act = document.getElementById('edActivity');
        if (act) charts.push(new Chart(act, {
            type: 'line',
            data: { labels: D.activity.labels, datasets: [{ label: T.actions, data: D.activity.counts, borderColor: c.c1, borderWidth: 3, tension: .35, fill: true,
                backgroundColor: gradient(act.getContext('2d'), c.c1, 260), pointRadius: 0, pointHoverRadius: 6, pointHoverBackgroundColor: c.c1 }] },
            options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false }, tooltip: { ...tooltip(), callbacks: { label: (x) => ` ${T.actions}: ${fmt(x.parsed.y)}` } } },
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } } },
        }));
    }
    build();
    // light / dark switch → redraw with the new colours
    new MutationObserver(() => build()).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
});
</script>
@endpush
