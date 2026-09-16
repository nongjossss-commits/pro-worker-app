@extends('labor.layout')

@section('title', 'Dashboard - Pro Walker Labour')

@section('content')

@if(in_array($mode, ['overview', 'overview-plus-own-team']))
{{-- ================= ALL-TEAMS OVERVIEW ================= --}}
@php
    $overviewBilled = $teams->sum('billed');
    $overviewPaid = $teams->sum('paid');
    $overviewOutstanding = $teams->sum('outstanding');
@endphp
<h5 class="fw-bold mb-3"><i class="bi bi-globe me-2"></i>{{ __('All Teams Overview') }}</h5>
<div class="row mb-3 g-3">
    <div class="col-md-3">
        <div class="card stat-card shadow-sm border-0 text-dark" style="background-color: #fd7e14;">
            <div class="card-body">
                <div class="small text-uppercase fw-bold opacity-75">{{ __('Total Billed') }}</div>
                <div class="fs-2 fw-bold">{{ number_format($overviewBilled, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm border-0">
            <div class="card-body">
                <div class="text-success small text-uppercase fw-bold">{{ __('Total Paid') }}</div>
                <div class="fs-2 fw-bold text-success">{{ number_format($overviewPaid, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm border-0">
            <div class="card-body">
                <div class="text-warning small text-uppercase fw-bold">{{ __('Total Outstanding') }}</div>
                <div class="fs-2 fw-bold text-warning">{{ number_format($overviewOutstanding, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <a href="{{ route('labor.books.index') }}" class="text-decoration-none">
            <div class="card stat-card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold"><i class="bi bi-journal-text me-1"></i>{{ __('Company Books') }}</div>
                    <div class="fs-2 fw-bold {{ $booksBalance < 0 ? 'text-danger' : '' }}">{{ number_format($booksBalance, 2) }}</div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-fill me-2"></i>{{ __('Billed vs. Paid by Team') }}</h6>
    </div>
    <div class="card-body">
        <div style="position: relative; height: 320px; width: 100%;">
            <canvas id="overviewChart"></canvas>
        </div>
    </div>
</div>

@if($chargeTypeStats->isNotEmpty())
<div class="card stat-card shadow-sm border-0 mb-4 text-dark" style="background-color: #fd7e14;">
    <div class="card-body text-center py-4">
        <div class="small text-uppercase fw-bold mb-1 opacity-75"><i class="bi bi-people-fill me-1"></i>{{ __('Total Headcount (All Charge Types)') }}</div>
        <div class="display-4 fw-bold">{{ number_format($chargeTypeGrandTotal) }}</div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-steps me-2"></i>{{ __('Headcount by Charge Type & Nationality') }}</h6>
    </div>
    <div class="card-body">
        <div style="position: relative; height: 340px; width: 100%;">
            <canvas id="chargeTypeNationalityChart"></canvas>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
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
@endif

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">{{ __('Teams Summary') }}</h6>
        @can('manage-labor-ledger')
        <a href="{{ route('labor.teams.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-gear me-1"></i>{{ __('Manage Teams') }}
        </a>
        @endcan
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
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
                <tr>
                    <td>{{ $t['team']->name }}</td>
                    <td class="text-end">{{ number_format($t['billed'], 2) }}</td>
                    <td class="text-end text-success">{{ number_format($t['paid'], 2) }}</td>
                    <td class="text-end fw-bold {{ $t['outstanding'] > 0 ? 'text-warning' : 'text-success' }}">
                        {{ number_format($t['outstanding'], 2) }}
                    </td>
                    <td class="text-end">
                        <a href="{{ route('labor.teams.show', $t['team']) }}" class="btn btn-sm btn-outline-primary">
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

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>{{ __('Recent Activity (All Teams)') }}</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Team') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th class="text-end">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentActivity as $item)
                <tr>
                    <td class="text-nowrap">{{ optional($item['date'])->format('d/m/Y') }}</td>
                    <td><a href="{{ $item['link'] }}">{{ $item['team'] }}</a></td>
                    <td>
                        @if($item['type'] === 'bill')
                            <span class="badge bg-primary">{{ $item['label'] }}</span>
                        @elseif($item['type'] === 'payment')
                            <span class="badge bg-success">{{ $item['label'] }}</span>
                        @else
                            <span class="badge bg-warning text-dark">{{ $item['label'] }}</span>
                        @endif
                    </td>
                    <td>{{ $item['description'] }}</td>
                    <td class="text-end">{{ number_format(abs($item['amount']), 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">{{ __('No activity yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

@if(in_array($mode, ['own-team-only', 'overview-plus-own-team']))
{{-- ================= OWN TEAM ================= --}}
<div class="d-flex justify-content-between align-items-center mb-3 {{ $mode === 'overview-plus-own-team' ? 'mt-2' : '' }}">
    <h5 class="fw-bold mb-0">
        <i class="bi bi-people-fill me-2"></i>{{ __('My Team') }}: {{ $ownTeam['team']->name }}
    </h5>
    @if(auth()->user()->laborTeamMember)
        <a href="{{ route('labor.my-name.edit') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i>{{ __('Edit My Name') }}
        </a>
    @endif
</div>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="row g-3 mb-3">
            <div class="col-6">
                <div class="card stat-card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-bold">{{ __('Billed') }}</div>
                        <div class="fs-4 fw-bold">{{ number_format($ownTeam['billed'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card stat-card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-success small text-uppercase fw-bold">{{ __('Paid') }}</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format($ownTeam['paid'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card stat-card shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-warning small text-uppercase fw-bold">{{ __('Outstanding') }}</div>
                        <div class="fs-4 fw-bold text-warning">{{ number_format($ownTeam['outstanding'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-pie-chart-fill me-2"></i>{{ __('Paid vs. Outstanding') }}</h6>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 220px; width: 100%;">
                    <canvas id="ownTeamChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-person-vcard me-2"></i>{{ __('Team Members') }}</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ownTeam['members'] as $member)
                        <tr>
                            <td>{{ $member->name }}</td>
                            <td class="text-center">
                                @if($member->is_active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted py-4">{{ __('No members registered yet.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mt-3">
    <div class="card-header bg-white py-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>{{ __('Recent Activity') }}</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th class="text-end">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ownTeam['recentActivity'] as $item)
                <tr>
                    <td class="text-nowrap">{{ optional($item['date'])->format('d/m/Y') }}</td>
                    <td>
                        @if($item['type'] === 'bill')
                            <span class="badge bg-primary">{{ $item['label'] }}</span>
                        @elseif($item['type'] === 'payment')
                            <span class="badge bg-success">{{ $item['label'] }}</span>
                        @else
                            <span class="badge bg-warning text-dark">{{ $item['label'] }}</span>
                        @endif
                    </td>
                    <td>{{ $item['description'] }}</td>
                    <td class="text-end">{{ number_format(abs($item['amount']), 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">{{ __('No activity yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

@if($mode === 'own-member-only')
{{-- ================= MY OWN DATA (labor-member) ================= --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">
        <i class="bi bi-person-fill me-2"></i>{{ __('My Data') }}: {{ $ownMember['member']->name }}
    </h5>
    @if(auth()->user()->laborTeamMember)
        <a href="{{ route('labor.my-name.edit') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i>{{ __('Edit My Name') }}
        </a>
    @endif
</div>
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card stat-card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">{{ __('Billed') }}</div>
                <div class="fs-4 fw-bold">{{ number_format($ownMember['billed'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-success small text-uppercase fw-bold">{{ __('Paid') }}</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($ownMember['paid'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-warning small text-uppercase fw-bold">{{ __('Outstanding') }}</div>
                <div class="fs-4 fw-bold text-warning">{{ number_format($ownMember['outstanding'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>{{ __('Recent Activity') }}</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th class="text-end">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ownMember['recentActivity'] as $item)
                <tr>
                    <td class="text-nowrap">{{ optional($item['date'])->format('d/m/Y') }}</td>
                    <td>
                        @if($item['type'] === 'payment')
                            <span class="badge bg-success">{{ $item['label'] }}</span>
                        @else
                            <span class="badge bg-warning text-dark">{{ $item['label'] }}</span>
                        @endif
                    </td>
                    <td>{{ $item['description'] }}</td>
                    <td class="text-end">{{ number_format(abs($item['amount']), 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">{{ __('No activity yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if(in_array($mode, ['overview', 'overview-plus-own-team']))
    const overviewCtx = document.getElementById('overviewChart').getContext('2d');
    new Chart(overviewCtx, {
        type: 'bar',
        data: {
            labels: @json($teams->pluck('team.name')),
            datasets: [
                {
                    label: '{{ __('Paid') }}',
                    data: @json($teams->pluck('paid')),
                    backgroundColor: '#198754',
                    stack: 'total',
                },
                {
                    label: '{{ __('Outstanding') }}',
                    data: @json($teams->pluck('outstanding')),
                    backgroundColor: '#ffc107',
                    stack: 'total',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { stacked: true, grid: { display: false } },
                y: { stacked: true, beginAtZero: true, grid: { borderDash: [2, 4] } },
            },
            plugins: {
                legend: { position: 'bottom' },
            },
        },
    });
    @endif

    @if($chargeTypeStats->isNotEmpty())
    const chargeTypeCtx = document.getElementById('chargeTypeNationalityChart').getContext('2d');
    new Chart(chargeTypeCtx, {
        type: 'bar',
        data: {
            labels: @json($chargeTypeStats->pluck('type.name')),
            // Grouped (not stacked) on purpose: stacking squeezes a small
            // nationality's count into a sliver a few pixels tall inside a
            // much bigger total, making it unreadable and basically
            // unclickable. Each bar getting its own full-height column (plus
            // minBarLength so even a count of 1 stays visibly clickable)
            // keeps every value legible regardless of how the totals compare
            // across charge types.
            datasets: [
                { label: 'ลาว', data: @json($chargeTypeStats->pluck('laos')), backgroundColor: '#2a78d6', minBarLength: 4 },
                { label: 'เมียนมา', data: @json($chargeTypeStats->pluck('myanmar')), backgroundColor: '#eb6834', minBarLength: 4 },
                { label: 'กัมพูชา', data: @json($chargeTypeStats->pluck('cambodia')), backgroundColor: '#1baf7a', minBarLength: 4 },
                { label: 'เวียดนาม', data: @json($chargeTypeStats->pluck('vietnam')), backgroundColor: '#eda100', minBarLength: 4 },
                { label: '{{ __('Unspecified') }}', data: @json($chargeTypeStats->pluck('unspecified')), backgroundColor: '#898781', minBarLength: 4 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { borderDash: [2, 4] } },
            },
            plugins: {
                legend: { position: 'bottom' },
            },
        },
    });
    @endif

    @if(in_array($mode, ['own-team-only', 'overview-plus-own-team']))
    const ownTeamCtx = document.getElementById('ownTeamChart').getContext('2d');
    new Chart(ownTeamCtx, {
        type: 'doughnut',
        data: {
            labels: ['{{ __('Paid') }}', '{{ __('Outstanding') }}'],
            datasets: [{
                data: [{{ $ownTeam['paid'] }}, {{ $ownTeam['outstanding'] }}],
                backgroundColor: ['#198754', '#ffc107'],
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom' },
            },
        },
    });
    @endif
});
</script>
@endpush
