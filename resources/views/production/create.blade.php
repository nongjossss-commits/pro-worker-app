@extends('layouts.app')

@section('content')
<div class="container py-4">
    {{-- CSS to hide checkboxes in the cards, as we use hidden inputs for submission --}}
    <style>
        .employee-list .employee-checkbox {
            display: none !important;
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold">{{ __('Create New Project') }}</h1>
        <a href="{{ route('production.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('production.store') }}" method="POST" id="createProjectForm">
                @csrf

                {{-- Hidden Inputs for Selected Employees --}}
                @if($preSelectedEmployees->isNotEmpty())
                    @foreach($preSelectedEmployees as $emp)
                        <input type="hidden" name="selected_employees[]" value="{{ $emp->id }}">
                    @endforeach
                @endif

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">{{ __('Project Name') }}</label>
                        <input type="text" name="project_name" class="form-control" placeholder="{{ __('e.g. Visa Renewal Batch #101') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">{{ __('Project Type') }}</label>
                        <div class="d-flex gap-3 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="typeEmployer" value="employer"
                                    {{ $isIndependent ? 'disabled' : 'checked' }}>
                                <label class="form-check-label" for="typeEmployer">
                                    Standard (Single Employer)
                                    @if($isIndependent) <small class="text-danger d-block">(Disabled: Mixed employers selected)</small> @endif
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="typeIndependent" value="independent"
                                    {{ $isIndependent ? 'checked' : '' }}>
                                <label class="form-check-label" for="typeIndependent">{{ __('Independent (Multiple/No Employer)') }}</label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Employer Selection (Only visible if Standard) --}}
                <div class="mb-4" id="employerSelectionDiv" style="{{ $isIndependent ? 'display:none;' : '' }}">
                    <label class="form-label fw-bold">{{ __('Select Employer') }}</label>
                    <select name="employer_id" class="form-select" {{ $isIndependent ? '' : 'required' }}>
                        <option value="">{{ __('-- Choose Employer --') }}</option>
                        @if($preSelectedEmployees->isNotEmpty() && $employerId)
                             <option value="{{ $employerId }}" selected>
                                 {{ $preSelectedEmployees->first()->employer->employerNameTh ?? $preSelectedEmployees->first()->employer->employerNameEn ?? 'Selected Employer' }}
                             </option>
                        @elseif(isset($employers) && $employers->isNotEmpty())
                            @foreach($employers as $emp)
                                <option value="{{ $emp->id }}" {{ $employerId == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->employerNameTh }} ({{ $emp->employerNameEn }}) - {{ $emp->employerId }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <div class="form-text">{{ __('For Standard projects, all employees must belong to this employer.') }}</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">{{ __('Description / Note') }}</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>

                <hr>

                <h5 class="fw-bold mb-3">Selected Employees ({{ $preSelectedEmployees->count() }})</h5>
                @if($preSelectedEmployees->{{ __('isNotEmpty())') }}<div class="employee-list bg-light p-3 rounded border" style="max-height: 500px; overflow-y: auto;">
                        @php
                            $groupedEmployees = $preSelectedEmployees->groupBy('employer_id');
                        @endphp
                        @foreach($groupedEmployees as $employerId => $employees)
                            @php
                                $firstEmp = $employees->first();
                                $employerName = $firstEmp->employer ? ($firstEmp->employer->employerNameTh . ' (' . $firstEmp->employer->employerNameEn . ')') : 'Unknown / No Employer';
                            @endphp
                            <div class="mb-4">
                                <h5 class="bg-white p-2 rounded border-start border-4 border-primary shadow-sm sticky-top" style="top: -16px; z-index: 5;">
                                    <i class="bi bi-building me-2"></i>{{ $employerName }}
                                    <span class="badge bg-secondary ms-2">{{ $employees->count() }}</span>
                                </h5>
                                <div class="list-group ps-2">
                                    @foreach($employees as $emp)
                                        @include('partials._employee_card', [
                                            'employee' => $emp,
                                            'hideTeamTags' => true
                                        ])
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-warning">{{ __('No employees selected. You can add them in the next step.') }}</div>
                @endif

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="submit" class="btn btn-primary px-4">{{ __('Create Project') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Include Action Modals for Previews etc. --}}
@include('partials._employee_action_modals')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeRadios = document.querySelectorAll('input[name="type"]');
        const employerDiv = document.getElementById('employerSelectionDiv');
        const employerSelect = employerDiv.querySelector('select');

        typeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'employer') {
                    employerDiv.style.display = 'block';
                    employerSelect.required = true;
                } else {
                    employerDiv.style.display = 'none';
                    employerSelect.required = false;
                }
            });
        });
    });
</script>
@endsection
