@props(['employee', 'steps'])

@php
    $isCompleted = $employee->status === 'registration_completed';
    $isCancelled = $employee->status === 'registration_cancelled';

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
    $highestStep = $employee->renewalSteps->sortByDesc('order')->first();
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
     style="transition: all 0.3s ease; {{ $isCancelled ? 'filter: grayscale(100%);' : '' }}">

    {{-- Sequence Number (Outside Card) --}}
    <div class="employee-sequence-number me-2 fs-5 fw-bold text-muted opacity-50 text-end" style="min-width: 30px;"></div>

    <div class="card {{ $cardClass }} w-100">
    <div class="card-body p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
            {{-- Checkbox & Basic Info --}}
            <div class="d-flex align-items-center gap-3 w-100">
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

            {{-- 3 Extra Fields (Editable) --}}
            <div class="d-flex align-items-center gap-2 flex-wrap" x-data="{
                isEditing: false,
                nameList: '{{ $employee->name_list_number }}',
                reqNo: '{{ $employee->request_number }}',
                refId: '{{ $employee->employee_reference_id }}',
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
                <div class="d-flex gap-2">
                    {{-- Field 1: Name List (Renamed to RA) --}}
                    <div style="width: 140px;">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">เลข RA จากระบบ outsource</small>
                        <div x-show="!isEditing" class="fw-bold text-dark border rounded px-2 py-1 bg-light" style="min-height: 31px;" x-text="nameList || '-'"></div>
                        <input x-show="isEditing" type="text" class="form-control form-control-sm" x-model="nameList" placeholder="RA No.">
                    </div>
                    {{-- Field 2: Request No --}}
                    <div style="width: 140px;">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">เลขที่คำขอ</small>
                        <div x-show="!isEditing" class="fw-bold text-dark border rounded px-2 py-1 bg-light" style="min-height: 31px;" x-text="reqNo || '-'"></div>
                        <input x-show="isEditing" type="text" class="form-control form-control-sm" x-model="reqNo" placeholder="Request No.">
                    </div>
                    {{-- Field 3: Ref ID --}}
                    <div style="width: 140px;">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">เลขอ้างอิงคนงาน</small>
                        <div x-show="!isEditing" class="fw-bold text-dark border rounded px-2 py-1 bg-light" style="min-height: 31px;" x-text="refId || '-'"></div>
                        <input x-show="isEditing" type="text" class="form-control form-control-sm" x-model="refId" placeholder="Ref ID">
                    </div>
                </div>

                {{-- Action Buttons for 3 Fields --}}
                <div class="mt-3">
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
            </div>

            {{-- Actions --}}
            <div class="d-flex gap-2 flex-wrap justify-content-end">
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
                <button class="btn btn-sm btn-success rounded-pill px-3 {{ ($isCompleted || $isCancelled) ? 'd-none' : '' }}"
                    id="btn-save-{{ $employee->id }}"
                    title="Save to Database"
                    onclick="finalizeEmployee({{ $employee->id }})">
                    <i class="bi bi-check-lg"></i> <span class="d-none d-lg-inline">{{ __('Save to DB') }}</span>
                </button>

                {{-- CANCEL --}}
                <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 {{ ($isCompleted || $isCancelled) ? 'd-none' : '' }}"
                    id="btn-cancel-{{ $employee->id }}"
                    title="Cancel Registration"
                    onclick="cancelEmployee({{ $employee->id }})">
                    <i class="bi bi-x-circle"></i> <span class="d-none d-lg-inline">{{ __('Cancel') }}</span>
                </button>

                {{-- RESTORE (For Cancelled) --}}
                <button class="btn btn-sm btn-outline-warning rounded-pill px-3 {{ !$isCancelled ? 'd-none' : '' }}"
                    id="btn-restore-{{ $employee->id }}"
                    title="Restore"
                    onclick="restoreEmployeeState({{ $employee->id }})">
                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('Restore') }}
                </button>

                {{-- UNDO (For Completed) --}}
                <button class="btn btn-sm btn-outline-warning rounded-pill px-3 {{ !$isCompleted ? 'd-none' : '' }}"
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
                        $isStepCompleted = $employee->renewalSteps->contains($step->id);
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
