@php
    $employerName = $employee->employer?->employerNameTh ?? 'N/A';
    $employeeFullNameTh = ($employee->employeeTitleTh ? $employee->employeeTitleTh . ' ' : '') . ($employee->employeeNameTh ?? 'N/A');
    $employeeFullNameEn = ($employee->employeeTitleEn ? $employee->employeeTitleEn . ' ' : '') . ($employee->employeeNameEn ?? 'N/A');
    $countryCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
@endphp

<div id="history-row-{{ $employee->id }}" class="list-group-item list-group-item-action position-relative">
    @if(isset($employee->active_workflows) && $employee->active_workflows->isNotEmpty())
        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center rounded"
             style="background-color: rgba(255, 223, 0, 0.15); border: 2px solid #ffc107; z-index: 10; pointer-events: none;">
             <div class="d-flex flex-column gap-2" style="pointer-events: auto;">
                @foreach($employee->active_workflows as $wf)
                <a href="{{ $wf->url }}"
                       class="badge bg-warning text-dark text-decoration-none shadow-sm border border-dark fs-6 text-truncate"
                       style="max-width: 90%;">
                       <i class="bi bi-gear-fill me-1"></i> {{ $wf->status_label }}: {{ $wf->name }}
                    </a>
                @endforeach
             </div>
        </div>
    @endif
    <div class="d-flex w-100">
        {{-- Checkbox --}}
        <div class="me-3 d-flex align-items-center">
            <input class="form-check-input history-employee-checkbox" type="checkbox" value="{{ $employee->id }}" data-employee-id="{{ $employee->id }}">
        </div>

        {{-- Drag Handle --}}
        <div class="me-3 d-flex align-items-center">
            <i class="bi bi-grid-3x2-gap-fill text-muted cursor-grab"
               draggable="true"
               data-drag-payload="{{ json_encode([
                    'id' => $employee->id,
                    'title' => $employeeFullNameEn,
                    'subtitle' => 'Terminated: ' . ($employee->terminated_at ? $employee->terminated_at->format('d/m/Y') : ''),
                    'url' => route('employees.history') . '?highlight_employee=' . $employee->id
               ]) }}"
               ondragstart="window.startDragGlobal(event, 'employee', JSON.parse(this.dataset.dragPayload))"
               title="{{ __('Drag') }}"></i>
        </div>

        {{-- Employee Photo --}}
        <div class="me-3 d-flex align-items-center">
            <img src="{{ $employee->photo_url }}" alt="Photo" class="employee-photo-thumb">
        </div>

        {{-- Employee Details --}}
        <div class="flex-grow-1">
            <h5 class="mb-1">
                <span class="text-dark">{{ $employeeFullNameEn }}</span>
                @if($countryCode)
                    <span class="badge bg-light text-dark ms-2 d-inline-flex align-items-center">
                        <img src="{{ asset('images/flags/' . strtolower($countryCode) . '.png') }}" alt="{{ $countryCode }}" class="me-1" style="width: 16px;">
                        {{ $employee->employeeNationality }}
                    </span>
                @endif
            </h5>
            <p class="mb-1">{{ $employeeFullNameTh }}</p>
            <p class="mb-1 text-muted"><small>Employer: {{ $employerName }}</small></p>

            <div class="row gx-3 mt-2">
                <div class="col-md-6">
                    <small class="text-muted d-block">Passport: <strong>{{ $employee->employeePassport ?? '-' }}</strong></small>
                    <small class="text-muted d-block">Work Permit: <strong>{{ $employee->employeeWorkPermit ?? '-' }}</strong> ({{ $employee->workPermitType ?? '-' }})</small>
                </div>
                <div class="col-md-6 d-flex flex-column justify-content-center">
                    <small class="text-muted d-block">Terminated: <strong>{{ $employee->terminated_at ? $employee->terminated_at->format('d/m/Y') : '-' }}</strong> <span class="badge bg-secondary">{{ $employee->days_since_termination }} วัน</span></small>
                    <small class="text-muted d-block">Reason: {{ $employee->termination_reason ?: '-' }}</small>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="ms-auto ps-3 d-flex align-items-center">
            <div class="d-flex flex-column flex-md-row gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary btn-preview" data-model-type="employee" data-model-id="{{ $employee->id }}" title="พรีวิวข้อมูล">
                    <i class="bi bi-search"></i>
                </button>
                <a href="{{ route('employees.locate', $employee) }}" class="btn btn-sm btn-info" title="ไปที่ข้อมูลนายจ้าง">
                    <i class="bi bi-geo-alt-fill"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-primary" title="Create Job (Coming Soon)" disabled>
                    <i class="bi bi-briefcase-fill"></i>
                </button>
                 <button class="btn btn-sm btn-outline-success btn-reinstate" title="Restore" data-employee-id="{{ $employee->id }}"><i class="bi bi-arrow-counterclockwise"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-move-to-trash" title="Move to Trash" data-employee-id="{{ $employee->id }}"><i class="bi bi-trash3-fill"></i></button>
                <button class="btn btn-sm btn-outline-info btn-transfer-employee" title="Transfer Employer" data-employee-id="{{ $employee->id }}" data-employee-name="{{ $employee->employeeNameTh }}"><i class="bi bi-person-up"></i></button>
            </div>
        </div>
    </div>
</div>
