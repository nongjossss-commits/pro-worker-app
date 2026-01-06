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
        <h1 class="h3 fw-bold">Create New Project</h1>
        <a href="{{ route('production.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body" x-data="{ projectType: '{{ $isIndependent ? 'independent' : 'employer' }}' }">
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
                                    x-model="projectType"
                                    {{ $isIndependent ? 'disabled' : '' }}>
                                <label class="form-check-label" for="typeEmployer">
                                    Standard (Single Employer)
                                    @if($isIndependent) <small class="text-danger d-block">(Disabled: Mixed employers selected)</small> @endif
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="typeIndependent" value="independent"
                                    x-model="projectType">
                                <label class="form-check-label" for="typeIndependent">
                                    Independent (Multiple/No Employer)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Employer Selection (Only visible if Standard) --}}
                <div class="mb-4" x-show="projectType === 'employer'" style="display: none;">
                    <label class="form-label fw-bold">Select Employer</label>

                    @php
                        $initVal = '';
                        $initText = '';
                        if ($preSelectedEmployees->isNotEmpty() && $employerId) {
                            $initVal = $employerId;
                            $initText = $preSelectedEmployees->first()->employer->employerNameTh ?? $preSelectedEmployees->first()->employer->employerNameEn ?? 'Selected Employer';
                        } elseif($employerId) {
                            // Try to find in passed employers list (first 200)
                            $found = isset($employers) ? $employers->firstWhere('id', $employerId) : null;
                            if($found) {
                                $initVal = $found->id;
                                $initText = $found->employerNameTh . ' (' . $found->employerNameEn . ')';
                            }
                        }
                    @endphp

                    {{--
                        We bind 'required' to the expression 'projectType === "employer"'.
                        However, standard blade component :required="..." evaluates at server side.
                        We need client side dynamic required.
                        The component's 'required' prop sets the initial state.
                        To make it dynamic, we need to pass a JS expression to x-bind:required on the input inside the component.
                        Since we can't easily modify the component to accept raw JS string for x-bind,
                        we will rely on the fact that if hidden, HTML5 validation is often suppressed or we handle it.
                        BUT, standard 'required' on a hidden element prevents form submission in some browsers.

                        The Searchable Select component uses x-bind:required="required && !value".
                        'required' there is a JS variable initialized from blade prop.

                        We can wrap the component or modify it to observe parent state? No, encapsulated.

                        Cleanest fix: The component exposes the input.
                        Actually, the component accepts `required` as a boolean.
                        If we want it dynamic, we should use Alpine to toggle it.
                        The component has `x-data="{ required: ... }"`.
                        We can use `$watch` in the parent or simple conditional rendering?
                        Conditional rendering `x-if` removes it from DOM, solving validation.
                    --}}

                    <template x-if="projectType === 'employer'">
                        <x-input-searchable-select
                            name="employer_id"
                            placeholder="Type to search employer..."
                            apiUrl="{{ route('api-web.employers.list') }}"
                            :initialValue="$initVal"
                            :initialText="$initText"
                            :required="true"
                        />
                    </template>
                    {{-- Note: x-if creates a new scope. The component should still work. --}}

                    <div class="form-text">For Standard projects, all employees must belong to this employer.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Description / Note</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>

                <hr>

                <h5 class="fw-bold mb-3">Selected Employees ({{ $preSelectedEmployees->count() }})</h5>
                @if($preSelectedEmployees->isNotEmpty())
                    <div class="employee-list bg-light p-3 rounded border" style="max-height: 500px; overflow-y: auto;">
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
                    <div class="alert alert-warning">No employees selected. You can add them in the next step.</div>
                @endif

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="submit" class="btn btn-primary px-4">Create Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Include Action Modals for Previews etc. --}}
@include('partials._employee_action_modals')

@endsection
