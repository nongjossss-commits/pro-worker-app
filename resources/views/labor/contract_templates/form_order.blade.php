{{--
    "จัดลำดับฟอร์ม" — Super Admin only. Lets the fill-in ORDER of the
    issuance form (labor/contracts/_fields.blade.php) be freely rearranged
    (drag up/down, insert anywhere) completely independent of each field's
    physical page/x/y position on the PDF canvas, which stays the Template
    Builder's job (see contract_templates/builder.blade.php). Also fixes
    the "two Service Fee groups showed an identical label" bug, since a
    distinct label can be given to each row right here, and lets each
    row's WIDTH be narrowed (Full/Half/One-third/One-quarter) so short
    fields (a Service Fee numeral, a Nationality dropdown) can sit side by
    side on one line instead of always taking a full row.

    No SortableJS/drag-reorder library is used — plain native HTML5
    drag-and-drop, same technique the Template Builder itself already uses
    for dragging tools onto the document, so no new dependency is added.

    Expects: $template, $items (from
    LaborContractTemplateController::formOrder() — already formOrder-sorted,
    each carrying a pre-computed `displayLabel`).
--}}
@extends('labor.layout')

@section('title', __('Order Form Fields') . ' — ' . $template->name)

@section('content')
<div class="container-fluid" x-data="proworkerFormOrder()">
    <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">{{ __('Order Form Fields') }} (จัดลำดับฟอร์มเบิกสัญญา)</h4>
            <p class="text-muted small mb-0">
                {{ $template->name }} —
                {{ __('Drag to reorder, and narrow a field\'s width so short fields can share a line. This only changes the issuance form\'s layout — it never moves anything on the printed document.') }}
            </p>
        </div>
        <a href="{{ route('labor.contract-templates.builder', $template) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Builder') }}
        </a>
    </div>

    <div class="alert alert-success" x-show="saved" x-transition x-cloak>{{ __('Saved.') }}</div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Which fields are the employer's name / ID number → shown and searched in Contract History
         (ProWorkerContractEmployerFields). "Guess" picks by label until a field is chosen. --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-1"><i class="bi bi-search me-1"></i>{{ __('Employer fields for Contract History search') }}</h6>
            <p class="small text-muted mb-3">{{ __('Pick the form fields that hold the employer\'s name and ID number. Contract History shows them in the Employer column and its search box finds contracts by either one. Saving also updates contracts already issued from this template (search data only — the documents are not changed).') }}</p>
            <form method="POST" action="{{ route('labor.contract-templates.employer-fields.update', $template) }}" class="row g-2 align-items-end">
                @csrf
                @method('PUT')
                @foreach(['name' => __('Employer name field'), 'ref' => __('Employer ID number field')] as $role => $label)
                    @php
                        $metaKey = $role === 'name' ? \App\Services\ProWorkerContractEmployerFields::NAME_KEY : \App\Services\ProWorkerContractEmployerFields::REF_KEY;
                        $saved = ($template->meta_data ?? [])[$metaKey] ?? 'auto';
                        $guess = $employerKeys[$role . 'Auto'] ? $employerKeys[$role] : app(\App\Services\ProWorkerContractEmployerFields::class)->guess($employerTextFields, $role);
                    @endphp
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold">{{ $label }}</label>
                        <select name="{{ $role }}_field" class="form-select form-select-sm">
                            <option value="auto" @selected($saved === 'auto')>
                                {{ __('Guess from the label') }}: {{ $guess ? ($employerTextFields[$guess] ?? $guess) : __('(none found)') }}
                            </option>
                            @foreach($employerTextFields as $key => $fieldLabel)
                                <option value="{{ $key }}" @selected($saved === $key)>{{ $fieldLabel }}</option>
                            @endforeach
                            <option value="{{ \App\Services\ProWorkerContractEmployerFields::NONE }}" @selected($saved === \App\Services\ProWorkerContractEmployerFields::NONE)>— {{ __('Not used') }} —</option>
                        </select>
                    </div>
                @endforeach
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-save me-1"></i>{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            @if($items->isEmpty())
                <p class="text-muted mb-0">{{ __('This template has no fillable fields yet. Add some in the Builder first.') }}</p>
            @else
                <p class="small text-muted">{{ __('Field') }} <span x-text="rows.length"></span></p>
                <div class="list-group">
                    <template x-for="(row, index) in rows" :key="row._uid">
                        <div class="list-group-item d-flex align-items-center gap-2"
                             draggable="true"
                             @dragstart="dragIndex = index"
                             @dragover.prevent
                             @drop.prevent="onDrop(index)"
                             :class="{ 'bg-light': dragIndex === index }">
                            <i class="bi bi-grip-vertical text-muted" style="cursor: grab;"></i>
                            <span class="badge text-bg-secondary text-start" style="min-width: 130px;" x-text="kindLabel(row.kind)"></span>
                            <input type="text" class="form-control form-control-sm" x-model="row.label"
                                   placeholder="{{ __('Label shown to whoever fills this in') }}">
                            <select class="form-select form-select-sm" style="width: 160px; flex: 0 0 auto;" x-model.number="row.width">
                                <option value="12">{{ __('Full width') }} (100%)</option>
                                <option value="6">{{ __('Half width') }} (50%)</option>
                                <option value="4">{{ __('One-third width') }} (33%)</option>
                                <option value="3">{{ __('One-quarter width') }} (25%)</option>
                            </select>
                        </div>
                    </template>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn btn-primary btn-sm" @click="save()" :disabled="isSaving">
                        <span x-show="isSaving" class="spinner-border spinner-border-sm me-1"></span>{{ __('Save Order') }}
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

@php
    // Built as a plain array first (not inlined into @json() below) —
    // Blade's @json() directive parser gets confused by a nested array
    // literal inside an arrow function's body and mis-scans where the
    // directive ends.
    $rowsForJs = [];
    foreach ($items as $i => $item) {
        $rowsForJs[] = [
            '_uid' => $i,
            'kind' => $item['kind'],
            'key' => $item['key'] ?? null,
            'groupId' => $item['groupId'] ?? null,
            'label' => $item['displayLabel'],
            'width' => $item['formWidth'] ?? 12,
        ];
    }
@endphp
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('proworkerFormOrder', () => ({
        rows: @json($rowsForJs),
        dragIndex: null,
        isSaving: false,
        saved: false,

        kindLabel(kind) {
            return {
                text: '{{ __('Text Field') }}',
                worker_count: '{{ __('Worker Count') }}',
                address: '{{ __('Address') }}',
                business_type: '{{ __('Business Type') }}',
                nationality: '{{ __('Nationality') }}',
                fee: '{{ __('Service Fee') }}',
            }[kind] || kind;
        },

        // Native HTML5 drag-and-drop: dragIndex tracks the row picked up
        // (dragstart), and dropping onto another row's dragover-armed
        // target splices it out and reinserts it at that row's position —
        // the same "insert anywhere, not just swap two rows" behavior the
        // user asked for.
        onDrop(targetIndex) {
            if (this.dragIndex === null || this.dragIndex === targetIndex) {
                this.dragIndex = null;
                return;
            }
            const moved = this.rows.splice(this.dragIndex, 1)[0];
            this.rows.splice(targetIndex, 0, moved);
            this.dragIndex = null;
        },

        async save() {
            this.isSaving = true;
            this.saved = false;
            try {
                const order = this.rows.map(r => ({ kind: r.kind, key: r.key, groupId: r.groupId, label: r.label, width: r.width }));
                const response = await fetch('{{ route('labor.contract-templates.form-order.update', $template) }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ order }),
                });
                if (!response.ok) throw new Error('Save failed');
                this.saved = true;
                setTimeout(() => { this.saved = false; }, 2500);
            } catch (error) {
                console.error(error);
                alert('{{ __('Error saving order') }}: ' + error.message);
            } finally {
                this.isSaving = false;
            }
        },
    }));
});
</script>
@endpush
@endsection
