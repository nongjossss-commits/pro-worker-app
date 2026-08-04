@extends('labor.layout')

@section('title', 'Reports - Pro Walker Labor')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">{{ __('Reports') }}</h4>
    <a href="{{ route('labor.reports.export', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" class="btn btn-outline-secondary">
        <i class="bi bi-file-earmark-excel me-1"></i>{{ __('Export Excel') }}
    </a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('labor.reports.index') }}" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small">{{ __('From') }}</label>
                <input type="date" name="from" id="report-from" class="form-control form-control-sm" value="{{ $from->format('Y-m-d') }}">
            </div>
            <div class="col-auto">
                <label class="form-label small">{{ __('To') }}</label>
                <input type="date" name="to" id="report-to" class="form-control form-control-sm" value="{{ $to->format('Y-m-d') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">{{ __('Apply') }}</button>
            </div>
            <div class="col-auto ms-2 border-start ps-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="today">{{ __('Today') }}</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="week">{{ __('This Week') }}</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="month">{{ __('This Month') }}</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="year">{{ __('This Year') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card shadow-sm border-0 bg-dark text-white">
            <div class="card-body">
                <div class="text-white-50 small text-uppercase fw-bold">{{ __('Opening Balance') }}</div>
                <div class="fs-4 fw-bold">{{ number_format($report['totals']['opening_balance'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">{{ __('Charges This Period') }}</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($report['totals']['charges'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">{{ __('Payments This Period') }}</div>
                <div class="fs-4 fw-bold text-success">{{ number_format(abs($report['totals']['payments']), 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm border-0 bg-dark text-white">
            <div class="card-body">
                <div class="text-white-50 small text-uppercase fw-bold">{{ __('Closing Balance') }}</div>
                <div class="fs-4 fw-bold">{{ number_format($report['totals']['closing_balance'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold">{{ __('Per Team') }} — {{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Team') }}</th>
                    <th class="text-end">{{ __('Opening Balance') }}</th>
                    <th class="text-end">{{ __('Charges') }}</th>
                    <th class="text-end">{{ __('Payments') }}</th>
                    <th class="text-end">{{ __('Closing Balance') }}</th>
                    <th class="text-end">{{ __('Bills Issued') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['rows'] as $r)
                <tr>
                    <td>
                        <a href="{{ route('labor.teams.show', $r['team']) }}">{{ $r['team']->name }}</a>
                    </td>
                    <td class="text-end">{{ number_format($r['opening_balance'], 2) }}</td>
                    <td class="text-end text-danger">{{ number_format($r['charges'], 2) }}</td>
                    <td class="text-end text-success">{{ number_format(abs($r['payments']), 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($r['closing_balance'], 2) }}</td>
                    <td class="text-end">{{ $r['bills_issued'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">{{ __('No active teams.') }}</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="table-light fw-bold">
                    <td>{{ __('Total') }}</td>
                    <td class="text-end">{{ number_format($report['totals']['opening_balance'], 2) }}</td>
                    <td class="text-end">{{ number_format($report['totals']['charges'], 2) }}</td>
                    <td class="text-end">{{ number_format(abs($report['totals']['payments']), 2) }}</td>
                    <td class="text-end">{{ number_format($report['totals']['closing_balance'], 2) }}</td>
                    <td class="text-end">{{ $report['totals']['bills_issued'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fromInput = document.getElementById('report-from');
    const toInput = document.getElementById('report-to');

    document.querySelectorAll('[data-preset]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const today = new Date();
            let from = new Date(today);
            const to = new Date(today);

            switch (btn.dataset.preset) {
                case 'today':
                    break;
                case 'week':
                    from.setDate(today.getDate() - today.getDay());
                    break;
                case 'month':
                    from = new Date(today.getFullYear(), today.getMonth(), 1);
                    break;
                case 'year':
                    from = new Date(today.getFullYear(), 0, 1);
                    break;
            }

            const fmt = (d) => d.toISOString().slice(0, 10);
            fromInput.value = fmt(from);
            toInput.value = fmt(to);
            btn.closest('form').submit();
        });
    });
});
</script>
@endpush
@endsection
