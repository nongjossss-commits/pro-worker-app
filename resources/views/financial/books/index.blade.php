@extends('layouts.app')

@section('title', __('Income & Expense Books'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0">{{ __('Income & Expense Books') }} (บันทึกรายรับรายจ่าย)</h4>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('finance.income-categories.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-tags me-1"></i>{{ __('Income Categories') }}
            </a>
            <a href="{{ route('finance.expense-categories.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-tags me-1"></i>{{ __('Expense Categories') }}
            </a>
            <a href="{{ route('finance.bank-accounts.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-bank me-1"></i>{{ __('Bank Accounts') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php $reportTabActive = request('tab') === 'report'; @endphp
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <button class="nav-link {{ !$reportTabActive ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#accountsTabPane" type="button">
                <i class="bi bi-bank me-1"></i>{{ __('Accounts') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ $reportTabActive ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#reportTabPane" type="button">
                <i class="bi bi-file-earmark-bar-graph me-1"></i>{{ __('Reports') }}
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade {{ !$reportTabActive ? 'show active' : '' }}" id="accountsTabPane">
            <div class="row mb-3 g-3">
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm border-0 bg-dark text-white">
                        <div class="card-body">
                            <div class="text-white-50 small text-uppercase fw-bold">{{ __('Total Balance (All Accounts)') }}</div>
                            <div class="fs-2 fw-bold">{{ number_format($totalBalance, 2) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm border-0">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase fw-bold">{{ __('Accounts') }}</div>
                            <div class="fs-2 fw-bold">{{ $accounts->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Account') }}</th>
                                <th>{{ __('Bank') }}</th>
                                <th class="text-end">{{ __('Balance') }}</th>
                                <th class="text-center">{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $acc)
                            <tr>
                                <td>{{ $acc->account_name ?: $acc->bank_name }}</td>
                                <td class="text-muted small">
                                    {{ $acc->bank_name }}
                                    @if($acc->account_number) — {{ $acc->account_number }} @endif
                                </td>
                                <td class="text-end fw-bold {{ $acc->current_balance < 0 ? 'text-danger' : '' }}">
                                    {{ number_format($acc->current_balance, 2) }}
                                </td>
                                <td class="text-center">
                                    @if($acc->is_active)
                                        <span class="badge bg-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('finance.books.show', $acc) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>{{ __('View') }}
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">{{ __('No book accounts yet.') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade {{ $reportTabActive ? 'show active' : '' }}" id="reportTabPane">
            @php
                $accounts = $reportAccounts;
                $categoryTransactions = $reportCategoryTransactions;
                $from = $reportFrom;
                $to = $reportTo;
                $reportFormAction = route('finance.books.index');
                $isEmbeddedTab = true;
            @endphp
            @include('financial.books-reports._content')
        </div>
    </div>

    @include('financial.books._quick_entry')
</div>
@endsection
