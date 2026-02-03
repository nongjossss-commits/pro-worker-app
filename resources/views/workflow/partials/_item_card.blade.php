@props(['item', 'steps', 'order' => null])

@php
    $order = $order ?? $item->order;
    $status = $item->status ?? 'pending';
    $isCompleted = $status === 'completed';
    $isCancelled = $status === 'cancelled';

    // Style: if completed/cancelled, flat/grey out.
    $cardClass = 'bg-white border shadow-sm';
    $overlayClass = '';

    if ($isCompleted) {
        $cardClass = 'bg-success bg-opacity-10 border-0 text-muted';
        $overlayClass = 'opacity-75 pointer-events-none';
    } elseif ($isCancelled) {
        $cardClass = 'bg-light border-0 text-secondary grayscale-mode';
        $overlayClass = 'opacity-50 pointer-events-none';
    }

    // Employee Data Proxy
    $empNameEn = $item->employee->employeeNameEn ?? $item->new_employee_data['name_en'] ?? 'New Employee';
    $empNameTh = $item->employee->employeeNameTh ?? $item->new_employee_data['name_th'] ?? '';
    $empPhoto = $item->employee && $item->employee->employeePhoto ? asset('storage/' . $item->employee->employeePhoto) : 'https://placehold.co/50x50/e2e8f0/6c757d?text=PIC';
    $empPassport = $item->employee->employeePassport ?? $item->new_employee_data['passport_no'] ?? '-';
    $empNationality = $item->employee->employeeNationality ?? $item->new_employee_data['nationality'] ?? '-';
    $empId = $item->employee_id;

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

    // Resolution Group Logic (MOU Badge)
    $mouGroup = $item->employee->workPermitMOUGroup ?? $item->new_employee_data['work_permit_mou_group'] ?? null;
    $badgeColor = 'bg-secondary';
    $badgeText = $mouGroup;

    if ($mouGroup === 'MOU') {
        $badgeColor = 'bg-primary'; // Blue
    } elseif ($mouGroup === 'มติขึ้นทะเบียนใหม่') {
        $badgeColor = 'bg-danger'; // Red
    } elseif ($mouGroup === 'มติต่ออายุในประเทศ') {
        $badgeColor = 'bg-success'; // Green
    } elseif ($mouGroup === 'อื่นๆ ระบุ') {
        $badgeColor = 'bg-secondary'; // Gray
    }
@endphp

<div class="d-flex align-items-center item-card-outer mb-3 item-card-wrapper"
     id="item-card-{{ $item->id }}"
     data-status="{{ $status }}"
     style="transition: all 0.3s ease; {{ $isCancelled ? 'filter: grayscale(100%);' : '' }}">

    {{-- Sequence Number (CSS Counter can handle this if parent has counter-reset) --}}
    <div class="item-sequence-number me-2 fs-5 fw-bold text-muted opacity-50 text-end" style="min-width: 30px;"></div>

    <div class="card {{ $cardClass }} w-100">
    <div class="card-body p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
            {{-- Checkbox & Basic Info --}}
            <div class="d-flex align-items-center gap-3 w-100">
                {{-- Checkbox (Optional, for bulk actions if implemented later) --}}
                <div class="form-check {{ ($isCompleted || $isCancelled) ? 'd-none' : '' }}" id="checkbox-container-{{ $item->id }}">
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
                        <div class="fw-bold text-dark d-flex align-items-center gap-2">
                            {{ $empNameEn }}
                            @if($badgeText)
                                <span class="badge rounded-pill {{ $badgeColor }}" style="font-size: 0.65rem;">{{ $badgeText }}</span>
                            @endif
                        </div>
                        <div class="text-muted small">
                            {{ $empNameTh }}
                        </div>
                        <div class="small text-muted mt-1">
                            <span class="me-2"><i class="bi bi-passport text-primary me-1"></i> {{ $empPassport }}</span>
                            <span class="d-inline-flex align-items-center">
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                {{ $empNationality }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Appointment Date (Replaces Group Field) --}}
                <div class="ms-md-4" x-data="{
                    isEditing: false,
                    dateValue: '{{ $appValue }}',
                    displayValue: '{{ $appDisplay }}',
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
                    saveDate() {
                        Swal.fire({
                            title: '{{ __("Confirm Appointment") }}',
                            text: '{{ __("Are you sure you want to set this appointment date?") }}',
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
                                    body: JSON.stringify({ appointment_date: this.dateValue })
                                }).then(res => res.json()).then(data => {
                                    if(data.success) {
                                        this.isEditing = false;
                                        Swal.fire({
                                            toast: true,
                                            position: 'top-end',
                                            icon: 'success',
                                            title: '{{ __("Appointment Saved") }}',
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
                        <small class="text-muted d-block" style="font-size: 0.7rem;">{{ __('Appointment Date') }}</small>

                        <div x-show="!isEditing" class="d-flex align-items-center gap-2 cursor-pointer"
                             @click="isEditing = true; $nextTick(() => initFlatpickr())">
                             <div class="text-primary fw-bold small border rounded px-2 py-1 bg-white shadow-sm d-flex align-items-center gap-2" style="min-height: 31px;">
                                <i class="bi bi-calendar-event text-warning"></i> <span x-text="displayValue"></span>
                             </div>
                        </div>

                        <div x-show="isEditing" class="d-flex gap-1 align-items-center" style="display: none;">
                             <div style="width: 140px;">
                                <input x-ref="dateInput" type="text" class="form-control form-control-sm" placeholder="Date...">
                             </div>
                             <button @click="saveDate()" class="btn btn-sm btn-success p-1"><i class="bi bi-check-lg"></i></button>
                             <button @click="isEditing = false" class="btn btn-sm btn-outline-danger p-1"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="d-flex gap-2 flex-wrap justify-content-end">
                 @if($empId)
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
