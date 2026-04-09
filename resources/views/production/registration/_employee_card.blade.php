@props(['employee', 'steps', 'isHistory' => false, 'show_employer' => false])

@php
    $isCompleted = in_array($employee->status, ['registration_completed', 'renewal_completed']);
    $isCancelled = in_array($employee->status, ['registration_cancelled', 'renewal_cancelled']);

    // Support for Pre-Production / Workflow where the card is reused and status is on the item
    if (isset($employee->production_item)) {
        $isCompleted = $employee->production_item->status === 'completed';
        $isCancelled = $employee->production_item->status === 'cancelled';
    }

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

    // Determine the relevant steps relationship depending on the active module
    $completedStepsCollection = isset($employee->production_item)
        ? $employee->production_item->completedWorkTypeSteps
        : $employee->registrationSteps;

    // Determine Highest Completed Step for Filtering
    $highestStep = $completedStepsCollection->sortByDesc('order')->first();
    $highestStepId = $highestStep ? $highestStep->id : '';

    // "Not Started" logic must exactly match the backend: missing Step 1.
    // If they have steps 2, 3, etc. but no step 1, they are STILL considered "Not Started".
    // We check if the collection contains any step with 'order' === 1 to avoid relying on external variables.
    $hasStepOne = $completedStepsCollection->where('order', 1)->isNotEmpty();

    $isNotStarted = (!$isCancelled && !$hasStepOne);

    // Contextual status for JS
    $itemStatus = isset($employee->production_item) ? $employee->production_item->status : $employee->status;

    // Operator Logic
    $operator = $employee->operator;
    $operatorId = $employee->operator_id;
    $customOperatorName = $employee->custom_operator_name;
    $isMe = $operatorId === auth()->id();

    if ($customOperatorName) {
        $operatorName = $customOperatorName . ' (ภายนอก)';
        $operatorId = 'external'; // Used for JS logic
    } else {
        $operatorName = $operator ? $operator->name : null;
    }
@endphp

<div class="d-flex align-items-center employee-card-outer mb-3 employee-card-wrapper {{ $isCancelled ? 'status-cancelled' : '' }}"
     id="employee-card-{{ $employee->id }}"
     data-highest-step-id="{{ $highestStepId }}"
     data-status="{{ $itemStatus }}"
     data-is-not-started="{{ $isNotStarted ? 'true' : 'false' }}"
     data-employer-id="{{ $employee->employer_id }}"
     data-biometrics-collected="{{ $employee->biometrics_collected_at ? 'true' : 'false' }}"
     style="transition: all 0.3s ease; {{ $isCancelled ? 'filter: grayscale(100%);' : '' }}">

    <div class="card {{ $cardClass }} w-100 position-relative">
    <div class="employee-sequence-number"></div>

    <x-last-edited-badge :model="$employee" />

    {{-- Operator Badge (Bottom Right) --}}
    <div class="position-absolute bottom-0 end-0 m-2 z-index-10">
        @php
            $toggleUrl = request()->is('production/renewal*')
                ? route('production.renewal.toggle_operator', $employee->id)
                : route('production.registration.toggle_operator', $employee->id);

            $btnClass = 'btn-outline-secondary';
            if ($customOperatorName) {
                $btnClass = 'btn-info text-dark';
            } else if ($operatorId && $operatorId !== 'external') {
                $btnClass = $isMe ? 'btn-primary' : 'btn-secondary';
            }
        @endphp
        <button class="btn btn-sm {{ $btnClass }} rounded-pill shadow-sm py-0 px-2"
                style="font-size: 0.75rem; border-width: 1px;"
                @hasanyrole('super-admin|admin|staff')
                onclick="window.toggleOperator ? window.toggleOperator({{ $employee->id }}, this, '{{ $employee->operator_id ?? '' }}', '{{ addslashes($customOperatorName ?? '') }}', '{{ $toggleUrl }}') : console.error('toggleOperator not defined')"
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
        <div class="d-flex flex-column justify-content-between align-items-start gap-3">
            {{-- Checkbox & Basic Info --}}
            <div class="d-flex align-items-start gap-3 w-100 emp-info-section">
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
                           data-status="{{ $itemStatus }}"
                           data-name-th="{{ $employee->employeeNameTh }}"
                           data-name-en="{{ $employee->employeeNameEn }}"
                           data-title-th="{{ $employee->employeeTitleTh }}"
                           data-title-en="{{ $employee->employeeTitleEn }}"
                           data-nationality="{{ $employee->employeeNationality }}"
                           data-photo="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/40x40/e2e8f0/6c757d?text=PIC' }}"
                           data-employer-name="{{ $employee->employer->employerNameTh ?? 'N/A' }}"
                           data-insurance-type="{{ $employee->insurance_type }}"
                           data-passport="{{ $employee->employeePassport }}"
                           data-production-item-id="{{ isset($employee->production_item) ? $employee->production_item->id : '' }}">
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
                        <div class="fw-bold text-dark d-flex align-items-center gap-1 flex-wrap">
                             {{-- English Name + Title --}}
                            <span>{{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? '-' }}</span>
                            {{-- Employee Preview Button --}}
                            <button class="btn btn-sm btn-link p-0 btn-preview" style="text-decoration: none; line-height: 1;" data-model-type="employee" data-model-id="{{ $employee->id }}" title="{{ __('Preview Employee') }}">
                                <i class="bi bi-search text-info"></i>
                            </button>
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

            {{-- Outsource Login Information --}}
            @can('edit-employees')
            <div class="ms-md-2" x-data="{
                isEditing: false,
                email: '{{ $employee->email ?? '' }}',
                originalEmail: '{{ $employee->email ?? '' }}',
                password: '{{ $employee->password ?? '' }}',
                originalPassword: '{{ $employee->password ?? '' }}',
                outsource_code: '{{ $employee->outsource_code ?? '' }}',
                originalOutsourceCode: '{{ $employee->outsource_code ?? '' }}',
                saveOutsourceLogin() {
                    let body = {
                        email: this.email,
                        password: this.password,
                        outsource_code: this.outsource_code,
                        _token: '{{ csrf_token() }}'
                    };

                    fetch('/production/{{ $employee->id }}/update-outsource-login', {
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
                            showToast('{{ __('Login Info updated') }}', 'success');
                            this.originalEmail = this.email;
                            this.originalPassword = this.password;
                            this.originalOutsourceCode = this.outsource_code;
                            this.isEditing = false;
                        } else {
                            showToast(data.message || '{{ __('Error saving') }}', 'danger');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        showToast('{{ __('Error saving') }}', 'danger');
                    });
                },
                copyToClipboard(text, fieldName) {
                    if (!text) return;
                    navigator.clipboard.writeText(text).then(() => {
                        showToast('คัดลอก ' + fieldName + ' สำเร็จ', 'success');
                    }).catch(err => {
                        console.error('Failed to copy text: ', err);
                    });
                }
            }">
                <div style="min-width: 250px;">
                    <small class="text-muted d-block" style="font-size: 0.7rem;"><i class="bi bi-lock-fill"></i> {{ __('ข้อมูลการเข้าสู่ระบบ Outsource') }}</small>

                    <div x-show="!isEditing" class="d-flex align-items-center gap-2 position-relative">
                         <div class="small border rounded px-2 py-1 bg-white shadow-sm d-flex flex-column justify-content-center w-100" style="min-height: 38px;">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold text-primary">Login Info</span>
                                <i class="bi bi-pencil-fill text-muted cursor-pointer" style="font-size: 0.7rem;" @click="isEditing = true"></i>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-1" style="font-size: 0.75rem;">
                                <span class="text-muted text-truncate" style="max-width: 150px;">
                                    <i class="bi bi-envelope me-1"></i><span x-text="email || '-'"></span>
                                </span>
                                <button x-show="email" @click="copyToClipboard(email, 'อีเมล')" class="btn btn-sm btn-link p-0 text-secondary" title="คัดลอกอีเมล"><i class="bi bi-clipboard"></i></button>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-1" style="font-size: 0.75rem;">
                                <span class="text-muted text-truncate" style="max-width: 150px;">
                                    <i class="bi bi-key me-1"></i><span x-text="password || '-'"></span>
                                </span>
                                <button x-show="password" @click="copyToClipboard(password, 'รหัสสำหรับอีเมล')" class="btn btn-sm btn-link p-0 text-secondary" title="คัดลอกรหัสผ่าน"><i class="bi bi-clipboard"></i></button>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-1" style="font-size: 0.75rem;">
                                <span class="text-muted text-truncate" style="max-width: 150px;">
                                    <i class="bi bi-shield-lock me-1"></i><span x-text="outsource_code || '-'"></span>
                                </span>
                                <button x-show="outsource_code" @click="copyToClipboard(outsource_code, 'รหัส Outsource')" class="btn btn-sm btn-link p-0 text-secondary" title="คัดลอกรหัส Outsource"><i class="bi bi-clipboard"></i></button>
                            </div>
                         </div>
                    </div>

                    <div x-show="isEditing" @click.outside="isEditing = false" class="flex-column gap-2 p-2 bg-white border rounded shadow-sm position-absolute" style="display: none; z-index: 1060; min-width: 250px;">

                         <label class="small fw-bold mt-1">อีเมล</label>
                         <input type="text" class="form-control form-control-sm" x-model="email" placeholder="Email">

                         <label class="small fw-bold mt-1">รหัสสำหรับอีเมล</label>
                         <input type="text" class="form-control form-control-sm" x-model="password" placeholder="Password">

                         <label class="small fw-bold mt-1">รหัสสำหรับระบบ Outsource</label>
                         <input type="text" class="form-control form-control-sm" x-model="outsource_code" placeholder="Outsource Code">

                         <div class="d-flex gap-1 mt-2">
                            <button @click="saveOutsourceLogin()" class="btn btn-sm btn-success flex-grow-1"><i class="bi bi-check-lg"></i> {{ __('Save') }}</button>
                            <button @click="isEditing = false; email = originalEmail; password = originalPassword; outsource_code = originalOutsourceCode;" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></button>
                         </div>
                    </div>
                </div>

                {{-- Appointment Date & Location --}}
                @php
                    $appDate = $employee->appointment_date ? \Carbon\Carbon::parse($employee->appointment_date) : null;
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
                    $isRenewalContext = request()->is('production/renewal*');
                    $modulePath = $isRenewalContext ? 'production/renewal' : 'production/registration';
                @endphp
                <div class="mt-2" style="min-width: 250px;" x-data="{
                    isAppCompleted: {{ $isAppCompleted ? 'true' : 'false' }},
                    dateValue: '{{ $appValue }}',
                    displayValue: '{{ $appDisplay }}',
                    locationValue: '{{ $appLocation }}',
                    toggleAppComplete() {
                        fetch('/{{ $modulePath }}/{{ $employee->id }}/appointment-complete', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                        }).then(res => res.json()).then(data => {
                            if(data.success) {
                                // toggled
                            } else {
                                this.isAppCompleted = !this.isAppCompleted; // revert
                            }
                        });
                    },
                    openEditModal() {
                        Swal.fire({
                            title: '{{ __("อัพเดทวันนัดหมาย") }}',
                            html: `
                                <div class='mb-3 text-start'>
                                    <label class='form-label fw-bold'>{{ __('Date & Time') }}</label>
                                    <input type='text' id='swal-dateInput' class='form-control bg-white' placeholder='{{ __('Select Date...') }}'>
                                </div>
                                <div class='mb-3 text-start'>
                                    <label class='form-label fw-bold'>{{ __('Location') }}</label>
                                    <input type='text' id='swal-locationValue' class='form-control' value='${this.locationValue}' placeholder='{{ __('e.g., Office A') }}'>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: '<i class=\'bi bi-check-lg\'></i> {{ __('Save') }}',
                            cancelButtonText: '{{ __('Cancel') }}',
                            confirmButtonColor: '#198754',
                            focusConfirm: false,
                            didOpen: () => {
                                flatpickr(document.getElementById('swal-dateInput'), {
                                    enableTime: true,
                                    dateFormat: 'Y-m-d H:i',
                                    altInput: true,
                                    altFormat: 'd/m/Y H:i',
                                    time_24hr: true,
                                    defaultDate: this.dateValue
                                });
                            },
                            preConfirm: () => {
                                return {
                                    date: document.getElementById('swal-dateInput').value,
                                    location: document.getElementById('swal-locationValue').value
                                }
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const data = result.value;
                                fetch('/{{ $modulePath }}/{{ $employee->id }}/appointment', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                                    body: JSON.stringify({ appointment_date: data.date, appointment_location: data.location })
                                }).then(res => res.json()).then(resData => {
                                    if(resData.success) {
                                        this.dateValue = data.date;
                                        this.locationValue = data.location;
                                        showToast('{{ __('Saved') }}', 'success');
                                        if (!this.dateValue) {
                                            this.displayValue = '-';
                                        } else {
                                            try {
                                                let parts = this.dateValue.split(' ');
                                                let dateParts = parts[0].split('-'); // [YYYY, MM, DD]
                                                let timePart = parts[1] || '00:00';
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
                        });
                    }
                }">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">{{ __('Appointment') }}</small>
                        <div class="form-check form-switch" title="{{ __('Mark Appointment Completed') }}">
                            <input class="form-check-input cursor-pointer" type="checkbox" x-model="isAppCompleted" @change="toggleAppComplete()" style="transform: scale(0.8);">
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 cursor-pointer position-relative"
                         @click="openEditModal()"
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
                    </div>
                </div>

            </div>
            @endcan
            <div class="d-flex flex-column gap-2 ms-md-2">
            <div class="d-flex gap-2 align-items-start">
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
                isEditing: false,
                remarkText: {{ json_encode($remarkText ?? '') }},
                tempRemarkText: '',
                isSaving: false,
                startEditing() {
                    this.tempRemarkText = this.remarkText;
                    this.isEditing = true;
                    this.$nextTick(() => { this.$refs.remarkInput.focus(); });
                },
                saveRemark() {
                    this.isSaving = true;
                    fetch('{{ $remarkUrl }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ remarks: this.tempRemarkText })
                    })
                    .then(response => {
                        if (!response.ok) throw new Error(response.statusText);
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            this.remarkText = data.remarks ?? this.tempRemarkText;
                            this.isEditing = false;
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'บันทึกหมายเหตุสำเร็จ',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        } else {
                            Swal.fire('Error', 'เกิดข้อผิดพลาดในการบันทึก', 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', `เกิดข้อผิดพลาด: ${error}`, 'error');
                    })
                    .finally(() => {
                        this.isSaving = false;
                    });
                }
            }">
                <div style="min-width: 140px; max-width: 250px;">
                    <small class="text-muted d-block fw-bold" style="font-size: 0.7rem;">หมายเหตุ</small>

                    <div class="d-flex align-items-start gap-1 mb-2">
                        <div x-cloak :style="{ display: !isEditing ? 'flex' : 'none' }" class="align-items-start gap-1 w-100">
                            <div class="text-dark small border rounded px-2 py-1 bg-light flex-grow-1 text-wrap overflow-hidden" style="min-height: 31px; word-break: break-word;">
                                <span x-text="remarkText || '-'"></span>
                            </div>
                            <button @click="startEditing()" class="btn btn-sm btn-outline-secondary rounded-circle flex-shrink-0" style="padding: 2px 6px;" title="แก้ไขหมายเหตุ">
                                <i class="bi bi-pencil-fill" style="font-size: 0.75rem;"></i>
                            </button>
                        </div>

                        <div x-cloak :style="{ display: isEditing ? 'block' : 'none' }" class="w-100">
                            <textarea x-ref="remarkInput" x-model="tempRemarkText" class="form-control form-control-sm mb-1" rows="3" placeholder="กรอกข้อความหมายเหตุ..."></textarea>
                            <div class="d-flex gap-1">
                                <button @click="saveRemark()" :disabled="isSaving" class="btn btn-sm btn-success flex-grow-1">
                                    <i class="bi bi-check-lg" x-show="!isSaving"></i>
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" x-show="isSaving" style="display: none;"></span>
                                </button>
                                <button @click="isEditing = false" :disabled="isSaving" class="btn btn-sm btn-outline-secondary flex-shrink-0">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- INSURANCE TYPE SECTION --}}
            <div x-data="{
                isEditing: false,
                insuranceType: '{{ $employee->insurance_type ?? '' }}',
                tempInsuranceType: '{{ $employee->insurance_type ?? '' }}',
                isSaving: false,
                startEditing() {
                    this.tempInsuranceType = this.insuranceType;
                    this.isEditing = true;
                    this.$nextTick(() => { this.$refs.insuranceInput.focus(); });
                },
                saveInsurance() {
                    this.isSaving = true;

                    let formData = new FormData();
                    formData.append('_method', 'PUT');
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('insurance_type', this.tempInsuranceType);
                    // required fields for employees.update usually include these for safety if not using patch
                    formData.append('employer_id', '{{ $employee->employer_id }}');
                    formData.append('employeeNameEn', '{{ $employee->employeeNameEn }}');

                    fetch('{{ route('employees.update', $employee->id) }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) throw new Error(response.statusText);
                        return response.json();
                    })
                    .then(data => {
                        this.insuranceType = this.tempInsuranceType;
                        this.isEditing = false;

                        // Also update the hidden data attribute if it exists, so bulk actions or finance tab might pick it up
                        let checkbox = document.querySelector('#check_{{ $employee->id }}');
                        if (checkbox) {
                            checkbox.setAttribute('data-insurance-type', this.insuranceType);
                        }

                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'บันทึกประเภทประกันสำเร็จ',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    })
                    .catch(error => {
                        Swal.fire('Error', `เกิดข้อผิดพลาด: ${error}`, 'error');
                    })
                    .finally(() => {
                        this.isSaving = false;
                    });
                }
            }">
                <div style="min-width: 140px; max-width: 250px;">
                    <small class="text-muted d-block fw-bold" style="font-size: 0.7rem;">ประเภทประกัน</small>

                    <div class="d-flex align-items-start gap-1 mb-2">
                        <div x-cloak :style="{ display: !isEditing ? 'flex' : 'none' }" class="align-items-center gap-1 w-100">
                            <div class="text-dark small border rounded px-2 py-1 bg-light flex-grow-1 text-truncate" style="min-height: 31px;">
                                <span x-text="insuranceType || '-'"></span>
                            </div>
                            <button @click="startEditing()" class="btn btn-sm btn-outline-secondary rounded-circle flex-shrink-0" style="padding: 2px 6px;" title="แก้ไขประเภทประกัน">
                                <i class="bi bi-pencil-fill" style="font-size: 0.75rem;"></i>
                            </button>
                        </div>

                        <div x-cloak :style="{ display: isEditing ? 'block' : 'none' }" class="w-100">
                            <select x-ref="insuranceInput" x-model="tempInsuranceType" class="form-select form-select-sm mb-1">
                                <option value="">{{ __('- ไม่ระบุ -') }}</option>
                                <option value="ประกันเอกชน">{{ __('ประกันเอกชน') }}</option>
                                <option value="ประกันโรงพยาบาล">{{ __('ประกันโรงพยาบาล') }}</option>
                                <option value="ประกันสังคม">{{ __('ประกันสังคม') }}</option>
                            </select>
                            <div class="d-flex gap-1">
                                <button @click="saveInsurance()" :disabled="isSaving" class="btn btn-sm btn-success flex-grow-1">
                                    <i class="bi bi-check-lg" x-show="!isSaving"></i>
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" x-show="isSaving" style="display: none;"></span>
                                </button>
                                <button @click="isEditing = false" :disabled="isSaving" class="btn btn-sm btn-outline-secondary flex-shrink-0">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            </div> {{-- End d-flex gap-2 align-items-start --}}

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
                        class="btn btn-warning btn-sm ms-2 rounded-circle cal-badge-pulse"
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

                @php
                    $isPreProductionContext = isset($activeTab) && $activeTab instanceof \App\Models\WorkType;
                @endphp

                @if($isPreProductionContext && isset($employee->production_item))
                    @if($activeTab->slug !== 'mou')
                        {{-- SEND TO WORKFLOW (P Production Context) - Not shown in MOU --}}
                        <button class="btn btn-sm btn-primary rounded-pill px-3"
                            id="btn-send-to-workflow-{{ $employee->id }}"
                            title="{{ __('Send to Workflow') }}"
                            onclick="sendToWorkflow({{ $employee->production_item->id }})">
                            <i class="bi bi-send-check"></i> <span class="d-none d-lg-inline">{{ __('Send to Workflow') }}</span>
                        </button>
                    @endif

                    {{-- CANCEL --}}
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 {{ ($isCompleted || $isCancelled || $isHistory) ? 'd-none' : '' }}"
                        id="btn-cancel-{{ $employee->id }}"
                        title="Cancel Registration"
                        onclick="cancelEmployee({{ $employee->id }}, {{ $employee->production_item->id }})">
                        <i class="bi bi-x-circle"></i> <span class="d-none d-lg-inline">{{ __('Cancel') }}</span>
                    </button>

                    {{-- RESTORE (For Cancelled) --}}
                    <button class="btn btn-sm btn-outline-warning rounded-pill px-3 {{ (!$isCancelled || $isHistory) ? 'd-none' : '' }}"
                        id="btn-restore-{{ $employee->id }}"
                        title="Restore"
                        onclick="restoreEmployeeState({{ $employee->id }}, {{ $employee->production_item->id }})">
                        <i class="bi bi-arrow-counterclockwise"></i> {{ __('Restore') }}
                    </button>

                    {{-- Delete (Soft) --}}
                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Delete" onclick="deleteEmployee({{ $employee->id }}, {{ $employee->production_item->id }})">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                @else
                    {{-- SAVE TO DB (Registration Context) --}}
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
                @endif
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
                        $isPreProductionContext = isset($activeTab) && $activeTab instanceof \App\Models\WorkType;

                        if ($isPreProductionContext) {
                            $isStepCompleted = isset($employee->production_item) && $employee->production_item->completedWorkTypeSteps->contains('id', $step->id);
                        } else {
                            $isStepCompleted = $employee->registrationSteps->contains($step->id);
                        }

                        // Determine styles based on hex or class
                        $hexColor = isset($step->color) && str_starts_with($step->color, '#') ? $step->color : null;

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
                                if (isset($step->color) && in_array($step->color, ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'])) {
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

                            // Check context to determine which toggle function to use
                            // Pre-Production reuses this card but its steps are WorkTypeStep, not RegistrationStep
                            $isPreProductionContext = isset($activeTab) && $activeTab instanceof \App\Models\WorkType;

                            if ($isPreProductionContext) {
                                // In Pre-Production, the 'employee' variable is mapped from the ProductionItem,
                                // but we need the production item ID for the toggleWorkStep function
                                $itemId = isset($employee->production_item) ? $employee->production_item->id : $employee->id;
                                $onclick = $canManage ? "onclick=\"toggleWorkStep({$itemId}, {$step->id}, " . ($isStepCompleted ? 'false' : 'true') . ")\"" : '';
                            } else {
                                $onclick = $canManage ? "onclick=\"toggleStep({$employee->id}, {$step->id}, " . ($isStepCompleted ? 'false' : 'true') . ")\"" : '';
                            }
                        @endphp
                    <button
                        class="btn btn-sm {{ $btnClass }} rounded-pill px-3 step-btn-{{ isset($employee->production_item) ? $employee->production_item->id : $employee->id }}-{{ $step->id }}"
                            style="font-size: 0.8rem; {{ $btnStyle }} {{ $pointerEvents }}"
                            {!! $onclick !!}
                        data-step-id="{{ $step->id }}"
                        data-color="{{ $step->color ?? '' }}"
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
