@extends('layouts.app')

@section('content')
@php
    $account = $detail['account'];
    $hasDiff = abs($detail['diff']) > 0.005;
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.reconciliation.index') }}" class="text-decoration-none small">&larr; {{ __('Reconciliation') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">
                {{ $account->bank_name }}
                <span class="text-muted small">{{ $account->account_name ?: $account->account_number ?: '' }}</span>
            </h1>
        </div>
        @if($hasDiff)
            <form action="{{ route('finance.reconciliation.repair', $account) }}" method="POST" onsubmit="return confirm('{{ __('Reset current_balance to the expected value re-computed from the ledger? This is logged in the audit trail.') }}')">
                @csrf
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-wrench-adjustable"></i> {{ __('Repair Balance') }}
                </button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-secondary shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">{{ __('Initial Balance') }}</div>
                    <div class="h4 mb-0">{{ number_format($detail['initial_balance'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">{{ __('Expected') }}</div>
                    <div class="h4 mb-0 text-primary">{{ number_format($detail['final_expected'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">{{ __('Actual (current_balance)') }}</div>
                    <div class="h4 mb-0 text-success">{{ number_format($detail['final_actual'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-{{ $hasDiff ? 'danger' : 'muted' }} shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">{{ __('Diff (Actual − Expected)') }}</div>
                    <div class="h4 mb-0 {{ $hasDiff ? 'text-danger' : 'text-muted' }}">
                        @if($hasDiff)
                            {{ ($detail['diff'] >= 0 ? '+' : '') . number_format($detail['diff'], 2) }}
                        @else
                            0.00 ✓
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header">
            <strong>{{ __('Entries — Running Balance') }}</strong>
            <span class="badge bg-secondary-subtle text-dark ms-2">{{ count($detail['rows']) }} {{ __('entries') }}</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Entry #') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Counterparty') }}</th>
                            <th class="text-end">{{ __('Delta') }}</th>
                            <th class="text-end">{{ __('Running Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-light">
                            <td colspan="5" class="text-end fw-bold">{{ __('Initial') }}</td>
                            <td class="text-end fw-bold">{{ number_format($detail['initial_balance'], 2) }}</td>
                        </tr>
                        @forelse($detail['rows'] as $row)
                            <tr>
                                <td>{{ optional($row['entry']->entry_date)->format('d/m/Y') }}</td>
                                <td><a href="{{ route('finance.ledger.show', $row['entry']) }}" class="text-decoration-none">{{ $row['entry']->entry_no }}</a></td>
                                <td>
                                    @if($row['entry']->type === 'income')
                                        <span class="badge bg-success-subtle text-success">Income</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Expense</span>
                                    @endif
                                </td>
                                <td>{{ $row['entry']->counterparty_name ?: '—' }}</td>
                                <td class="text-end {{ $row['delta'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ ($row['delta'] >= 0 ? '+' : '') . number_format($row['delta'], 2) }}
                                </td>
                                <td class="text-end fw-bold">{{ number_format($row['running'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">{{ __('No ledger entries for this account.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
