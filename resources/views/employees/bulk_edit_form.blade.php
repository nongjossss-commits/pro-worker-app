@extends('layouts.app')

@section('title', 'Advanced Bulk Edit')

@section('content')
{{-- CSS Fix for Tailwind + Bootstrap Collapse Conflict --}}
<style>
    /* Fix for Tailwind's .collapse { visibility: collapse } conflicting with Bootstrap */
    .accordion-collapse.collapse {
        visibility: visible !important;
    }
    .accordion-collapse.collapsing {
        visibility: visible !important;
    }

    /* Enhanced UI Styles */
    .accordion-button:not(.collapsed) {
        background-color: var(--bs-primary-light);
        color: white;
        box-shadow: inset 0 -1px 0 rgba(0,0,0,.125);
    }
    .accordion-button:not(.collapsed)::after {
        filter: brightness(0) invert(1);
    }
    .master-control-card {
        border: 2px solid var(--bs-primary);
        box-shadow: 0 4px 6px -1px rgba(249, 115, 22, 0.2);
    }
    .employee-checkbox {
        width: 1.2em;
        height: 1.2em;
        cursor: pointer;
    }
</style>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-ui-checks-grid me-2 text-primary"></i>{{ __('Advanced Bulk Edit') }}
            <span class="badge bg-secondary fs-6 ms-2">{{ count($employees) }} {{ __('Employees') }}</span>
        </h2>
        <div>
            <button type="button" class="btn btn-outline-secondary me-2" id="btn-expand-all">
                <i class="bi bi-arrows-expand"></i> {{ __('Expand All') }}
            </button>
            <button type="button" class="btn btn-outline-secondary" id="btn-collapse-all">
                <i class="bi bi-arrows-collapse"></i> {{ __('Collapse All') }}
            </button>
        </div>
    </div>

    <form action="{{ route('employees.bulk_update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Hidden inputs for Employee IDs --}}
        @foreach($employees as $employee)
            <input type="hidden" name="employee_ids[]" value="{{ $employee->id }}">
        @endforeach

        {{-- Hidden inputs for Selected Fields --}}
        @foreach($selectedFields as $field)
            <input type="hidden" name="selected_fields[]" value="{{ $field }}">
        @endforeach

        {{-- Master Control Section --}}
        <div class="card mb-4 master-control-card">
            <div class="card-header bg-primary text-white fw-bold d-flex align-items-center">
                <i class="bi bi-sliders me-2 fs-5"></i>
                <span>{{ __('Master Controls (Apply to All)') }}</span>
            </div>
            <div class="card-body bg-light">
                <div class="alert alert-info border-0 shadow-sm mb-3 d-flex align-items-center">
                    <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                    <span>{{ __('Use the inputs below to set a value for ALL employees at once. Click "Apply to All" to fill the individual forms below.') }}</span>
                </div>
                <div class="row g-3">
                    @foreach($selectedFields as $field)
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-white shadow-sm h-100 d-flex flex-column">
                                <label class="form-label fw-bold text-primary mb-2">{{ $fieldLabels[$field] ?? $field }}</label>

                                <div class="mb-2 flex-grow-1">
                                    @if(in_array($field, $fileFields))
                                        {{-- File Upload for Master --}}
                                        <div class="input-group">
                                            <input type="file" class="form-control master-input" data-field="{{ $field }}">
                                        </div>
                                        <div class="form-text text-muted small mt-1">
                                            <i class="bi bi-exclamation-circle"></i> {{ __('Uploads cannot be auto-applied due to browser security. Please upload individually.') }}
                                        </div>
                                    @elseif(in_array($field, $dateFields))
                                        {{-- Date Input --}}
                                        <input type="date" class="form-control master-input" data-field="{{ $field }}">
                                    @elseif(isset($options[$field]))
                                        {{-- Select Dropdown --}}
                                        <select class="form-select master-input" data-field="{{ $field }}">
                                            <option value="">-- {{ __('Select to Apply All') }} --</option>
                                            @foreach($options[$field] as $val => $label)
                                                <option value="{{ $val }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        {{-- Text Input --}}
                                        <input type="text" class="form-control master-input" data-field="{{ $field }}" placeholder="{{ __('Type to update all') }}">
                                    @endif
                                </div>

                                <button type="button" class="btn btn-sm btn-outline-primary w-100 apply-master-btn mt-auto" data-field="{{ $field }}" {{ in_array($field, $fileFields) ? 'disabled' : '' }}>
                                    <i class="bi bi-arrow-down-circle me-1"></i> {{ __('Apply to All') }}
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Individual Employee List --}}
        <div class="accordion shadow-sm" id="employeeAccordion">
            @foreach($employees as $index => $employee)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading{{ $employee->id }}">
                        <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $employee->id }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $employee->id }}">
                            <div class="d-flex align-items-center w-100">
                                <span class="badge bg-secondary me-3 rounded-pill">{{ $index + 1 }}</span>
                                <span class="fw-bold me-3">{{ $employee->employeeNameTh ?? $employee->employeeNameEn }}</span>
                                <span class="text-muted small me-auto"><i class="bi bi-person-badge"></i> {{ $employee->employeeCode ?? $employee->employeePassport ?? 'N/A' }}</span>

                                @if($employee->employer)
                                    <span class="badge bg-light text-dark border me-3 d-none d-md-inline-block">
                                        <i class="bi bi-building"></i> {{ $employee->employer->employerNameTh }}
                                    </span>
                                @endif
                            </div>
                        </button>
                    </h2>
                    <div id="collapse{{ $employee->id }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" aria-labelledby="heading{{ $employee->id }}">
                        <div class="accordion-body bg-white">
                            <div class="row g-3">
                                @foreach($selectedFields as $field)
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small text-uppercase fw-bold">{{ $fieldLabels[$field] ?? $field }}</label>

                                        @if(in_array($field, $fileFields))
                                            {{-- File Upload --}}
                                            <div class="input-group">
                                                <input type="file" class="form-control individual-input" name="data[{{ $employee->id }}][{{ $field }}]" data-field="{{ $field }}">
                                            </div>
                                            @if($employee->$field)
                                                <div class="mt-1 text-success small">
                                                    <i class="bi bi-check-circle-fill"></i> {{ __('File exists') }}
                                                    <a href="{{ Storage::disk('public')->url($employee->$field) }}" target="_blank" class="text-decoration-none ms-1">({{ __('View') }})</a>
                                                </div>
                                            @else
                                                <div class="mt-1 text-muted small">
                                                    <i class="bi bi-dash-circle"></i> {{ __('No file') }}
                                                </div>
                                            @endif
                                        @elseif(in_array($field, $dateFields))
                                            {{-- Date Input --}}
                                            <input type="date" class="form-control individual-input" name="data[{{ $employee->id }}][{{ $field }}]" value="{{ $employee->$field ? \Carbon\Carbon::parse($employee->$field)->format('Y-m-d') : '' }}" data-field="{{ $field }}">
                                        @elseif(isset($options[$field]))
                                            {{-- Select Dropdown --}}
                                            <select class="form-select individual-input" name="data[{{ $employee->id }}][{{ $field }}]" data-field="{{ $field }}">
                                                <option value="">-- {{ __('Select') }} --</option>
                                                @foreach($options[$field] as $val => $label)
                                                    <option value="{{ $val }}" {{ $employee->$field == $val ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            {{-- Text Input --}}
                                            <input type="text" class="form-control individual-input" name="data[{{ $employee->id }}][{{ $field }}]" value="{{ $employee->$field }}" data-field="{{ $field }}">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="fixed-bottom bg-white border-top p-3 shadow-lg" style="z-index: 1030;">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="bi bi-info-circle"></i> {{ __('Changes are not saved until you click "Save All Changes".') }}
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('employees.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-success btn-lg shadow-sm">
                        <i class="bi bi-save-fill me-2"></i> {{ __('Save All Changes') }}
                    </button>
                </div>
            </div>
        </div>
        {{-- Spacer for fixed bottom bar --}}
        <div style="height: 100px;"></div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle Master Field Sync
    const applyButtons = document.querySelectorAll('.apply-master-btn');

    applyButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const field = this.dataset.field;
            const masterInput = document.querySelector(`.master-input[data-field="${field}"]`);
            const individualInputs = document.querySelectorAll(`.individual-input[data-field="${field}"]`);

            if (!masterInput) return;

            // Safety check for file inputs, though button should be disabled
            if (masterInput.type === 'file') {
                return;
            }

            const value = masterInput.value;
            individualInputs.forEach(input => {
                input.value = value;
                // Trigger change event so any other listeners know it updated
                input.dispatchEvent(new Event('change'));

                // Visual highlight effect
                input.classList.add('bg-success-subtle');
                setTimeout(() => input.classList.remove('bg-success-subtle'), 1000);
            });

            // Button Visual feedback
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> {{ __("Applied!") }}';
            btn.classList.replace('btn-outline-primary', 'btn-success');

            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.classList.replace('btn-success', 'btn-outline-primary');
            }, 1500);
        });
    });

    // Expand/Collapse All
    const expandAllBtn = document.getElementById('btn-expand-all');
    const collapseAllBtn = document.getElementById('btn-collapse-all');
    const accordionCollapses = document.querySelectorAll('.accordion-collapse');

    if(expandAllBtn && collapseAllBtn) {
        expandAllBtn.addEventListener('click', () => {
            accordionCollapses.forEach(el => {
                // Use Bootstrap 5 API if available, or fallback to class manipulation
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
                bsCollapse.show();
            });
        });

        collapseAllBtn.addEventListener('click', () => {
            accordionCollapses.forEach(el => {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
                bsCollapse.hide();
            });
        });
    }
});
</script>
@endpush
@endsection
