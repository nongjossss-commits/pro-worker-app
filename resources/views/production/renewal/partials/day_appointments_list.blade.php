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

                    <div class="card border-0 shadow-sm overflow-hidden h-100 position-relative">
                        <div class="position-absolute top-0 start-0 h-100" style="width: 5px; background-color: {{ $employee->appointment_completed_at ? '#10B981' : '#F59E0B' }};"></div>

                        <div class="card-body p-4 ms-2">
                            <div class="row align-items-center">
                                {{-- Employee Info --}}
                                <div class="col-md-5 mb-3 mb-md-0">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 48px; height: 48px; font-size: 1.2rem;">
                                            {{ mb_substr($employee->employeeNameTh ?? $employee->employeeNameEn ?? 'E', 0, 1) }}
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-1 text-dark">
                                                {{ $employee->employeeTitle ?? '' }} {{ $employee->employeeNameEn ?? '' }}
                                                <span class="text-secondary fs-6">({{ $employee->employeeNameTh ?? '' }})</span>
                                            </h5>
                                            <div class="text-muted small d-flex gap-3">
                                                <span><i class="bi bi-person-badge me-1"></i>{{ $employee->employee_reference_id ?? '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Employer Info --}}
                                <div class="col-md-4 mb-3 mb-md-0 border-start ps-4">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-light rounded p-2 me-3 text-secondary">
                                            <i class="bi bi-building fs-5"></i>
                                        </div>
                                        <div>
                                            <span class="text-uppercase small fw-bold text-muted d-block mb-1">{{ __('Employer') }}</span>
                                            <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width: 200px;" title="{{ $employee->employer->employerNameTh ?? '' }}">
                                                {{ $employee->employer->employerNameTh ?? '-' }}
                                            </h6>
                                        </div>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="col-md-3 text-md-end">
                                    <div class="d-flex flex-column gap-2 align-items-md-end">
                                        {{-- Checkbox for bulk actions --}}
                                        <div class="form-check form-check-inline mb-2 me-0 text-start text-md-end w-100">
                                            <input class="form-check-input employee-checkbox float-md-end shadow-sm" type="checkbox"
                                                   style="width: 20px; height: 20px; cursor: pointer; border-color: #6c757d;"
                                                   value="{{ $employee->id }}"
                                                   data-id="{{ $employee->id }}"
                                                   data-employee-id="{{ $employee->id }}"
                                                   data-employer-id="{{ $employee->employer_id }}"
                                                   data-name-en="{{ $employee->employeeNameEn ?? '' }}"
                                                   data-name-th="{{ $employee->employeeNameTh ?? '' }}">
                                            <label class="form-check-label ms-2 d-md-none fw-bold text-muted">{{ __('Select Employee') }}</label>
                                        </div>

                                        <div class="btn-group shadow-sm w-100 mb-2">
                                            <a href="{{ route('employees.edit', $employee->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm" title="{{ __('Edit Employee') }}">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-primary btn-sm btn-preview" data-model-type="employee" data-model-id="{{ $employee->id }}" title="{{ __('Preview Employee') }}">
                                                <i class="bi bi-person-vcard"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info btn-sm btn-preview" data-model-type="employer" data-model-id="{{ $employee->employer_id }}" title="{{ __('Preview Employer') }}">
                                                <i class="bi bi-building"></i>
                                            </button>
                                        </div>

                                        <div class="d-flex gap-2 w-100">
                                            <button class="btn btn-sm w-100 fw-bold {{ $employee->appointment_completed_at ? 'btn-success' : 'btn-warning text-dark' }}"
                                                    onclick="editAppointment({{ $employee->id }}, '{{ $employee->appointment_date ? \Carbon\Carbon::parse($employee->appointment_date)->format('Y-m-d\TH:i') : '' }}', '{{ $employee->appointment_location ?? '' }}', {{ $employee->appointment_completed_at ? 'true' : 'false' }})">
                                                @if($employee->appointment_completed_at)
                                                    <i class="bi bi-check-circle-fill me-1"></i> {{ __('Completed') }}
                                                @else
                                                    <i class="bi bi-clock-fill me-1"></i> {{ __('Update') }}
                                                @endif
                                            </button>

                                            @if(!$employee->appointment_completed_at)
                                            <button class="btn btn-sm btn-success w-100 fw-bold" onclick="markAppointmentCompleted({{ $employee->id }}, 'production/renewal', this)" title="{{ __('Mark Appointment Completed') }}">
                                                <i class="bi bi-check-lg me-1"></i> {{ __('Complete') }}
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

@endif