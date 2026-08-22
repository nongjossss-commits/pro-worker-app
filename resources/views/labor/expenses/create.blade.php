@extends('labor.layout')

@section('title', 'Record Expense - Pro Walker Labor')

@section('content')
<div class="mb-3">
    <a href="{{ route('labor.books.index') }}" class="text-decoration-none small">&larr; {{ __('Company Books') }}</a>
    <h4 class="fw-bold mb-0 mt-1">{{ __('Record Expense') }} (บันทึกรายจ่าย)</h4>
</div>

<form action="{{ route('labor.expenses.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Pay From Account') }} *</label>
                    <select name="labor_book_account_id" class="form-select" required>
                        <option value="">-- {{ __('Select') }} --</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ (string) old('labor_book_account_id') === (string) $account->id ? 'selected' : '' }}>
                                {{ $account->name }} @if($account->bank_name) ({{ $account->bank_name }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @if($accounts->isEmpty())
                    <div class="form-text text-danger">{{ __('No active book accounts yet — add one in Company Books first.') }}</div>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Expense Category') }} *</label>
                    <select name="labor_expense_category_id" id="expenseCategorySelect" class="form-select" required>
                        <option value="">-- {{ __('Select') }} --</option>
                        @foreach($expenseCategories as $expCategory)
                            <option value="{{ $expCategory->id }}" data-note="{{ e($expCategory->note) }}" {{ (string) old('labor_expense_category_id') === (string) $expCategory->id ? 'selected' : '' }}>
                                {{ $expCategory->name }}{{ $expCategory->is_tax_deductible ? ' (' . __('tax deductible') . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @if($expenseCategories->isEmpty())
                    <div class="form-text text-danger">{{ __('No active expense categories yet — ask a Super Admin to add one in Company Books.') }}</div>
                    @endif
                    <div id="expenseCategoryNote" class="form-text d-none">
                        <i class="bi bi-info-circle me-1"></i><span id="expenseCategoryNoteText"></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Quantity (optional)') }}</label>
                    <input type="number" min="1" step="1" name="quantity" class="form-control" value="{{ old('quantity') }}" placeholder="{{ __('e.g. 5') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Amount') }} *</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required value="{{ old('amount') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Date') }} *</label>
                    <input type="date" name="transaction_date" class="form-control" required value="{{ old('transaction_date', now()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-12">
                    <label class="form-label">{{ __('Description') }} *</label>
                    <input type="text" name="description" class="form-control" required maxlength="255" value="{{ old('description') }}">
                </div>
                <div class="col-md-12">
                    <label class="form-label">{{ __('Attach Bill / Receipt (optional)') }}</label>
                    <input type="file" name="attachment" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('labor.books.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-danger">{{ __('Save Expense') }}</button>
    </div>
</form>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('expenseCategorySelect');
        const noteBox = document.getElementById('expenseCategoryNote');
        const noteText = document.getElementById('expenseCategoryNoteText');
        if (!select || !noteBox || !noteText) return;

        function updateNote() {
            const option = select.options[select.selectedIndex];
            const note = option ? option.dataset.note : '';
            if (note) {
                noteText.textContent = note;
                noteBox.classList.remove('d-none');
            } else {
                noteBox.classList.add('d-none');
            }
        }

        select.addEventListener('change', updateNote);
        updateNote();
    });
</script>
@endpush
@endsection
