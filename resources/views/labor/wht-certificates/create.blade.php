@extends('labor.layout')

@section('title', 'New WHT Certificate - Pro Walker Labor')

@section('content')
<div class="mb-3">
    <a href="{{ route('labor.wht-certificates.index') }}" class="text-decoration-none small">&larr; {{ __('WHT Certificates') }}</a>
    <h4 class="fw-bold mb-0 mt-1">{{ __('New WHT Certificate') }} (ใบหัก ณ ที่จ่าย)</h4>
</div>

<form action="{{ route('labor.wht-certificates.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white"><strong>{{ __('Type') }}</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Direction') }} *</label>
                    <select name="type" class="form-select" required>
                        <option value="received" selected>{{ __('Received (customer withheld tax when paying us)') }}</option>
                        <option value="issued">{{ __('Issued (we withheld tax when paying someone)') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Form') }} *</label>
                    <select name="wht_type" class="form-select" required>
                        <option value="pnd3">{{ __('ภ.ง.ด.3 (individual)') }}</option>
                        <option value="pnd53">{{ __('ภ.ง.ด.53 (juristic)') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Bill Reference (optional)') }}</label>
                    <select name="labor_bill_id" class="form-select">
                        <option value="">-- {{ __('None') }} --</option>
                        @foreach($bills as $bill)
                            <option value="{{ $bill->id }}" {{ (string) old('labor_bill_id') === (string) $bill->id ? 'selected' : '' }}>
                                {{ $bill->bill_no }} — {{ $bill->team->name ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white"><strong>{{ __('Parties') }}</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Payer Name') }} *</label>
                    <input type="text" name="payer_name" class="form-control" required maxlength="255" value="{{ old('payer_name') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Payer Tax ID') }}</label>
                    <input type="text" name="payer_tax_id" class="form-control" maxlength="15" value="{{ old('payer_tax_id') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Payee Name') }} *</label>
                    <input type="text" name="payee_name" class="form-control" required maxlength="255" value="{{ old('payee_name') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Payee Tax ID') }}</label>
                    <input type="text" name="payee_tax_id" class="form-control" maxlength="15" value="{{ old('payee_tax_id') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white"><strong>{{ __('Amounts') }}</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Income Type') }}</label>
                    <select name="income_type" class="form-select">
                        <option value="service">{{ __('Service') }}</option>
                        <option value="rent">{{ __('Rent') }}</option>
                        <option value="advertising">{{ __('Advertising') }}</option>
                        <option value="transport">{{ __('Transport') }}</option>
                        <option value="contract">{{ __('Contract') }}</option>
                        <option value="other">{{ __('Other') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Payment Date') }} *</label>
                    <input type="date" name="paid_at" class="form-control" required value="{{ old('paid_at', now()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Amount Paid') }} *</label>
                    <input type="number" step="0.01" min="0" name="amount_paid" class="form-control" required value="{{ old('amount_paid') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('WHT Rate (%)') }} *</label>
                    <input type="number" step="0.01" min="0" max="100" name="wht_rate" class="form-control" required value="{{ old('wht_rate', 3) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('WHT Amount') }} *</label>
                    <input type="number" step="0.01" min="0" name="wht_amount" class="form-control" required value="{{ old('wht_amount') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white"><strong>{{ __('Certificate File (optional)') }}</strong></div>
        <div class="card-body">
            <input type="file" name="certificate" class="form-control">
            <div class="form-text">{{ __('For "Received" certificates, upload the scanned copy the payer gave you — the app will not generate a new PDF if one is uploaded here.') }}</div>
            <label class="form-label mt-3">{{ __('Notes') }}</label>
            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('labor.wht-certificates.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        <button type="submit" name="action" value="draft" class="btn btn-outline-primary">{{ __('Save as Draft') }}</button>
        <button type="submit" name="action" value="issue" class="btn btn-success">{{ __('Save & Issue') }}</button>
    </div>
</form>
@endsection
