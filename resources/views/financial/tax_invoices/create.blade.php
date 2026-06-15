@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="taxInvoiceForm()">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.tax-invoices.index') }}" class="text-decoration-none small">&larr; {{ __('Tax Invoices') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">{{ __('New Tax Invoice') }}</h1>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('finance.tax-invoices.store') }}" method="POST">
        @csrf
        <div class="card shadow mb-3">
            <div class="card-header"><strong>{{ __('Header') }}</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Invoice Date') }} *</label>
                        <input type="date" name="invoice_date" class="form-control" required value="{{ now()->format('Y-m-d') }}">
                        <div class="form-text">{{ __('Fiscal year auto-derived from date') }}</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">{{ __('Issuer (Biller Profile)') }} *</label>
                        <select name="issuer_profile_id" class="form-select" required>
                            <option value="">— {{ __('Select biller') }} —</option>
                            @foreach($profiles as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} @if($p->tax_id)({{ $p->tax_id }})@endif</option>
                            @endforeach
                        </select>
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
                        <input type="text" name="customer_name" class="form-control" required maxlength="255">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Tax ID') }}</label>
                        <input type="text" name="customer_tax_id" class="form-control" maxlength="15">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Branch') }}</label>
                        <input type="text" name="customer_branch" class="form-control" maxlength="50" placeholder="e.g., 00000 (สำนักงานใหญ่)">
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
                        <input type="number" step="0.01" min="0" name="subtotal" class="form-control" x-model.number="subtotal" @input="recalculate" required>
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
                        <label class="form-label">{{ __('Total') }} *</label>
                        <input type="number" step="0.01" min="0" name="total" class="form-control" x-model.number="total" required readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-3">
            <div class="card-body">
                <label class="form-label">{{ __('Notes') }}</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('finance.tax-invoices.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" name="action" value="draft" class="btn btn-outline-primary">{{ __('Save as Draft') }}</button>
            <button type="submit" name="action" value="issue" class="btn btn-success">
                <i class="bi bi-check-circle"></i> {{ __('Save & Issue') }}
            </button>
        </div>
    </form>
</div>

<script>
function taxInvoiceForm() {
    return {
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
