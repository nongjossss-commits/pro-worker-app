@extends('layouts.app')

@section('title', __('Advanced Bulk Edit Selection'))

@section('content')
<div class="container-fluid p-4" id="bulk-edit-selector-wrapper">
    <h2 class="mb-4">{{ __('Select Fields for Bulk Edit') }}</h2>

    <form action="{{ route('employees.bulk_edit.form') }}" method="POST">
        @csrf
        {{-- Pass the employee IDs to the next step --}}
        @foreach($employeeIds as $id)
            <input type="hidden" name="employee_ids[]" value="{{ $id }}">
        @endforeach

        @if(isset($redirectTo))
            <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
        @endif

        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-2"></i>
            {{ __('Please select the fields you want to edit (Maximum 5 fields).') }}
        </div>

        <div class="row g-3">
            {{-- Iterate through groups of fields --}}
            @foreach($fieldGroups as $groupName => $fields)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-header fw-bold">{{ __($groupName) }}</div>
                        <div class="card-body">
                            @foreach($fields as $key => $label)
                                <div class="form-check mb-2">
                                    <input class="form-check-input field-checkbox" type="checkbox" name="selected_fields[]" value="{{ $key }}" id="field_{{ $key }}">
                                    <label class="form-check-label" for="field_{{ $key }}">
                                        {{ __($label) }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-flex justify-content-between">
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                {{ __('Proceed to Edit') }}
            </button>
        </div>
    </form>

    <script>
        (function() {
            const checkboxes = document.querySelectorAll('.field-checkbox');
            const submitBtn = document.getElementById('submit-btn');
            const MAX_SELECTION = 5;

            function updateState() {
                const checked = document.querySelectorAll('.field-checkbox:checked');
                const count = checked.length;

                // Enable/Disable checkboxes based on max selection
                checkboxes.forEach(cb => {
                    if (!cb.checked) {
                        cb.disabled = count >= MAX_SELECTION;
                    } else {
                        cb.disabled = false;
                    }
                });

                // Enable submit only if at least one field is selected
                if (submitBtn) {
                    submitBtn.disabled = count === 0;
                }
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateState);
            });

            // Initial check
            updateState();
        })();
    </script>
</div>
@endsection
