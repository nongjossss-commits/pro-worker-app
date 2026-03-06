<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">{{ __('General Expenses') }}</h6>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createExpenseModal">
            <i class="bi bi-plus-lg"></i> {{ __('Add Expense') }}
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Paid From') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th class="text-center">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses ?? [] as $exp)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($exp->expense_date)->format('d/m/Y') }}</td>
                        <td>{{ $exp->category ? $exp->category->name : 'N/A' }}</td>
                        <td class="text-end text-danger">{{ number_format($exp->amount, 2) }}</td>
                        <td>{{ $exp->bankAccount ? $exp->bankAccount->bank_name . ' (' . $exp->bankAccount->account_number . ')' : 'N/A' }}</td>
                        <td>{{ Str::limit($exp->description, 50) }}</td>
                        <td class="text-center">
                            @if($exp->receipt_path)
                                <a href="{{ asset('storage/' . $exp->receipt_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="View Receipt">
                                    <i class="bi bi-receipt"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">{{ __('No general expenses found.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3">
            @if(isset($expenses) && method_exists($expenses, 'links'))
                {{ $expenses->links() }}
            @endif
        </div>
    </div>
</div>

<!-- Create Expense Modal -->
<div class="modal fade" id="createExpenseModal" tabindex="-1" aria-labelledby="createExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('finance.expenses.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createExpenseModalLabel">{{ __('Add General Expense') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Category') }} <span class="text-danger">*</span></label>
                        <select name="expense_category_id" class="form-select" required>
                            <option value="">-- {{ __('Select Category') }} --</option>
                            @foreach(\App\Models\ExpenseCategory::where('is_active', true)->get() as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Paid From Account') }} <span class="text-danger">*</span></label>
                        <select name="bank_account_id" class="form-select" required>
                            <option value="">-- {{ __('Select Bank Account') }} --</option>
                            @foreach(\App\Models\BankAccount::where('is_active', true)->get() as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->bank_name }} ({{ $bank->account_number }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Receipt/Document') }}</label>
                        <input type="file" name="receipt" class="form-control" accept=".pdf,image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Expense') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
