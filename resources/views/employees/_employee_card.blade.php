@php
    // Helper to prevent errors if employer relationship is not loaded
    $employerName = $employee->employer->employerNameTh ?? 'N/A';
@endphp
@php
    // V2.5: Check completeness for badge display (Only in Incomplete View)
    $missingCount = 0;
    if (isset($is_incomplete_view) && $is_incomplete_view) {
        $missingCount = count(\App\Helpers\CompletenessHelper::getMissingFields($employee, $mandatoryFields ?? null));
    }
@endphp

<div id="employee-card-{{ $employee->id }}" class="list-group-item list-group-item-action position-relative">
    @if($missingCount > 0)
        <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-warning text-dark border border-light shadow-sm" style="z-index: 10; margin-left: 15px; margin-top: 15px; font-size: 0.8rem;">
            {{ $missingCount }}
            <span class="visually-hidden">missing fields</span>
        </span>
    @endif
    <div class="d-flex align-items-center">
        {{-- Checkbox for Bulk Actions --}}
        <div class="me-3">
            <input class="form-check-input employee-checkbox" type="checkbox" value="{{ $employee->id }}" id="employee_checkbox_{{ $employee->id }}" data-employee-id="{{ $employee->id }}">
        </div>

        {{-- Employee Photo --}}
        <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}"
             alt="Photo" class="employee-photo-thumb">

        {{-- Employee Details --}}
        <div class="flex-grow-1">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">
                    {{ trim(($employee->employeeTitleEn ?? '') . ' ' . ($employee->employeeNameEn ?? '')) ?: 'N/A' }}
                    <button class="btn btn-sm btn-link p-0 ms-1 btn-preview"
                            data-model-type="employee"
                            data-model-id="{{ $employee->id }}"
                            @click.stop
                            title="{{ __('Preview Employee') }}">
                        <i class="bi bi-search"></i>
                    </button>

                    @if($employee->employeeNationality)
                        @php $flagCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality); @endphp
                        @if($flagCode)
                            <img src="https://flagcdn.com/w20/{{ $flagCode }}.png" alt="{{ $employee->employeeNationality }}" title="{{ $employee->employeeNationality }}">
                        @endif
                    @endif
                </h5>
                <small class="text-muted" title="นายจ้าง">
                    {{ $employerName }}
                    @if($employee->employer)
                        <button class="btn btn-sm btn-link p-0 ms-1 btn-preview"
                                data-model-type="employer"
                                data-model-id="{{ $employee->employer->id }}"
                                @click.stop
                                title="{{ __('Preview Employer') }}">
                            <i class="bi bi-search"></i>
                        </button>
                    @endif
                </small>
            </div>
            <p class="mb-1">{{ trim(($employee->employeeTitleTh ?? '') . ' ' . ($employee->employeeNameTh ?? '')) ?: 'N/A' }} ({{ $employee->job_title ?? 'N/A' }})</p>
            <small class="text-muted d-block">Passport: {{ $employee->employeePassport ?? '-' }} (หมดอายุ: {{ optional($employee->passportExpiryDate)->format('d/m/Y') ?? '-' }})</small>
            <small class="text-muted d-block">Work Permit: <strong>{{ $employee->employeeWorkPermit ?? '-' }}</strong> (หมดอายุ: {{ optional($employee->workPermitExpiryDate)->format('d/m/Y') ?? '-' }})</small>
            <small class="text-muted d-block">Visa ({{ $employee->workPermitMOUGroup ?? '-' }}) | 90-Day: {{ optional($employee->ninetyDayReportDate)->format('d/m/Y') ?? '-' }}</small>
        </div>

        {{-- Action Buttons --}}
        <div class="ms-auto ps-3">
             <div class="btn-group-vertical btn-group-sm">
                 <a href="{{ route('employees.edit', ['employer' => $employee->employer_id, 'employee' => $employee->id]) }}" class="btn btn-outline-primary" title="แก้ไข"><i class="bi bi-pencil-fill"></i></a>
                 <a href="{{ route('employees.locate', $employee) }}" class="btn btn-outline-info" title="ไปที่ข้อมูลนายจ้าง"><i class="bi bi-geo-alt-fill"></i></a>
                 <button type="button" class="btn btn-outline-danger" title="ลบ"><i class="bi bi-trash-fill"></i></button>
             </div>
        </div>
    </div>
</div>
