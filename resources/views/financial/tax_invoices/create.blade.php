@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="taxInvoiceForm({
    profiles: {{ Js::from($profiles->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'bank_accounts' => $p->bankAccounts])) }},
    thaiBanks: {{ Js::from($thaiBanks) }},
})">
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
                        <select name="issuer_profile_id" class="form-select" required x-model="issuerProfileId" @change="onIssuerChange">
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

        {{-- Payment Methods (multi-select) — rendered in the bottom-left of the PDF.
             paymentMethods is an array of objects; we serialize it to a hidden
             input on submit so the controller gets a clean JSON payload. --}}
        <input type="hidden" name="payment_methods" :value="paymentMethodsJson">
        <div class="card shadow mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-credit-card-2-front me-1"></i> {{ __('Payment Methods') }}</strong>
                <small class="text-muted">{{ __('Tick all channels the customer may use to pay this invoice.') }}</small>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    {{-- Cash --}}
                    <div class="col-md-6">
                        <div class="form-check border rounded p-2">
                            <input type="checkbox" class="form-check-input" id="pmCash" x-model="usingCash">
                            <label for="pmCash" class="form-check-label fw-bold">
                                <i class="bi bi-cash-stack me-1"></i> {{ __('Cash') }}
                            </label>
                        </div>
                    </div>

                    {{-- PromptPay --}}
                    <div class="col-md-6">
                        <div class="form-check border rounded p-2">
                            <input type="checkbox" class="form-check-input" id="pmPromptPay" x-model="usingPromptPay">
                            <label for="pmPromptPay" class="form-check-label fw-bold">
                                <i class="bi bi-qr-code me-1"></i> {{ __('PromptPay') }}
                            </label>
                            <div x-show="usingPromptPay" class="mt-2">
                                <input type="text" class="form-control form-control-sm" x-model="promptPayId" :placeholder="promptPayPlaceholder()">
                                <div class="form-text small" x-show="profilePromptPayAccounts.length > 0">
                                    {{ __('Pre-filled from profile.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bank Transfer (one or more accounts) --}}
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="pmTransfer" x-model="usingTransfer" @change="onUsingTransferChange">
                                <label for="pmTransfer" class="form-check-label fw-bold">
                                    <i class="bi bi-bank2 me-1"></i> {{ __('Bank Transfer') }}
                                </label>
                            </div>

                            <div x-show="usingTransfer">
                                {{-- Hint when no issuer profile picked yet --}}
                                <div x-show="!issuerProfileId" class="alert alert-warning small py-2 mb-2">
                                    {{ __('Please select an issuer profile first to load its bank accounts.') }}
                                </div>

                                {{-- Selected transfer accounts (chip list) --}}
                                <template x-for="(t, idx) in transferList" :key="idx">
                                    <div class="d-flex align-items-center gap-2 border rounded p-2 mb-2 bg-light">
                                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                              :style="`background:${transferBadge(t).color}; width:32px; height:32px; font-size:11px;`"
                                              x-text="transferBadge(t).initial"></span>
                                        <div class="small flex-grow-1" style="min-width:0;">
                                            <div class="fw-bold text-truncate" x-text="t.bank_name || '—'"></div>
                                            <div class="text-muted text-truncate">
                                                <span x-text="t.account_name || ''"></span>
                                                <span x-show="t.account_name && t.account_number"> · </span>
                                                <span x-text="t.account_number || ''"></span>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" @click="removeTransfer(idx)"><i class="bi bi-x"></i></button>
                                    </div>
                                </template>

                                {{-- "Add account" button — shown when picker is closed and at least one already added --}}
                                <div x-show="!transferPickerOpen && !showCustomTransfer && transferList.length > 0" class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="openTransferPicker">
                                        <i class="bi bi-plus-circle"></i> {{ __('Add another bank account') }}
                                    </button>
                                </div>

                                {{-- Picker dropdown — auto-opens when usingTransfer first toggles on --}}
                                <div x-show="transferPickerOpen" class="border rounded p-2 mt-2 bg-white">
                                    <div class="d-flex gap-2 align-items-center mb-2">
                                        <input type="text" class="form-control form-control-sm flex-grow-1" x-model="transferSearch" placeholder="{{ __('Search bank...') }}" autofocus>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="openCustomTransfer">
                                            <i class="bi bi-pencil-square"></i> {{ __('Custom') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="closeTransferPicker" title="{{ __('Close') }}">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                    <div style="max-height:260px; overflow-y:auto;">
                                        {{-- Profile accounts first --}}
                                        <template x-if="filteredProfileAccounts.length > 0">
                                            <div>
                                                <div class="px-2 py-1 small text-muted bg-light fw-bold">{{ __('From this profile') }}</div>
                                                <template x-for="acc in filteredProfileAccounts" :key="'p'+acc.id">
                                                    <div class="d-flex align-items-center gap-2 p-2 bank-pick" style="cursor:pointer;" @click="addProfileTransfer(acc)">
                                                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold"
                                                              :style="`background:${transferBadge(acc).color}; width:28px; height:28px; font-size:11px;`"
                                                              x-text="transferBadge(acc).initial"></span>
                                                        <div class="small flex-grow-1" style="min-width:0;">
                                                            <div class="fw-bold text-truncate" x-text="acc.bank_name"></div>
                                                            <div class="text-muted text-truncate" x-text="(acc.account_name || '') + ' · ' + (acc.account_number || '')"></div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        {{-- No profile accounts hint --}}
                                        <div x-show="issuerProfileId && profileTransferAccounts.length === 0" class="px-2 py-2 small text-muted">
                                            {{ __('No bank accounts on this profile. Use Custom or pick a preset bank below.') }}
                                        </div>

                                        {{-- Preset Thai banks --}}
                                        <div class="px-2 py-1 small text-muted bg-light fw-bold mt-1">{{ __('Other Thai banks') }}</div>
                                        <template x-for="b in filteredPresetBanks" :key="'b'+b.code">
                                            <div class="d-flex align-items-center gap-2 p-2 bank-pick" style="cursor:pointer;" @click="addPresetTransfer(b)">
                                                <span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold"
                                                      :style="`background:${b.color}; width:28px; height:28px; font-size:11px;`"
                                                      x-text="b.initial"></span>
                                                <span class="small flex-grow-1" x-text="b.name_th"></span>
                                                <span class="small text-muted" x-text="b.name_en"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Custom transfer entry inline form --}}
                                <div x-show="showCustomTransfer" class="border rounded p-2 mt-2 bg-white">
                                    <div class="small fw-bold mb-1">{{ __('Custom bank account') }}</div>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <input type="text" class="form-control form-control-sm" x-model="customTransfer.bank_name" placeholder="{{ __('Bank name') }} *">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" class="form-control form-control-sm" x-model="customTransfer.account_name" placeholder="{{ __('Account name') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control form-control-sm" x-model="customTransfer.account_number" placeholder="{{ __('Account number') }}">
                                        </div>
                                        <div class="col-md-1 d-grid">
                                            <button type="button" class="btn btn-sm btn-success" @click="addCustomTransfer" title="{{ __('Add') }}"><i class="bi bi-check-lg"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Other --}}
                    <div class="col-12">
                        <div class="form-check border rounded p-2">
                            <input type="checkbox" class="form-check-input" id="pmOther" x-model="usingOther">
                            <label for="pmOther" class="form-check-label fw-bold">
                                <i class="bi bi-three-dots me-1"></i> {{ __('Other') }}
                            </label>
                            <div x-show="usingOther" class="mt-2">
                                <input type="text" class="form-control form-control-sm" x-model="otherNote" placeholder="{{ __('Describe the payment method...') }}">
                            </div>
                        </div>
                    </div>
                </div>
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

<style>
    .bank-pick:hover { background:#f5f5f5; }
</style>
<script>
function taxInvoiceForm(opts = {}) {
    const profiles  = Array.isArray(opts.profiles)  ? opts.profiles  : [];
    const thaiBanks = Array.isArray(opts.thaiBanks) ? opts.thaiBanks : [];

    return {
        // --- Header / amounts ---
        profiles,
        thaiBanks,
        issuerProfileId: '',
        subtotal: 0,
        vatRate: 7,
        vatAmount: 0,
        total: 0,

        // --- Payment method state ---
        usingCash: false,
        usingTransfer: false,
        usingPromptPay: false,
        usingOther: false,
        promptPayId: '',
        otherNote: '',
        transferList: [],          // [{bank_code, bank_name, account_name, account_number, source}]
        transferSearch: '',
        transferPickerOpen: false,
        showCustomTransfer: false,
        customTransfer: { bank_name: '', account_name: '', account_number: '' },

        recalculate() {
            const s = parseFloat(this.subtotal) || 0;
            const r = parseFloat(this.vatRate) || 0;
            this.vatAmount = Math.round((s * r / 100) * 100) / 100;
            this.total = Math.round((s + this.vatAmount) * 100) / 100;
        },

        // --- Profile-driven derived data ---
        get currentProfile() {
            return this.profiles.find(p => String(p.id) === String(this.issuerProfileId)) || null;
        },
        get profileAccounts() {
            return this.currentProfile?.bank_accounts || [];
        },
        get profilePromptPayAccounts() {
            return this.profileAccounts.filter(a => a.bank_type === 'promptpay');
        },
        get profileTransferAccounts() {
            return this.profileAccounts.filter(a => a.bank_type !== 'promptpay');
        },
        get filteredProfileAccounts() {
            const q = this.transferSearch.toLowerCase().trim();
            if (!q) return this.profileTransferAccounts;
            return this.profileTransferAccounts.filter(a =>
                (a.bank_name || '').toLowerCase().includes(q)
                || (a.account_name || '').toLowerCase().includes(q)
                || (a.account_number || '').toLowerCase().includes(q)
            );
        },
        get filteredPresetBanks() {
            const q = this.transferSearch.toLowerCase().trim();
            if (!q) return this.thaiBanks;
            return this.thaiBanks.filter(b =>
                (b.name_th || '').toLowerCase().includes(q)
                || (b.name_en || '').toLowerCase().includes(q)
                || (b.code || '').toLowerCase().includes(q)
            );
        },

        onIssuerChange() {
            // Auto-fill PromptPay from profile if exactly one is on file.
            if (this.profilePromptPayAccounts.length === 1 && !this.promptPayId) {
                this.promptPayId = this.profilePromptPayAccounts[0].promptpay_id || '';
            }
            // Clear stale transfer selections that came from the prior profile.
            this.transferList = this.transferList.filter(t => t.source !== 'profile');
            // If user already had Bank Transfer ticked, re-open the picker so they
            // see the new profile's accounts immediately.
            if (this.usingTransfer && this.transferList.length === 0) {
                this.transferPickerOpen = true;
            }
        },

        onUsingTransferChange() {
            // When the user first ticks Bank Transfer, auto-open the picker
            // so the profile's accounts are visible without having to search.
            if (this.usingTransfer && this.transferList.length === 0) {
                this.transferPickerOpen = true;
                this.transferSearch = '';
                this.showCustomTransfer = false;
            } else if (!this.usingTransfer) {
                this.transferPickerOpen = false;
                this.showCustomTransfer = false;
            }
        },

        openTransferPicker() {
            this.transferPickerOpen = true;
            this.transferSearch = '';
            this.showCustomTransfer = false;
        },
        closeTransferPicker() {
            this.transferPickerOpen = false;
            this.transferSearch = '';
        },

        promptPayPlaceholder() {
            const p = this.profilePromptPayAccounts[0];
            return p?.promptpay_id || 'เบอร์โทร / เลขบัตรประชาชน';
        },

        // --- Badge resolver (mirrors BankAccount::getBadgeAttribute) ---
        transferBadge(item) {
            // Preset bank lookup by code
            if (item.bank_code) {
                const preset = this.thaiBanks.find(b => b.code === item.bank_code);
                if (preset) return { initial: preset.initial, color: preset.color };
            }
            // PromptPay (shouldn't appear in transfer list, but defend anyway)
            if (item.bank_type === 'promptpay') return { initial: 'PP', color: '#1d4ed8' };
            // Other / custom
            const name = item.bank_name || '?';
            return { initial: name.charAt(0).toUpperCase(), color: '#6b7280' };
        },

        // --- Transfer list mutations ---
        addProfileTransfer(acc) {
            this.transferList.push({
                bank_code: acc.bank_code || '',
                bank_name: acc.bank_name || '',
                account_name: acc.account_name || '',
                account_number: acc.account_number || '',
                source: 'profile',
            });
            this.transferSearch = '';
            this.transferPickerOpen = false;
        },
        addPresetTransfer(b) {
            this.transferList.push({
                bank_code: b.code,
                bank_name: b.name_th,
                account_name: '',
                account_number: '',
                source: 'preset',
            });
            this.transferSearch = '';
            this.transferPickerOpen = false;
        },
        openCustomTransfer() {
            this.showCustomTransfer = true;
            this.transferPickerOpen = false;
            this.customTransfer = { bank_name: '', account_name: '', account_number: '' };
        },
        addCustomTransfer() {
            if (!this.customTransfer.bank_name.trim()) return;
            this.transferList.push({
                bank_code: '',
                bank_name: this.customTransfer.bank_name.trim(),
                account_name: this.customTransfer.account_name.trim(),
                account_number: this.customTransfer.account_number.trim(),
                source: 'custom',
            });
            this.showCustomTransfer = false;
            this.customTransfer = { bank_name: '', account_name: '', account_number: '' };
        },
        removeTransfer(idx) {
            this.transferList.splice(idx, 1);
        },

        // --- Serialize for hidden input on submit ---
        get paymentMethodsJson() {
            const out = [];
            if (this.usingCash)     out.push({ type: 'cash' });
            if (this.usingTransfer) {
                this.transferList.forEach(t => out.push({
                    type: 'transfer',
                    bank_code: t.bank_code || null,
                    bank_name: t.bank_name || null,
                    account_name: t.account_name || null,
                    account_number: t.account_number || null,
                }));
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
