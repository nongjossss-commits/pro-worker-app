@extends('layouts.app')

@section('content')
<div class="container py-4" x-data="{
        documentType: '{{ old('document_type', 'quotation') }}',
        get employerRequired() { return this.documentType === 'invoice'; }
    }">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">{{ __('Create Manual Bill') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('finance.store') }}" method="POST"
                          @submit="if (employerRequired && !$el.querySelector('input[name=employer_id]').value) { $event.preventDefault(); appAlert(@js(__('Please pick an employer for this document type.')), 'warning'); }">
                        @csrf

                        <div class="mb-3">
                            <label for="document_type" class="form-label">{{ __('Document Type') }} <span class="text-danger">*</span></label>
                            <select name="document_type" id="document_type" class="form-select" x-model="documentType" required>
                                @foreach($documentTypes as $key => $label)
                                    <option value="{{ $key }}" {{ old('document_type', 'quotation') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('Choose what kind of document this will be. More settings on the next screen match this choice.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label for="employer_id" class="form-label">
                                {{ __('Select Employer') }}
                                <span class="text-danger" x-show="employerRequired" x-cloak>*</span>
                                <span class="text-muted small" x-show="!employerRequired" x-cloak>({{ __('Optional') }})</span>
                            </label>
                            {{-- Search-as-you-type employer picker (or open the list and scroll) —
                                 FinancialHubController::manualEmployerOptions. Picking only fills the
                                 field; the bill is created by the button below. --}}
                            <div class="position-relative mb-picker" x-data="manualEmployerPicker({
                                    url: @js(route('finance.create.employer_options')),
                                    selected: @js($selectedEmployer ? ['id' => $selectedEmployer->id, 'label' => trim(($selectedEmployer->employerNameTh ?: $selectedEmployer->employerNameEn) . ($selectedEmployer->name_suffix ? ' ' . $selectedEmployer->name_suffix : '')), 'sub' => $selectedEmployer->employerNameEn && $selectedEmployer->employerNameEn !== $selectedEmployer->employerNameTh ? $selectedEmployer->employerNameEn : ''] : null),
                                 })" @click.outside="open = false" @keydown.escape="open = false">
                                <input type="hidden" name="employer_id" :value="selected ? selected.id : ''">

                                <div x-show="selected" x-cloak>{{-- (wrapper: Bootstrap's d-flex !important would override x-show) --}}
                                <div class="d-flex align-items-center gap-2 border rounded px-3 py-2 bg-body-tertiary">
                                    <i class="bi bi-building text-primary"></i>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-semibold text-truncate" x-text="selected && selected.label"></div>
                                        <div class="small text-muted text-truncate" x-show="selected && selected.sub" x-text="selected && selected.sub"></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="clear()" title="{{ __('Change employer') }}"><i class="bi bi-x-lg"></i></button>
                                </div>
                                </div>

                                <div x-show="!selected" class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="employer_search" x-ref="search" autocomplete="off"
                                           placeholder="{{ __('Type employer name, ID, tax ID or job owner — or open the list') }}"
                                           x-model="q" @input.debounce.300ms="load(true)" @focus="openList()"
                                           @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                                           @keydown.enter.prevent="pick(items[active])">
                                    <button type="button" class="btn btn-outline-secondary" @click="open ? (open = false) : openList()" title="{{ __('Show all employers') }}">
                                        <i class="bi" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                    </button>
                                </div>

                                <div x-show="open && !selected" x-cloak class="mb-picker-list border rounded shadow bg-body" @scroll="onScroll($event)">
                                    <template x-for="(it, i) in items" :key="it.id">
                                        <button type="button" class="mb-picker-item" :class="{ active: i === active }"
                                                @mouseenter="active = i" @click="pick(it)">
                                            <div class="fw-semibold text-truncate" x-text="it.label"></div>
                                            <div class="small text-muted text-truncate" x-show="it.sub" x-text="it.sub"></div>
                                        </button>
                                    </template>
                                    <div class="px-3 py-2 small text-muted text-center" x-show="loading"><span class="spinner-border spinner-border-sm me-1"></span>{{ __('Loading...') }}</div>
                                    <div class="px-3 py-3 small text-muted text-center" x-show="!loading && !items.length">{{ __('No matching employers') }}</div>
                                </div>
                            </div>
                            <div class="form-text" x-show="employerRequired" x-cloak>{{ __('The bill will be linked to this employer.') }}</div>
                            <div class="form-text" x-show="!employerRequired" x-cloak>{{ __('Leave blank for a per-unit quotation with no committed headcount yet — you can still pick an employer if you already know who it\'s for.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">{{ __('Bill Description') }}</label>
                            <input type="text" class="form-control" id="description" name="description" value="{{ old('description') }}" placeholder="{{ __('e.g. Service Fee for X') }}">
                            <div class="form-text">{{ __('Leave blank to use default (Manual Bill - Date).') }}</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="bill_date" class="form-label">{{ __('Bill Date') }}</label>
                                <input type="date" class="form-control" id="bill_date" name="bill_date" value="{{ old('bill_date', date('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">{{ __('Initial Amount (Optional)') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">฿</span>
                                    <input type="number" step="0.01" class="form-control" id="amount" name="amount" value="{{ old('amount') }}" placeholder="0.00">
                                </div>
                                <div class="form-text">{{ __('If entered, a pending transaction will be created immediately.') }}</div>
                            </div>
                        </div>

                        @if(isset($selectedEmployees) && $selectedEmployees->count() > 0)
                        <div class="mb-3">
                            <label class="form-label">{{ __('Selected Employees') }} <span class="badge bg-info text-white">{{ $selectedEmployees->count() }}</span></label>
                            <div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                <ul class="list-unstyled mb-0">
                                    @foreach($selectedEmployees as $emp)
                                        <li class="mb-1">
                                            <i class="bi bi-person me-2 text-secondary"></i>
                                            {{ $emp->employeeNameTh ?? $emp->employeeNameEn }}
                                            @if($emp->employer_employee_id)
                                                <span class="text-muted small">({{ $emp->employer_employee_id }})</span>
                                            @endif
                                            <input type="hidden" name="employee_ids[]" value="{{ $emp->id }}">
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="form-text text-success"><i class="bi bi-info-circle me-1"></i>{{ __('These employees will be automatically added to the bill.') }}</div>
                        </div>
                        @endif

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('finance.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> {{ __('Create Bill & Continue') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .mb-picker .min-w-0 { min-width: 0; }
    .mb-picker-list { position: absolute; z-index: 1050; left: 0; right: 0; top: calc(100% + 4px); max-height: 320px; overflow-y: auto; }
    .mb-picker-item { display: block; width: 100%; text-align: left; border: 0; border-bottom: 1px solid var(--bs-border-color-translucent); background: transparent; padding: .5rem .85rem; color: var(--bs-body-color); }
    .mb-picker-item:last-of-type { border-bottom: 0; }
    .mb-picker-item.active, .mb-picker-item:hover { background: var(--bs-primary-bg-subtle); }
</style>
<script>
    // Employer picker of "Create Manual Bill" — results 30 at a time, more on scroll.
    function manualEmployerPicker({ url, selected }) {
        return {
            url, selected, q: '', items: [], open: false, loading: false, active: -1, page: 1, more: false, seq: 0,
            openList() { this.open = true; if (!this.items.length) this.load(true); },
            load(reset) {
                if (reset) { this.page = 1; this.items = []; this.active = -1; }
                this.open = true; this.loading = true;
                const mySeq = ++this.seq;
                fetch(this.url + '?' + new URLSearchParams({ q: this.q.trim(), page: this.page }), { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(d => {
                        if (mySeq !== this.seq) return; // an older request
                        this.items = this.items.concat(d.items || []);
                        this.more = !!d.more;
                        if (this.active < 0 && this.items.length) this.active = 0;
                    })
                    .finally(() => { if (mySeq === this.seq) this.loading = false; });
            },
            onScroll(e) {
                const el = e.target;
                if (this.more && !this.loading && el.scrollTop + el.clientHeight >= el.scrollHeight - 40) { this.page++; this.load(false); }
            },
            move(d) {
                if (!this.open) return this.openList();
                if (!this.items.length) return;
                this.active = Math.max(0, Math.min(this.items.length - 1, this.active + d));
                this.$nextTick(() => this.$el.querySelector('.mb-picker-item.active')?.scrollIntoView({ block: 'nearest' }));
            },
            pick(it) { if (!it) return; this.selected = it; this.open = false; },
            clear() { this.selected = null; this.q = ''; this.$nextTick(() => this.$refs.search.focus()); },
        };
    }
</script>
@endsection
