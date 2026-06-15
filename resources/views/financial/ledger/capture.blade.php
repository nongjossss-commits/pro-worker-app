@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="quickCapture({
    extractUrl: '{{ route('finance.ledger.capture.extract') }}',
    csrf: '{{ csrf_token() }}',
    accounts: {{ Js::from($accounts) }},
    incomeCategories: {{ Js::from($incomeCategories) }},
    expenseCategories: {{ Js::from($expenseCategories) }},
})">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.ledger.index') }}" class="text-decoration-none small">&larr; {{ __('Ledger') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">
                <i class="bi bi-magic me-1"></i> {{ __('Quick Capture') }}
            </h1>
            <div class="small text-muted mt-1">
                {{ __('Upload a receipt or paste a quick note — the AI engine pre-fills the ledger form for you.') }}
            </div>
        </div>
        <div class="text-end small text-muted">
            <div>{{ __('AI Engine') }}: <strong>{{ $engineName }}</strong></div>
            @if(!$aiReady)
                <span class="badge bg-warning-subtle text-warning-emphasis">{{ __('Manual mode') }}</span>
            @else
                <span class="badge bg-success-subtle text-success-emphasis">{{ __('Ready') }}</span>
            @endif
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Step 1: Capture input --}}
    <div class="card shadow mb-3">
        <div class="card-header"><strong>1. {{ __('Capture') }}</strong></div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link" :class="{ 'active': mode === 'image' }" href="#" @click.prevent="mode = 'image'">
                        <i class="bi bi-camera"></i> {{ __('Upload Receipt') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{ 'active': mode === 'text' }" href="#" @click.prevent="mode = 'text'">
                        <i class="bi bi-pencil-square"></i> {{ __('Type Description') }}
                    </a>
                </li>
            </ul>

            <div x-show="mode === 'image'">
                <input type="file" class="form-control" accept="image/*,.pdf" @change="onImagePick($event)">
                <div class="form-text">{{ __('Receipt / slip / invoice photo. PDF also accepted.') }}</div>
                <template x-if="imagePreview">
                    <div class="mt-3 text-center">
                        <img :src="imagePreview" class="img-thumbnail" style="max-height: 240px;">
                    </div>
                </template>
            </div>

            <div x-show="mode === 'text'">
                <textarea class="form-control" rows="3" x-model="text" placeholder="{{ __('e.g. จ่ายค่าโทรศัพท์ AIS 599 บาท เมื่อวานนี้') }}"></textarea>
            </div>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" @click="resetCapture">
                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('Reset') }}
                </button>
                <button type="button" class="btn btn-primary" :disabled="extracting" @click="runExtract">
                    <span x-show="!extracting"><i class="bi bi-magic"></i> {{ __('Extract & Fill') }}</span>
                    <span x-show="extracting"><span class="spinner-border spinner-border-sm me-1"></span> {{ __('Extracting…') }}</span>
                </button>
            </div>

            <template x-if="extractInfo">
                <div class="alert mt-3 mb-0" :class="extractSucceeded ? 'alert-success' : 'alert-info'">
                    <i class="bi bi-info-circle"></i> <span x-text="extractInfo"></span>
                </div>
            </template>
        </div>
    </div>

    {{-- Step 2: Confirm & save --}}
    <form action="{{ route('finance.ledger.capture.save') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="ai_source" :value="aiSource">
        <input type="hidden" name="ai_confidence" :value="aiConfidence">
        <input type="hidden" name="ai_extracted_data" :value="aiExtractedJson">

        <div class="card shadow mb-3">
            <div class="card-header"><strong>2. {{ __('Confirm & Save') }}</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Date') }} *</label>
                        <input type="date" name="entry_date" class="form-control" required x-model="form.entry_date">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Type') }} *</label>
                        <select name="type" class="form-select" required x-model="form.type" @change="onTypeChange">
                            <option value="expense">{{ __('Expense') }}</option>
                            <option value="income">{{ __('Income') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Bank Account') }} *</label>
                        <select name="bank_account_id" class="form-select" required x-model="form.bank_account_id" @change="onAccountChange">
                            <option value="">— {{ __('Select account') }} —</option>
                            <template x-for="acc in accounts" :key="acc.id">
                                <option :value="acc.id" x-text="(acc.account_type === 'personal' ? '👤 ' : '🏢 ') + acc.bank_name + ' — ' + (acc.account_name || acc.account_number || '')"></option>
                            </template>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="category_id" class="form-select" x-model="form.category_id" @change="onCategoryChange">
                            <option value="">— {{ __('None') }} —</option>
                            <template x-for="cat in activeCategories" :key="cat.id">
                                <option :value="cat.id" x-text="cat.name"></option>
                            </template>
                        </select>
                        <input type="hidden" name="category_type" :value="form.type">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Counterparty') }}</label>
                        <input type="text" name="counterparty_name" class="form-control" x-model="form.counterparty_name">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ __('Counterparty Tax ID') }}</label>
                        <input type="text" name="counterparty_tax_id" class="form-control" maxlength="15" x-model="form.counterparty_tax_id">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Gross Amount') }} *</label>
                        <input type="number" step="0.01" min="0" name="gross_amount" class="form-control" required x-model.number="form.gross_amount">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small">{{ __('VAT Treatment') }}</label>
                        <select name="vat_treatment" class="form-select form-select-sm" x-model="form.vat_treatment" :disabled="isPersonal()">
                            <option value="none">None</option>
                            <option value="taxable">Taxable</option>
                            <option value="exempt">Exempt</option>
                            <option value="zero_rate">Zero-rate</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">{{ __('VAT Rate %') }}</label>
                        <input type="number" step="0.01" name="vat_rate" class="form-control form-control-sm" x-model.number="form.vat_rate" :disabled="isPersonal() || form.vat_treatment !== 'taxable'">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small">{{ __('WHT Type') }}</label>
                        <select name="wht_type" class="form-select form-select-sm" x-model="form.wht_type" :disabled="isPersonal()">
                            <option value="none">None</option>
                            <option value="pnd3">ภ.ง.ด.3</option>
                            <option value="pnd53">ภ.ง.ด.53</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">{{ __('WHT Rate %') }}</label>
                        <input type="number" step="0.01" name="wht_rate" class="form-control form-control-sm" x-model.number="form.wht_rate" :disabled="isPersonal() || form.wht_type === 'none'">
                    </div>

                    <div class="col-12">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="2" x-model="form.description"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" x-model="form.notes"></textarea>
                    </div>

                    {{-- Attach captured image as receipt --}}
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Receipt / Slip (attach captured image)') }}</label>
                        <input type="file" name="receipt" class="form-control" accept="image/*,.pdf" x-ref="receiptInput">
                        <div class="form-text">{{ __('If you uploaded an image above, click "Use captured image" to attach it.') }}</div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" @click="reuseCapturedReceipt" x-show="capturedFile">
                            <i class="bi bi-arrow-down"></i> {{ __('Use captured image') }}
                        </button>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Other Attachments') }}</label>
                        <input type="file" name="attachments[]" class="form-control" multiple accept="image/*,.pdf">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('finance.ledger.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle"></i> {{ __('Save Ledger Entry') }}
            </button>
        </div>
    </form>
</div>

<script src="{{ asset('js/quick-capture.js?v=' . filemtime(public_path('js/quick-capture.js'))) }}"></script>
@endsection
