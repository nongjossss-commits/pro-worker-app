@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.index') }}" class="text-decoration-none small">&larr; {{ __('Finance Hub') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">{{ __('Ledger — สมุดบัญชี') }}</h1>
        </div>
        <div>
            <a href="{{ route('finance.ledger.capture') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-magic me-1"></i> {{ __('Quick Capture') }}
            </a>
            <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#createIncomeModal">
                <i class="bi bi-arrow-down-circle me-1"></i> {{ __('Record Income') }}
            </button>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#createExpenseModal">
                <i class="bi bi-arrow-up-circle me-1"></i> {{ __('Record Expense') }}
            </button>
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

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('finance.ledger.index') }}" method="GET" class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="{{ __('Search entry no, counterparty, invoice…') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">{{ __('Type: All') }}</option>
                        <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Income</option>
                        <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Expense</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="account_type" class="form-select">
                        <option value="">{{ __('Account: All') }}</option>
                        <option value="company" {{ request('account_type') === 'company' ? 'selected' : '' }}>Company</option>
                        <option value="personal" {{ request('account_type') === 'personal' ? 'selected' : '' }}>Personal</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="bank_account_id" class="form-select">
                        <option value="">{{ __('Bank: All') }}</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ (string) request('bank_account_id') === (string) $acc->id ? 'selected' : '' }}>
                                {{ $acc->bank_name }} — {{ $acc->account_name ?: $acc->account_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-1">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Entry #') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Account') }}</th>
                            <th>{{ __('Counterparty') }}</th>
                            <th class="text-end">{{ __('Gross') }}</th>
                            <th class="text-end">{{ __('VAT') }}</th>
                            <th class="text-end">{{ __('WHT') }}</th>
                            <th class="text-end">{{ __('Net') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                            <tr>
                                <td>{{ optional($entry->entry_date)->format('d/m/Y') }}</td>
                                <td>
                                    <a href="{{ route('finance.ledger.show', $entry) }}" class="text-decoration-none">
                                        {{ $entry->entry_no }}
                                    </a>
                                </td>
                                <td>
                                    @if($entry->type === 'income')
                                        <span class="badge bg-success">Income</span>
                                    @else
                                        <span class="badge bg-danger">Expense</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small fw-bold">{{ $entry->bankAccount?->bank_name }}</div>
                                    @if($entry->bankAccount?->account_type === 'personal')
                                        <span class="badge bg-secondary-subtle text-dark border">{{ __('Personal') }}</span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary border">{{ __('Company') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $entry->counterparty_name ?: '—' }}</div>
                                    @if($entry->counterparty_tax_id)
                                        <div class="small text-muted">{{ $entry->counterparty_tax_id }}</div>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($entry->gross_amount, 2) }}</td>
                                <td class="text-end {{ $entry->vat_amount > 0 ? 'text-info' : 'text-muted' }}">
                                    {{ number_format($entry->vat_amount, 2) }}
                                </td>
                                <td class="text-end {{ $entry->wht_amount > 0 ? 'text-warning' : 'text-muted' }}">
                                    {{ number_format($entry->wht_amount, 2) }}
                                </td>
                                <td class="text-end fw-bold {{ $entry->type === 'income' ? 'text-success' : 'text-danger' }}">
                                    {{ $entry->type === 'income' ? '+' : '-' }}{{ number_format($entry->net_amount, 2) }}
                                </td>
                                <td>
                                    <a href="{{ route('finance.ledger.show', $entry) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">{{ __('No ledger entries yet. Click "Record Income" or "Record Expense" to begin.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">
                {{ $entries->links() }}
            </div>
        </div>
    </div>
</div>

{{-- Create Income Modal --}}
<div class="modal fade" id="createIncomeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('finance.ledger.store') }}" method="POST" enctype="multipart/form-data" x-data="ledgerForm({{ Js::from(['type' => 'income', 'accounts' => $accounts, 'categories' => $incomeCategories]) }})">
                @csrf
                <input type="hidden" name="type" value="income">
                <input type="hidden" name="category_type" value="income">
                <div class="modal-header bg-success-subtle">
                    <h5 class="modal-title"><i class="bi bi-arrow-down-circle me-1"></i> {{ __('Record Income') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('financial.ledger._form')
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('Save Income') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Create Expense Modal --}}
<div class="modal fade" id="createExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('finance.ledger.store') }}" method="POST" enctype="multipart/form-data" x-data="ledgerForm({{ Js::from(['type' => 'expense', 'accounts' => $accounts, 'categories' => $expenseCategories]) }})">
                @csrf
                <input type="hidden" name="type" value="expense">
                <input type="hidden" name="category_type" value="expense">
                <div class="modal-header bg-danger-subtle">
                    <h5 class="modal-title"><i class="bi bi-arrow-up-circle me-1"></i> {{ __('Record Expense') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('financial.ledger._form')
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Save Expense') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/ledger-form.js?v=' . filemtime(public_path('js/ledger-form.js'))) }}"></script>
@endsection
