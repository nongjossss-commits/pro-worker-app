@props(['item', 'steps', 'order' => null])

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
    } elseif (!$isCheckedToday && !$isPreProduction) {
        // Highlight Pending Check (Orange Border/Glow) ONLY in Workflow
        $cardClass = 'bg-white border border-warning border-3 shadow';
    }

    // Employee Data Proxy
    $empNameEn = $item->employee->employeeNameEn ?? $item->new_employee_data['name_en'] ?? 'New Employee';
    $empNameTh = $item->employee->employeeNameTh ?? $item->new_employee_data['name_th'] ?? '';
    $empPhoto = $item->employee && $item->employee->employeePhoto ? asset('storage/' . $item->employee->employeePhoto) : 'https://placehold.co/50x50/e2e8f0/6c757d?text=PIC';
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
                    <input class="form-check-input item-checkbox"
                           type="checkbox"
                           value="{{ $item->id }}"
                           id="check_{{ $item->id }}">
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
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
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
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
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
            </div>

            {{-- Actions --}}
            <div class="d-flex gap-2 flex-wrap justify-content-end align-items-center">

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

                 @if($empId)
                 {{-- Edit Button --}}
                 <a href="{{ route('employees.edit', $empId) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Edit Employee">
                    <i class="bi bi-pencil-square"></i>
                 </a>

                 <button class="btn btn-sm btn-outline-info btn-preview rounded-pill px-3"
                    data-model-type="employee"
                    data-model-id="{{ $empId }}"
                    title="Preview">
                    <i class="bi bi-eye-fill"></i>
                </button>

                {{-- Manage Team --}}
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
                    onclick="openManageTeamModal({{ $item->id }}, this)"
                    data-group-name="{{ $item->group_name }}"
                    data-order-id="{{ $order->id }}"
                    title="{{ __('Manage Team') }}">
                    <i class="bi bi-people-fill"></i> <span class="d-none d-lg-inline">{{ __('Team') }}</span>
                </button>
                @endif

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

                {{-- UNDO (For Completed) --}}
                <button class="btn btn-sm btn-outline-warning rounded-pill px-3 {{ !$isCompleted ? 'd-none' : '' }}"
                    id="btn-undo-{{ $item->id }}"
                    title="Undo"
                    onclick="restoreItem({{ $item->id }})">
                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('Undo') }}
                </button>

                {{-- Delete (Soft) --}}
                <button class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Delete" onclick="deleteItem({{ $item->id }})">
                    <i class="bi bi-trash-fill"></i>
                </button>
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

                        $disabled = ($isCompleted || $isCancelled) ? 'disabled' : '';
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
