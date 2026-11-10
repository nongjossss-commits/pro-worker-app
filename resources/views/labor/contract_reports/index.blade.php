@extends('labor.layout')

@section('title', __('Contract Statistics'))

@section('content')
@include('labor.contracts._pwc_styles')
@php
    $maxTeam = max(1, (int) collect($byTeam)->max('total'));
    $maxStaff = max(1, (int) collect($byStaff)->max('total'));
@endphp
<div class="container-fluid pwc">
    <div class="pwc-hero">
        <div class="pwc-hero-main">
            <div class="pwc-hero-icon"><i class="bi bi-bar-chart-line"></i></div>
            <div>
                <h4 class="pwc-hero-title">{{ __('Pro Worker Contract Statistics') }}</h4>
                @if(app()->getLocale() !== 'th')<p class="pwc-hero-sub">รายงานสถิติการเบิกสัญญา</p>@endif
            </div>
        </div>
        <div class="pwc-hero-actions">
            <a href="{{ route('labor.contract-reports.export', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>{{ __('Export Excel') }}
            </a>
        </div>
    </div>

    <div class="card pwc-panel pwc-filter mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('labor.contract-reports.index') }}" id="reportFilterForm" class="row g-2 align-items-end">
                <input type="hidden" name="period" id="report-period" value="{{ $activePeriod }}">
                <div class="col-auto">
                    <label class="form-label small">{{ __('Date') }}</label>
                    <input type="date" name="date" id="report-date" class="form-control form-control-sm" value="{{ $activeDate->format('Y-m-d') }}">
                </div>
                <div class="col-auto">
                    <label class="form-label small d-block">&nbsp;</label>
                    <div class="pwc-seg">
                    @foreach(['day' => __('Day'), 'week' => __('Week'), 'month' => __('Month'), 'quarter' => __('Quarter'), 'year' => __('Year')] as $key => $label)
                        <button type="button" class="btn btn-sm {{ $activePeriod === $key ? 'btn-primary' : 'btn-outline-secondary' }}" data-period="{{ $key }}">{{ $label }}</button>
                    @endforeach
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-4">
            <div class="pwc-stat is-ink">
                <i class="bi bi-file-earmark-text pwc-stat-icon"></i>
                <div class="pwc-stat-label">{{ __('Total Contracts') }}</div>
                <div class="pwc-stat-value">{{ number_format($total) }}</div>
                <div class="pwc-stat-sub"><i class="bi bi-calendar3 me-1"></i>{{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="pwc-stat is-brand">
                <i class="bi bi-people-fill pwc-stat-icon"></i>
                <div class="pwc-stat-label">{{ __('Total Worker Count') }}</div>
                <div class="pwc-stat-value">{{ number_format($totalWorkers) }}</div>
                <div class="pwc-stat-sub"><i class="bi bi-calendar3 me-1"></i>{{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card pwc-panel h-100">
                <div class="card-header"><span class="pwc-chip-icon"><i class="bi bi-people-fill"></i></span>{{ __('By Team') }}</div>
                <div class="table-responsive">
                    <table data-mobile-table="scroll" class="table table-hover align-middle mb-0 pwc-table">
                        <thead><tr><th>{{ __('Team') }}</th><th class="text-end">{{ __('Contracts') }}</th><th class="text-end">{{ __('Worker Count') }}</th></tr></thead>
                        <tbody>
                            @forelse($byTeam as $row)
                            <tr>
                                <td><div class="d-flex align-items-center gap-2"><span class="pwc-rank {{ $loop->iteration <= 3 ? 'is-' . $loop->iteration : '' }}">{{ $loop->iteration }}</span><span class="fw-semibold">{{ $row->name }}</span></div></td>
                                <td class="text-end"><span class="fw-bold">{{ $row->total }}</span><div class="pwc-bar ms-auto"><span style="width: {{ round($row->total / $maxTeam * 100, 1) }}%"></span></div></td>
                                <td class="text-end">{{ $row->total_workers }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-4"><i class="bi bi-bar-chart d-block fs-2 mb-1 opacity-50"></i>{{ __('No contracts in this period.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card pwc-panel h-100">
                <div class="card-header"><span class="pwc-chip-icon"><i class="bi bi-person-badge"></i></span>{{ __('By Staff') }}</div>
                <div class="table-responsive">
                    <table data-mobile-table="scroll" class="table table-hover align-middle mb-0 pwc-table">
                        <thead><tr><th>{{ __('Staff') }}</th><th class="text-end">{{ __('Contracts') }}</th><th class="text-end">{{ __('Worker Count') }}</th></tr></thead>
                        <tbody>
                            @forelse($byStaff as $row)
                            <tr>
                                <td><div class="d-flex align-items-center gap-2"><span class="pwc-rank {{ $loop->iteration <= 3 ? 'is-' . $loop->iteration : '' }}">{{ $loop->iteration }}</span><span><span class="fw-semibold">{{ $row->name }}</span> @if($row->staff_code) <span class="text-muted small">({{ $row->staff_code }})</span> @endif</span></div></td>
                                <td class="text-end"><span class="fw-bold">{{ $row->total }}</span><div class="pwc-bar ms-auto"><span style="width: {{ round($row->total / $maxStaff * 100, 1) }}%"></span></div></td>
                                <td class="text-end">{{ $row->total_workers }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-4"><i class="bi bi-bar-chart d-block fs-2 mb-1 opacity-50"></i>{{ __('No contracts in this period.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('reportFilterForm');
    const periodInput = document.getElementById('report-period');
    document.querySelectorAll('[data-period]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            periodInput.value = btn.dataset.period;
            form.submit();
        });
    });
});
</script>
@endpush
@endsection
