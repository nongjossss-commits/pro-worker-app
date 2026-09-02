@extends('labor.layout')

@section('title', 'New Tax Invoice - Pro Walker Labour')

@section('content')
<div x-data="laborTaxInvoiceForm({
        subtotal: {{ (float) old('subtotal', $prefill['subtotal'] ?? 0) }},
        vatRate: {{ (float) old('vat_rate', $prefill['vat_rate'] ?? 7) }},
    })">
    <div class="mb-3">
        <a href="{{ route('labor.tax-invoices.index') }}" class="text-decoration-none small">&larr; {{ __('Tax Invoices') }}</a>
        <h4 class="fw-bold mb-0 mt-1">{{ __('New Tax Invoice') }}</h4>
    </div>

    <form action="{{ route('labor.tax-invoices.store') }}" method="POST">
        @csrf

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white"><strong>{{ __('Header') }}</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Invoice Date') }} *</label>
                        <input type="date" name="invoice_date" class="form-control" required
                               value="{{ old('invoice_date', $prefill['invoice_date'] ?? now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">{{ __('Issuer (Biller Profile)') }} *</label>
                        <select name="issuer_profile_id" class="form-select" required>
                            <option value="">-- {{ __('Select biller') }} --</option>
                            @foreach($profiles as $p)
                                <option value="{{ $p->id }}" {{ (string) old('issuer_profile_id', $prefill['issuer_profile_id'] ?? '') === (string) $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} @if($p->tax_id) ({{ $p->tax_id }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('From Bill (optional)') }}</label>
                        <select name="labor_bill_id" class="form-select" onchange="if(this.value) window.location = '{{ route('labor.tax-invoices.create') }}?labor_bill_id=' + this.value">
                            <option value="">-- {{ __('None — manual entry') }} --</option>
                            @foreach($bills as $bill)
                                <option value="{{ $bill->id }}" {{ (string) old('labor_bill_id', $prefill['labor_bill_id'] ?? '') === (string) $bill->id ? 'selected' : '' }}>
                                    {{ $bill->bill_no }} — {{ $bill->team->name ?? '-' }} ({{ number_format($bill->period_charges, 2) }} {{ __('baht') }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('Picking a bill pre-fills customer and subtotal from it.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white"><strong>{{ __('Customer') }}</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">{{ __('Customer Name') }} *</label>
                        <input type="text" name="customer_name" class="form-control" required maxlength="255"
                               value="{{ old('customer_name', $prefill['customer_name'] ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Tax ID') }}</label>
                        <input type="text" name="customer_tax_id" class="form-control" maxlength="15"
                               value="{{ old('customer_tax_id', $prefill['customer_tax_id'] ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Branch') }}</label>
                        <input type="text" name="customer_branch" class="form-control" maxlength="50"
                               value="{{ old('customer_branch', $prefill['customer_branch'] ?? '') }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">{{ __('Address') }}</label>
                        <textarea name="customer_address" class="form-control" rows="2">{{ old('customer_address', $prefill['customer_address'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white"><strong>{{ __('Amounts') }}</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Subtotal') }} *</label>
                        <input type="number" step="0.01" min="0" name="subtotal" class="form-control" x-model.number="subtotal" @input="recalculate" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('VAT Rate (%)') }} *</label>
                        <input type="number" step="0.01" min="0" max="100" name="vat_rate" class="form-control" x-model.number="vatRate" @input="recalculate" required>
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

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <label class="form-label">{{ __('Description / Notes') }}</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                <div class="form-text">{{ __('Shown as the line-item description on the PDF. Leave blank to auto-describe from the linked bill.') }}</div>
            </div>
        </div>

        <input type="hidden" name="payment_methods" :value="paymentMethodsJson">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white"><strong>{{ __('Payment Methods') }}</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="pmCash" x-model="usingCash">
                            <label for="pmCash" class="form-check-label">{{ __('Cash') }}</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="pmPromptPay" x-model="usingPromptPay">
                            <label for="pmPromptPay" class="form-check-label">{{ __('PromptPay') }}</label>
                        </div>
                        <input type="text" class="form-control form-control-sm mt-1" x-show="usingPromptPay" x-model="promptPayId" placeholder="{{ __('PromptPay ID') }}">
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="pmOther" x-model="usingOther">
                            <label for="pmOther" class="form-check-label">{{ __('Other') }}</label>
                        </div>
                        <input type="text" class="form-control form-control-sm mt-1" x-show="usingOther" x-model="otherNote" placeholder="{{ __('Describe...') }}">
                    </div>
                    <div class="col-12">
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="pmTransfer" x-model="usingTransfer">
                            <label for="pmTransfer" class="form-check-label">{{ __('Bank Transfer') }}</label>
                        </div>
                        <template x-if="usingTransfer">
                            <div>
                                <template x-for="(t, idx) in transferList" :key="idx">
                                    <div class="row g-2 align-items-center mb-2">
                                        <div class="col-md-3"><input type="text" class="form-control form-control-sm" x-model="t.bank_name" placeholder="{{ __('Bank name') }}"></div>
                                        <div class="col-md-3"><input type="text" class="form-control form-control-sm" x-model="t.account_name" placeholder="{{ __('Account name') }}"></div>
                                        <div class="col-md-3"><input type="text" class="form-control form-control-sm" x-model="t.account_number" placeholder="{{ __('Account number') }}"></div>
                                        <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger" @click="transferList.splice(idx,1)"><i class="bi bi-x"></i></button></div>
                                    </div>
                                </template>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="transferList.push({bank_name:'',account_name:'',account_number:''})">
                                    <i class="bi bi-plus-circle"></i> {{ __('Add bank account') }}
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('labor.tax-invoices.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" name="action" value="draft" class="btn btn-outline-primary">{{ __('Save as Draft') }}</button>
            <button type="submit" name="action" value="issue" class="btn btn-success">
                <i class="bi bi-check-circle"></i> {{ __('Save & Issue') }}
            </button>
        </div>
    </form>
</div>

<script>
function laborTaxInvoiceForm(opts) {
    return {
        subtotal: opts.subtotal || 0,
        vatRate: opts.vatRate || 7,
        vatAmount: 0,
        total: 0,
        usingCash: false,
        usingTransfer: false,
        usingPromptPay: false,
        usingOther: false,
        promptPayId: '',
        otherNote: '',
        transferList: [],
        init() {
            this.recalculate();
        },
        recalculate() {
            const s = parseFloat(this.subtotal) || 0;
            const r = parseFloat(this.vatRate) || 0;
            this.vatAmount = Math.round((s * r / 100) * 100) / 100;
            this.total = Math.round((s + this.vatAmount) * 100) / 100;
        },
        get paymentMethodsJson() {
            const out = [];
            if (this.usingCash) out.push({ type: 'cash' });
            if (this.usingTransfer) {
                this.transferList.forEach(t => out.push({ type: 'transfer', ...t }));
            }
            if (this.usingPromptPay && this.promptPayId.trim()) {
                out.push({ type: 'promptpay', promptpay_id: this.promptPayId.trim() });
            }
            if (this.usingOther && this.otherNote.trim()) {
                out.push({ type: 'other', note: this.otherNote.trim() });
            }
            return JSON.stringify(out);
        },
    };
}
</script>
@endsection
