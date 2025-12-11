@props(['employee', 'steps'])

@php
    $isCompleted = $employee->status === 'registration_completed';
    // Style: if completed, flatten/grey out.
    $cardClass = $isCompleted ? 'bg-secondary bg-opacity-10 border-0 text-muted' : 'bg-white border shadow-sm';
    $overlayClass = $isCompleted ? 'opacity-50 pointer-events-none' : '';
@endphp

<div class="card {{ $cardClass }} mb-3" id="employee-card-{{ $employee->id }}" style="transition: all 0.3s ease;">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start">
            {{-- Checkbox & Basic Info --}}
            <div class="d-flex align-items-center gap-3 w-100">
                {{-- Only show checkbox if NOT completed --}}
                @if(!$isCompleted)
                    <div class="form-check">
                        <input class="form-check-input registration-checkbox" type="checkbox" value="{{ $employee->id }}" id="check_{{ $employee->id }}">
                    </div>
                @endif

                <div class="d-flex align-items-center gap-3 {{ $overlayClass }}">
                    {{-- Avatar --}}
                    <div class="avatar-container">
                        @if($employee->employeePhoto)
                            <img src="{{ Storage::disk('public')->url($employee->employeePhoto) }}" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.2rem;">
                                {{ substr($employee->employeeNameEn ?? 'U', 0, 1) }}
                            </div>
                        @endif
                    </div>

                    {{-- Info --}}
                    <div>
                        <h6 class="mb-0 fw-bold">
                            {{ $employee->employeeNameTh }}
                            @if($employee->employeeNameEn) <span class="text-muted small">({{ $employee->employeeNameEn }})</span> @endif
                        </h6>
                        <div class="small text-muted">
                            <i class="bi bi-passport me-1"></i> {{ $employee->employeePassport ?? '-' }} |
                            <i class="bi bi-geo-alt me-1"></i> {{ $employee->employeeNationality ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="d-flex gap-2">
                 {{-- Preview Button (Universal) --}}
                 <button class="btn btn-sm btn-outline-info btn-preview"
                    data-model-type="employee"
                    data-model-id="{{ $employee->id }}"
                    title="Preview">
                    <i class="bi bi-search"></i>
                </button>

                 {{-- Drawer Button (Custom Fields) --}}
                 <button class="btn btn-sm btn-outline-primary" title="Custom Fields" onclick="openEmployeeDrawer({{ json_encode($employee) }})">
                    <i class="bi bi-layout-text-sidebar-reverse"></i>
                </button>

                @if(!$isCompleted)
                    {{-- Standard Actions --}}
                    <button class="btn btn-sm btn-outline-success" title="Save to Database" onclick="finalizeEmployee({{ $employee->id }})">
                        <i class="bi bi-check-lg"></i> <span class="d-none d-md-inline">{{ __('Save to DB') }}</span>
                    </button>
                    {{-- Add other actions like Edit/Delete here if needed --}}
                @else
                    {{-- Restore Action --}}
                    <button class="btn btn-sm btn-outline-warning" title="Restore / Undo" onclick="restoreEmployeeState({{ $employee->id }})">
                        <i class="bi bi-arrow-counterclockwise"></i> {{ __('Undo') }}
                    </button>
                    <span class="badge bg-success align-self-center"><i class="bi bi-database-check"></i> {{ __('Saved') }}</span>
                @endif
            </div>
        </div>

        {{-- Steps Progress Bar (Disable interaction if completed) --}}
        <div class="mt-3 {{ $overlayClass }}">
            <label class="small text-muted mb-1">{{ __('Workflow Progress') }}</label>
            <div class="d-flex gap-1 flex-wrap">
                @foreach($steps as $step)
                    @php
                        $isStepCompleted = $employee->registrationSteps->contains($step->id);
                    @endphp
                    <button
                        class="btn btn-sm {{ $isStepCompleted ? 'btn-success' : 'btn-outline-secondary' }}"
                        style="font-size: 0.75rem;"
                        onclick="toggleStep({{ $employee->id }}, {{ $step->id }}, {{ $isStepCompleted ? 'false' : 'true' }})"
                        {{ $isCompleted ? 'disabled' : '' }}
                    >
                        {{ $step->name }}
                        @if($isStepCompleted) <i class="bi bi-check"></i> @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
