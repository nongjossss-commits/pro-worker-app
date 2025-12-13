@props(['employee', 'steps'])

@php
    $isCompleted = $employee->status === 'registration_completed';
    $isCancelled = $employee->status === 'registration_cancelled';

    // Style: if completed/cancelled, flat/grey out.
    // Cancelled gets a specific flat grey look.
    $cardClass = 'bg-white border shadow-sm';
    $overlayClass = '';

    if ($isCompleted) {
        $cardClass = 'bg-success bg-opacity-10 border-0 text-muted';
        $overlayClass = 'opacity-75 pointer-events-none';
    } elseif ($isCancelled) {
        $cardClass = 'bg-light border-0 text-secondary grayscale-mode'; // Add grayscale class or inline style
        $overlayClass = 'opacity-50 pointer-events-none';
    }
@endphp

<div class="card {{ $cardClass }} mb-3" id="employee-card-{{ $employee->id }}" style="transition: all 0.3s ease; {{ $isCancelled ? 'filter: grayscale(100%);' : '' }}">
    <div class="card-body p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
            {{-- Checkbox & Basic Info --}}
            <div class="d-flex align-items-center gap-3 w-100">
                {{-- Only show checkbox if Active (Pending) --}}
                @if(!$isCompleted && !$isCancelled)
                    <div class="form-check">
                        <input class="form-check-input employee-checkbox"
                               type="checkbox"
                               value="{{ $employee->id }}"
                               id="check_{{ $employee->id }}"
                               data-employee-id="{{ $employee->id }}"
                               data-employer-id="{{ $employee->employer_id }}"
                               data-name-th="{{ $employee->employeeNameTh }}"
                               data-name-en="{{ $employee->employeeNameEn }}"
                               data-photo="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/40x40/e2e8f0/6c757d?text=PIC' }}"
                               data-employer-name="{{ $employee->employer->employerNameTh ?? 'N/A' }}">
                    </div>
                @endif

                <div class="d-flex align-items-center gap-3 {{ $overlayClass }}">
                    {{-- Avatar --}}
                    <div class="avatar-container position-relative">
                        @if($employee->employeePhoto)
                            <img src="{{ Storage::disk('public')->url($employee->employeePhoto) }}" class="rounded-circle shadow-sm" style="width: 50px; height: 50px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px; font-size: 1.2rem;">
                                {{ substr($employee->employeeNameEn ?? 'U', 0, 1) }}
                            </div>
                        @endif
                        @if($isCompleted)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success border border-white">
                                <i class="bi bi-check"></i>
                            </span>
                        @endif
                         @if($isCancelled)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary border border-white">
                                <i class="bi bi-x"></i>
                            </span>
                        @endif
                    </div>

                    {{-- Info --}}
                    <div>
                        <h6 class="mb-0 fw-bold text-dark">
                            {{ $employee->employeeNameTh }}
                            @if($employee->employeeNameEn) <span class="text-muted small">({{ $employee->employeeNameEn }})</span> @endif
                        </h6>
                        <div class="small text-muted mt-1">
                            <span class="me-2"><i class="bi bi-passport text-primary me-1"></i> {{ $employee->employeePassport ?? '-' }}</span>
                            <span><i class="bi bi-geo-alt-fill text-danger me-1"></i> {{ $employee->employeeNationality ?? '-' }}</span>
                        </div>
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

                 {{-- Inline Drawer Toggle --}}
                 <button class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Custom Fields"
                    onclick="toggleInlineDrawer({{ $employee->id }}, {{ json_encode($employee) }})">
                    <i class="bi bi-layout-text-window-reverse"></i> {{ __('Fields') }}
                </button>

                <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-sm btn-outline-warning rounded-pill px-3" title="Edit">
                    <i class="bi bi-pencil-fill"></i>
                </a>

                @if(!$isCompleted && !$isCancelled)
                    {{-- Standard Actions --}}
                    <button class="btn btn-sm btn-success rounded-pill px-3" title="Save to Database" onclick="finalizeEmployee({{ $employee->id }})">
                        <i class="bi bi-check-lg"></i> <span class="d-none d-lg-inline">{{ __('Save to DB') }}</span>
                    </button>

                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="Cancel Registration" onclick="cancelEmployee({{ $employee->id }})">
                        <i class="bi bi-x-circle"></i> <span class="d-none d-lg-inline">{{ __('Cancel') }}</span>
                    </button>
                @endif

                @if($isCancelled)
                    {{-- Restore Action for Cancelled --}}
                    <button class="btn btn-sm btn-outline-warning rounded-pill px-3" title="Restore" onclick="restoreEmployeeState({{ $employee->id }})">
                        <i class="bi bi-arrow-counterclockwise"></i> {{ __('Restore') }}
                    </button>
                @endif

                @if($isCompleted)
                     {{-- Undo Complete --}}
                    <button class="btn btn-sm btn-outline-warning rounded-pill px-3" title="Undo / Restore" onclick="restoreEmployeeState({{ $employee->id }})">
                        <i class="bi bi-arrow-counterclockwise"></i> {{ __('Undo') }}
                    </button>
                @endif

                {{-- Delete (Soft) --}}
                <button class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Delete" onclick="deleteEmployee({{ $employee->id }})">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </div>
        </div>

        {{-- Steps Progress Bar (Disable interaction if completed/cancelled) --}}
        <div class="mt-3 {{ $overlayClass }}">
            <div class="d-flex gap-2 flex-wrap">
                @foreach($steps as $step)
                    @php
                        $isStepCompleted = $employee->registrationSteps->contains($step->id);
                        // Determine styles based on hex or class
                        $hexColor = str_starts_with($step->color, '#') ? $step->color : null;

                        // Default State: Incomplete -> Gray outline
                        $btnClass = 'btn-outline-secondary';
                        $btnStyle = '';

                        // Completed State: Colored background
                        if ($isStepCompleted) {
                            if ($hexColor) {
                                $btnClass = 'text-white border-0';
                                $btnStyle = "background-color: {$hexColor} !important; border-color: {$hexColor} !important;";
                            } else {
                                $btnClass = "btn-{$step->color} text-white";
                            }
                        }
                    @endphp
                    <button
                        class="btn btn-sm {{ $btnClass }} rounded-pill px-3"
                        style="font-size: 0.8rem; {{ $btnStyle }}"
                        onclick="toggleStep({{ $employee->id }}, {{ $step->id }}, {{ $isStepCompleted ? 'false' : 'true' }})"
                        data-step-id="{{ $step->id }}"
                        data-color="{{ $step->color }}"
                        data-hex-color="{{ $hexColor }}"
                        {{ ($isCompleted || $isCancelled) ? 'disabled' : '' }}
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
