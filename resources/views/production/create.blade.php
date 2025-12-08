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
                        <input type="hidden" name="selected_employees[]" value="{{ $emp->id }}">
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
                                 {{ $preSelectedEmployees->first()->employer->name_th ?? 'Selected Employer' }}
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

                <h5 class="fw-bold mb-3">Selected Employees ({{ $preSelectedEmployees->count() }})</h5>
                @if($preSelectedEmployees->isNotEmpty())
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Name</th>
                                    <th>Passport</th>
                                    <th>Employer</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($preSelectedEmployees as $emp)
                                    <tr>
                                        <td>{{ $emp->fullname_th ?? $emp->name_th }}</td>
                                        <td>{{ $emp->passport_number ?? $emp->passport_no }}</td>
                                        <td>{{ $emp->employer->name_th ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
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
</script>
@endsection
