@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold">Create New Project</h1>
        <a href="{{ route('production.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('production.store') }}" method="POST" id="createProjectForm">
                @csrf

                {{-- Hidden Inputs for Selected Employees --}}
                @if($preSelectedEmployees->isNotEmpty())
                    @foreach($preSelectedEmployees as $emp)
                        <input type="hidden" name="selected_employees[]" value="{{ $emp->id }}" id="hidden-input-{{ $emp->id }}">
                    @endforeach
                @endif

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Project Name</label>
                        <input type="text" name="project_name" class="form-control" placeholder="e.g. Visa Renewal Batch #101" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Project Type</label>
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
                                <label class="form-check-label" for="typeIndependent">
                                    Independent (Multiple/No Employer)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Employer Selection (Only visible if Standard) --}}
                <div class="mb-4" id="employerSelectionDiv" style="{{ $isIndependent ? 'display:none;' : '' }}">
                    <label class="form-label fw-bold">Select Employer</label>
                    <select name="employer_id" class="form-select" {{ $isIndependent ? '' : 'required' }}>
                        <option value="">-- Choose Employer --</option>
                        @if($preSelectedEmployees->isNotEmpty() && $employerId)
                             <option value="{{ $employerId }}" selected>
                                 {{ $preSelectedEmployees->first()->employer->employerNameTh ?? 'Selected Employer' }}
                             </option>
                        @elseif(isset($employers) && $employers->isNotEmpty())
                            @foreach($employers as $emp)
                                <option value="{{ $emp->id }}" {{ $employerId == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->employerNameTh }} ({{ $emp->employerNameEn }}) - {{ $emp->employer_id }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <div class="form-text">For Standard projects, all employees must belong to this employer.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Description / Note</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>

                <hr>

                <h5 class="fw-bold mb-3">Selected Employees (<span id="selected-count">{{ $preSelectedEmployees->count() }}</span>)</h5>
                @if($preSelectedEmployees->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            @php
                                $groupedEmployees = $preSelectedEmployees->groupBy('employer_id');
                            @endphp

                            @foreach($groupedEmployees as $employerId => $employees)
                                @php
                                    $firstEmp = $employees->first();
                                    $employer = $firstEmp->employer;
                                    $employerName = $employer ? ($employer->employerNameTh . ' (' . $employer->employerNameEn . ')') : 'Unknown Employer';
                                @endphp
                                <tbody class="group-tbody">
                                    <tr class="table-light">
                                        <td colspan="4" class="fw-bold py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span>
                                                    <i class="bi bi-building me-2"></i> {{ $employerName }}
                                                    <span class="badge bg-secondary ms-2">{{ $employees->count() }} Employees</span>
                                                </span>
                                                @if($employer)
                                                    <button type="button" class="btn btn-sm btn-outline-info btn-preview"
                                                            data-model-type="employer"
                                                            data-model-id="{{ $employer->id }}"
                                                            title="Preview Employer">
                                                        <i class="bi bi-search"></i> Preview
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @foreach($employees as $emp)
                                        <tr id="row-{{ $emp->id }}">
                                            <td style="width: 60px;" class="text-center">
                                                <img src="{{ $emp->employeePhoto ? asset('storage/' . $emp->employeePhoto) : asset('images/default-profile.png') }}"
                                                     alt="Photo" class="rounded-circle border" width="40" height="40" style="object-fit: cover;">
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $emp->employeeNameTh }}</div>
                                                <div class="text-muted small">{{ $emp->employeeNameEn }}</div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column small">
                                                    <span><i class="bi bi-passport me-1"></i> {{ $emp->employeePassport ?? '-' }}</span>
                                                    @if($emp->employeeNationality)
                                                        @php
                                                            $countryCode = \App\Helpers\CountryHelper::getCountryCode($emp->employeeNationality);
                                                        @endphp
                                                        <span class="text-muted mt-1">
                                                            @if($countryCode)
                                                                <img src="{{ asset('images/flags/' . strtolower($countryCode) . '.png') }}" alt="{{ $countryCode }}" style="width: 16px; height: 12px;" class="me-1">
                                                            @endif
                                                            {{ $emp->employeeNationality }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center" style="width: 120px;">
                                                <button type="button" class="btn btn-sm btn-outline-info btn-preview me-1"
                                                        data-model-type="employee"
                                                        data-model-id="{{ $emp->id }}"
                                                        title="Preview Employee">
                                                    <i class="bi bi-search"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="removeEmployee({{ $emp->id }})"
                                                        title="Remove">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            @endforeach
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning">No employees selected. You can add them in the next step.</div>
                @endif

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="submit" class="btn btn-primary px-4">Create Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

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

    function removeEmployee(id) {
        if (confirm('Are you sure you want to remove this employee from the list?')) {
            // Remove the row
            const row = document.getElementById('row-' + id);
            if (row) row.remove();

            // Remove the hidden input
            const input = document.getElementById('hidden-input-' + id);
            if (input) input.remove();

            // Update count
            const countSpan = document.getElementById('selected-count');
            let count = parseInt(countSpan.innerText);
            if (count > 0) {
                countSpan.innerText = count - 1;
            }

            // Optional: Hide empty groups
            // (If complex logic needed, we can implement it, but simple row removal is usually enough)
        }
    }
</script>
@endsection
