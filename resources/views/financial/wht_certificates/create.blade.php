@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="whtForm()">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.wht-certificates.index') }}" class="text-decoration-none small">&larr; {{ __('WHT Certificates') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">{{ __('New WHT Certificate') }}</h1>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('finance.wht-certificates.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card shadow mb-3">
            <div class="card-header"><strong>{{ __('Certificate Type') }}</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Direction') }} *</label>
                        <select name="type" class="form-select" required x-model="direction">
                            <option value="issued">↗ {{ __('Issued — เราออกให้ supplier') }}</option>
                            <option value="received">↙ {{ __('Received — ลูกค้าออกให้เรา') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Form Type') }} *</label>
                        <select name="wht_type" class="form-select" required>
                            <option value="pnd3">ภ.ง.ด.3 — {{ __('บุคคลธรรมดา') }}</option>
                            <option value="pnd53">ภ.ง.ด.53 — {{ __('นิติบุคคล') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Paid At') }} *</label>
                        <input type="date" name="paid_at" class="form-control" required value="{{ now()->format('Y-m-d') }}" x-model="paidAt" @change="onDateChange">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Tax Period Year') }}</label>
                        <input type="number" name="tax_period_year" class="form-control" x-model="periodYear">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Tax Period Month') }}</label>
                        <input type="number" min="1" max="12" name="tax_period_month" class="form-control" x-model="periodMonth">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-3">
            <div class="card-header"><strong>{{ __('Payer (ผู้จ่าย)') }}</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">{{ __('Name') }} *</label>
                        <input type="text" name="payer_name" class="form-control" required maxlength="255">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Tax ID') }}</label>
                        <input type="text" name="payer_tax_id" class="form-control" maxlength="15">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-3">
            <div class="card-header"><strong>{{ __('Payee (ผู้รับเงิน)') }}</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">{{ __('Name') }} *</label>
                        <input type="text" name="payee_name" class="form-control" required maxlength="255">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Tax ID') }}</label>
                        <input type="text" name="payee_tax_id" class="form-control" maxlength="15">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-3">
            <div class="card-header"><strong>{{ __('Amount') }}</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Income Type') }}</label>
                        <select name="income_type" class="form-select">
                            <option value="">— {{ __('Select') }} —</option>
                            <option value="service">{{ __('Service — ค่าบริการ (3%)') }}</option>
                            <option value="rent">{{ __('Rent — ค่าเช่า (5%)') }}</option>
                            <option value="advertising">{{ __('Advertising — ค่าโฆษณา (2%)') }}</option>
                            <option value="transport">{{ __('Transport — ค่าขนส่ง (1%)') }}</option>
                            <option value="contract">{{ __('Contract — รับจ้างทำของ (3%)') }}</option>
                            <option value="other">{{ __('Other') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Amount Paid') }} *</label>
                        <input type="number" step="0.01" min="0" name="amount_paid" class="form-control" x-model.number="amountPaid" @input="recalculate" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('WHT Rate (%)') }} *</label>
                        <input type="number" step="0.01" min="0" max="100" name="wht_rate" class="form-control" x-model.number="whtRate" @input="recalculate" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('WHT Amount') }} *</label>
                        <input type="number" step="0.01" min="0" name="wht_amount" class="form-control" x-model.number="whtAmount" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Certificate PDF (upload)') }}</label>
                        <input type="file" name="certificate" class="form-control" accept="image/*,.pdf">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('finance.wht-certificates.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" name="action" value="draft" class="btn btn-outline-primary">{{ __('Save as Draft') }}</button>
            <button type="submit" name="action" value="issue" class="btn btn-success">
                <i class="bi bi-check-circle"></i> {{ __('Save & Issue') }}
            </button>
        </div>
    </form>
</div>

<script>
function whtForm() {
    return {
        direction: 'issued',
        paidAt: '{{ now()->format("Y-m-d") }}',
        periodYear: {{ now()->year }},
        periodMonth: {{ now()->month }},
        amountPaid: 0,
        whtRate: 3,
        whtAmount: 0,
        onDateChange() {
            const d = new Date(this.paidAt);
            if (!isNaN(d)) {
                this.periodYear = d.getFullYear();
                this.periodMonth = d.getMonth() + 1;
            }
        },
        recalculate() {
            const a = parseFloat(this.amountPaid) || 0;
            const r = parseFloat(this.whtRate) || 0;
            this.whtAmount = Math.round((a * r / 100) * 100) / 100;
        },
    };
}
</script>
@endsection
