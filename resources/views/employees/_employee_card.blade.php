@php
    // Helper to prevent errors if employer relationship is not loaded
    $employerName = $employee->employer->employerNameTh ?? 'N/A';
@endphp
<div id="employee-card-{{ $employee->id }}" class="list-group-item list-group-item-action">
    <div class="d-flex align-items-center">
        {{-- Checkbox for Bulk Actions --}}
        <div class="me-3">
            <input class="form-check-input bulk-action-checkbox" type="checkbox" value="{{ $employee->id }}" id="employee_checkbox_{{ $employee->id }}">
        </div>

        {{-- Employee Photo --}}
        <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}"
             alt="Photo" class="employee-photo-thumb">

        {{-- Employee Details --}}
        <div class="flex-grow-1">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">
                    {{ $employee->employeeNameEn ?? 'N/A' }}
                    @if($employee->employeeNationality)
                        @php $flagCode = \App\Helpers\CountryHelper::getFlagCode($employee->employeeNationality); @endphp
                        @if($flagCode)
                            <img src="https://flagcdn.com/w20/{{ $flagCode }}.png" alt="{{ $employee->employeeNationality }}" title="{{ $employee->employeeNationality }}">
                        @endif
                    @endif
                </h5>
                <small class="text-muted" title="นายจ้าง">{{ $employerName }}</small>
            </div>
            <p class="mb-1">{{ $employee->employeeNameTh ?? 'N/A' }} ({{ $employee->employeePosition ?? 'N/A' }})</p>
            <small class="text-muted d-block">Passport: {{ $employee->employeePassport ?? '-' }} (หมดอายุ: {{ $employee->passportExpiryDate ? $employee->passportExpiryDate->format('d/m/Y') : '-' }})</small>
            <small class="text-muted d-block">Work Permit: {{ $employee->employeeWorkPermit ?? '-' }} (หมดอายุ: {{ $employee->workPermitExpiryDate ? $employee->workPermitExpiryDate->format('d/m/Y') : '-' }})</small>
            <small class="text-muted d-block">Visa ({{ $employee->workPermitMOUGroup ?? '-' }}) | 90-Day: {{ $employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : '-' }}</small>
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
