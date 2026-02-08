@props(['item', 'steps', 'order' => null, 'isHistory' => false])

@php
    $order = $order ?? $item->order;
    $status = $item->status ?? 'pending';
    $isCompleted = $status === 'completed';
    $isCancelled = $status === 'cancelled';
    $isPreProduction = $order->status === 'pre_production';

    // Daily Check Logic
    $isCheckedToday = $item->is_checked_today;
    $daysMissed = $item->days_since_last_check ?? 0;

    // Style: if completed/cancelled, flat/grey out.
    $cardClass = 'bg-white border shadow-sm';
    $overlayClass = '';

    if ($isCompleted) {
        $cardClass = 'bg-success bg-opacity-10 border-0 text-muted';
        $overlayClass = 'opacity-75 pointer-events-none';
    } elseif ($isCancelled) {
        $cardClass = 'bg-light border-0 text-secondary grayscale-mode';
        $overlayClass = 'opacity-50 pointer-events-none';
    } elseif ($isPreProduction) {
        // Pre-Production: Blue Border/Glow + "Preparing" visual cue (User Request: Blue/Cyan for Pre-Production)
        $cardClass = 'bg-white border border-info border-3 shadow';
    } elseif (!$isCheckedToday) {
        // Highlight Pending Check (Orange Border/Glow) ONLY in Workflow
        $cardClass = 'bg-white border border-warning border-3 shadow';
    }

    // Employee Data Proxy
    $titleEn = $item->employee->employeeTitleEn ?? '';
    $nameEn = $item->employee->employeeNameEn ?? $item->new_employee_data['name_en'] ?? 'New Employee';
    $empNameEn = trim("$titleEn $nameEn");

    $titleTh = $item->employee->employeeTitleTh ?? '';
    $nameTh = $item->employee->employeeNameTh ?? $item->new_employee_data['name_th'] ?? '';
    $empNameTh = trim("$titleTh $nameTh");

    $empPhoto = $item->employee && $item->employee->employeePhoto ? asset('storage/' . $item->employee->employeePhoto) . '?t=' . time() : 'https://placehold.co/50x50/e2e8f0/6c757d?text=PIC';
    $empPassport = $item->employee->employeePassport ?? $item->new_employee_data['passport_no'] ?? '-';
    $empNationality = $item->employee->employeeNationality ?? $item->new_employee_data['nationality'] ?? '-';
    $empId = $item->employee_id;

    // MOU Group Color
    $mouGroup = $item->employee->workPermitMOUGroup ?? 'N/A';
    $mouBadgeClass = 'bg-secondary';
    if (str_contains($mouGroup, 'MOU')) {
        $mouBadgeClass = 'bg-primary'; // Blue
    } elseif (str_contains($mouGroup, 'มติขึ้นทะเบียน') || str_contains($mouGroup, 'มติ ลงทะเบียนในประเทศ')) {
        $mouBadgeClass = 'bg-danger'; // Red
    } elseif (str_contains($mouGroup, 'มติต่ออายุในประเทศ')) {
        $mouBadgeClass = 'bg-success'; // Green
    }

    // Appointment Date Logic
    $appDate = $item->appointment_date;
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

    $appLocation = $item->appointment_location ?? '';
    $isAppCompleted = $item->appointment_completed_at ? true : false;

    // Permissions
    $user = auth()->user();
    $isEmployer = $user->hasRole('employer');
    $canManage = $user->can('manage-own-workflow');
    $isReadOnly = $isEmployer && !$canManage;
    $canDelete = $user->hasRole('admin');

    // Restore Logic
    $canRestore = false;
    $expiresAtTimestamp = 0;

    if ($isCompleted && $item->completed_at && !$isHistory) {
        $completedAt = \Carbon\Carbon::parse($item->completed_at);
        $expiresAt = $completedAt->copy()->addHours(24);
        if ($expiresAt->isFuture()) {
            $canRestore = true;
            $expiresAtTimestamp = $expiresAt->timestamp * 1000;
        }
    }
@endphp

<div class="d-flex align-items-center item-card-outer mb-3 item-card-wrapper"
     id="item-card-{{ $item->id }}"
     data-status="{{ $status }}"
     style="transition: all 0.3s ease; {{ $isCancelled ? 'filter: grayscale(100%);' : '' }}">

    {{-- Sequence Number (CSS Counter can handle this if parent has counter-reset) --}}
    <div class="item-sequence-number me-2 fs-5 fw-bold text-muted opacity-50 text-end" style="min-width: 30px;"></div>

    <div class="card {{ $cardClass }} w-100 position-relative">
    <div class="card-body p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
            {{-- Checkbox & Basic Info --}}
            <div class="d-flex align-items-center gap-3 w-100">
                {{-- Checkbox (Optional, for bulk actions if implemented later) --}}
                <div class="form-check" id="checkbox-container-{{ $item->id }}">
                <input class="form-check-input employee-checkbox"
                           type="checkbox"
                       value="{{ $item->employee_id ?? '' }}"
                       id="check_{{ $item->id }}"
                       data-employee-id="{{ $item->employee_id ?? '' }}"
                       data-employer-id="{{ $order->employer_id ?? '' }}"
                       data-name-th="{{ $item->employee->employeeNameTh ?? '' }}"
                       data-name-en="{{ $item->employee->employeeNameEn ?? '' }}"
                       data-photo="{{ $empPhoto }}"
                       data-employer-name="{{ $order->employer->employerNameTh ?? 'N/A' }}"
                       {{ (!$item->employee_id || $isReadOnly) ? 'disabled' : '' }}>
                </div>

                <div class="d-flex align-items-center gap-3 {{ $overlayClass }}" id="info-container-{{ $item->id }}">
                    {{-- Avatar --}}
                    <div class="avatar-container position-relative">
                        <img src="{{ $empPhoto }}" class="rounded-circle shadow-sm" style="width: 50px; height: 50px; object-fit: cover;">

                        {{-- Status Badges --}}
                        <span id="badge-completed-{{ $item->id }}" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success border border-white {{ !$isCompleted ? 'd-none' : '' }}">
                            <i class="bi bi-check"></i>
                        </span>
                        <span id="badge-cancelled-{{ $item->id }}" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary border border-white {{ !$isCancelled ? 'd-none' : '' }}">
                            <i class="bi bi-x"></i>
                        </span>
                    </div>

                    {{-- Info --}}
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                             <div class="fw-bold text-dark">{{ $empNameEn }}</div>
                             {{-- Resolution Badge --}}
                             @if($mouGroup !== 'N/A')
                                <span class="badge rounded-pill {{ $mouBadgeClass }} small" style="font-size: 0.65rem;">{{ $mouGroup }}</span>
                             @endif
                             {{-- Pre-Production Badge --}}
                             @if($isPreProduction)
                                <span class="badge rounded-pill bg-info text-dark small" style="font-size: 0.65rem;">{{ __('Preparing') }}</span>
                             @endif
                        </div>
                        <div class="text-muted small">
                            {{ $empNameTh }}
                        </div>

                        {{-- Employer Info (Requested Feature) --}}
                        @if($order && $order->employer)
                        <div class="small text-primary mt-1">
                             <i class="bi bi-building me-1"></i> {{ $order->employer->employerNameTh ?? $order->employer->employerNameEn }}
                             <button class="btn btn-sm btn-link p-0 ms-1 btn-preview"
                                style="text-decoration: none;"
                                data-model-type="employer"
                                data-model-id="{{ $order->employer_id }}"
                                title="Preview Employer">
                                 <i class="bi bi-eye-fill"></i>
                             </button>
                        </div>
                        @endif

                        <div class="small text-muted mt-1">
                            <span class="me-2"><i class="bi bi-passport text-primary me-1"></i> {{ $empPassport }}</span>
                            <span class="d-inline-flex align-items-center">
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                {{ $empNationality }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Appointment Date & Location --}}
                <div class="ms-md-4" x-data="{
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
                        fetch('/workflow/item/{{ $item->id }}/appointment-complete', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                        }).then(res => res.json()).then(data => {
                            if(data.success) {
                                // this.isAppCompleted is already toggled by x-model, but let's confirm logic
                            } else {
                                this.isAppCompleted = !this.isAppCompleted; // revert
                            }
                        });
                    },
                    saveDate() {
                        Swal.fire({
                            title: '{{ __("Confirm Appointment") }}',
                            text: '{{ __("Save appointment details?") }}',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: '{{ __("Yes, save it!") }}',
                            cancelButtonText: '{{ __("Cancel") }}'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                fetch('/workflow/item/{{ $item->id }}/appointment', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                                    body: JSON.stringify({ appointment_date: this.dateValue, appointment_location: this.locationValue })
                                }).then(res => res.json()).then(data => {
                                    if(data.success) {
                                        this.isEditing = false;
                                        Swal.fire({
                                            toast: true,
                                            position: 'top-end',
                                            icon: 'success',
                                            title: '{{ __("Saved") }}',
                                            showConfirmButton: false,
                                            timer: 1500
                                        });

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
                             <input x-model="locationValue" type="text" class="form-control form-control-sm" placeholder="e.g. Office, Site A">

                             <div class="d-flex gap-1 mt-2">
                                <button @click="saveDate()" class="btn btn-sm btn-success flex-grow-1"><i class="bi bi-check-lg"></i> Save</button>
                                <button @click="isEditing = false" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></button>
                             </div>
                        </div>
                    </div>
                </div>

                {{-- NEW CREDENTIALS SECTION --}}
                <div class="ms-md-4" x-data="{
                    isEditing: false,
                    email: {{ json_encode($item->employee->email ?? '') }},
                    outsource_code: {{ json_encode($item->employee->outsource_code ?? '') }},
                    copy(el, text) {
                        if (!text) return;
                        navigator.clipboard.writeText(text).then(() => {
                            const originalHtml = el.innerHTML;
                            el.innerHTML = '<i class=\'bi bi-check text-success\'></i>';
                            setTimeout(() => el.innerHTML = originalHtml, 1000);
                        });
                    },
                    save() {
                        fetch('/workflow/item/{{ $item->id }}/update-credentials', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({ email: this.email, outsource_code: this.outsource_code })
                        })
                        .then(res => {
                            if (!res.ok) {
                                return res.json().then(err => { throw err; });
                            }
                            return res.json();
                        })
                        .then(data => {
                            if(data.success) {
                                this.isEditing = false;
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'success',
                                    title: '{{ __("Saved") }}',
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                            }
                        })
                        .catch(error => {
                            let msg = '{{ __("Failed to save.") }}';
                            if (error.message) msg = error.message;
                            if (error.errors) {
                                const firstKey = Object.keys(error.errors)[0];
                                if (firstKey) msg = error.errors[firstKey][0];
                            }
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("Error") }}',
                                text: msg,
                                confirmButtonText: '{{ __("OK") }}'
                            });
                        });
                    }
                }">
                    {{-- Display Mode --}}
                    <div x-show="!isEditing" class="align-items-center gap-2" :class="{ 'd-flex': !isEditing }">
                        <div class="d-flex flex-column gap-1">
                             <div class="d-flex align-items-center gap-1 border rounded px-2 py-1 bg-white shadow-sm" style="min-width: 200px;">
                                <i class="bi bi-envelope text-muted me-1"></i>
                                <span x-text="email || '-'" class="small text-truncate" style="max-width: 150px;" :title="email"></span>
                                <button @click="copy($event.currentTarget, email)" class="btn btn-link p-0 ms-auto text-secondary" title="Copy Email" x-show="email">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                             </div>
                             <div class="d-flex align-items-center gap-1 border rounded px-2 py-1 bg-white shadow-sm" style="min-width: 200px;">
                                <i class="bi bi-key text-muted me-1"></i>
                                <span x-text="outsource_code || '-'" class="small text-truncate" style="max-width: 150px;" :title="outsource_code"></span>
                                <button @click="copy($event.currentTarget, outsource_code)" class="btn btn-link p-0 ms-auto text-secondary" title="Copy Outsource Code" x-show="outsource_code">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                             </div>
                        </div>
                        @if(!$isReadOnly)
                        <button @click="isEditing = true" class="btn btn-sm btn-outline-secondary rounded-circle" title="Edit Credentials">
                            <i class="bi bi-pencil-fill" style="font-size: 0.7rem;"></i>
                        </button>
                        @endif
                    </div>

                    {{-- Edit Mode --}}
                    <div x-show="isEditing" @click.outside="isEditing = false" class="flex-column gap-1 p-2 bg-white border rounded shadow-sm" :class="{ 'd-flex': isEditing }" style="display: none; min-width: 220px;">
                        <input x-model="email" type="email" class="form-control form-control-sm" placeholder="Email">
                        <input x-model="outsource_code" type="text" class="form-control form-control-sm" placeholder="Outsource Code">
                        <div class="d-flex gap-1 mt-1">
                            <button @click="save()" class="btn btn-sm btn-success flex-grow-1"><i class="bi bi-check-lg"></i></button>
                            <button @click="isEditing = false" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="d-flex gap-2 flex-wrap justify-content-end align-items-center">

                @if(!$isReadOnly)
                    @if($isPreProduction)
                        {{-- Send to Workflow Button (Only in Pre-Production) --}}
                        <button class="btn btn-primary btn-sm fw-bold shadow-sm px-3"
                                onclick="sendToWorkflow({{ $item->id }})"
                                title="{{ __('Send to Workflow') }}">
                            <i class="bi bi-box-arrow-right"></i> <span class="d-none d-lg-inline">{{ __('Send to Workflow') }}</span>
                        </button>
                    @else
                        {{-- Daily Check Button (Only in Workflow) --}}
                        @if(!$isCheckedToday && !$isCompleted && !$isCancelled)
                            <button class="btn btn-warning btn-sm fw-bold shadow-sm" onclick="checkDaily({{ $item->id }})" title="Daily Check">
                                <i class="bi bi-clipboard-check-fill"></i> {{ __('Check') }}
                                @if($daysMissed > 0)
                                    <span class="badge bg-danger ms-1 border border-white">{{ $daysMissed }}d</span>
                                @endif
                            </button>
                        @endif
                    @endif

                    {{-- Edit Button --}}
                    @if($empId)
                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
                            onclick="openEditEmployeeModal({{ $empId }}, {{ $item->id }})"
                            title="Edit Employee">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    @else
                        {{-- Temp Employee (Edit not fully linked yet, but button visible as requested) --}}
                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
                            onclick="Swal.fire('{{ __('Notice') }}', '{{ __('Please register this employee first to edit full details.') }}', 'info')"
                            title="Edit Employee">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    @endif
                @endif

                 <button class="btn btn-sm btn-outline-info btn-preview rounded-pill px-3"
                    data-model-type="employee"
                    data-model-id="{{ $empId ?? 0 }}"
                    title="Preview">
                    <i class="bi bi-eye-fill"></i>
                </button>

                @if(!$isReadOnly)
                    {{-- Manage Team --}}
                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
                        onclick="openManageTeamModal({{ $item->id }}, this)"
                        data-group-name="{{ $item->group_name }}"
                        data-order-id="{{ $order->id }}"
                        title="{{ __('Manage Team') }}">
                        <i class="bi bi-people-fill"></i> <span class="d-none d-lg-inline">{{ __('Team') }}</span>
                    </button>

                    {{-- SAVE TO DB (Finalize) --}}
                    <button class="btn btn-sm btn-success rounded-pill px-3 {{ ($isCompleted || $isCancelled) ? 'd-none' : '' }}"
                        id="btn-save-{{ $item->id }}"
                        title="Mark as Completed"
                        onclick="finalizeItem({{ $item->id }})">
                        <i class="bi bi-check-lg"></i> <span class="d-none d-lg-inline">{{ __('Finish') }}</span>
                    </button>

                    {{-- CANCEL --}}
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 {{ ($isCompleted || $isCancelled) ? 'd-none' : '' }}"
                        id="btn-cancel-{{ $item->id }}"
                        title="Cancel Item"
                        onclick="cancelItem({{ $item->id }})">
                        <i class="bi bi-x-circle"></i> <span class="d-none d-lg-inline">{{ __('Cancel') }}</span>
                    </button>

                    {{-- RESTORE (For Cancelled) --}}
                    <button class="btn btn-sm btn-outline-warning rounded-pill px-3 {{ !$isCancelled ? 'd-none' : '' }}"
                        id="btn-restore-{{ $item->id }}"
                        title="Restore"
                        onclick="restoreItem({{ $item->id }})">
                        <i class="bi bi-arrow-counterclockwise"></i> {{ __('Restore') }}
                    </button>

                    {{-- UNDO (For Completed - Within 24h) --}}
                    @if($canRestore)
                    <div x-data="{
                        expires: {{ $expiresAtTimestamp }},
                        text: '',
                        init() {
                             this.tick();
                             setInterval(() => this.tick(), 60000);
                        },
                        tick() {
                             const now = new Date().getTime();
                             const diff = this.expires - now;
                             if (diff <= 0) {
                                 this.text = 'Expired';
                             } else {
                                 const hours = Math.floor(diff / (1000 * 60 * 60));
                                 const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                 this.text = hours + 'h ' + minutes + 'm';
                             }
                        }
                    }" class="d-inline-block">
                        <button class="btn btn-sm btn-outline-warning rounded-pill px-3"
                            id="btn-undo-{{ $item->id }}"
                            title="Undo"
                            onclick="restoreItem({{ $item->id }})">
                            <i class="bi bi-arrow-counterclockwise"></i> {{ __('Undo') }}
                            <span class="badge bg-warning text-dark ms-1" x-text="text"></span>
                        </button>
                    </div>
                    @endif

                    @if($isHistory)
                        <span class="badge bg-secondary align-self-center">{{ __('Archived') }}</span>
                    @endif
                @endif

                {{-- Delete (Soft) --}}
                @if($canDelete)
                <button class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Delete" onclick="deleteItem({{ $item->id }})">
                    <i class="bi bi-trash-fill"></i>
                </button>
                @endif
            </div>
        </div>

        {{-- Steps Progress Bar --}}
        <div class="mt-3 {{ $overlayClass }}" id="steps-container-{{ $item->id }}">
            <div class="d-flex gap-2 flex-wrap">
                @foreach($steps as $step)
                    @php
                        // Check if completed via completedWorkTypeSteps pivot
                        // $item->completedWorkTypeSteps is loaded
                        $isStepCompleted = $item->completedWorkTypeSteps->contains('id', $step->id);

                        // Default Style
                        $btnClass = 'btn-light border text-secondary';
                        $btnStyle = '';

                        if ($isStepCompleted) {
                             $btnClass = "btn-success text-white"; // Default success for workflow
                        }

                        $disabled = ($isCompleted || $isCancelled || $isReadOnly) ? 'disabled' : '';
                        $onclick = "onclick=\"toggleWorkStep({$item->id}, {$step->id}, " . ($isStepCompleted ? 'false' : 'true') . ")\"";
                    @endphp
                    <button
                        class="btn btn-sm {{ $btnClass }} rounded-pill px-3 step-btn-{{ $item->id }}-{{ $step->id }}"
                            style="font-size: 0.8rem; {{ $btnStyle }}"
                            {!! $onclick !!}
                        data-step-id="{{ $step->id }}"
                            {{ $disabled }}
                    >
                        {{ $step->name }}
                        @if($isStepCompleted) <i class="bi bi-check-circle-fill ms-1"></i> @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>
    </div>
</div>
