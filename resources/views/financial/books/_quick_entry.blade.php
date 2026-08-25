{{--
    Floating "Record Income/Expense" button — stays pinned near the top of
    the viewport on every "บันทึกรายรับรายจ่าย" page (index + show) so
    recording a transaction never requires scrolling or navigating first.
    Included from financial/books/index.blade.php (no $account in scope)
    and financial/books/show.blade.php (passes $account to pre-select it,
    still changeable). Posts to the existing finance.ledger.store route —
    no new backend here.

    Centered (not right-aligned) since the top-right corner is already
    crowded by the page's own header buttons (Income/Expense Categories,
    Bank Accounts) — centering avoids overlapping them, and the empty
    middle of the header is otherwise unused. Sized up (btn-lg) so it
    reads as the page's primary call-to-action rather than blending in.

    Expects: $activeAccounts, $incomeCategories, $expenseCategories.
--}}
@php
    $quickEntryAccountId = isset($account) ? $account->id : old('bank_account_id');
@endphp

<button type="button"
    class="btn btn-primary btn-lg rounded-pill shadow"
    style="position: fixed; top: 90px; left: 50%; transform: translateX(-50%); z-index: 1050;"
    data-bs-toggle="modal" data-bs-target="#quickEntryModal">
    <i class="bi bi-plus-lg me-md-2"></i><span class="d-none d-md-inline">{{ __('Record Income/Expense') }}</span>
</button>

<div class="modal fade" id="quickEntryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('finance.ledger.store') }}" x-data="{ txnType: 'income' }" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Record Income/Expense') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type') }} *</label>
                        <select name="type" class="form-select" x-model="txnType" required>
                            <option value="income">{{ __('Income') }}</option>
                            <option value="expense">{{ __('Expense') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Account') }} *</label>
                        <select name="bank_account_id" class="form-select" required>
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach($activeAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ (string) $quickEntryAccountId === (string) $acc->id ? 'selected' : '' }}>
                                    {{ $acc->account_name ?: $acc->bank_name }} @if($acc->bank_name) ({{ $acc->bank_name }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @if($activeAccounts->isEmpty())
                        <div class="form-text text-danger">{{ __('No active book accounts yet — add one in Bank Accounts first.') }}</div>
                        @endif
                    </div>
                    <div class="mb-3" x-show="txnType === 'income'">
                        <label class="form-label">{{ __('Category (Income)') }} *</label>
                        <select name="category_id" class="form-select" :disabled="txnType !== 'income'" :required="txnType === 'income'">
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach($incomeCategories->where('is_active', true) as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="category_type" value="income" :disabled="txnType !== 'income'">
                    </div>
                    <div class="mb-3" x-show="txnType === 'expense'" x-cloak>
                        <label class="form-label">{{ __('Category (Expense)') }} *</label>
                        <select name="category_id" class="form-select" :disabled="txnType !== 'expense'" :required="txnType === 'expense'">
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach($expenseCategories->where('is_active', true) as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="category_type" value="expense" :disabled="txnType !== 'expense'">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Amount') }} *</label>
                        <input type="number" step="0.01" min="0.01" name="gross_amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Date') }} *</label>
                        <input type="date" name="entry_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }} *</label>
                        <input type="text" name="description" class="form-control" required maxlength="255">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">{{ __('Attach Bill / Receipt (optional)') }}</label>
                        <input type="file" name="receipt" class="form-control">
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
