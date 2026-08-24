@extends('labor.layout')

@section('title', __('Contract Statistics'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">{{ __('Pro Worker Contract Statistics') }} (รายงานสถิติการเบิกสัญญา)</h4>
        <a href="{{ route('labor.contract-reports.export', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i>{{ __('Export Excel') }}
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('labor.contract-reports.index') }}" id="reportFilterForm" class="row g-2 align-items-end">
                <input type="hidden" name="period" id="report-period" value="{{ $activePeriod }}">
                <div class="col-auto">
                    <label class="form-label small">{{ __('Date') }}</label>
                    <input type="date" name="date" id="report-date" class="form-control form-control-sm" value="{{ $activeDate->format('Y-m-d') }}">
                </div>
                <div class="col-auto ms-2 border-start ps-3">
                    <label class="form-label small d-block">&nbsp;</label>
                    @foreach(['day' => __('Day'), 'week' => __('Week'), 'month' => __('Month'), 'quarter' => __('Quarter'), 'year' => __('Year')] as $key => $label)
                        <button type="button" class="btn btn-sm {{ $activePeriod === $key ? 'btn-primary' : 'btn-outline-secondary' }}" data-period="{{ $key }}">{{ $label }}</button>
                    @endforeach
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card shadow-sm border-0 bg-dark text-white">
                <div class="card-body">
                    <div class="text-white-50 small text-uppercase fw-bold">{{ __('Total Contracts') }}</div>
                    <div class="fs-2 fw-bold">{{ $total }}</div>
                    <div class="text-white-50 small">{{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow-sm border-0 bg-primary text-white">
                <div class="card-body">
                    <div class="text-white-50 small text-uppercase fw-bold">{{ __('Total Worker Count') }}</div>
                    <div class="fs-2 fw-bold">{{ $totalWorkers }}</div>
                    <div class="text-white-50 small">{{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">{{ __('By Team') }}</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>{{ __('Team') }}</th><th class="text-end">{{ __('Contracts') }}</th><th class="text-end">{{ __('Worker Count') }}</th></tr></thead>
                        <tbody>
                            @forelse($byTeam as $row)
                            <tr><td>{{ $row->name }}</td><td class="text-end">{{ $row->total }}</td><td class="text-end">{{ $row->total_workers }}</td></tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">{{ __('No contracts in this period.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">{{ __('By Staff') }}</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>{{ __('Staff') }}</th><th class="text-end">{{ __('Contracts') }}</th><th class="text-end">{{ __('Worker Count') }}</th></tr></thead>
                        <tbody>
                            @forelse($byStaff as $row)
                            <tr><td>{{ $row->name }} @if($row->staff_code) <span class="text-muted small">({{ $row->staff_code }})</span> @endif</td><td class="text-end">{{ $row->total }}</td><td class="text-end">{{ $row->total_workers }}</td></tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">{{ __('No contracts in this period.') }}</td></tr>
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
