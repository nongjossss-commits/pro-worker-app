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

    <div class="card {{ $cardClass }} w-100">
    <div class="card-body p-3">
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
                           data-photo="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/40x40/e2e8f0/6c757d?text=PIC' }}"
                           data-employer-name="{{ $employee->employer->employerNameTh ?? 'N/A' }}">
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
                        @if(isset($employee->financialStatus) && $employee->financialStatus === 'paid')
                            <span class="position-absolute bottom-0 start-100 translate-middle badge rounded-pill bg-success border border-white" title="{{ __('Fully Paid') }}">
                                <i class="bi bi-currency-dollar"></i>
                            </span>
                        @elseif(isset($employee->financialStatus) && $employee->financialStatus === 'partial')
                            <span class="position-absolute bottom-0 start-100 translate-middle badge rounded-pill bg-warning text-dark border border-white" title="{{ __('Partial/Pending Payment') }}">
                                <i class="bi bi-currency-dollar"></i>
                            </span>
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
            @endphp

            <div class="ms-md-2" x-data="{
                isEditing: false,
                isAppCompleted: {{ $isAppCompleted ? 'true' : 'false' }},
                dateValue: '{{ $appValue }}',
                displayValue: '{{ $appDisplay }}',
                locationValue: '{{ $appLocation }}',
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

                    <div x-show="!isEditing" class="d-flex align-items-center gap-2 cursor-pointer position-relative"
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

            {{-- 3 Extra Fields (Editable) --}}
            <div class="d-flex flex-column gap-2" x-data="{
                isEditing: false,
                nameList: '{{ $employee->name_list_number }}',
                reqNo: '{{ $employee->request_number }}',
                refId: '{{ $employee->employee_reference_id }}',
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
                    formData.append('name_list_number', this.nameList);
                    formData.append('request_number', this.reqNo);
                    formData.append('employee_reference_id', this.refId);
                    formData.append('_method', 'PUT');
                    formData.append('_token', '{{ csrf_token() }}');

                    // Minimal required fields to pass validation if controller is strict
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
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            showToast('{{ __('Saved successfully') }}', 'success');
                            this.isEditing = false;
                        } else {
                            showToast('{{ __('Error saving') }}', 'danger');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        showToast('{{ __('Error saving') }}', 'danger');
                    });
                }
            }">
                <div class="d-flex align-items-end gap-2">
                    {{-- Field 1: Name List (Renamed to RA) --}}
                    <div style="width: 140px;">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">เลข RA จากระบบ outsource</small>
                        <div x-show="!isEditing" x-ref="raDisplay" x-init="fitText($el)" class="small text-dark border rounded px-2 py-1 bg-light text-nowrap overflow-hidden" style="min-height: 31px;" x-text="nameList || '-'"></div>
                        <input x-show="isEditing" type="text" class="form-control form-control-sm" x-model="nameList" placeholder="RA No.">
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex gap-1 mb-1">
                        <button x-show="!isEditing" @click="isEditing = true" class="btn btn-sm btn-outline-secondary rounded-circle" title="Edit Fields">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button x-show="isEditing" @click="saveFields()" class="btn btn-sm btn-success rounded-circle" title="Save Fields">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <button x-show="isEditing" @click="isEditing = false" class="btn btn-sm btn-outline-danger rounded-circle" title="Cancel">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                {{-- Field 2: Request No --}}
                <div style="width: 140px;">
                    <small class="text-muted d-block" style="font-size: 0.7rem;">เลขที่คำขอ</small>
                    <div x-show="!isEditing" x-ref="reqDisplay" x-init="fitText($el)" class="small text-dark border rounded px-2 py-1 bg-light text-nowrap overflow-hidden" style="min-height: 31px;" x-text="reqNo || '-'"></div>
                    <input x-show="isEditing" type="text" class="form-control form-control-sm" x-model="reqNo" placeholder="Request No.">
                </div>

                {{-- Field 3: Ref ID --}}
                <div style="width: 140px;">
                    <small class="text-muted d-block" style="font-size: 0.7rem;">เลขอ้างอิงคนงาน</small>
                    <div x-show="!isEditing" x-ref="refDisplay" x-init="fitText($el)" class="small text-dark border rounded px-2 py-1 bg-light text-nowrap overflow-hidden" style="min-height: 31px;" x-text="refId || '-'"></div>
                    <input x-show="isEditing" type="text" class="form-control form-control-sm" x-model="refId" placeholder="Ref ID">
                </div>
            </div>
            </div>

            {{-- Actions --}}
            <div class="d-flex gap-2 flex-wrap justify-content-end">
                 @can('edit-employees')
                 {{-- Biometrics Button --}}
                 <input type="file" id="biometrics-input-{{ $employee->id }}" class="d-none" onchange="uploadBiometrics({{ $employee->id }})">

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
