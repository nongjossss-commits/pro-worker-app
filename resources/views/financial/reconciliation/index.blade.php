@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.index') }}" class="text-decoration-none small">&larr; {{ __('Finance Hub') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">{{ __('Bank Reconciliation — กระทบยอด') }}</h1>
            <div class="small text-muted mt-1">
                {{ __('Compares each bank account\'s current_balance against the balance re-derived from every ledger entry. Any non-zero diff means the actual value drifted from what the ledger would compute.') }}
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Bank Account') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th class="text-end">{{ __('Initial') }}</th>
                            <th class="text-end">{{ __('Total Income') }}</th>
                            <th class="text-end">{{ __('Total Expense') }}</th>
                            <th class="text-end">{{ __('Expected') }}</th>
                            <th class="text-end">{{ __('Actual') }}</th>
                            <th class="text-end">{{ __('Diff') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary as $row)
                            @php $hasDiff = abs($row['diff']) > 0.005; @endphp
                            <tr class="{{ $hasDiff ? 'table-warning' : '' }}">
                                <td>
                                    <div class="fw-bold">{{ $row['account']->bank_name }}</div>
                                    <div class="small text-muted">{{ $row['account']->account_name ?: $row['account']->account_number ?: '—' }}</div>
                                </td>
                                <td>
                                    @if($row['account']->account_type === 'personal')
                                        <span class="badge bg-secondary">{{ __('Personal') }}</span>
                                    @else
                                        <span class="badge bg-primary">{{ __('Company') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($row['initial_balance'], 2) }}</td>
                                <td class="text-end text-success">+{{ number_format($row['total_income'], 2) }}</td>
                                <td class="text-end text-danger">-{{ number_format($row['total_expense'], 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($row['expected_balance'], 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($row['actual_balance'], 2) }}</td>
                                <td class="text-end fw-bold {{ $hasDiff ? 'text-danger' : 'text-muted' }}">
                                    @if($hasDiff)
                                        {{ ($row['diff'] >= 0 ? '+' : '') . number_format($row['diff'], 2) }}
                                    @else
                                        0.00 <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('finance.reconciliation.account', $row['account']) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> {{ __('Detail') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">{{ __('No bank accounts yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-3 mb-0">
        <i class="bi bi-info-circle"></i>
        {{ __('Diff = Actual − Expected. A small rounding diff (< 0.01) is normal. If a row is highlighted, open the Detail page to walk through the running balance and find where the drift started.') }}
    </div>
</div>
@endsection
