{{-- Single appointment card for cross-date search results — same visual
     style as day_appointments_list.blade.php's card, but standalone (no
     fixed-id toolbar/script) since this card repeats once per matching
     date group and fixed ids would collide if duplicated. Read-only plus
     the two safe actions (Edit, Update Appointment) which only call global
     onclick functions, not id-bound listeners. --}}
@php
    $countryCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
    $reqNo = $employee->registration_request_number;
@endphp
<div class="col-12">
    <div class="card border-0 shadow-sm overflow-hidden position-relative">
        <div class="position-absolute top-0 start-0 h-100"
             style="width:5px; background-color:{{ $employee->appointment_completed_at ? '#10B981' : '#F59E0B' }};"></div>
        <div class="card-body py-3 ps-4 pe-3">
            <div class="d-flex align-items-start gap-3">
                <div class="flex-shrink-0">
                    @if($employee->employeePhoto)
                        <img src="{{ Storage::disk('public')->url($employee->employeePhoto) }}?v={{ optional($employee->updated_at)->timestamp ?? '0' }}"
                             class="rounded-circle shadow-sm" style="width:52px; height:52px; object-fit:cover;">
                    @else
                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center shadow-sm fw-bold"
                             style="width:52px; height:52px; font-size:1.3rem;">
                            {{ mb_substr($employee->employeeNameTh ?? $employee->employeeNameEn ?? 'E', 0, 1) }}
                        </div>
                    @endif
                </div>

                <div class="flex-grow-1" style="min-width:0;">
                    <div class="fw-bold text-dark lh-sm d-flex align-items-center gap-1 flex-wrap">
                        <span>{{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? '-' }}</span>
                        <button type="button" class="btn btn-sm btn-link p-0 border-0 text-muted btn-preview"
                                data-model-type="employee" data-model-id="{{ $employee->id }}"
                                title="{{ __('Preview Employee') }}">
                            <i class="bi bi-search" style="font-size:0.8rem;"></i>
                        </button>
                    </div>
                    <div class="text-muted small">{{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? '-' }}</div>

                    <div class="d-flex flex-wrap gap-2 mt-1" style="font-size:0.78rem; color:#6c757d;">
                        @if($employee->employeePassport)
                        <span><i class="bi bi-passport text-primary me-1"></i>{{ $employee->employeePassport }}</span>
                        @endif
                        @if($employee->employee_reference_id)
                        <span><i class="bi bi-person-badge me-1"></i>{{ $employee->employee_reference_id }}</span>
                        @endif
                        @if($reqNo)
                        <span><i class="bi bi-file-text me-1"></i>{{ $reqNo }}</span>
                        @endif
                        @if($employee->employeeNationality)
                        <span>
                            @if($countryCode)
                                <img src="{{ asset('images/flags/'.strtolower($countryCode).'.png') }}" style="width:15px; height:11px;" class="me-1">
                            @endif
                            {{ $employee->employeeNationality }}
                        </span>
                        @endif
                    </div>

                    <div class="mt-1 d-flex align-items-center gap-1 flex-wrap" style="font-size:0.82rem;">
                        <i class="bi bi-building text-secondary me-1"></i>
                        <span class="fw-semibold">{{ $employee->employer->employerNameTh ?? '-' }}</span>
                        @if($employee->employer->employerNameEn ?? null)
                            <span class="text-muted">({{ $employee->employer->employerNameEn }})</span>
                        @endif
                        <button type="button" class="btn btn-sm btn-link p-0 border-0 text-muted btn-preview"
                                data-model-type="employer" data-model-id="{{ $employee->employer_id }}"
                                title="{{ __('Preview Employer') }}">
                            <i class="bi bi-search" style="font-size:0.78rem;"></i>
                        </button>
                    </div>

                    @if($employee->appointment_date)
                    <div class="mt-1" style="font-size:0.78rem;">
                        <i class="bi bi-clock me-1 {{ $employee->appointment_completed_at ? 'text-success' : 'text-warning' }}"></i>
                        <span class="fw-bold {{ $employee->appointment_completed_at ? 'text-success' : 'text-warning' }}">
                            {{ \Carbon\Carbon::parse($employee->appointment_date)->format('H:i') }}
                        </span>
                        @if($employee->appointment_location)
                            <i class="bi bi-geo-alt-fill text-info ms-2 me-1"></i>
                            <span class="text-info">{{ $employee->appointment_location }}</span>
                        @endif
                        @if($employee->appointment_completed_at)
                            <span class="badge bg-success ms-1">{{ __('Done') }}</span>
                        @endif
                    </div>
                    @endif
                </div>

                @can('edit-employees')
                <div class="flex-shrink-0 d-flex flex-column gap-2 align-items-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            onclick="window.openEditEmployeeModal({{ $employee->id }})" title="{{ __('Edit Employee') }}">
                        <i class="bi bi-pencil me-1"></i>{{ __('Edit') }}
                    </button>
                    <button class="btn btn-sm fw-bold {{ $employee->appointment_completed_at ? 'btn-success' : 'btn-warning text-dark' }}"
                            onclick="editAppointment({{ $employee->id }}, '{{ $employee->appointment_date ? \Carbon\Carbon::parse($employee->appointment_date)->format('Y-m-d\TH:i') : '' }}', '{{ addslashes($employee->appointment_location ?? '') }}', {{ $employee->appointment_completed_at ? 'true' : 'false' }})">
                        @if($employee->appointment_completed_at)
                            <i class="bi bi-check-circle-fill me-1"></i>{{ __('Completed') }}
                        @else
                            <i class="bi bi-clock-fill me-1"></i>{{ __('Update') }}
                        @endif
                    </button>
                </div>
                @endcan

                <div class="flex-shrink-0 align-self-end">
                    <x-share-handle type="employee" :id="$employee->id" :name="$employee->employeeNameEn ?: $employee->employeeNameTh" :context="isset($tabId) ? 'tab:' . $tabId : null" />
                </div>
            </div>
        </div>
    </div>
</div>
