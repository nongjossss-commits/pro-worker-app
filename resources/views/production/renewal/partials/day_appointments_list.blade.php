@if($employees->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="bi bi-calendar-x fs-1 opacity-25"></i>
        <p class="mt-2">{{ __('No appointments found for this date.') }}</p>
    </div>
@else
    <div class="container-fluid p-3" x-data="appointmentSearch()">
        <div class="mb-4">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0" placeholder="{{ __('Search by employee name, employer name, or reference...') }}" x-model="searchQuery">
            </div>
        </div>

        <div class="row g-3">
            @foreach($employees as $employee)
                <div class="col-12 appointment-card"
                     data-employee-name-th="{{ strtolower($employee->employeeNameTh) }}"
                     data-employee-name-en="{{ strtolower($employee->employeeNameEn) }}"
                     data-employer-name="{{ strtolower($employee->employer->employerNameTh ?? '') }}"
                     data-reference="{{ strtolower($employee->employee_reference_id ?? '') }}"
                     x-show="matchesSearch($el)">

                    @include('production.registration._employee_card', [
                        'employee' => $employee,
                        'steps' => $steps,
                        'loop' => $loop,
                        'isHistory' => false,
                        'show_employer' => true
                    ])
                </div>
            @endforeach
        </div>
    </div>

@endif