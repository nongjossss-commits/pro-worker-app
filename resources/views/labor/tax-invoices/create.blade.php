@extends('labor.layout')

@section('title', 'New Tax Invoice - Pro Walker Labour')

@section('content')
@php
    // On validation-error redisplay, old('items') is the raw JSON string the
    // hidden field submitted (see itemsJson below) — decode it back to an
    // array so both branches feed Alpine the same shape.
    $oldItemsRaw = old('items');
    $itemsForJs = is_string($oldItemsRaw) ? (json_decode($oldItemsRaw, true) ?: []) : ($oldItemsRaw ?? ($prefill['items'] ?? []));
@endphp
<div x-data="laborTaxInvoiceForm({
        items: {{ Illuminate\Support\Js::from($itemsForJs) }},
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
                    <div class="col-md-6">
                        <label class="form-label">{{ __('From Bill (optional)') }}</label>
                        <select name="labor_bill_id" class="form-select" onchange="if(this.value) window.location = '{{ route('labor.tax-invoices.create') }}?labor_bill_id=' + this.value">
                            <option value="">-- {{ __('None') }} --</option>
                            @foreach($bills as $bill)
                                <option value="{{ $bill->id }}" {{ (string) old('labor_bill_id', $prefill['labor_bill_id'] ?? '') === (string) $bill->id ? 'selected' : '' }}>
                                    {{ $bill->bill_no }} — {{ $bill->team->name ?? '-' }} ({{ number_format($bill->period_charges, 2) }} {{ __('baht') }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('Bills the team itself for its own labor charges.') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('From External Customer (optional)') }}</label>
                        <select name="labor_customer_id" class="form-select" onchange="if(this.value) window.location = '{{ route('labor.tax-invoices.create') }}?labor_customer_id=' + this.value">
                            <option value="">-- {{ __('None') }} --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ (string) old('labor_customer_id', $prefill['labor_customer_id'] ?? '') === (string) $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} — {{ $customer->team->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('A team\'s own external customer — pick a bill above OR a customer here, not both.') }}</div>
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
            <div class="card-header bg-white"><strong>{{ __('Billing Period') }}</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Period Start') }}{{ ($prefill['labor_customer_id'] ?? old('labor_customer_id')) ? ' *' : '' }}</label>
                        <input type="date" name="period_start" class="form-control"
                               value="{{ old('period_start', $prefill['period_start'] ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Period End') }}{{ ($prefill['labor_customer_id'] ?? old('labor_customer_id')) ? ' *' : '' }}</label>
                        <input type="date" name="period_end" class="form-control"
                               value="{{ old('period_end', $prefill['period_end'] ?? '') }}">
                    </div>
                    <div class="col-12">
                        <div class="form-text">{{ __('Which billing period this invoice covers — required for external-customer invoices so the same month is never billed twice.') }}</div>
                        @error('period_start')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="items" :value="itemsJson">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>{{ __('Line Items') }}</strong>
                <button type="button" class="btn btn-sm btn-outline-primary" @click="addItem">
                    <i class="bi bi-plus-circle me-1"></i>{{ __('Add Item') }}
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 45%">{{ __('Description') }}</th>
                                <th style="width: 15%">{{ __('Qty (people/units)') }}</th>
                                <th style="width: 17%">{{ __('Unit Price') }}</th>
                                <th style="width: 17%" class="text-end">{{ __('Amount') }}</th>
                                <th style="width: 6%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in items" :key="idx">
                                <tr>
                                    <td><input type="text" class="form-control form-control-sm" x-model="item.description" placeholder="{{ __('e.g. Cleaning service, March 2026') }}"></td>
                                    <td><input type="number" step="0.01" min="0.01" class="form-control form-control-sm" x-model.number="item.quantity"></td>
                                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" x-model.number="item.unit_price"></td>
                                    <td class="text-end fw-bold" x-text="formatMoney(itemAmount(item))"></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" @click="items.splice(idx, 1)" x-show="items.length > 1">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                @error('items')
                    <div class="text-danger small p-3">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <div class="row g-2 justify-content-end">
                    <div class="col-md-4 d-flex justify-content-between">
                        <span class="text-muted">{{ __('Subtotal') }}</span>
                        <span class="fw-bold" x-text="formatMoney(subtotal)"></span>
                    </div>
                    <div class="col-md-4 d-flex justify-content-between align-items-center">
                        <span class="text-muted">
                            {{ __('VAT') }} (<input type="number" step="0.01" min="0" max="100" name="vat_rate" class="d-inline-block text-center" style="width: 3.5rem; border: none; border-bottom: 1px dashed #ccc;" x-model.number="vatRate">%)
                        </span>
                        <span class="fw-bold" x-text="formatMoney(vatAmount)"></span>
                    </div>
                    <div class="col-md-4 d-flex justify-content-between border-top pt-2 mt-1">
                        <span class="fw-bold">{{ __('Grand Total') }}</span>
                        <span class="fw-bold fs-5 text-primary" x-text="formatMoney(total)"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <label class="form-label">{{ __('Notes') }}</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                <div class="form-text">{{ __('Extra remarks shown on the PDF below the totals — payment terms, etc. The line items above already carry their own descriptions.') }}</div>
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
        items: (opts.items && opts.items.length) ? opts.items : [{ description: '', quantity: 1, unit_price: 0 }],
        vatRate: opts.vatRate || 7,
        usingCash: false,
        usingTransfer: false,
        usingPromptPay: false,
        usingOther: false,
        promptPayId: '',
        otherNote: '',
        transferList: [],
        addItem() {
            this.items.push({ description: '', quantity: 1, unit_price: 0 });
        },
        itemAmount(item) {
            const q = parseFloat(item.quantity) || 0;
            const p = parseFloat(item.unit_price) || 0;
            return Math.round(q * p * 100) / 100;
        },
        formatMoney(n) {
            return (parseFloat(n) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        get subtotal() {
            return Math.round(this.items.reduce((sum, item) => sum + this.itemAmount(item), 0) * 100) / 100;
        },
        get vatAmount() {
            const r = parseFloat(this.vatRate) || 0;
            return Math.round((this.subtotal * r / 100) * 100) / 100;
        },
        get total() {
            return Math.round((this.subtotal + this.vatAmount) * 100) / 100;
        },
        get itemsJson() {
            return JSON.stringify(this.items);
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
