@extends('labor.layout')

@section('title', 'Company Books - Pro Walker Labour')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">{{ __('Company Books') }} (สมุดบัญชี)</h4>
    <div>
        @role('super-admin')
        <button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#manageExpenseCategoriesModal">
            <i class="bi bi-tags me-1"></i>{{ __('Manage Expense Categories') }}
        </button>
        @endrole
        @can('manage-labor-ledger')
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Add Account') }}
        </button>
        @endcan
    </div>
</div>

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
                @forelse($accounts as $account)
                <tr>
                    <td>{{ $account->name }}</td>
                    <td class="text-muted small">
                        {{ $account->bank_name }}
                        @if($account->account_number) — {{ $account->account_number }} @endif
                    </td>
                    <td class="text-end fw-bold {{ $account->computed_balance < 0 ? 'text-danger' : '' }}">
                        {{ number_format($account->computed_balance, 2) }}
                    </td>
                    <td class="text-center">
                        @if($account->is_active)
                            <span class="badge bg-success">{{ __('Active') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('labor.books.show', $account) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>{{ __('View') }}
                        </a>
                        @can('manage-labor-ledger')
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editAccountModal{{ $account->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @endcan
                    </td>
                </tr>
                @can('manage-labor-ledger')
                <div class="modal fade" id="editAccountModal{{ $account->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('labor.books.update', $account) }}">
                            @csrf @method('PUT')
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Edit Account') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Account Name') }} *</label>
                                        <input type="text" name="name" class="form-control" value="{{ $account->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Bank') }}</label>
                                        <input type="text" name="bank_name" class="form-control" value="{{ $account->bank_name }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Account Number') }}</label>
                                        <input type="text" name="account_number" class="form-control" value="{{ $account->account_number }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Account Holder Name') }}</label>
                                        <input type="text" name="account_name" class="form-control" value="{{ $account->account_name }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Opening Balance') }}</label>
                                        <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ $account->opening_balance }}">
                                    </div>
                                    <div class="form-check mb-3">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" class="form-check-input" name="is_active" value="1" id="active{{ $account->id }}" {{ $account->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label" for="active{{ $account->id }}">{{ __('Active') }}</label>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label">{{ __('Notes') }}</label>
                                        <textarea name="notes" class="form-control" rows="2">{{ $account->notes }}</textarea>
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
                @endcan
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">{{ __('No book accounts yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 mt-4">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0 fw-bold">{{ __('Category Breakdown') }} — {{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</h6>
            <form method="GET" class="d-flex gap-2 align-items-end">
                <div>
                    <label class="form-label small mb-0">{{ __('From') }}</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $from->format('Y-m-d') }}">
                </div>
                <div>
                    <label class="form-label small mb-0">{{ __('To') }}</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $to->format('Y-m-d') }}">
                </div>
                <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Apply') }}</button>
            </form>
        </div>
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

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold">{{ __('Team Summary') }} — {{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</h6>
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

@can('manage-labor-ledger')
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('labor.books.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Book Account') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Account Name') }} *</label>
                        <input type="text" name="name" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Bank') }}</label>
                        <input type="text" name="bank_name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Account Number') }}</label>
                        <input type="text" name="account_number" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Account Holder Name') }}</label>
                        <input type="text" name="account_name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Opening Balance') }}</label>
                        <input type="number" step="0.01" name="opening_balance" class="form-control" value="0">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
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

@role('super-admin')
{{-- Manage Expense Categories modal --}}
<div class="modal fade" id="manageExpenseCategoriesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Manage Expense Categories') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Note') }}</th>
                            <th class="text-center">{{ __('Tax Deductible') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenseCategories ?? [] as $expCategory)
                        <tr>
                            <td>{{ $expCategory->name }}</td>
                            <td class="small text-muted">{{ $expCategory->note ?: '-' }}</td>
                            <td class="text-center">
                                @if($expCategory->is_tax_deductible)
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($expCategory->is_active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#editExpenseCategoryModal{{ $expCategory->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">{{ __('No expense categories yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <hr>

                <form method="POST" action="{{ route('labor.expense-categories.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-6">
                        <label class="form-label small">{{ __('New Expense Category Name') }}</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" class="form-check-input" name="is_tax_deductible" value="1" id="newExpCatDeductible">
                            <label class="form-check-label small" for="newExpCatDeductible">{{ __('Tax Deductible') }}</label>
                        </div>
                    </div>
                    <div class="col-3">
                        <button type="submit" class="btn btn-primary w-100">{{ __('Add') }}</button>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">{{ __('Note (what this category covers)') }}</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="{{ __('e.g. General office running costs — utilities, stationery, cleaning') }}"></textarea>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@foreach($expenseCategories ?? [] as $expCategory)
<div class="modal fade" id="editExpenseCategoryModal{{ $expCategory->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('labor.expense-categories.update', $expCategory) }}">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Expense Category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Name') }}</label>
                        <input type="text" name="name" class="form-control" value="{{ $expCategory->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Note (what this category covers)') }}</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="{{ __('e.g. General office running costs — utilities, stationery, cleaning') }}">{{ $expCategory->note }}</textarea>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_tax_deductible" value="1"
                               id="expCatDeductible{{ $expCategory->id }}" {{ $expCategory->is_tax_deductible ? 'checked' : '' }}>
                        <label class="form-check-label" for="expCatDeductible{{ $expCategory->id }}">{{ __('Tax Deductible') }}</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               id="expCatActive{{ $expCategory->id }}" {{ $expCategory->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="expCatActive{{ $expCategory->id }}">{{ __('Active') }}</label>
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
@endforeach
@endrole
@endsection
