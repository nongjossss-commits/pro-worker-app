@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="creditNoteForm({
    profiles: {{ Js::from($profiles->map(fn($p) => ['id' => $p->id, 'name' => $p->name])) }},
})">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.credit-notes.index') }}" class="text-decoration-none small">&larr; {{ __('Credit Notes') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">{{ __('New Credit Note') }}</h1>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Which bill this reduces --}}
    <div class="card shadow mb-3">
        <div class="card-header"><strong>{{ __('Bill to Reduce') }}</strong></div>
        <div class="card-body">
            @if($transaction)
                <div class="alert alert-info mb-0">
                    <div class="fw-bold">{{ __('Bill') }} #{{ $transaction->id }}</div>
                    <div class="small">
                        {{ __('Employer') }}: {{ $transaction->productionOrder?->employer?->employerNameTh ?? $transaction->productionOrder?->employer?->employerNameEn ?? '—' }}
                        &middot; {{ __('Billed Amount') }}: {{ number_format($transaction->amount, 2) }}
                        &middot; {{ __('Paid') }}: {{ number_format($transaction->paid_amount, 2) }}
                        &middot; {{ __('Already Credited') }}: {{ number_format($transaction->credit_amount, 2) }}
                    </div>
                </div>
            @else
                <label class="form-label">{{ __('Bill (Financial Transaction) ID') }} *</label>
                <div class="input-group" style="max-width:320px;">
                    <span class="input-group-text">#</span>
                    <input type="number" name="financial_transaction_id" class="form-control" required min="1" value="{{ old('financial_transaction_id') }}">
                </div>
                <div class="form-text">{{ __('Find the bill ID on the order\'s Finance tab, or issue a credit note directly from there instead.') }}</div>
            @endif
        </div>
    </div>

    <form action="{{ route('finance.credit-notes.store') }}" method="POST">
        @csrf
        @if($transaction)
            <input type="hidden" name="financial_transaction_id" value="{{ $transaction->id }}">
        @endif

        <div class="card shadow mb-3">
            <div class="card-header"><strong>{{ __('Header') }}</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Credit Note Date') }} *</label>
                        <input type="date" name="credit_note_date" class="form-control" required value="{{ now()->format('Y-m-d') }}">
                        <div class="form-text">{{ __('Fiscal year auto-derived from date') }}</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">{{ __('Issuer (Biller Profile)') }}</label>
                        <select name="issuer_profile_id" class="form-select">
                            <option value="">— {{ __('Select biller') }} —</option>
                            @foreach($profiles as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Related Tax Invoice') }} <span class="text-muted small">({{ __('optional') }})</span></label>
                        <input type="number" name="related_tax_invoice_id" class="form-control" min="1">
                        <div class="form-text">{{ __('If this bill already had a formal Tax Invoice issued and its declared VAT needs correcting.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-3">
            <div class="card-header"><strong>{{ __('Customer') }}</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">{{ __('Customer Name') }} *</label>
                        <input type="text" name="customer_name" class="form-control" required maxlength="255"
                               value="{{ old('customer_name', $transaction?->productionOrder?->employer?->employerNameTh ?? $transaction?->productionOrder?->employer?->employerNameEn) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Tax ID') }}</label>
                        <input type="text" name="customer_tax_id" class="form-control" maxlength="15" value="{{ old('customer_tax_id', $transaction?->productionOrder?->employer?->employerTaxId) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Branch') }}</label>
                        <input type="text" name="customer_branch" class="form-control" maxlength="50" placeholder="{{ __('e.g., 00000 (สำนักงานใหญ่)') }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">{{ __('Address') }}</label>
                        <textarea name="customer_address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-3">
            <div class="card-header"><strong>{{ __('Amounts') }}</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Subtotal') }} *</label>
                        <input type="number" step="0.01" min="0.01" name="subtotal" class="form-control" x-model.number="subtotal" @input="recalculate" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('VAT Rate (%)') }} *</label>
                        <input type="number" step="0.01" min="0" max="100" name="vat_rate" class="form-control" x-model.number="vatRate" @input="recalculate" value="7" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('VAT Amount') }} *</label>
                        <input type="number" step="0.01" min="0" name="vat_amount" class="form-control" x-model.number="vatAmount" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Total Credit') }} *</label>
                        <input type="number" step="0.01" min="0.01" name="total_credit" class="form-control" x-model.number="total" required readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-3">
            <div class="card-body">
                <label class="form-label">{{ __('Reason for Credit') }} *</label>
                <textarea name="reason" class="form-control" rows="3" required maxlength="2000" placeholder="{{ __('e.g., calculation error, price reduction, returned service — required for tax filing') }}">{{ old('reason') }}</textarea>
            </div>
        </div>

        <div class="card shadow mb-3">
            <div class="card-body">
                <label class="form-label">{{ __('Notes') }}</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('finance.credit-notes.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" name="action" value="draft" class="btn btn-outline-primary">{{ __('Save as Draft') }}</button>
            <button type="submit" name="action" value="issue" class="btn btn-success">
                <i class="bi bi-check-circle"></i> {{ __('Save & Issue') }}
            </button>
        </div>
    </form>
</div>

<script>
function creditNoteForm(opts = {}) {
    return {
        profiles: Array.isArray(opts.profiles) ? opts.profiles : [],
        subtotal: 0,
        vatRate: 7,
        vatAmount: 0,
        total: 0,
        recalculate() {
            const s = parseFloat(this.subtotal) || 0;
            const r = parseFloat(this.vatRate) || 0;
            this.vatAmount = Math.round((s * r / 100) * 100) / 100;
            this.total = Math.round((s + this.vatAmount) * 100) / 100;
        },
    };
}
</script>
@endsection
