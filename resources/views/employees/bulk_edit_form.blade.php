@extends('layouts.app')

@section('title', 'Advanced Bulk Edit')

@section('content')
<div class="container-fluid p-4">
    <h2 class="mb-4">{{ __('Advanced Bulk Edit') }} ({{ count($employees) }} {{ __('Employees') }})</h2>

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
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white fw-bold">
                <i class="bi bi-sliders me-2"></i> {{ __('Master Controls (Update All)') }}
            </div>
            <div class="card-body bg-light">
                <div class="row g-3">
                    @foreach($selectedFields as $field)
                        <div class="col-md-4">
                            <label class="form-label fw-bold">{{ $fieldLabels[$field] ?? $field }}</label>

                            @if(in_array($field, $fileFields))
                                {{-- File Upload for Master --}}
                                <div class="input-group">
                                    <input type="file" class="form-control master-input" data-field="{{ $field }}">
                                </div>
                                <small class="text-muted">{{ __('Upload to apply to all') }}</small>
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

                            <button type="button" class="btn btn-sm btn-outline-primary mt-2 w-100 apply-master-btn" data-field="{{ $field }}">
                                <i class="bi bi-arrow-down-circle me-1"></i> {{ __('Apply to All') }}
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Individual Employee List --}}
        <div class="accordion" id="employeeAccordion">
            @foreach($employees as $index => $employee)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading{{ $employee->id }}">
                        <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $employee->id }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $employee->id }}">
                            <span class="fw-bold me-3">{{ $index + 1 }}. {{ $employee->employeeNameTh ?? $employee->employeeNameEn }}</span>
                            <span class="text-muted small">{{ $employee->employeeCode ?? '' }}</span>
                        </button>
                    </h2>
                    <div id="collapse{{ $employee->id }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" aria-labelledby="heading{{ $employee->id }}">
                        <div class="accordion-body">
                            <div class="row g-3">
                                @foreach($selectedFields as $field)
                                    <div class="col-md-4">
                                        <label class="form-label">{{ $fieldLabels[$field] ?? $field }}</label>

                                        @if(in_array($field, $fileFields))
                                            {{-- File Upload --}}
                                            <input type="file" class="form-control individual-input" name="data[{{ $employee->id }}][{{ $field }}]" data-field="{{ $field }}">
                                            @if($employee->$field)
                                                <div class="mt-1">
                                                    <small class="text-success"><i class="bi bi-check-circle"></i> {{ __('File exists') }}</small>
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
            <div class="container-fluid d-flex justify-content-end gap-2">
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-save me-2"></i> {{ __('Save All Changes') }}
                </button>
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

            if (masterInput.type === 'file') {
                // File inputs cannot be programmatically set due to security.
                // We can interpret this as "Use the master file for everyone"
                // but handling file uploads multiple times from one input is tricky in backend without cloning.
                // For now, let's alert user or handle logically.
                // Strategy: If master has file, we might need a hidden input to signal "use master file"
                // OR just clone the file input logic in backend?
                // Actually, JS cannot set file input value.
                alert('{{ __("Cannot auto-fill file inputs due to browser security. Please upload individually or we need a backend strategy to copy one file to many.") }}');
                // Alternative: The backend can check if "master_file_[field]" exists and apply it to all IDs in the loop.
                // But here we just want UI sync.
            } else {
                const value = masterInput.value;
                individualInputs.forEach(input => {
                    input.value = value;
                    // Trigger change event if needed
                    input.dispatchEvent(new Event('change'));
                });

                // Visual feedback
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check"></i> {{ __("Applied") }}';
                btn.classList.replace('btn-outline-primary', 'btn-success');
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.replace('btn-success', 'btn-outline-primary');
                }, 2000);
            }
        });
    });
});
</script>
@endpush
@endsection
