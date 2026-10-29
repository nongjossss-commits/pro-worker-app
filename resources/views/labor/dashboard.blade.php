@extends('labor.layout')

@section('title', 'Dashboard - Pro Walker Labour')

@section('content')
{{--
    Same data and the same per-position visibility as before (see
    LaborDashboardController::index — overview / overview-plus-own-team /
    own-team-only / own-member-only); only the look follows the main
    program's executive dashboard: hero, KPI cards, charts, timeline.
--}}
@php
    $money = fn ($v) => number_format($v, 2);
    $rate = fn ($paid, $billed) => $billed > 0 ? min(100, round($paid / $billed * 100)) : null;
    $heroDate = now()->locale(app()->getLocale())->translatedFormat('l j F') . ' ' . (app()->getLocale() === 'th' ? now()->year + 543 : now()->year);
@endphp
<div class="labor-dash">

@if(in_array($mode, ['overview', 'overview-plus-own-team']))
{{-- ================= ALL-TEAMS OVERVIEW ================= --}}
@php
    $overviewBilled = $teams->sum('billed');
    $overviewPaid = $teams->sum('paid');
    $overviewOutstanding = $teams->sum('outstanding');
    $overviewRate = $rate($overviewPaid, $overviewBilled);
    $activeMembers = $teams->sum(fn ($t) => $t['members']->where('is_active', true)->count());
    $teamsOwing = $teams->filter(fn ($t) => $t['outstanding'] > 0.004)->count();
    $natTotals = [
        ['label' => 'ลาว', 'count' => $chargeTypeStats->sum('laos'), 'color' => '#2a78d6'],
        ['label' => 'เมียนมา', 'count' => $chargeTypeStats->sum('myanmar'), 'color' => '#eb6834'],
        ['label' => 'กัมพูชา', 'count' => $chargeTypeStats->sum('cambodia'), 'color' => '#1baf7a'],
        ['label' => 'เวียดนาม', 'count' => $chargeTypeStats->sum('vietnam'), 'color' => '#eda100'],
        ['label' => __('Unspecified'), 'count' => $chargeTypeStats->sum('unspecified'), 'color' => '#898781'],
    ];
@endphp

<div class="ld-hero mb-4">
    <div class="ld-hero-shapes" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="position-relative d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <div class="ld-hero-kicker"><i class="bi bi-stars me-1"></i>{{ __('Executive Overview') }}</div>
            <h1 class="ld-hero-title mb-1"><i class="bi bi-globe me-2"></i>{{ __('All Teams Overview') }}</h1>
            <div class="ld-hero-sub">{{ __('Billing, payments and headcount of every team at a glance.') }}</div>
        </div>
        <div class="text-end">
            <div class="ld-hero-date">{{ $heroDate }}</div>
            <div class="ld-hero-time"><i class="bi bi-arrow-repeat me-1"></i>{{ __('Updated') }} {{ now()->format('H:i') }}</div>
        </div>
    </div>
    <div class="position-relative ld-hero-strip mt-3">
        <div><span>{{ number_format($teams->count()) }}</span>{{ __('Teams') }}</div>
        <div><span>{{ number_format($activeMembers) }}</span>{{ __('Active members') }}</div>
        <div><span>{{ number_format($chargeTypeGrandTotal) }}</span>{{ __('Headcount') }}</div>
        <div><span>{{ is_null($overviewRate) ? '-' : $overviewRate . '%' }}</span>{{ __('Collection rate') }}</div>
    </div>
</div>

<div class="ld-kpis mb-4">
    <div class="ld-kpi ld-tone-orange">
        <span class="ld-kpi-icon"><i class="bi bi-receipt-cutoff"></i></span>
        <div class="ld-kpi-label">{{ __('Total Billed') }}</div>
        <div class="ld-kpi-value">{{ $money($overviewBilled) }}</div>
        <div class="ld-kpi-sub">{{ __(':n teams', ['n' => number_format($teams->count())]) }}</div>
    </div>
    <div class="ld-kpi ld-tone-emerald">
        <span class="ld-kpi-icon"><i class="bi bi-cash-stack"></i></span>
        <div class="ld-kpi-label">{{ __('Total Paid') }}</div>
        <div class="ld-kpi-value">{{ $money($overviewPaid) }}</div>
        <div class="ld-kpi-sub">{{ is_null($overviewRate) ? '-' : __(':pct% of billed', ['pct' => $overviewRate]) }}</div>
        @unless(is_null($overviewRate))<div class="ld-progress"><span style="width: {{ $overviewRate }}%;"></span></div>@endunless
    </div>
    <div class="ld-kpi ld-tone-amber">
        <span class="ld-kpi-icon"><i class="bi bi-hourglass-split"></i></span>
        <div class="ld-kpi-label">{{ __('Total Outstanding') }}</div>
        <div class="ld-kpi-value">{{ $money($overviewOutstanding) }}</div>
        <div class="ld-kpi-sub">{{ __(':n teams still owe', ['n' => number_format($teamsOwing)]) }}</div>
    </div>
    <a href="{{ route('labor.books.index') }}" class="ld-kpi ld-tone-violet text-decoration-none">
        <span class="ld-kpi-icon"><i class="bi bi-journal-text"></i></span>
        <div class="ld-kpi-label">{{ __('Company Books') }}</div>
        <div class="ld-kpi-value {{ $booksBalance < 0 ? 'text-danger' : '' }}">{{ $money($booksBalance) }}</div>
        <div class="ld-kpi-sub">{{ __('Balance of every active account') }} <i class="bi bi-arrow-right-short"></i></div>
    </a>
    @if($chargeTypeStats->isNotEmpty())
    <div class="ld-kpi ld-tone-primary">
        <span class="ld-kpi-icon"><i class="bi bi-people-fill"></i></span>
        <div class="ld-kpi-label">{{ __('Total Headcount (All Charge Types)') }}</div>
        <div class="ld-kpi-value">{{ number_format($chargeTypeGrandTotal) }}</div>
        <div class="ld-kpi-sub">{{ __(':n charge types', ['n' => number_format($chargeTypeStats->count())]) }}</div>
    </div>
    @endif
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="ld-card h-100">
            <div class="ld-card-head">
                <div>
                    <h6><i class="bi bi-bar-chart-fill me-2"></i>{{ __('Billed vs. Paid by Team') }}</h6>
                    <div class="ld-card-sub">{{ __('Paid and still outstanding, per team') }}</div>
                </div>
                <div class="ld-pill">{{ __('Total Outstanding') }} <strong>{{ $money($overviewOutstanding) }}</strong></div>
            </div>
            <div class="ld-chart" style="height: 320px;"><canvas id="overviewChart"></canvas></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="ld-card h-100">
            <div class="ld-card-head">
                <div>
                    <h6><i class="bi bi-pie-chart-fill me-2"></i>{{ __('Paid vs. Outstanding') }}</h6>
                    <div class="ld-card-sub">{{ __('All teams') }}</div>
                </div>
            </div>
            <div class="ld-chart" style="height: 260px;">
                <canvas id="overviewDonut"></canvas>
                <div class="ld-donut-center"><strong>{{ is_null($overviewRate) ? '-' : $overviewRate . '%' }}</strong><span>{{ __('Collected') }}</span></div>
            </div>
            <div class="d-flex justify-content-around mt-3 small">
                <span class="ld-legend"><i style="background:#10b981"></i>{{ __('Paid') }} <strong>{{ $money($overviewPaid) }}</strong></span>
                <span class="ld-legend"><i style="background:#f59e0b"></i>{{ __('Outstanding') }} <strong>{{ $money($overviewOutstanding) }}</strong></span>
            </div>
        </div>
    </div>
</div>

@if($chargeTypeStats->isNotEmpty())
<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="ld-card h-100">
            <div class="ld-card-head">
                <div>
                    <h6><i class="bi bi-bar-chart-steps me-2"></i>{{ __('Headcount by Charge Type & Nationality') }}</h6>
                    <div class="ld-card-sub">{{ __('Total Headcount (All Charge Types)') }}: {{ number_format($chargeTypeGrandTotal) }}</div>
                </div>
            </div>
            <div class="ld-chart" style="height: 320px;"><canvas id="chargeTypeNationalityChart"></canvas></div>
            <div class="table-responsive mt-3">
                <table class="table table-sm table-hover align-middle mb-0 ld-table">
                    <thead>
                        <tr>
                            <th>{{ __('Charge Type') }}</th>
                            <th class="text-end">{{ __('Total') }}</th>
                            <th class="text-end">ลาว</th>
                            <th class="text-end">เมียนมา</th>
                            <th class="text-end">กัมพูชา</th>
                            <th class="text-end">เวียดนาม</th>
                            <th class="text-end text-muted">{{ __('Unspecified') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($chargeTypeStats as $stat)
                        <tr>
                            <td>{{ $stat['type']->name }}</td>
                            <td class="text-end fw-bold">{{ number_format($stat['total']) }}</td>
                            <td class="text-end">{{ number_format($stat['laos']) }}</td>
                            <td class="text-end">{{ number_format($stat['myanmar']) }}</td>
                            <td class="text-end">{{ number_format($stat['cambodia']) }}</td>
                            <td class="text-end">{{ number_format($stat['vietnam']) }}</td>
                            <td class="text-end text-muted">{{ $stat['unspecified'] > 0 ? number_format($stat['unspecified']) : '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="ld-card h-100">
            <div class="ld-card-head">
                <div>
                    <h6><i class="bi bi-flag-fill me-2"></i>{{ __('Nationality mix') }}</h6>
                    <div class="ld-card-sub">{{ __('All charge types combined') }}</div>
                </div>
            </div>
            <div class="ld-chart" style="height: 240px;">
                <canvas id="nationalityDonut"></canvas>
                <div class="ld-donut-center"><strong>{{ number_format($chargeTypeGrandTotal) }}</strong><span>{{ __('Headcount') }}</span></div>
            </div>
            <div class="mt-3">
                @foreach($natTotals as $n)
                    @php $share = $chargeTypeGrandTotal > 0 ? round($n['count'] / $chargeTypeGrandTotal * 100) : 0; @endphp
                    <div class="ld-bar-row">
                        <span class="ld-legend"><i style="background: {{ $n['color'] }}"></i>{{ $n['label'] }}</span>
                        <div class="ld-bar"><span style="width: {{ $share }}%; background: {{ $n['color'] }};"></span></div>
                        <strong>{{ number_format($n['count']) }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="ld-card h-100">
            <div class="ld-card-head">
                <div>
                    <h6><i class="bi bi-people me-2"></i>{{ __('Teams Summary') }}</h6>
                    <div class="ld-card-sub">{{ __('The bar shows how much of each team\'s bills has been paid') }}</div>
                </div>
                @can('manage-labor-ledger')
                <a href="{{ route('labor.teams.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-gear me-1"></i>{{ __('Manage Teams') }}
                </a>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 ld-table">
                    <thead>
                        <tr>
                            <th>{{ __('Team') }}</th>
                            <th class="text-end">{{ __('Billed') }}</th>
                            <th class="text-end">{{ __('Paid') }}</th>
                            <th class="text-end">{{ __('Outstanding') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teams as $t)
                        @php $teamRate = $rate($t['paid'], $t['billed']); @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $t['team']->name }}</div>
                                <div class="ld-progress ld-progress-sm" title="{{ is_null($teamRate) ? '-' : __(':pct% of billed', ['pct' => $teamRate]) }}"><span style="width: {{ $teamRate ?? 0 }}%;"></span></div>
                            </td>
                            <td class="text-end">{{ $money($t['billed']) }}</td>
                            <td class="text-end text-success">{{ $money($t['paid']) }}</td>
                            <td class="text-end fw-bold {{ $t['outstanding'] > 0 ? 'text-warning' : 'text-success' }}">{{ $money($t['outstanding']) }}</td>
                            <td class="text-end">
                                <a href="{{ route('labor.teams.show', $t['team']) }}" class="btn btn-sm btn-outline-primary rounded-pill">
                                    <i class="bi bi-eye me-1"></i>{{ __('View') }}
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">{{ __('No teams yet.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="ld-card h-100">
            <div class="ld-card-head">
                <div>
                    <h6><i class="bi bi-clock-history me-2"></i>{{ __('Recent Activity (All Teams)') }}</h6>
                    <div class="ld-card-sub">{{ __('Bills issued, charges and payments') }}</div>
                </div>
            </div>
            @include('labor._dashboard_activity', ['items' => $recentActivity, 'showTeam' => true])
        </div>
    </div>
</div>
@endif

@if(in_array($mode, ['own-team-only', 'overview-plus-own-team']))
{{-- ================= OWN TEAM ================= --}}
@php
    $ownRate = $rate($ownTeam['paid'], $ownTeam['billed']);
    $ownActive = $ownTeam['members']->where('is_active', true)->count();
@endphp
<div class="ld-hero ld-hero-team mb-4 {{ $mode === 'overview-plus-own-team' ? 'mt-2' : '' }}">
    <div class="ld-hero-shapes" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="position-relative d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <div class="ld-hero-kicker"><i class="bi bi-people-fill me-1"></i>{{ __('My Team') }}</div>
            <h1 class="ld-hero-title mb-1">{{ $ownTeam['team']->name }}</h1>
            <div class="ld-hero-sub">{{ __('Your team\'s bills, payments and members.') }}</div>
        </div>
        <div class="text-end">
            @if(auth()->user()->laborTeamMember)
                <a href="{{ route('labor.my-name.edit') }}" class="btn btn-sm btn-light rounded-pill mb-2">
                    <i class="bi bi-pencil me-1"></i>{{ __('Edit My Name') }}
                </a>
            @endif
            @if($mode === 'own-team-only')
                <div class="ld-hero-date">{{ $heroDate }}</div>
            @endif
        </div>
    </div>
    <div class="position-relative ld-hero-strip mt-3">
        <div><span>{{ number_format($ownTeam['members']->count()) }}</span>{{ __('Members') }}</div>
        <div><span>{{ number_format($ownActive) }}</span>{{ __('Active members') }}</div>
        <div><span>{{ is_null($ownRate) ? '-' : $ownRate . '%' }}</span>{{ __('Collection rate') }}</div>
        <div><span>{{ $money($ownTeam['outstanding']) }}</span>{{ __('Outstanding') }}</div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="ld-kpis ld-kpis-stack mb-4">
            <div class="ld-kpi ld-tone-orange">
                <span class="ld-kpi-icon"><i class="bi bi-receipt-cutoff"></i></span>
                <div class="ld-kpi-label">{{ __('Billed') }}</div>
                <div class="ld-kpi-value">{{ $money($ownTeam['billed']) }}</div>
            </div>
            <div class="ld-kpi ld-tone-emerald">
                <span class="ld-kpi-icon"><i class="bi bi-cash-stack"></i></span>
                <div class="ld-kpi-label">{{ __('Paid') }}</div>
                <div class="ld-kpi-value">{{ $money($ownTeam['paid']) }}</div>
                @unless(is_null($ownRate))<div class="ld-progress"><span style="width: {{ $ownRate }}%;"></span></div>@endunless
            </div>
            <div class="ld-kpi ld-tone-amber ld-kpi-wide">
                <span class="ld-kpi-icon"><i class="bi bi-hourglass-split"></i></span>
                <div class="ld-kpi-label">{{ __('Outstanding') }}</div>
                <div class="ld-kpi-value">{{ $money($ownTeam['outstanding']) }}</div>
            </div>
        </div>
        <div class="ld-card">
            <div class="ld-card-head">
                <h6><i class="bi bi-pie-chart-fill me-2"></i>{{ __('Paid vs. Outstanding') }}</h6>
            </div>
            <div class="ld-chart" style="height: 220px;">
                <canvas id="ownTeamChart"></canvas>
                <div class="ld-donut-center"><strong>{{ is_null($ownRate) ? '-' : $ownRate . '%' }}</strong><span>{{ __('Collected') }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="ld-card h-100">
            <div class="ld-card-head">
                <div>
                    <h6><i class="bi bi-person-vcard me-2"></i>{{ __('Team Members') }}</h6>
                    <div class="ld-card-sub">{{ number_format($ownActive) }} / {{ number_format($ownTeam['members']->count()) }} {{ __('Active') }}</div>
                </div>
            </div>
            <div class="ld-members">
                @forelse($ownTeam['members'] as $member)
                    <div class="ld-member {{ $member->is_active ? '' : 'is-inactive' }}">
                        <span class="ld-avatar">{{ mb_substr(trim($member->name), 0, 1) }}</span>
                        <span class="ld-member-name text-truncate" title="{{ $member->name }}">{{ $member->name }}</span>
                        @if($member->is_active)
                            <span class="badge bg-success-subtle text-success-emphasis rounded-pill">{{ __('Active') }}</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">{{ __('Inactive') }}</span>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted py-4" style="grid-column: 1 / -1;"><i class="bi bi-person-plus fs-3 d-block mb-1"></i>{{ __('No members registered yet.') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="ld-card mb-4">
    <div class="ld-card-head">
        <h6><i class="bi bi-clock-history me-2"></i>{{ __('Recent Activity') }}</h6>
    </div>
    @include('labor._dashboard_activity', ['items' => $ownTeam['recentActivity'], 'showTeam' => false])
</div>
@endif

@if($mode === 'own-member-only')
{{-- ================= MY OWN DATA (labor-member) ================= --}}
@php $memberRate = $rate($ownMember['paid'], $ownMember['billed']); @endphp
<div class="ld-hero mb-4">
    <div class="ld-hero-shapes" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="position-relative d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <div class="ld-hero-kicker"><i class="bi bi-person-fill me-1"></i>{{ __('My Data') }}</div>
            <h1 class="ld-hero-title mb-1">{{ $ownMember['member']->name }}</h1>
        </div>
        <div class="text-end">
            @if(auth()->user()->laborTeamMember)
                <a href="{{ route('labor.my-name.edit') }}" class="btn btn-sm btn-light rounded-pill mb-2">
                    <i class="bi bi-pencil me-1"></i>{{ __('Edit My Name') }}
                </a>
            @endif
            <div class="ld-hero-date">{{ $heroDate }}</div>
        </div>
    </div>
</div>

<div class="ld-kpis mb-4">
    <div class="ld-kpi ld-tone-orange">
        <span class="ld-kpi-icon"><i class="bi bi-receipt-cutoff"></i></span>
        <div class="ld-kpi-label">{{ __('Billed') }}</div>
        <div class="ld-kpi-value">{{ $money($ownMember['billed']) }}</div>
    </div>
    <div class="ld-kpi ld-tone-emerald">
        <span class="ld-kpi-icon"><i class="bi bi-cash-stack"></i></span>
        <div class="ld-kpi-label">{{ __('Paid') }}</div>
        <div class="ld-kpi-value">{{ $money($ownMember['paid']) }}</div>
        <div class="ld-kpi-sub">{{ is_null($memberRate) ? '-' : __(':pct% of billed', ['pct' => $memberRate]) }}</div>
        @unless(is_null($memberRate))<div class="ld-progress"><span style="width: {{ $memberRate }}%;"></span></div>@endunless
    </div>
    <div class="ld-kpi ld-tone-amber">
        <span class="ld-kpi-icon"><i class="bi bi-hourglass-split"></i></span>
        <div class="ld-kpi-label">{{ __('Outstanding') }}</div>
        <div class="ld-kpi-value">{{ $money($ownMember['outstanding']) }}</div>
    </div>
</div>

<div class="ld-card mb-4">
    <div class="ld-card-head">
        <h6><i class="bi bi-clock-history me-2"></i>{{ __('Recent Activity') }}</h6>
    </div>
    @include('labor._dashboard_activity', ['items' => $ownMember['recentActivity'], 'showTeam' => false])
</div>
@endif

</div>

<style>
    .labor-dash { --ld-radius: 1.1rem; }
    .labor-dash .ld-hero {
        position: relative; overflow: hidden; border-radius: 1.4rem; padding: 1.6rem 1.6rem 1.2rem; color: #fff;
        background: radial-gradient(120% 140% at 0% 0%, rgba(255,255,255,.18) 0%, transparent 45%),
                    linear-gradient(120deg, var(--bs-primary) 0%, color-mix(in srgb, var(--bs-primary) 55%, #1e1b4b) 100%);
        box-shadow: 0 18px 40px -18px color-mix(in srgb, var(--bs-primary) 70%, transparent);
    }
    .labor-dash .ld-hero-team {
        background: radial-gradient(120% 140% at 0% 0%, rgba(255,255,255,.18) 0%, transparent 45%),
                    linear-gradient(120deg, #0f766e 0%, #134e4a 100%);
        box-shadow: 0 18px 40px -18px rgba(15, 118, 110, .6);
    }
    .labor-dash .ld-hero-shapes span { position: absolute; border-radius: 50%; background: rgba(255,255,255,.08); }
    .labor-dash .ld-hero-shapes span:nth-child(1) { width: 260px; height: 260px; right: -60px; top: -110px; }
    .labor-dash .ld-hero-shapes span:nth-child(2) { width: 140px; height: 140px; right: 180px; bottom: -70px; background: rgba(255,255,255,.06); }
    .labor-dash .ld-hero-shapes span:nth-child(3) { width: 70px; height: 70px; left: 45%; top: 18px; background: rgba(255,255,255,.05); }
    .labor-dash .ld-hero-kicker { font-size: .78rem; letter-spacing: .08em; text-transform: uppercase; opacity: .85; }
    .labor-dash .ld-hero-title { font-size: clamp(1.35rem, 2.4vw, 2rem); font-weight: 800; color: #fff; }
    .labor-dash .ld-hero-sub { opacity: .85; font-size: .92rem; }
    .labor-dash .ld-hero-date { font-weight: 700; font-size: 1.05rem; }
    .labor-dash .ld-hero-time { font-size: .8rem; opacity: .8; }
    .labor-dash .ld-hero-strip { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
    .labor-dash .ld-hero-strip > div { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.16); border-radius: .9rem; padding: .6rem .8rem; font-size: .8rem; }
    .labor-dash .ld-hero-strip span { display: block; font-size: 1.35rem; font-weight: 800; line-height: 1.2; }
    @media (max-width: 767.98px) { .labor-dash .ld-hero-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); } .labor-dash .ld-hero { padding: 1.2rem 1rem 1rem; } }

    .labor-dash .ld-kpis { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); }
    .labor-dash .ld-kpis-stack { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .labor-dash .ld-kpis-stack .ld-kpi-wide { grid-column: 1 / -1; }
    @media (max-width: 575.98px) { .labor-dash .ld-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; } }
    .labor-dash .ld-kpi {
        --tone: var(--bs-primary); display: block; color: inherit;
        position: relative; overflow: hidden; border-radius: var(--ld-radius); padding: 1rem 1rem .85rem;
        background: var(--bs-body-bg); border: 1px solid var(--bs-border-color-translucent);
        box-shadow: 0 6px 20px -12px rgba(15, 23, 42, .25); transition: transform .18s ease, box-shadow .18s ease;
    }
    .labor-dash .ld-kpi::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 4px; background: var(--tone); }
    .labor-dash .ld-kpi:hover { transform: translateY(-3px); box-shadow: 0 14px 30px -14px color-mix(in srgb, var(--tone) 55%, transparent); }
    .labor-dash .ld-tone-primary { --tone: var(--bs-primary); }
    .labor-dash .ld-tone-violet { --tone: #8b5cf6; }
    .labor-dash .ld-tone-amber { --tone: #f59e0b; }
    .labor-dash .ld-tone-emerald { --tone: #10b981; }
    .labor-dash .ld-tone-orange { --tone: #ea580c; }
    .labor-dash .ld-kpi-icon {
        display: inline-flex; align-items: center; justify-content: center; width: 2.4rem; height: 2.4rem; border-radius: .8rem; font-size: 1.15rem;
        color: var(--tone); background: color-mix(in srgb, var(--tone) 14%, transparent);
    }
    .labor-dash .ld-kpi-label { margin-top: .6rem; font-size: .82rem; color: var(--bs-secondary-color); font-weight: 600; }
    .labor-dash .ld-kpi-value { font-size: clamp(1.25rem, 2vw, 1.7rem); font-weight: 800; letter-spacing: -.01em; color: var(--bs-emphasis-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .labor-dash .ld-kpi-sub { font-size: .76rem; color: var(--bs-secondary-color); }
    .labor-dash .ld-progress { height: 6px; border-radius: 999px; background: color-mix(in srgb, var(--bs-secondary-color) 16%, transparent); overflow: hidden; margin-top: .5rem; }
    .labor-dash .ld-progress > span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #34d399, #10b981); }
    .labor-dash .ld-progress-sm { height: 4px; margin-top: .3rem; max-width: 220px; }

    .labor-dash .ld-card {
        border-radius: var(--ld-radius); padding: 1.1rem 1.2rem; background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color-translucent); box-shadow: 0 6px 20px -14px rgba(15, 23, 42, .3);
    }
    .labor-dash .ld-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; flex-wrap: wrap; margin-bottom: .75rem; }
    .labor-dash .ld-card-head h6 { font-weight: 800; margin: 0; }
    .labor-dash .ld-card-sub { font-size: .76rem; color: var(--bs-secondary-color); }
    .labor-dash .ld-pill { font-size: .78rem; border-radius: 999px; padding: .25rem .7rem; background: var(--bs-tertiary-bg); color: var(--bs-secondary-color); white-space: nowrap; }
    .labor-dash .ld-pill strong { color: var(--bs-emphasis-color); margin-left: .25rem; }
    .labor-dash .ld-legend { display: inline-flex; align-items: center; gap: .35rem; color: var(--bs-secondary-color); }
    .labor-dash .ld-legend i { width: .65rem; height: .65rem; border-radius: .2rem; display: inline-block; }
    .labor-dash .ld-legend strong { color: var(--bs-emphasis-color); }
    .labor-dash .ld-chart { position: relative; width: 100%; }
    .labor-dash .ld-donut-center { position: absolute; left: 0; right: 0; top: 50%; transform: translateY(-50%); text-align: center; pointer-events: none; }
    .labor-dash .ld-donut-center strong { display: block; font-size: 1.7rem; font-weight: 800; color: var(--bs-emphasis-color); line-height: 1; }
    .labor-dash .ld-donut-center span { font-size: .78rem; color: var(--bs-secondary-color); }
    .labor-dash .ld-bar-row { display: grid; grid-template-columns: 6.5rem 1fr 3rem; align-items: center; gap: .6rem; font-size: .82rem; padding: .2rem 0; }
    .labor-dash .ld-bar-row strong { text-align: right; }
    .labor-dash .ld-bar { height: 8px; border-radius: 999px; background: color-mix(in srgb, var(--bs-secondary-color) 16%, transparent); overflow: hidden; }
    .labor-dash .ld-bar > span { display: block; height: 100%; border-radius: inherit; }
    .labor-dash .ld-table thead th { font-size: .74rem; text-transform: uppercase; letter-spacing: .03em; color: var(--bs-secondary-color); background: var(--bs-tertiary-bg); border-bottom: 0; }
    .labor-dash .ld-table thead th:first-child { border-radius: .6rem 0 0 .6rem; }
    .labor-dash .ld-table thead th:last-child { border-radius: 0 .6rem .6rem 0; }

    .labor-dash .ld-timeline { max-height: 460px; overflow-y: auto; padding-right: .25rem; }
    .labor-dash .ld-tl-item { display: flex; align-items: center; gap: .75rem; padding: .6rem 0; border-bottom: 1px dashed var(--bs-border-color-translucent); }
    .labor-dash .ld-tl-item:last-child { border-bottom: 0; }
    .labor-dash .ld-tl-icon {
        flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; width: 2.1rem; height: 2.1rem; border-radius: .7rem;
        color: var(--tone); background: color-mix(in srgb, var(--tone) 14%, transparent);
    }
    .labor-dash .ld-tl-date { font-size: .74rem; color: var(--bs-secondary-color); }
    .labor-dash .ld-tl-desc { font-size: .85rem; margin-top: .15rem; }
    .labor-dash .ld-tl-amount { font-weight: 700; white-space: nowrap; color: var(--bs-emphasis-color); }
    .labor-dash .ld-min-w-0 { min-width: 0; }

    .labor-dash .ld-members { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: .6rem; }
    .labor-dash .ld-member { display: flex; align-items: center; gap: .6rem; padding: .55rem .7rem; border-radius: .8rem; border: 1px solid var(--bs-border-color-translucent); background: var(--bs-tertiary-bg); min-width: 0; }
    .labor-dash .ld-member.is-inactive { opacity: .6; }
    .labor-dash .ld-member-name { flex: 1 1 auto; min-width: 0; font-weight: 600; font-size: .88rem; }
    .labor-dash .ld-avatar {
        flex: 0 0 auto; width: 2rem; height: 2rem; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; color: #fff; background: linear-gradient(135deg, #14b8a6, #0f766e);
    }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const css = getComputedStyle(document.documentElement);
    const textColor = css.getPropertyValue('--bs-secondary-color').trim() || '#6c757d';
    const gridColor = css.getPropertyValue('--bs-border-color-translucent').trim() || 'rgba(0,0,0,.1)';
    Chart.defaults.color = textColor;
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    const money = v => Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    // Nothing to show yet (all zero) → a plain grey ring instead of an empty space.
    const donut = (id, labels, data, colors, fmt) => {
        const empty = data.every(v => !Number(v));
        return new Chart(document.getElementById(id), {
            type: 'doughnut',
            data: { labels: empty ? [''] : labels, datasets: [{ data: empty ? [1] : data, backgroundColor: empty ? [gridColor] : colors, borderWidth: 0, hoverOffset: empty ? 0 : 6 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false },
                tooltip: { enabled: !empty, callbacks: { label: c => ' ' + c.label + ': ' + fmt(c.parsed) } } } },
        });
    };

    @if(in_array($mode, ['overview', 'overview-plus-own-team']))
    // Grouped (not stacked): a team with a small Paid or Outstanding value
    // next to a much bigger one would otherwise be squeezed into a sliver.
    new Chart(document.getElementById('overviewChart'), {
        type: 'bar',
        data: {
            labels: @json($teams->pluck('team.name')),
            datasets: [
                { label: @json(__('Paid')), data: @json($teams->pluck('paid')), backgroundColor: '#10b981', borderRadius: 6, maxBarThickness: 34, minBarLength: 4 },
                { label: @json(__('Outstanding')), data: @json($teams->pluck('outstanding')), backgroundColor: '#f59e0b', borderRadius: 6, maxBarThickness: 34, minBarLength: 4 },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: gridColor }, ticks: { callback: v => Number(v).toLocaleString() } } },
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } }, tooltip: { callbacks: { label: c => ' ' + c.dataset.label + ': ' + money(c.parsed.y) } } },
        },
    });
    donut('overviewDonut', [@json(__('Paid')), @json(__('Outstanding'))], [{{ $overviewPaid }}, {{ max(0, $overviewOutstanding) }}], ['#10b981', '#f59e0b'], money);
    @endif

    {{-- $chargeTypeStats is only passed in overview modes (labor-member /
         labor-team dashboards don't get it, nor the chart canvas). --}}
    @if(isset($chargeTypeStats) && $chargeTypeStats->isNotEmpty())
    // Grouped (not stacked) on purpose: a small nationality's count stays legible and clickable.
    new Chart(document.getElementById('chargeTypeNationalityChart'), {
        type: 'bar',
        data: {
            labels: @json($chargeTypeStats->pluck('type.name')),
            datasets: [
                { label: 'ลาว', data: @json($chargeTypeStats->pluck('laos')), backgroundColor: '#2a78d6', borderRadius: 5, minBarLength: 4 },
                { label: 'เมียนมา', data: @json($chargeTypeStats->pluck('myanmar')), backgroundColor: '#eb6834', borderRadius: 5, minBarLength: 4 },
                { label: 'กัมพูชา', data: @json($chargeTypeStats->pluck('cambodia')), backgroundColor: '#1baf7a', borderRadius: 5, minBarLength: 4 },
                { label: 'เวียดนาม', data: @json($chargeTypeStats->pluck('vietnam')), backgroundColor: '#eda100', borderRadius: 5, minBarLength: 4 },
                { label: @json(__('Unspecified')), data: @json($chargeTypeStats->pluck('unspecified')), backgroundColor: '#898781', borderRadius: 5, minBarLength: 4 },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor } } },
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } },
        },
    });
    donut('nationalityDonut', @json(collect($natTotals)->pluck('label')), @json(collect($natTotals)->pluck('count')), @json(collect($natTotals)->pluck('color')), v => Number(v).toLocaleString());
    @endif

    @if(in_array($mode, ['own-team-only', 'overview-plus-own-team']))
    donut('ownTeamChart', [@json(__('Paid')), @json(__('Outstanding'))], [{{ $ownTeam['paid'] }}, {{ max(0, $ownTeam['outstanding']) }}], ['#10b981', '#f59e0b'], money);
    @endif
});
</script>
@endpush
