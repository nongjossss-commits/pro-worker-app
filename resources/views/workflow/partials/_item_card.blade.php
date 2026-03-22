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
    $appUpdatedByName = $item->appointmentUpdatedBy->name ?? '';
    $appUpdatedAtHuman = $item->appointment_updated_at ? $item->appointment_updated_at->diffForHumans() : '';

    // Permissions
    $user = auth()->user();
    $isEmployer = $user->hasRole('employer');
    $canManage = $user->can('manage-own-workflow');
    $isReadOnly = $isEmployer && !$canManage;
    $canDelete = $user->hasAnyRole(['admin', 'super-admin']);

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

    // Operator Logic
    $operator = $item->operator; // Relation must be loaded or accessible
    $operatorName = $operator ? $operator->name : null;
    $operatorId = $item->operator_id;
    $isMe = $operatorId === auth()->id();
@endphp

<div class="d-flex align-items-center item-card-outer mb-3"
     id="item-card-{{ $item->id }}"
     data-status="{{ $status }}"
     style="transition: all 0.3s ease; {{ $isCancelled ? 'filter: grayscale(100%);' : '' }}">

    {{-- Sequence Number (CSS Counter can handle this if parent has counter-reset) --}}
    <div class="item-sequence-number me-2 fs-5 fw-bold text-muted opacity-50 text-end" style="min-width: 30px;"></div>

    <div class="card {{ $cardClass }} w-100 position-relative">

    {{-- Operator Badge (Bottom Right) --}}
    <div class="position-absolute bottom-0 end-0 m-2 z-index-10">
        <button class="btn btn-sm {{ $operatorId ? ($isMe ? 'btn-primary' : 'btn-secondary') : 'btn-outline-secondary' }} rounded-pill shadow-sm py-0 px-2"
                style="font-size: 0.75rem; border-width: 1px;"
                @hasanyrole('super-admin|admin|staff')
                onclick="window.toggleOperator ? window.toggleOperator({{ $item->id }}, this, {{ $operatorId ? 'true' : 'false' }}) : console.error('toggleOperator not defined')"
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
                       data-title-th="{{ $item->employee->employeeTitleTh ?? '' }}"
                       data-title-en="{{ $item->employee->employeeTitleEn ?? '' }}"
                       data-nationality="{{ $empNationality }}"
                       data-photo="{{ $empPhoto }}"
                       data-employer-name="{{ $order->employer->employerNameTh ?? 'N/A' }}"
                       data-insurance-type="{{ $item->employee->insurance_type ?? '' }}"
                       data-passport="{{ $empPassport }}"
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
                    isAppCompleted: {{ $isAppCompleted ? 'true' : 'false' }},
                    dateValue: '{{ $appValue }}',
                    displayValue: '{{ $appDisplay }}',
                    locationValue: '{{ $appLocation }}',
                    updatedByName: '{{ $appUpdatedByName }}',
                    updatedAtHuman: '{{ $appUpdatedAtHuman }}',
                    toggleAppComplete() {
                        fetch('/workflow/item/{{ $item->id }}/appointment-complete', {
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
                                fetch('/workflow/item/{{ $item->id }}/appointment', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                                    body: JSON.stringify({ appointment_date: data.date, appointment_location: data.location })
                                }).then(res => res.json()).then(resData => {
                                    if(resData.success) {
                                        this.dateValue = data.date;
                                        this.locationValue = data.location;

                                        if (resData.appointment_updated_by_name) {
                                            this.updatedByName = resData.appointment_updated_by_name;
                                            this.updatedAtHuman = resData.appointment_updated_at_human;
                                        }

                                        Swal.fire({
                                            toast: true,
                                            position: 'top-end',
                                            icon: 'success',
                                            title: '{{ __("Saved") }}',
                                            showConfirmButton: false,
                                            timer: 1500
                                        });

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
                    <div style="min-width: 170px;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">{{ __('Appointment') }}</small>
                            <div class="form-check form-switch" title="{{ __('Mark Appointment Completed') }}">
                                <input class="form-check-input cursor-pointer" type="checkbox" x-model="isAppCompleted" @change="toggleAppComplete()">
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

                        <!-- Appt Updated By -->
                        <div x-show="updatedByName" class="mt-1 text-end" style="font-size: 0.65rem;" x-cloak>
                            <span class="text-muted"><i class="bi bi-clock"></i> <span x-text="updatedAtHuman"></span> โดย <span x-text="updatedByName"></span></span>
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
                        <input x-model="email" type="text" class="form-control form-control-sm" placeholder="Email">
                        <input x-model="outsource_code" type="text" class="form-control form-control-sm" placeholder="Outsource Code">
                        <div class="d-flex gap-1 mt-1">
                            <button @click="save()" class="btn btn-sm btn-success flex-grow-1"><i class="bi bi-check-lg"></i></button>
                            <button @click="isEditing = false" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                </div>

                {{-- REMARKS & 3 Extra Fields SECTION --}}
                <div class="ms-md-4 d-flex flex-column gap-2" style="min-width: 140px; max-width: 250px;">
                    <div x-data="{
                        isEditing: false,
                        remarkText: {{ json_encode($item->remarks ?? '') }},
                        tempRemarkText: '',
                        isSaving: false,
                        startEditing() {
                            this.tempRemarkText = this.remarkText;
                            this.isEditing = true;
                            this.$nextTick(() => { this.$refs.remarkInput.focus(); });
                        },
                        saveRemark() {
                            this.isSaving = true;
                            fetch('/workflow/item/{{ $item->id }}/remarks', {
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
                                    this.remarkText = data.remarks ?? this.tempRemarkText; // Update local text from response or input
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
                        <div>
                            <small class="text-muted d-block fw-bold" style="font-size: 0.7rem;">หมายเหตุ</small>

                            <div class="d-flex align-items-start gap-1">
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

                    {{-- 3 Extra Fields (Editable) --}}
                    <div class="d-flex flex-column gap-2" x-data="{
                        isEditing: false,
                        nameList: '{{ $item->employee->name_list_number ?? '' }}',
                        reqNo: '{{ $item->request_number ?? '' }}',
                        refId: '{{ $item->employee->employee_reference_id ?? '' }}',
                        updateMethod: 'item_update',
                        updateUrl: '{{ route('production.items.update_fields', $item->id) }}',
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
                            formData.append('request_number', this.reqNo);

                            // Request 1: Update the specific request number (item)
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
                            empFormData.append('employer_id', '{{ $item->employee->employer_id ?? '' }}');
                            empFormData.append('employeeNameEn', '{{ $item->employee->employeeNameEn ?? '' }}');

                            let req2 = fetch('{{ route('employees.update', $item->employee->id ?? 0) }}', {
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
                                // Check if both succeeded, or if at least the primary one succeeded
                                if (typeof showToast === 'function') {
                                    showToast('{{ __('Saved successfully') }}', 'success');
                                } else {
                                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: '{{ __('Saved successfully') }}', showConfirmButton: false, timer: 1500 });
                                }
                                this.isEditing = false;
                            })
                            .catch(err => {
                                console.error(err);
                                if (typeof showToast === 'function') {
                                    showToast('{{ __('Error saving') }}', 'danger');
                                } else {
                                    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: '{{ __('Error saving') }}', showConfirmButton: false, timer: 1500 });
                                }
                            });
                        }
                    }">
                        <div class="d-flex align-items-end gap-2">
                            {{-- Field 1: Name List (Renamed to RA) --}}
                            <div style="flex-grow: 1;">
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
                        <div style="width: 100%;">
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
                        <div style="width: 100%;">
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
            <div class="d-flex gap-2 flex-wrap justify-content-end align-items-center mt-2">

                @if(!$isReadOnly)
                    @if($isPreProduction)
                        {{-- Send to Workflow Button (Only in Pre-Production) --}}
                        <button class="btn btn-primary btn-sm fw-bold shadow-sm px-3"
                                onclick="sendToWorkflow({{ $item->id }})"
                                title="{{ __('Send to Workflow') }}">
                            <i class="bi bi-box-arrow-right"></i> <span class="d-none d-lg-inline">{{ __('Send to Workflow') }}</span>
                        </button>
                    @else
                        {{-- Send Back to Pre-Production --}}
                        @if(!$isCompleted && !$isCancelled)
                        <button class="btn btn-info btn-sm fw-bold shadow-sm px-3 text-white"
                                onclick="sendBackToPreProduction({{ $item->id }})"
                                title="{{ __('Back to Preparation') }}">
                             <i class="bi bi-arrow-counterclockwise"></i> <span class="d-none d-lg-inline">{{ __('Back to Prep') }}</span>
                        </button>
                        @endif

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

        {{-- COUNTDOWN TIMER (If Completed & Within 24h) --}}
        @if($canRestore)
             <div class="mt-3 w-100 d-flex justify-content-center" x-data="{
                expires: {{ $expiresAtTimestamp }},
                displayText: '',
                init() {
                        this.update();
                        setInterval(() => this.update(), 60000);
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
