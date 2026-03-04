@props(['employee', 'steps', 'isHistory' => false, 'show_employer' => false])

@php
    $isCompleted = in_array($employee->status, ['registration_completed', 'renewal_completed']);
    $isCancelled = in_array($employee->status, ['registration_cancelled', 'renewal_cancelled']);

    if ($isHistory) {
        // $isCompleted = true; // Force completed styling if not already
    }

    // Style: if completed/cancelled, flat/grey out.
    // Cancelled gets a specific flat grey look.
    $cardClass = 'bg-white border shadow-sm';
    $overlayClass = ''; // Added to the avatar/info container and steps container

    if ($isCompleted) {
        $cardClass = 'bg-success bg-opacity-10 border-0 text-muted';
        $overlayClass = 'opacity-75 pointer-events-none';
    } elseif ($isCancelled) {
        $cardClass = 'bg-light border-0 text-secondary grayscale-mode'; // Add grayscale class or inline style
        $overlayClass = 'opacity-50 pointer-events-none';
    }

    // Determine Highest Completed Step for Filtering
    $highestStep = $employee->registrationSteps->sortByDesc('order')->first();
    $highestStepId = $highestStep ? $highestStep->id : '';
    // Determine if "Not Started" (only if active status and no steps)
    $isNotStarted = (!$isCompleted && !$isCancelled && !$highestStep);

    // Operator Logic
    $operator = $employee->operator;
    $operatorName = $operator ? $operator->name : null;
    $operatorId = $employee->operator_id;
    $isMe = $operatorId === auth()->id();
@endphp

<div class="d-flex align-items-center employee-card-outer mb-3 employee-card-wrapper"
     id="employee-card-{{ $employee->id }}"
     data-highest-step-id="{{ $highestStepId }}"
     data-status="{{ $employee->status }}"
     data-is-not-started="{{ $isNotStarted ? 'true' : 'false' }}"
     data-employer-id="{{ $employee->employer_id }}"
     data-biometrics-collected="{{ $employee->biometrics_collected_at ? 'true' : 'false' }}"
     style="transition: all 0.3s ease; {{ $isCancelled ? 'filter: grayscale(100%);' : '' }}">

    {{-- Sequence Number (Outside Card) --}}
    <div class="employee-sequence-number me-2 fs-5 fw-bold text-muted opacity-50 text-end" style="min-width: 30px;"></div>

    <div class="card {{ $cardClass }} w-100 position-relative">

    {{-- Operator Badge (Bottom Right) --}}
    <div class="position-absolute bottom-0 end-0 m-2 z-index-10">
        @php
            $toggleUrl = request()->is('production/renewal*')
                ? route('production.renewal.toggle_operator', $employee->id)
                : route('production.registration.toggle_operator', $employee->id);
        @endphp
        <button class="btn btn-sm {{ $operatorId ? ($isMe ? 'btn-primary' : 'btn-secondary') : 'btn-outline-secondary' }} rounded-pill shadow-sm py-0 px-2"
                style="font-size: 0.75rem; border-width: 1px;"
                @hasanyrole('super-admin|admin|staff')
                onclick="window.toggleOperator ? window.toggleOperator({{ $employee->id }}, this, '{{ $operatorId ?? '' }}', '{{ $toggleUrl }}') : console.error('toggleOperator not defined')"
                title="{{ $operatorName ? 'Operator: '.$operatorName : 'Click to Assign' }}"
                @else
                disabled
                title="{{ $operatorName ? 'Operator: '.$operatorName : 'Not Assigned' }}"
                @endhasanyrole
                >
            <i class="bi bi-person-badge-fill"></i>
            @if($operatorName)
                <span class="ms-1 fw-bold">{{ $operatorName }}</span>
            @endif
        </button>
    </div>

    <div class="card-body p-3 pt-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
            {{-- Checkbox & Basic Info --}}
            <div class="d-flex align-items-start gap-3 w-100">
                <div class="d-flex align-items-center gap-3">
                @can('edit-employees')
                {{-- Only show checkbox if Active (Pending) --}}
                <div class="form-check {{ ($isCompleted || $isCancelled) ? 'd-none' : '' }}" id="checkbox-container-{{ $employee->id }}">
                    <input class="form-check-input employee-checkbox"
                           type="checkbox"
                           value="{{ $employee->id }}"
                           id="check_{{ $employee->id }}"
                           data-employee-id="{{ $employee->id }}"
                           data-employer-id="{{ $employee->employer_id }}"
                           data-status="{{ $employee->status }}"
                           data-name-th="{{ $employee->employeeNameTh }}"
                           data-name-en="{{ $employee->employeeNameEn }}"
                           data-title-th="{{ $employee->employeeTitleTh }}"
                           data-title-en="{{ $employee->employeeTitleEn }}"
                           data-nationality="{{ $employee->employeeNationality }}"
                           data-photo="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/40x40/e2e8f0/6c757d?text=PIC' }}"
                           data-employer-name="{{ $employee->employer->employerNameTh ?? 'N/A' }}"
                           data-insurance-type="{{ $employee->insurance_type }}"
                           data-passport="{{ $employee->employeePassport }}">
                </div>
                @endcan

                <div class="d-flex align-items-center gap-3 {{ $overlayClass }}" id="info-container-{{ $employee->id }}">
                    {{-- Avatar --}}
                    <div class="avatar-container position-relative">
                        @if($employee->employeePhoto)
                            <img src="{{ Storage::disk('public')->url($employee->employeePhoto) }}" class="rounded-circle shadow-sm" style="width: 50px; height: 50px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px; font-size: 1.2rem;">
                                {{ substr($employee->employeeNameEn ?? 'U', 0, 1) }}
                            </div>
                        @endif

                        {{-- Status Badges (Hidden by default, toggled by JS/PHP) --}}
                        <span id="badge-completed-{{ $employee->id }}" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success border border-white {{ !$isCompleted ? 'd-none' : '' }}">
                            <i class="bi bi-check"></i>
                        </span>
                        <span id="badge-cancelled-{{ $employee->id }}" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary border border-white {{ !$isCancelled ? 'd-none' : '' }}">
                            <i class="bi bi-x"></i>
                        </span>

                        {{-- Financial Status Badge --}}
                        @if(isset($employee->financialStatus))
                            @if($employee->financialStatus === 'paid')
                                <span class="position-absolute bottom-0 start-100 translate-middle badge rounded-pill bg-success border border-white" title="{{ __('Fully Paid') }}">
                                    <i class="bi bi-currency-dollar"></i>
                                </span>
                            @elseif($employee->financialStatus === 'partial')
                                <span class="position-absolute bottom-0 start-100 translate-middle badge rounded-pill bg-primary border border-white" title="{{ __('Partial/Pending Payment') }}">
                                    <i class="bi bi-currency-dollar"></i>
                                </span>
                            @elseif($employee->financialStatus === 'installment_created')
                                <span class="position-absolute bottom-0 start-100 translate-middle badge rounded-pill bg-warning text-dark border border-white" title="{{ __('Installment Created') }}">
                                    <i class="bi bi-currency-dollar"></i>
                                </span>
                            @elseif($employee->financialStatus === 'priced')
                                <span class="position-absolute bottom-0 start-100 translate-middle badge rounded-pill bg-secondary border border-white" title="{{ __('Priced') }}">
                                    <i class="bi bi-currency-dollar"></i>
                                </span>
                            @endif
                        @endif
                    </div>

                    {{-- Info --}}
                    <div>
                        <div class="fw-bold text-dark">
                             {{-- English Name + Title --}}
                            {{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? '-' }}
                        </div>
                        <div class="text-muted small">
                            {{-- Thai Name + Title --}}
                            {{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? '-' }}
                        </div>
                        @if($show_employer)
                            <div class="text-muted small mt-1">
                                <i class="bi bi-building me-1"></i>
                                {{ $employee->employer->employerNameTh ?? '-' }}
                                <button class="btn btn-sm btn-link p-0 ms-1 btn-preview"
                                        data-model-type="employer"
                                        data-model-id="{{ $employee->employer_id }}"
                                        title="{{ __('Preview Employer') }}">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        @endif
                        <div class="small text-muted mt-1">
                             <span class="me-2" title="{{ __('Date of Birth') }}">
                                <i class="bi bi-calendar-event me-1"></i>
                                {{ $employee->employeeDob ? \Carbon\Carbon::parse($employee->employeeDob)->format('d/m/Y') : '-' }}
                                ({{ __('Age') }} : {{ $employee->age ?? '-' }} {{ __('Years') }})
                             </span>
                        </div>
                        <div class="small text-muted mt-1">
                            <span class="me-2"><i class="bi bi-passport text-primary me-1"></i> {{ $employee->employeePassport ?? '-' }}</span>
                            <span class="d-inline-flex align-items-center">
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                @php
                                    $countryCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
                                @endphp
                                @if($countryCode)
                                    <img src="{{ asset('images/flags/' . strtolower($countryCode) . '.png') }}"
                                         alt="{{ $countryCode }}"
                                         style="width: 16px; height: 12px; margin-right: 5px;">
                                @endif
                                {{ $employee->employeeNationality ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>
                </div>

            {{-- Insurance Type (Only for Renewal or general edit) --}}
            @can('edit-employees')
            <div class="ms-md-2" x-data="{
                isEditing: false,
                type: '{{ $employee->insurance_type }}',
                hospital: '{{ $employee->hospital_name ?? $employee->insurance_detail }}',
                company: '{{ $employee->insurance_company ?? $employee->insurance_detail_private }}',
                saveInsurance() {
                    let body = {
                        insurance_type: this.type,
                        _token: '{{ csrf_token() }}'
                    };
                    if (this.type === 'ประกันสังคม' || this.type === 'ประกันโรงพยาบาล') {
                        body.insurance_detail_social = this.hospital; // Maps to hospital_name
                        body.insurance_detail_hospital = this.hospital;
                    } else if (this.type === 'ประกันเอกชน') {
                        body.insurance_detail_private = this.company;
                    }

                    fetch('/production/renewal/{{ $employee->id }}/update-insurance', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(body)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            if(data.html) updateCardHTML({{ $employee->id }}, data.html);
                            showToast('{{ __('Insurance updated') }}', 'success');
                            window.dispatchEvent(new CustomEvent('insurance-updated', {
                                detail: {
                                    employeeId: {{ $employee->id }},
                                    insuranceType: this.type
                                }
                            }));
                        } else {
                            showToast(data.message || '{{ __('Error saving') }}', 'danger');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        showToast('{{ __('Error saving') }}', 'danger');
                    });
                }
            }">
                <div style="min-width: 140px;">
                    <small class="text-muted d-block" style="font-size: 0.7rem;">{{ __('Insurance') }}</small>

                    <div x-show="!isEditing" class="d-flex align-items-center gap-2 cursor-pointer position-relative"
                         @click="isEditing = true">
                         <div class="small border rounded px-2 py-1 bg-white shadow-sm d-flex flex-column justify-content-center w-100" style="min-height: 38px;">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fw-bold text-primary" x-text="type || '-'"></span>
                                <i class="bi bi-pencil-fill text-muted" style="font-size: 0.7rem;"></i>
                            </div>
                            <div x-show="type === 'ประกันสังคม' && hospital" class="text-muted text-truncate" style="font-size: 0.7rem; max-width: 130px;">
                                <i class="bi bi-hospital me-1"></i><span x-text="hospital"></span>
                            </div>
                            <div x-show="type === 'ประกันโรงพยาบาล' && hospital" class="text-muted text-truncate" style="font-size: 0.7rem; max-width: 130px;">
                                <i class="bi bi-hospital me-1"></i><span x-text="hospital"></span>
                            </div>
                            <div x-show="type === 'ประกันเอกชน' && company" class="text-muted text-truncate" style="font-size: 0.7rem; max-width: 130px;">
                                <i class="bi bi-building me-1"></i><span x-text="company"></span>
                            </div>
                         </div>
                    </div>

                    <div x-show="isEditing" @click.outside="isEditing = false" class="flex-column gap-2 p-2 bg-white border rounded shadow-sm position-absolute" style="display: none; z-index: 1060; min-width: 220px;">
                         <label class="small fw-bold">{{ __('Select Type') }}</label>
                         <select class="form-select form-select-sm" x-model="type">
                             <option value="">{{ __('None') }}</option>
                             <option value="ประกันสังคม">{{ __('ประกันสังคม') }}</option>
                             <option value="ประกันเอกชน">{{ __('ประกันเอกชน') }}</option>
                             <option value="ประกันโรงพยาบาล">{{ __('ประกันโรงพยาบาล') }}</option>
                         </select>

                         <div x-show="type === 'ประกันสังคม' || type === 'ประกันโรงพยาบาล'">
                             <label class="small fw-bold mt-1">{{ __('Hospital') }}</label>
                             <input type="text" class="form-control form-control-sm" x-model="hospital" placeholder="Hospital Name">
                         </div>

                         <div x-show="type === 'ประกันเอกชน'">
                             <label class="small fw-bold mt-1">{{ __('Company') }}</label>
                             <input type="text" class="form-control form-control-sm" x-model="company" placeholder="Insurance Company">
                         </div>

                         <div class="d-flex gap-1 mt-2">
                            <button @click="saveInsurance()" class="btn btn-sm btn-success flex-grow-1"><i class="bi bi-check-lg"></i> {{ __('Save') }}</button>
                            <button @click="isEditing = false" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></button>
                         </div>
                    </div>
                </div>
            </div>
            @endcan

            {{-- Appointment Date & Location (Copied from Workflow) --}}
            @php
                $appDate = $employee->appointment_date;
                $appDisplay = '-';
                $appValue = '';
                if ($appDate) {
                    $appValue = $appDate->format('Y-m-d H:i');
                    if ($appDate->format('H:i:s') === '00:00:00') {
                        $appDisplay = $appDate->format('d/m/Y');
                    } else {
                        $appDisplay = $appDate->format('d/m/Y H:i');
                    }
                }
                $appLocation = $employee->appointment_location ?? '';
                $isAppCompleted = $employee->appointment_completed_at ? true : false;
                $appUpdatedByName = $employee->appointmentUpdatedBy->name ?? '';
                $appUpdatedAtHuman = $employee->appointment_updated_at ? $employee->appointment_updated_at->diffForHumans() : '';
            @endphp

            <div class="ms-md-2" x-data="{
                isEditing: false,
                isAppCompleted: {{ $isAppCompleted ? 'true' : 'false' }},
                dateValue: '{{ $appValue }}',
                displayValue: '{{ $appDisplay }}',
                locationValue: '{{ $appLocation }}',
                updatedByName: '{{ $appUpdatedByName }}',
                updatedAtHuman: '{{ $appUpdatedAtHuman }}',
                initFlatpickr() {
                    if (this.$refs.dateInput._flatpickr) return;
                    flatpickr(this.$refs.dateInput, {
                        enableTime: true,
                        dateFormat: 'Y-m-d H:i',
                        altInput: true,
                        altFormat: 'd/m/Y H:i',
                        time_24hr: true,
                        defaultDate: this.dateValue,
                        onChange: (selectedDates, dateStr) => {
                            this.dateValue = dateStr;
                        }
                    });
                },
                toggleAppComplete() {
                    fetch('/production/registration/{{ $employee->id }}/appointment-complete', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    }).then(res => res.json()).then(data => {
                        if(data.success) {
                            // Logic handled by x-model
                        } else {
                            this.isAppCompleted = !this.isAppCompleted; // revert
                        }
                    });
                },
                saveDate() {
                    fetch('/production/registration/{{ $employee->id }}/appointment', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ appointment_date: this.dateValue, appointment_location: this.locationValue })
                    }).then(res => res.json()).then(data => {
                        if(data.success) {
                            this.isEditing = false;

                            if (data.appointment_updated_by_name) {
                                this.updatedByName = data.appointment_updated_by_name;
                                this.updatedAtHuman = data.appointment_updated_at_human;
                            }

                            // Update display logic
                            if (!this.dateValue) {
                                this.displayValue = '-';
                            } else {
                                try {
                                    let parts = this.dateValue.split(' ');
                                    let dateParts = parts[0].split('-'); // [YYYY, MM, DD]
                                    let timePart = parts[1];
                                    let displayDate = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
                                    if (timePart === '00:00') {
                                        this.displayValue = displayDate;
                                    } else {
                                        this.displayValue = `${displayDate} ${timePart}`;
                                    }
                                } catch (e) {
                                    this.displayValue = this.dateValue;
                                }
                            }
                        }
                    });
                }
            }">
                <div style="min-width: 170px;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">{{ __('Appointment') }}</small>
                        <div class="form-check form-switch" title="{{ __('Mark Appointment Completed') }}">
                            <input class="form-check-input cursor-pointer" type="checkbox" x-model="isAppCompleted" @change="toggleAppComplete()">
                        </div>
                    </div>

                    <div x-show="!isEditing" class="cursor-pointer position-relative"
                         @click="isEditing = true; $nextTick(() => initFlatpickr())"
                         :class="{ 'opacity-50': isAppCompleted }">

                         <div class="text-primary fw-bold small border rounded px-2 py-1 bg-white shadow-sm d-flex flex-column justify-content-center px-2 w-100" style="min-height: 38px;">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar-event text-warning me-1"></i>
                                <span x-text="displayValue"></span>
                                <i x-show="isAppCompleted" class="bi bi-check-circle-fill text-success ms-auto"></i>
                            </div>
                            <div x-show="locationValue" class="text-muted" style="font-size: 0.7rem;">
                                <i class="bi bi-geo-alt me-1"></i><span x-text="locationValue"></span>
                            </div>
                         </div>

                         <!-- Appt Updated By -->
                         <div x-show="updatedByName" class="mt-1 text-start" style="font-size: 0.65rem;" x-cloak>
                            <span class="text-muted"><i class="bi bi-clock"></i> <span x-text="updatedAtHuman"></span> โดย <span x-text="updatedByName"></span></span>
                         </div>
                    </div>

                    <div x-show="isEditing" @click.outside="isEditing = false" :class="{ 'd-flex': isEditing }" class="flex-column gap-1 p-2 bg-white border rounded shadow-sm" style="display: none; position: absolute; z-index: 1050; min-width: 200px;">
                         <label class="small fw-bold">Date & Time</label>
                         <div>
                            <input x-ref="dateInput" type="text" class="form-control form-control-sm" placeholder="Date...">
                         </div>

                         <label class="small fw-bold mt-1">Location</label>
                         <input x-model="locationValue" type="text" class="form-control form-control-sm" placeholder="e.g. Office">

                         <div class="d-flex gap-1 mt-2">
                            <button @click="saveDate()" class="btn btn-sm btn-success flex-grow-1"><i class="bi bi-check-lg"></i> Save</button>
                            <button @click="isEditing = false" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></button>
                         </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column gap-2 ms-md-2">
            {{-- REMARKS SECTION --}}
            @php
                $isRenewal = request()->is('production/renewal*');
                $remarkText = $isRenewal ? $employee->renewal_remarks : $employee->registration_remarks;
                // Need absolute or generated URL because we are in shared partial, but we will use route named params if possible
                // It's safer to generate based on request
                $remarkUrl = $isRenewal
                    ? route('production.renewal.remarks', $employee->id)
                    : route('production.registration.remarks', $employee->id);
            @endphp
            <div x-data="{
                remarkText: {{ json_encode($remarkText ?? '') }},
                openRemarkPopup() {
                    Swal.fire({
                        title: '{{ __("แก้ไขหมายเหตุ") }}',
                        input: 'textarea',
                        inputValue: this.remarkText,
                        inputPlaceholder: 'กรอกข้อความหมายเหตุ...',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: '{{ __("บันทึก") }}',
                        cancelButtonText: '{{ __("ยกเลิก") }}',
                        showLoaderOnConfirm: true,
                        preConfirm: (text) => {
                            return fetch('{{ $remarkUrl }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ remarks: text })
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(response.statusText)
                                }
                                return response.json().then(data => ({ data, text }));
                            })
                            .catch(error => {
                                Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error}`);
                            });
                        },
                        allowOutsideClick: () => !Swal.isLoading()
                    }).then((result) => {
                        if (result.isConfirmed && result.value.data.success) {
                            this.remarkText = result.value.data.remarks ?? result.value.text;
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'บันทึกหมายเหตุสำเร็จ',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        } else if (result.isConfirmed && !result.value.data.success) {
                            Swal.fire('Error', 'เกิดข้อผิดพลาดในการบันทึก', 'error');
                        }
                    });
                }
            }">
                <div style="min-width: 140px; max-width: 250px;">
                    <small class="text-muted d-block fw-bold" style="font-size: 0.7rem;">หมายเหตุ</small>

                    <div class="d-flex align-items-start gap-1 mb-2">
                        <div class="text-dark small border rounded px-2 py-1 bg-light flex-grow-1 text-wrap overflow-hidden" style="min-height: 31px; word-break: break-word;">
                            <span x-text="remarkText || '-'"></span>
                        </div>
                        <button @click="openRemarkPopup()" class="btn btn-sm btn-outline-secondary rounded-circle flex-shrink-0" style="padding: 2px 6px;" title="แก้ไขหมายเหตุ">
                            <i class="bi bi-pencil-fill" style="font-size: 0.75rem;"></i>
                        </button>
                    </div>
                </div>
            </div>

            @php
                $isRegistration = request()->is('*registration*');
                $isRenewal = request()->is('*renewal*');
                $isPreProduction = request()->is('*production*') && !$isRegistration && !$isRenewal;
                $isWorkflow = request()->is('*workflow*');

                // Determine request number
                $currentRequestNumber = '';
                $updateUrl = route('employees.update', $employee->id);
                $updateMethod = 'employee_update';

                if (isset($item) && $item instanceof \App\Models\ProductionItem) {
                    $currentRequestNumber = $item->request_number;
                    $updateUrl = route('production.items.update_fields', $item->id);
                    $updateMethod = 'item_update';
                } elseif ($isRegistration) {
                    $currentRequestNumber = $employee->registration_request_number;
                    $updateUrl = route('employees.update_menu_fields', $employee->id);
                    $updateMethod = 'menu_update';
                } elseif ($isRenewal) {
                    $currentRequestNumber = $employee->renewal_request_number;
                    $updateUrl = route('employees.update_menu_fields', $employee->id);
                    $updateMethod = 'menu_update';
                } else {
                    $currentRequestNumber = $employee->request_number; // Fallback
                }
            @endphp

            {{-- 3 Extra Fields (Editable) --}}
            <div class="d-flex flex-column gap-2" x-data="{
                isEditing: false,
                nameList: '{{ $employee->name_list_number }}',
                reqNo: '{{ $currentRequestNumber }}',
                refId: '{{ $employee->employee_reference_id }}',
                updateMethod: '{{ $updateMethod }}',
                updateUrl: '{{ $updateUrl }}',
                copy(el, text) {
                    if (!text) return;
                    navigator.clipboard.writeText(text).then(() => {
                        const originalHtml = el.innerHTML;
                        el.innerHTML = '<i class=\'bi bi-check text-success\'></i>';
                        setTimeout(() => el.innerHTML = originalHtml, 1000);
                    });
                },
                fitText(el) {
                    if (!el) return;
                    el.style.fontSize = '';
                    this.$nextTick(() => {
                        if (el.offsetParent === null) return;
                        if (el.scrollWidth > el.clientWidth) {
                             let size = 87.5;
                             while (el.scrollWidth > el.clientWidth && size > 50) {
                                 size -= 5;
                                 el.style.fontSize = size + '%';
                             }
                        }
                    });
                },
                init() {
                    this.$watch('isEditing', value => {
                        if (!value) {
                            this.$nextTick(() => {
                                this.fitText(this.$refs.raDisplay);
                                this.fitText(this.$refs.reqDisplay);
                                this.fitText(this.$refs.refDisplay);
                            });
                        }
                    });
                },
                saveFields() {
                    let formData = new FormData();
                    formData.append('_method', 'PUT');
                    formData.append('_token', '{{ csrf_token() }}');

                    if (this.updateMethod === 'item_update') {
                        formData.append('request_number', this.reqNo);
                    } else if (this.updateMethod === 'menu_update') {
                        formData.append('type', '{{ $isRegistration ? 'registration' : 'renewal' }}');
                        formData.append('request_number', this.reqNo);
                    }

                    // Request 1: Update the specific request number (item or menu)
                    let req1 = fetch(this.updateUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    // Request 2: Update the global fields (name_list_number, employee_reference_id)
                    let empFormData = new FormData();
                    empFormData.append('_method', 'PUT');
                    empFormData.append('_token', '{{ csrf_token() }}');
                    empFormData.append('name_list_number', this.nameList);
                    empFormData.append('employee_reference_id', this.refId);
                    empFormData.append('employer_id', '{{ $employee->employer_id }}');
                    empFormData.append('employeeNameEn', '{{ $employee->employeeNameEn }}');

                    let req2 = fetch('{{ route('employees.update', $employee->id) }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: empFormData
                    });

                    Promise.all([req1, req2])
                    .then(responses => Promise.all(responses.map(res => res.json())))
                    .then(dataArray => {
                        showToast('{{ __('Saved successfully') }}', 'success');
                        this.isEditing = false;
                    })
                    .catch(err => {
                        console.error(err);
                        showToast('{{ __('Error saving') }}', 'danger');
                    });
                }
            }">
                <div class="d-flex align-items-center gap-2">
                    {{-- Field 1: Name List (Renamed to RA) --}}
                    <div style="min-width: 140px; max-width: 250px; flex-grow: 1;">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">เลข RA จากระบบ outsource</small>
                        <div x-show="!isEditing" class="d-flex align-items-center justify-content-between small text-dark border rounded px-2 py-1 bg-light overflow-hidden" style="min-height: 31px;">
                            <div x-ref="raDisplay" x-init="fitText($el)" class="text-nowrap overflow-hidden flex-grow-1" x-text="nameList || '-'"></div>
                            <button x-show="nameList" @click="copy($event.currentTarget, nameList)" class="btn btn-link p-0 text-secondary ms-1 flex-shrink-0" title="{{ __('Copy') }}">
                                <i class="bi bi-clipboard" style="font-size: 0.8rem;"></i>
                            </button>
                        </div>
                        <input x-show="isEditing" type="text" class="form-control form-control-sm" x-model="nameList" placeholder="RA No.">
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex gap-1">
                        <button x-show="!isEditing" @click="isEditing = true" class="btn btn-sm btn-outline-secondary rounded-circle mt-3" title="Edit Fields">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button x-show="isEditing" @click="saveFields()" class="btn btn-sm btn-success rounded-circle mt-3" title="Save Fields">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <button x-show="isEditing" @click="isEditing = false" class="btn btn-sm btn-outline-danger rounded-circle mt-3" title="Cancel">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                {{-- Field 2: Request No --}}
                <div style="min-width: 140px; max-width: 250px;">
                    <small class="text-muted d-block" style="font-size: 0.7rem;">เลขที่คำขอ</small>
                    <div x-show="!isEditing" class="d-flex align-items-center justify-content-between small text-dark border rounded px-2 py-1 bg-light overflow-hidden" style="min-height: 31px;">
                        <div x-ref="reqDisplay" x-init="fitText($el)" class="text-nowrap overflow-hidden flex-grow-1" x-text="reqNo || '-'"></div>
                        <button x-show="reqNo" @click="copy($event.currentTarget, reqNo)" class="btn btn-link p-0 text-secondary ms-1 flex-shrink-0" title="{{ __('Copy') }}">
                            <i class="bi bi-clipboard" style="font-size: 0.8rem;"></i>
                        </button>
                    </div>
                    <input x-show="isEditing" type="text" class="form-control form-control-sm" x-model="reqNo" placeholder="Request No.">
                </div>

                {{-- Field 3: Ref ID --}}
                <div style="min-width: 140px; max-width: 250px;">
                    <small class="text-muted d-block" style="font-size: 0.7rem;">เลขอ้างอิงคนงาน</small>
                    <div x-show="!isEditing" class="d-flex align-items-center justify-content-between small text-dark border rounded px-2 py-1 bg-light overflow-hidden" style="min-height: 31px;">
                        <div x-ref="refDisplay" x-init="fitText($el)" class="text-nowrap overflow-hidden flex-grow-1" x-text="refId || '-'"></div>
                        <button x-show="refId" @click="copy($event.currentTarget, refId)" class="btn btn-link p-0 text-secondary ms-1 flex-shrink-0" title="{{ __('Copy') }}">
                            <i class="bi bi-clipboard" style="font-size: 0.8rem;"></i>
                        </button>
                    </div>
                    <input x-show="isEditing" type="text" class="form-control form-control-sm" x-model="refId" placeholder="Ref ID">
                </div>
            </div>


            </div>

            </div>

            {{-- Actions --}}
            <div class="d-flex gap-2 flex-wrap justify-content-end">
                 {{-- Daily Check --}}
                 <div class="d-flex align-items-center me-2" x-data="{
                    dailyCheckEnabled: {{ $employee->daily_check_enabled ? 'true' : 'false' }},
                    isPending: {{ $employee->is_daily_check_pending ? 'true' : 'false' }},
                    checking: false,
                    toggleDailyCheck() {
                        let url = '{{ request()->is('production/registration*') ? route('production.registration.toggle_daily_check', $employee->id) : route('production.renewal.toggle_daily_check', $employee->id) }}';
                        fetch(url, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                        }).then(res => res.json()).then(data => {
                            if(data.success) {
                                this.dailyCheckEnabled = data.enabled;
                                this.isPending = data.pending;
                                if (typeof updateDailyCheckScoreboard === 'function') {
                                    updateDailyCheckScoreboard(data.enabled, data.pending);
                                }
                            }
                        });
                    },
                    checkDaily() {
                        if(this.checking) return;
                        this.checking = true;
                        let url = '{{ request()->is('production/registration*') ? route('production.registration.check_daily', $employee->id) : route('production.renewal.check_daily', $employee->id) }}';
                        fetch(url, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                        }).then(res => res.json()).then(data => {
                            this.checking = false;
                            if(data.success) {
                                this.isPending = false;
                                if (typeof updateDailyCheckScoreboard === 'function') {
                                    updateDailyCheckScoreboard(true, false); // true=enabled, false=pending (decrements count)
                                }
                            }
                        });
                    }
                 }">
                    <span class="me-2 text-muted" style="font-size: 0.75rem;">Check</span>
                    <div class="form-check form-switch mb-0" title="Enable Daily Check">
                        <input class="form-check-input" type="checkbox" role="switch"
                            :checked="dailyCheckEnabled"
                            @change="toggleDailyCheck()">
                    </div>

                    <button x-show="dailyCheckEnabled && isPending"
                        @click="checkDaily()"
                        class="btn btn-warning btn-sm ms-2 rounded-circle animate__animated animate__pulse animate__infinite"
                        style="width: 30px; height: 30px; padding: 0;"
                        :disabled="checking"
                        title="Mark as Checked Today">
                        <i class="bi bi-calendar-check" :class="{'bi-arrow-clockwise fa-spin': checking}"></i>
                    </button>
                 </div>

                 @can('edit-employees')
                 {{-- Biometrics Button --}}
                 <input type="file" id="biometrics-input-{{ $employee->id }}" class="d-none" onchange="if(window.interceptFileSelect) window.interceptFileSelect(event); if(this.files.length > 0) uploadBiometrics({{ $employee->id }})" multiple>

                 <div class="btn-group">
                     {{-- Toggle Tick Button --}}
                     <button class="btn btn-sm {{ $employee->biometrics_collected_at ? 'btn-success' : 'btn-outline-secondary' }} rounded-start-pill px-3"
                         id="btn-biometrics-toggle-{{ $employee->id }}"
                         title="{{ __('Mark Biometrics Collected') }}"
                         onclick="toggleBiometricsStatus({{ $employee->id }})">
                         <i class="bi bi-person-bounding-box"></i>
                         @if($employee->biometrics_collected_at) <i class="bi bi-check-lg ms-1"></i> @endif
                     </button>

                     {{-- Scan/Upload Button --}}
                     <button class="btn btn-sm {{ $employee->employee_doc_9 ? 'btn-success' : 'btn-outline-warning' }} rounded-end-pill px-3 biometrics-btn border-start-0"
                         id="btn-biometrics-{{ $employee->id }}"
                         data-collected="{{ $employee->employee_doc_9 ? 'true' : 'false' }}"
                         title="{{ __('Scan / Upload Biometrics') }}"
                         onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'biometrics-input-{{ $employee->id }}' } }))">
                         <i class="bi bi-fingerprint"></i>
                         <span class="d-none d-lg-inline">{{ $employee->employee_doc_9 ? __('Collected') : __('Biometrics') }}</span>
                     </button>
                 </div>
                 @endcan

                 {{-- Preview Button (Universal) --}}
                 <button class="btn btn-sm btn-outline-info btn-preview rounded-pill px-3"
                    data-model-type="employee"
                    data-model-id="{{ $employee->id }}"
                    title="Preview">
                    <i class="bi bi-eye-fill"></i>
                </button>

                 @can('edit-employees')
                 {{-- Inline Drawer Toggle --}}
                 <button class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Custom Fields"
                    onclick="toggleInlineDrawer({{ $employee->id }}, {{ json_encode($employee) }})">
                    <i class="bi bi-layout-text-window-reverse"></i> {{ __('Fields') }}
                </button>

                <button class="btn btn-sm btn-outline-warning rounded-pill px-3" title="Edit"
                    onclick="openEditEmployeeModal({{ $employee->id }})">
                    <i class="bi bi-pencil-fill"></i>
                </button>

                {{-- SAVE TO DB --}}
                <button class="btn btn-sm btn-success rounded-pill px-3 {{ ($isCompleted || $isCancelled || $isHistory) ? 'd-none' : '' }}"
                    id="btn-save-{{ $employee->id }}"
                    title="Save to Database"
                    onclick="finalizeEmployee({{ $employee->id }})">
                    <i class="bi bi-check-lg"></i> <span class="d-none d-lg-inline">{{ __('Save to DB') }}</span>
                </button>

                {{-- CANCEL --}}
                <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 {{ ($isCompleted || $isCancelled || $isHistory) ? 'd-none' : '' }}"
                    id="btn-cancel-{{ $employee->id }}"
                    title="Cancel Registration"
                    onclick="cancelEmployee({{ $employee->id }})">
                    <i class="bi bi-x-circle"></i> <span class="d-none d-lg-inline">{{ __('Cancel') }}</span>
                </button>

                {{-- RESTORE (For Cancelled) --}}
                <button class="btn btn-sm btn-outline-warning rounded-pill px-3 {{ (!$isCancelled || $isHistory) ? 'd-none' : '' }}"
                    id="btn-restore-{{ $employee->id }}"
                    title="Restore"
                    onclick="restoreEmployeeState({{ $employee->id }})">
                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('Restore') }}
                </button>

                {{-- UNDO (For Completed) --}}
                <button class="btn btn-sm btn-outline-warning rounded-pill px-3 {{ (!$isCompleted || $isHistory) ? 'd-none' : '' }}"
                    id="btn-undo-{{ $employee->id }}"
                    title="Undo / Restore"
                    onclick="restoreEmployeeState({{ $employee->id }})">
                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('Undo') }}
                </button>

                {{-- Delete (Soft) --}}
                <button class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Delete" onclick="deleteEmployee({{ $employee->id }})">
                    <i class="bi bi-trash-fill"></i>
                </button>
                @endcan
            </div>
        </div>

        {{-- COUNTDOWN TIMER (If Completed & Within 24h) --}}
        @if($isCompleted && $employee->resolution_completed_at && !$isHistory)
            @php
                $completedAt = \Carbon\Carbon::parse($employee->resolution_completed_at);
                $expiresAt = $completedAt->copy()->addHours(24);
                $expiresAtTimestamp = $expiresAt->timestamp * 1000; // MS for JS
                $isExpired = $expiresAt->isPast();
            @endphp
            @if(!$isExpired)
                <div class="mt-3 w-100 d-flex justify-content-center" x-data="{
                    expires: {{ $expiresAtTimestamp }},
                    displayText: '',
                    init() {
                         this.update();
                         setInterval(() => this.update(), 60000); // Check every minute
                    },
                    update() {
                         const now = new Date().getTime();
                         const diff = this.expires - now;
                         if (diff <= 0) {
                             this.displayText = '{{ __('Locked') }}';
                         } else {
                             const totalMinutes = Math.floor(diff / (1000 * 60));
                             const hours = Math.floor(totalMinutes / 60);
                             const minutes = totalMinutes % 60;

                             if (hours >= 1) {
                                 this.displayText = hours + ' {{ __('Hours remaining') }}';
                             } else {
                                 this.displayText = minutes + ' {{ __('Minutes remaining') }}';
                             }
                         }
                    }
                }">
                    <span class="badge bg-success fs-6 shadow-sm px-3 py-2" x-text="displayText" x-show="displayText !== '{{ __('Locked') }}'"></span>
                </div>
            @endif
        @endif

        {{-- Steps Progress Bar (Disable interaction if completed/cancelled) --}}
        <div class="mt-3 {{ $overlayClass }}" id="steps-container-{{ $employee->id }}">
            <div class="d-flex gap-2 flex-wrap">
                @foreach($steps as $step)
                    @php
                        $isStepCompleted = $employee->registrationSteps->contains($step->id);
                        // Determine styles based on hex or class
                        $hexColor = str_starts_with($step->color, '#') ? $step->color : null;

                        // Default State: Incomplete -> Solid Gray (visible "To Do" state)
                        $btnClass = 'btn-light border text-secondary';
                        $btnStyle = '';

                        // Completed State: Colored background
                        if ($isStepCompleted) {
                            if ($hexColor) {
                                $btnClass = 'text-white border-0';
                                $btnStyle = "background-color: {$hexColor} !important; border-color: {$hexColor} !important;";
                            } else {
                                // For standard bootstrap classes like 'primary', 'success', etc.
                                // We check if it is one of the standard contextual classes
                                if (in_array($step->color, ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'])) {
                                     $btnClass = "btn-{$step->color} text-white";
                                     if($step->color == 'warning' || $step->color == 'light') {
                                         $btnClass = "btn-{$step->color} text-dark"; // Better contrast
                                     }
                                } else {
                                    // Fallback for custom strings that aren't hex or standard
                                     $btnClass = "btn-primary text-white";
                                }
                            }
                        }
                    @endphp
                        @php
                            $canManage = auth()->user()->can('edit-employees');
                            $disabled = ($isCompleted || $isCancelled || !$canManage) ? 'disabled' : '';
                            $pointerEvents = !$canManage ? 'pointer-events: none;' : '';
                            $onclick = $canManage ? "onclick=\"toggleStep({$employee->id}, {$step->id}, " . ($isStepCompleted ? 'false' : 'true') . ")\"" : '';
                        @endphp
                    <button
                        class="btn btn-sm {{ $btnClass }} rounded-pill px-3"
                            style="font-size: 0.8rem; {{ $btnStyle }} {{ $pointerEvents }}"
                            {!! $onclick !!}
                        data-step-id="{{ $step->id }}"
                        data-color="{{ $step->color }}"
                        data-hex-color="{{ $hexColor }}"
                            {{ $disabled }}
                    >
                        {{ $step->name }}
                        @if($isStepCompleted) <i class="bi bi-check-circle-fill ms-1"></i> @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Inline Drawer Container --}}
        <div class="collapse mt-3" id="drawer-employee-{{ $employee->id }}">
            <div class="card card-body bg-light border-0 rounded-3">
                <div id="drawer-content-{{ $employee->id }}" class="position-relative" style="min-height: 100px;">
                    <div class="d-flex justify-content-center align-items-center h-100 py-3">
                         <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                         <span class="ms-2 small text-muted">Loading fields...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
