{{-- Reusable form partial — used by both Income and Expense modals
     Alpine x-data scope: ledgerForm({ type, accounts, categories }) --}}
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">{{ __('Date') }} *</label>
        <input type="date" name="entry_date" class="form-control" required value="{{ now()->format('Y-m-d') }}">
    </div>
    <div class="col-md-8">
        <label class="form-label">{{ __('Bank Account') }} *</label>
        <select name="bank_account_id" class="form-select" required x-model="bankAccountId" @change="onAccountChange">
            <option value="">— {{ __('Select account') }} —</option>
            <template x-for="acc in accounts" :key="acc.id">
                <option :value="acc.id" x-text="(acc.account_type === 'personal' ? '👤 ' : '🏢 ') + acc.bank_name + ' — ' + (acc.account_name || acc.account_number || '')"></option>
            </template>
        </select>
        <div class="form-text" x-show="isPersonal()">
            <span class="text-secondary"><i class="bi bi-info-circle"></i> {{ __('Personal account: off-book — VAT/WHT will be skipped.') }}</span>
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Category') }}</label>
        <select name="category_id" class="form-select" x-model="categoryId" @change="onCategoryChange">
            <option value="">— {{ __('Select category') }} —</option>
            <template x-for="cat in categories" :key="cat.id">
                <option :value="cat.id" :data-vat="cat.default_vat_treatment" :data-wht-type="cat.default_wht_type" :data-wht-rate="cat.default_wht_rate" x-text="cat.name"></option>
            </template>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Counterparty Name') }}</label>
        <input type="text" name="counterparty_name" class="form-control" placeholder="{{ __('e.g., Customer / Supplier name') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Counterparty Tax ID') }}</label>
        <input type="text" name="counterparty_tax_id" class="form-control" maxlength="15" placeholder="13-digit">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Gross Amount') }} *</label>
        <input type="number" step="0.01" min="0" name="gross_amount" class="form-control" required x-model.number="gross" @input="recalculate">
    </div>

    {{-- Tax section — disabled when personal --}}
    <div class="col-12">
        <div class="border rounded p-3 bg-light" :class="{ 'opacity-50': isPersonal() }">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong><i class="bi bi-receipt me-1"></i> {{ __('Tax Settings') }}</strong>
                <small class="text-muted" x-show="isPersonal()">{{ __('Disabled for personal account') }}</small>
            </div>
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small">{{ __('VAT Treatment') }}</label>
                    <select name="vat_treatment" class="form-select form-select-sm" x-model="vatTreatment" @change="recalculate" :disabled="isPersonal()">
                        <option value="none">None</option>
                        <option value="taxable">Taxable</option>
                        <option value="exempt">Exempt</option>
                        <option value="zero_rate">Zero-rate</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ __('VAT Rate %') }}</label>
                    <input type="number" step="0.01" name="vat_rate" class="form-control form-control-sm" x-model.number="vatRate" @input="recalculate" :disabled="isPersonal() || vatTreatment !== 'taxable'">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">
                        <input type="checkbox" name="vat_inclusive" value="1" x-model="vatInclusive" @change="recalculate" :disabled="isPersonal()">
                        {{ __('VAT included') }}
                    </label>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ __('WHT Type') }}</label>
                    <select name="wht_type" class="form-select form-select-sm" x-model="whtType" @change="recalculate" :disabled="isPersonal()">
                        <option value="none">None</option>
                        <option value="pnd3">ภ.ง.ด.3</option>
                        <option value="pnd53">ภ.ง.ด.53</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ __('WHT Rate %') }}</label>
                    <input type="number" step="0.01" name="wht_rate" class="form-control form-control-sm" x-model.number="whtRate" @input="recalculate" :disabled="isPersonal() || whtType === 'none'">
                </div>
            </div>

            {{-- Live preview --}}
            <div class="row g-2 mt-2 small">
                <div class="col-md-3"><div class="text-muted">Subtotal</div><div class="fw-bold" x-text="fmt(preview.subtotal)"></div></div>
                <div class="col-md-3"><div class="text-muted">VAT</div><div class="fw-bold text-info" x-text="fmt(preview.vat_amount)"></div></div>
                <div class="col-md-3"><div class="text-muted">WHT</div><div class="fw-bold text-warning" x-text="fmt(preview.wht_amount)"></div></div>
                <div class="col-md-3"><div class="text-muted">{{ __('Net (to bank)') }}</div><div class="fw-bold fs-6 text-success" x-text="fmt(preview.net_amount)"></div></div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Tax Invoice No.') }}</label>
        <input type="text" name="tax_invoice_no" class="form-control" maxlength="50" :disabled="isPersonal()">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Tax Invoice Date') }}</label>
        <input type="date" name="tax_invoice_date" class="form-control" :disabled="isPersonal()">
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Receipt / Slip') }}</label>
        <input type="file" name="receipt" class="form-control" accept="image/*,.pdf">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Other Attachments') }}</label>
        <input type="file" name="attachments[]" class="form-control" multiple accept="image/*,.pdf">
    </div>

    <div class="col-12">
        <label class="form-label">{{ __('Description') }}</label>
        <textarea name="description" class="form-control" rows="2"></textarea>
    </div>
    <div class="col-12">
        <label class="form-label">{{ __('Notes') }}</label>
        <textarea name="notes" class="form-control" rows="2"></textarea>
    </div>

    {{-- Phase 2.1: auto-generate linked documents --}}
    <div class="col-12">
        <div class="border-top pt-3">
            <div class="form-check" x-show="type === 'income' && vatTreatment === 'taxable' && !isPersonal()">
                <input type="checkbox" name="generate_tax_invoice" value="1" class="form-check-input" id="genInvoice_{{ $type }}">
                <label class="form-check-label" for="genInvoice_{{ $type }}">
                    <i class="bi bi-receipt"></i> {{ __('Generate Tax Invoice (ใบกำกับภาษี) automatically') }}
                    <small class="text-muted d-block">{{ __('Requires bank account linked to a biller profile.') }}</small>
                </label>
            </div>
            <div class="form-check mt-2" x-show="whtType !== 'none' && whtRate > 0 && !isPersonal()">
                <input type="checkbox" name="generate_wht_cert" value="1" class="form-check-input" id="genWht_{{ $type }}">
                <label class="form-check-label" for="genWht_{{ $type }}">
                    <i class="bi bi-file-earmark-text"></i>
                    <span x-show="type === 'expense'">{{ __('Generate WHT Certificate (Issued — ที่เราออกให้ supplier)') }}</span>
                    <span x-show="type === 'income'">{{ __('Generate WHT Certificate (Received — ที่ลูกค้าออกให้เรา)') }}</span>
                </label>
            </div>
        </div>
    </div>
</div>
