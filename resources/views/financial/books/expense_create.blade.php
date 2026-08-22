@extends('layouts.app')

@section('title', __('Record Expense'))

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route('finance.books.index') }}" class="text-decoration-none small">&larr; {{ __('Income & Expense Books') }}</a>
        <h4 class="fw-bold mb-0 mt-1">{{ __('Record Expense') }} (บันทึกรายจ่าย)</h4>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('finance.ledger.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="type" value="expense">
        <input type="hidden" name="category_type" value="expense">

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Pay From Account') }} *</label>
                        <select name="bank_account_id" class="form-select" required>
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ (string) old('bank_account_id') === (string) $account->id ? 'selected' : '' }}>
                                    {{ $account->account_name ?: $account->bank_name }} @if($account->bank_name) ({{ $account->bank_name }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @if($accounts->isEmpty())
                        <div class="form-text text-danger">{{ __('No active book accounts yet — add one in Bank Accounts first.') }}</div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Expense Category') }} *</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach($expenseCategories as $cat)
                                <option value="{{ $cat->id }}" {{ (string) old('category_id') === (string) $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}{{ $cat->is_tax_deductible ? ' (' . __('tax deductible') . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if($expenseCategories->isEmpty())
                        <div class="form-text text-danger">{{ __('No active expense categories yet — add one in Expense Categories first.') }}</div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Amount') }} *</label>
                        <input type="number" step="0.01" min="0.01" name="gross_amount" class="form-control" required value="{{ old('gross_amount') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Date') }} *</label>
                        <input type="date" name="entry_date" class="form-control" required value="{{ old('entry_date', now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">{{ __('Description') }} *</label>
                        <input type="text" name="description" class="form-control" required maxlength="255" value="{{ old('description') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">{{ __('Attach Bill / Receipt (optional)') }}</label>
                        <input type="file" name="receipt" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('finance.books.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn btn-danger">{{ __('Save Expense') }}</button>
        </div>
    </form>
</div>
@endsection
