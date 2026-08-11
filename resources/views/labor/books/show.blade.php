@extends('labor.layout')

@section('title', $account->name . ' - Pro Walker Labor')

@section('content')
<div class="mb-3">
    <a href="{{ route('labor.books.index') }}" class="text-decoration-none small">&larr; {{ __('Company Books') }}</a>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0">{{ $account->name }}</h4>
        <span class="text-muted">{{ $account->bank_name }} @if($account->account_number) — {{ $account->account_number }} @endif</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('labor.books.export', array_merge(['account' => $account], request()->only(['type', 'category', 'from', 'to']))) }}" class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-excel me-1"></i>{{ __('Export Excel') }}
        </a>
        @can('manage-labor-ledger')
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Add Transaction') }}
        </button>
        @endcan
    </div>
</div>

<div class="row mb-3 g-3">
    <div class="col-md-4">
        <div class="card stat-card shadow-sm border-0 bg-dark text-white">
            <div class="card-body">
                <div class="text-white-50 small text-uppercase fw-bold">{{ __('Current Balance') }}</div>
                <div class="fs-2 fw-bold">{{ number_format($balance, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card shadow-sm border-0">
            <div class="card-body">
                <div class="text-success small text-uppercase fw-bold">{{ __('Total Income') }}</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($incomeTotal, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card shadow-sm border-0">
            <div class="card-body">
                <div class="text-danger small text-uppercase fw-bold">{{ __('Total Expense') }}</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($expenseTotal, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-3" role="button" data-bs-toggle="collapse" data-bs-target="#reconcilePanel">
        <h6 class="fw-bold mb-0"><i class="bi bi-check2-square me-2"></i>{{ __('Reconcile with Bank Statement') }} <i class="bi bi-chevron-down small text-muted"></i></h6>
    </div>
    <div class="collapse {{ $reconciliation ? 'show' : '' }}" id="reconcilePanel">
        <div class="card-body">
            <p class="text-muted small mb-3">{{ __('Compare what the books say the balance was on a given date against your bank statement — a mismatch usually means a transaction is missing here.') }}</p>
            <form method="GET" class="row g-2 align-items-end mb-3">
                @foreach(request()->except(['reconcile_date', 'statement_balance']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <div class="col-md-3">
                    <label class="form-label small">{{ __('As of Date') }}</label>
                    <input type="date" name="reconcile_date" class="form-control" value="{{ request('reconcile_date', now()->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ __('Statement Balance') }}</label>
                    <input type="number" step="0.01" name="statement_balance" class="form-control" value="{{ request('statement_balance') }}" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">{{ __('Compare') }}</button>
                </div>
            </form>

            @if($reconciliation)
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="small text-uppercase text-muted fw-bold">{{ __('Books Balance as of') }} {{ $reconciliation['as_of']->format('d/m/Y') }}</div>
                    <div class="fs-5 fw-bold">{{ number_format($reconciliation['expected'], 2) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-uppercase text-muted fw-bold">{{ __('Statement Balance') }}</div>
                    <div class="fs-5 fw-bold">{{ number_format($reconciliation['statement'], 2) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-uppercase text-muted fw-bold">{{ __('Difference') }}</div>
                    <div class="fs-5 fw-bold {{ abs($reconciliation['diff']) > 0.005 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($reconciliation['diff'], 2) }}
                        @if(abs($reconciliation['diff']) <= 0.005)
                            <i class="bi bi-check-circle-fill"></i>
                        @else
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small">{{ __('From') }}</label>
                <input type="date" name="from" class="form-control" value="{{ request('from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('To') }}</label>
                <input type="date" name="to" class="form-control" value="{{ request('to') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('Type') }}</label>
                <select name="type" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>{{ __('Income') }}</option>
                    <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>{{ __('Expense') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('Category') }}</label>
                <select name="category" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach(\App\Http\Controllers\Labor\LaborBookController::CATEGORIES as $group => $opts)
                        <optgroup label="{{ ucfirst($group) }}">
                            @foreach($opts as $key => $label)
                                <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-primary w-100">{{ __('Apply') }}</button>
            </div>
            @if(request('type') || request('category') || request('from') || request('to'))
            <div class="col-md-2">
                <a href="{{ route('labor.books.show', $account) }}" class="btn btn-outline-secondary w-100">{{ __('Clear') }}</a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Category') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th class="text-end">{{ __('Amount') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                <tr>
                    <td class="text-nowrap">{{ $t->transaction_date->format('d/m/Y') }}</td>
                    <td>
                        @if($t->type === 'income')
                            <span class="badge bg-success">{{ __('Income') }}</span>
                        @else
                            <span class="badge bg-danger">{{ __('Expense') }}</span>
                        @endif
                        @if($t->isAutoGenerated())
                            <span class="badge bg-secondary" title="{{ __('Auto-generated') }}"><i class="bi bi-robot"></i></span>
                        @endif
                    </td>
                    <td class="small text-muted">
                        {{ \App\Http\Controllers\Labor\LaborBookController::CATEGORIES[$t->type][$t->category] ?? $t->category ?? '-' }}
                    </td>
                    <td>{{ $t->description }}</td>
                    <td class="text-end fw-bold {{ $t->type === 'income' ? 'text-success' : 'text-danger' }}">
                        {{ $t->type === 'income' ? '+' : '-' }}{{ number_format($t->amount, 2) }}
                    </td>
                    <td class="text-end">
                        @can('manage-labor-ledger')
                            @if(!$t->isAutoGenerated())
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editTxnModal{{ $t->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('labor.books.transactions.destroy', [$account, $t]) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete this transaction?') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        @endcan
                    </td>
                </tr>
                @can('manage-labor-ledger')
                @if(!$t->isAutoGenerated())
                <div class="modal fade" id="editTxnModal{{ $t->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('labor.books.transactions.update', [$account, $t]) }}">
                            @csrf @method('PUT')
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Edit Transaction') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Type') }} *</label>
                                        <select name="type" class="form-select" required>
                                            <option value="income" {{ $t->type === 'income' ? 'selected' : '' }}>{{ __('Income') }}</option>
                                            <option value="expense" {{ $t->type === 'expense' ? 'selected' : '' }}>{{ __('Expense') }}</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Category') }}</label>
                                        <select name="category" class="form-select">
                                            <option value="">-- {{ __('None') }} --</option>
                                            @foreach(\App\Http\Controllers\Labor\LaborBookController::CATEGORIES as $group => $opts)
                                                <optgroup label="{{ ucfirst($group) }}">
                                                    @foreach($opts as $key => $label)
                                                        <option value="{{ $key }}" {{ $t->category === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Amount') }} *</label>
                                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ $t->amount }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Date') }} *</label>
                                        <input type="date" name="transaction_date" class="form-control" value="{{ $t->transaction_date->format('Y-m-d') }}" required>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label">{{ __('Description') }} *</label>
                                        <input type="text" name="description" class="form-control" value="{{ $t->description }}" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
                @endcan
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">{{ __('No transactions yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
    <div class="card-footer bg-white">{{ $transactions->links() }}</div>
    @endif
</div>

@can('manage-labor-ledger')
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('labor.books.transactions.store', $account) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Transaction') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type') }} *</label>
                        <select name="type" class="form-select" required>
                            <option value="income">{{ __('Income') }}</option>
                            <option value="expense">{{ __('Expense') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="category" class="form-select">
                            <option value="">-- {{ __('None') }} --</option>
                            @foreach(\App\Http\Controllers\Labor\LaborBookController::CATEGORIES as $group => $opts)
                                <optgroup label="{{ ucfirst($group) }}">
                                    @foreach($opts as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Amount') }} *</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Date') }} *</label>
                        <input type="date" name="transaction_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">{{ __('Description') }} *</label>
                        <input type="text" name="description" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Add') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection
