@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Expenses (รายจ่าย)') }}</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus"></i> {{ __('Record Expense') }}
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->{{ __('any())') }}<div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Paid From') }}</th>
                            <th>{{ __('Tax Deductible') }}</th>
                            <th class="text-end">{{ __('Amount') }}</th>
                            <th class="text-center">{{ __('Documents') }}</th>
                            <th class="text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                            <tr>
                                <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                                <td>{{ $expense->category ? $expense->category->name : 'N/A' }}</td>
                                <td>
                                    {{ Str::limit($expense->description, 50) }}
                                    @if($expense->{{ __('financialProfile)') }}<div class="small text-muted mt-1"><i class="bi bi-building"></i> {{ $expense->financialProfile->name }}</div>
                                    @endif
                                    @if($expense->{{ __('productionOrder)') }}<div class="small text-info mt-1"><i class="bi bi-briefcase"></i> Job: {{ $expense->productionOrder->project_name }}</div>
                                    @endif
                                </td>
                                <td>{{ $expense->bankAccount ? $expense->bankAccount->bank_name : 'N/A' }}</td>
                                <td class="text-center">
                                    @if($expense->is_tax_deductible)
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i>{{ __('Yes') }}</span>
                                    @else
                                        <span class="badge bg-secondary"><i class="bi bi-dash-circle"></i>{{ __('No') }}</span>
                                    @endif
                                </td>
                                <td class="text-end text-danger fw-bold">
                                    {{ number_format($expense->amount + $expense->vat_amount, 2) }}
                                    @if($expense->vat_amount > 0)
                                        <div class="small text-muted">(inc. VAT {{ number_format($expense->vat_amount, 2) }})</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        @if($expense->receipt_path)
                                            <a href="{{ asset('storage/' . $expense->receipt_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="View Receipt">
                                                <i class="bi bi-receipt"></i>
                                            </a>
                                        @endif
                                        @if($expense->wht_document_path)
                                            <a href="{{ asset('storage/' . $expense->wht_document_path) }}" target="_blank" class="btn btn-sm btn-outline-info" title="View WHT">
                                                <i class="bi bi-file-earmark-text"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('finance.expenses.destroy', $expense) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this expense? The bank balance will be restored.') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="{{ __('Delete') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">{{ __('No expenses recorded.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $expenses->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('finance.expenses.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Record New Expense') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Date') }} *</label>
                            <input type="date" name="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Category') }} *</label>
                            <select name="expense_category_id" class="form-select" required>
                                <option value="">-- {{ __('Select Category') }} --</option>
                                @foreach(\App\Models\ExpenseCategory::where('is_active', true)->orderBy('name')->get() as $cat)
                                    <option value="{{ $cat->id }}" data-tax="{{ $cat->is_tax_deductible ? '1' : '0' }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Paid From (Bank Account)') }} *</label>
                            <select name="bank_account_id" class="form-select" required>
                                <option value="">-- {{ __('Select Account') }} --</option>
                                @foreach(\App\Models\BankAccount::where('is_active', true)->get() as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->bank_name }} ({{ number_format($bank->current_balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Biller Profile (Optional)') }}</label>
                            <select name="financial_profile_id" class="form-select">
                                <option value="">-- {{ __('Select Biller') }} --</option>
                                @foreach(\App\Models\FinancialProfile::where('type', 'biller')->get() as $profile)
                                    <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Amount (Excluding VAT)') }} *</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('VAT Amount (Optional)') }}</label>
                            <input type="number" step="0.01" name="vat_amount" class="form-control" value="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Description / Note') }}</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_tax_deductible" class="form-check-input" value="1" id="isTaxDeductibleCheck">
                        <label class="form-check-label" for="isTaxDeductibleCheck">
                            {{ __('This expense is Tax Deductible (นำไปหักภาษีได้)') }}
                            <br><small class="text-muted">{{ __('Checking this allows the expense to be included in the Tax-Only monthly export.') }}</small>
                        </label>
                    </div>

                    <hr>
                    <h6 class="mb-3">{{ __('Evidence Documents') }}</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Receipt / Slip') }}</label>
                            <input type="file" name="receipt" class="form-control" accept=".pdf,image/*">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('WHT Document (50 ทวิ)') }}</label>
                            <input type="file" name="wht_document" class="form-control" accept=".pdf,image/*">
                        </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.querySelector('select[name="expense_category_id"]');
    const taxCheckbox = document.getElementById('isTaxDeductibleCheck');

    if(categorySelect && taxCheckbox) {
        categorySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if(selectedOption && selectedOption.dataset.tax === '1') {
                taxCheckbox.checked = true;
            } else {
                taxCheckbox.checked = false;
            }
        });
    }
});
</script>
@endsection
