@extends('layouts.app')

@section('title', ($account->account_name ?: $account->bank_name))

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route('finance.books.index') }}" class="text-decoration-none small">&larr; {{ __('Income & Expense Books') }}</a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">{{ $account->account_name ?: $account->bank_name }}</h4>
            <span class="text-muted">{{ $account->bank_name }} @if($account->account_number) — {{ $account->account_number }} @endif</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('finance.books.export', array_merge(['account' => $account], request()->only(['type', 'category_id', 'category_type', 'from', 'to']))) }}" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-excel me-1"></i>{{ __('Export Excel') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
            <form method="GET" class="row g-2 align-items-end" x-data="{ filterType: '{{ request('type', '') }}' }">
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
                    <select name="type" class="form-select" x-model="filterType">
                        <option value="">{{ __('All') }}</option>
                        <option value="income">{{ __('Income') }}</option>
                        <option value="expense">{{ __('Expense') }}</option>
                    </select>
                </div>
                <div class="col-md-3" x-show="filterType === '' || filterType === 'income'">
                    <label class="form-label small">{{ __('Category (Income)') }}</label>
                    <select name="category_id" class="form-select" :disabled="filterType === 'expense'">
                        <option value="">{{ __('All') }}</option>
                        @foreach($incomeCategories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) request('category_id') === (string) $cat->id && request('category_type') === 'income' ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="category_type" value="income" :disabled="filterType === 'expense'">
                </div>
                <div class="col-md-3" x-show="filterType === 'expense'" x-cloak>
                    <label class="form-label small">{{ __('Category (Expense)') }}</label>
                    <select name="category_id" class="form-select" :disabled="filterType !== 'expense'">
                        <option value="">{{ __('All') }}</option>
                        @foreach($expenseCategories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) request('category_id') === (string) $cat->id && request('category_type') === 'expense' ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="category_type" value="expense" :disabled="filterType !== 'expense'">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-outline-primary w-100">{{ __('Apply') }}</button>
                </div>
                @if(request('type') || request('category_id') || request('from') || request('to'))
                <div class="col-md-2">
                    <a href="{{ route('finance.books.show', $account) }}" class="btn btn-outline-secondary w-100">{{ __('Clear') }}</a>
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
                        <td class="text-nowrap">{{ $t->entry_date->format('d/m/Y') }}</td>
                        <td>
                            @if($t->type === 'income')
                                <span class="badge bg-success">{{ __('Income') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('Expense') }}</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $t->category->name ?? '-' }}</td>
                        <td>
                            {{ $t->description }}
                            @if($t->receipt_path)
                                <a href="{{ Storage::disk('public')->url($t->receipt_path) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill ms-1">
                                    <i class="bi bi-paperclip me-1"></i>{{ __('View attachment') }}
                                </a>
                            @endif
                            @if($t->adjustment_of_id)
                                <a href="{{ route('finance.ledger.show', $t->adjustment_of_id) }}" class="badge bg-info-subtle text-info-emphasis text-decoration-none ms-1" title="{{ __('This entry is a correction — click to view the original.') }}">
                                    <i class="bi bi-arrow-repeat me-1"></i>{{ __('Correction') }}
                                </a>
                            @endif
                        </td>
                        <td class="text-end fw-bold {{ $t->type === 'income' ? 'text-success' : 'text-danger' }}">
                            {{ $t->type === 'income' ? '+' : '-' }}{{ number_format($t->net_amount, 2) }}
                        </td>
                        <td class="text-end">
                            @if(\App\Services\AccountingPeriodService::isOpen($t->entry_date))
                                <a href="{{ route('finance.ledger.show', $t) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('View / Edit (VAT & WHT details)') }}">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('finance.ledger.destroy', $t) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete this transaction?') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            @else
                                <i class="bi bi-lock-fill text-muted" title="{{ __('This day\'s books are closed (locked at 05:00 the next day). Ask a Super Admin to make a correction.') }}"></i>
                                @role('super-admin')
                                    <button type="button" class="btn btn-sm btn-outline-warning ms-1" data-bs-toggle="modal" data-bs-target="#correctEntryModal{{ $t->id }}">
                                        <i class="bi bi-clock-history me-1"></i>{{ __('Correct') }}
                                    </button>
                                @endrole
                            @endif
                        </td>
                    </tr>
                    @role('super-admin')
                    @if(!\App\Services\AccountingPeriodService::isOpen($t->entry_date))
                    <div class="modal fade" id="correctEntryModal{{ $t->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" action="{{ route('finance.books.correct', $t) }}" x-data="{ correctionType: '{{ $t->type }}' }">
                                @csrf
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ __('Correct a Closed-Day Entry') }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="alert alert-warning small">
                                            {{ __('This entry is from :date, which has already been closed. The original will be kept untouched — a reversal and a corrected replacement will be posted today instead.', ['date' => $t->entry_date->format('d/m/Y')]) }}
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('Type') }} *</label>
                                            <select name="type" class="form-select" x-model="correctionType" required>
                                                <option value="income">{{ __('Income') }}</option>
                                                <option value="expense">{{ __('Expense') }}</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('Account') }} *</label>
                                            <select name="bank_account_id" class="form-select" required>
                                                @foreach($activeAccounts as $acc)
                                                    <option value="{{ $acc->id }}" {{ $t->bank_account_id === $acc->id ? 'selected' : '' }}>
                                                        {{ $acc->account_name ?: $acc->bank_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3" x-show="correctionType === 'income'">
                                            <label class="form-label">{{ __('Category (Income)') }} *</label>
                                            <select name="category_id" class="form-select" :disabled="correctionType !== 'income'" :required="correctionType === 'income'">
                                                <option value="">-- {{ __('Select') }} --</option>
                                                @foreach($incomeCategories as $cat)
                                                    <option value="{{ $cat->id }}" {{ $t->category_type === 'income' && $t->category_id === $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3" x-show="correctionType === 'expense'" x-cloak>
                                            <label class="form-label">{{ __('Category (Expense)') }} *</label>
                                            <select name="category_id" class="form-select" :disabled="correctionType !== 'expense'" :required="correctionType === 'expense'">
                                                <option value="">-- {{ __('Select') }} --</option>
                                                @foreach($expenseCategories as $cat)
                                                    <option value="{{ $cat->id }}" {{ $t->category_type === 'expense' && $t->category_id === $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('Corrected Amount') }} *</label>
                                            <input type="number" step="0.01" min="0.01" name="gross_amount" class="form-control" value="{{ $t->gross_amount }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('Description') }} *</label>
                                            <input type="text" name="description" class="form-control" value="{{ $t->description }}" required maxlength="255">
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label">{{ __('Reason for Correction') }} *</label>
                                            <textarea name="reason" class="form-control" rows="2" required placeholder="{{ __('e.g. wrong amount entered, wrong account selected...') }}"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                        <button type="submit" class="btn btn-warning">{{ __('Post Correction') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif
                    @endrole
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

    @include('financial.books._quick_entry')
</div>
@endsection
