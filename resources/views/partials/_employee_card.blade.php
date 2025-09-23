@php
    $employerName = $employee->employer->employerNameTh ?? 'N/A';
@endphp
<div id="employee-card-{{ $employee->id }}" class="list-group-item list-group-item-action employee-card">
    <div class="d-flex align-items-center">
        <div class="me-3">
            <input class="form-check-input bulk-action-checkbox" type="checkbox" value="{{ $employee->id }}" id="employee_checkbox_{{ $employee->id }}">
        </div>
        <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}"
             alt="Photo" class="employee-photo-thumb" style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%; margin-right: 1rem;">
        <div class="flex-grow-1">
            <p class="mb-0">
                <strong>
                    {{-- Calculate the correct item number based on pagination --}}
                    @if(isset($pagination) && $pagination instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        {{ ($pagination->currentPage() - 1) * $pagination->perPage() + $loop->iteration }}.
                    @else
                        {{ $loop->iteration }}.
                    @endif
                    {{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? 'No English Name' }}
                </strong>
                @if($employee->employeeNationality)
                    @php $flagCode = \App\Helpers\CountryHelper::getFlagCode($employee->employeeNationality); @endphp
                    @if($flagCode)
                        <img src="https://flagcdn.com/w20/{{ $flagCode }}.png" alt="{{ $employee->employeeNationality }}" title="{{ $employee->employeeNationality }}">
                    @endif
                @endif
            </p>
            {{-- FIX: Added Name Prefix --}}
            <p class="mb-1">{{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? 'N/A' }} ({{ $employee->employeePosition ?? 'N/A' }})</p>
            <small class="text-muted d-block" title="นายจ้าง">นายจ้าง: {{ $employerName }}</small>
            <small class="text-muted d-block">Passport: {{ $employee->employeePassport ?? '-' }} (หมดอายุ: {{ $employee->passportExpiryDate ? $employee->passportExpiryDate->format('d/m/Y') : '-' }})</small>
            <small class="text-muted d-block">Work Permit: {{ $employee->employeeWorkPermit ?? '-' }} (หมดอายุ: {{ $employee->workPermitExpiryDate ? $employee->workPermitExpiryDate->format('d/m/Y') : '-' }})</small>
            <small class="text-muted d-block">Visa ({{ $employee->workPermitMOUGroup ?? '-' }}) | 90-Day: {{ $employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : '-' }}</small>
        </div>
        <div class="ms-auto ps-3">
             <div class="btn-group btn-group-sm">
                 <a href="{{ route('employees.edit', ['employer' => $employee->employer_id, 'employee' => $employee->id]) }}" class="btn btn-outline-primary" title="แก้ไข">
                     <i class="bi bi-pencil-fill"></i>
                 </a>
                 <button type="button" class="btn btn-outline-warning terminate-employee-btn" data-id="{{ $employee->id }}" title="แจ้งออก/เลิกจ้าง">
                     <i class="bi bi-person-dash-fill"></i>
                 </button>
                 <button type="button" class="btn btn-outline-danger delete-employee-btn" data-id="{{ $employee->id }}" title="ลบข้อมูล (ถาวร)">
                     <i class="bi bi-trash-fill"></i>
                 </button>
             </div>
        </div>
    </div>
</div>
