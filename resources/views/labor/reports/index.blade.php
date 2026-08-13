@extends('labor.layout')

@section('title', 'Reports - Pro Walker Labor')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">{{ __('Reports') }}</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('labor.reports.pdf', ['period' => $activePeriod, 'date' => $activeDate->format('Y-m-d')]) }}" target="_blank" class="btn btn-outline-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>{{ __('Export PDF') }}
        </a>
        <a href="{{ route('labor.reports.export', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-excel me-1"></i>{{ __('Export Excel') }}
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('labor.reports.index') }}" id="reportFilterForm" class="row g-2 align-items-end">
            <input type="hidden" name="period" id="report-period" value="{{ $activePeriod }}">
            <div class="col-auto">
                <label class="form-label small">{{ __('Date') }}</label>
                <input type="text" name="date" id="report-date" class="form-control form-control-sm js-flatpickr" value="{{ $activeDate->format('Y-m-d') }}" autocomplete="off">
            </div>
            <div class="col-auto">
                <label class="form-label small d-block">&nbsp;</label>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-nav="prev" title="{{ __('Previous period') }}"><i class="bi bi-chevron-left"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-nav="next" title="{{ __('Next period') }}"><i class="bi bi-chevron-right"></i></button>
                </div>
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

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold">{{ __('Per Team') }} ({{ __('Central Billing Ledger') }}) — {{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</h6>
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

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold">{{ __('Team Summary') }} ({{ __('Company Books') }}) — {{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Team') }}</th>
                    <th class="text-end">{{ __('Received (this period)') }}</th>
                    <th class="text-end">{{ __('Total Billed') }}</th>
                    <th class="text-end">{{ __('Total Paid') }}</th>
                    <th class="text-end">{{ __('Outstanding') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teamSummary as $row)
                <tr>
                    <td>{{ $row->team->name }}</td>
                    <td class="text-end">{{ number_format($row->received_in_range, 2) }}</td>
                    <td class="text-end">{{ number_format($row->total_due, 2) }}</td>
                    <td class="text-end">{{ number_format($row->total_paid, 2) }}</td>
                    <td class="text-end fw-bold {{ $row->balance_due > 0 ? 'text-danger' : '' }}">{{ number_format($row->balance_due, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">{{ __('No team activity in this period.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-7 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold">{{ __('Category Breakdown') }} — {{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th class="text-end">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categorySummary as $row)
                        <tr>
                            <td>
                                @if($row->type === 'income')
                                    <span class="badge bg-success">{{ __('Income') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ __('Expense') }}</span>
                                @endif
                            </td>
                            <td>{{ $row->label }}</td>
                            <td class="text-end">{{ number_format($row->total, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">{{ __('No transactions in this period.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold">{{ __('Account Balances') }} ({{ __('as of now') }})</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Account') }}</th>
                            <th class="text-end">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                        <tr>
                            <td>{{ $account->name }}</td>
                            <td class="text-end fw-bold {{ $account->computed_balance < 0 ? 'text-danger' : '' }}">{{ number_format($account->computed_balance, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted py-4">{{ __('No book accounts yet.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td>{{ __('Total') }}</td>
                            <td class="text-end">{{ number_format($accounts->sum('computed_balance'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('reportFilterForm');
    const periodInput = document.getElementById('report-period');
    const dateInput = document.getElementById('report-date');

    document.querySelectorAll('[data-period]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            periodInput.value = btn.dataset.period;
            form.submit();
        });
    });

    document.querySelectorAll('[data-nav]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const period = periodInput.value;
            const d = new Date(dateInput.value + 'T00:00:00');
            const dir = btn.dataset.nav === 'prev' ? -1 : 1;

            switch (period) {
                case 'day':
                    d.setDate(d.getDate() + dir);
                    break;
                case 'week':
                    d.setDate(d.getDate() + (dir * 7));
                    break;
                case 'month':
                    d.setMonth(d.getMonth() + dir);
                    break;
                case 'quarter':
                    d.setMonth(d.getMonth() + (dir * 3));
                    break;
                case 'year':
                    d.setFullYear(d.getFullYear() + dir);
                    break;
            }

            const fmt = (x) => x.toISOString().slice(0, 10);
            dateInput.value = fmt(d);
            if (dateInput._flatpickr) {
                dateInput._flatpickr.setDate(d, false);
            }
            form.submit();
        });
    });
});
</script>
@endpush
@endsection
