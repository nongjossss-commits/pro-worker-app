@php

$employerName = $employee->employer->employerNameTh ?? 'N/A';
@endphp

<div class="card employee-card" id="employee-card-{{ $employee->id }}">
    <div class="card-body">
        <div class="d-flex align-items-center mb-2">
            @if($employee->employeePhoto)
            <img src="{{ asset('storage/' . $employee->employeePhoto) }}"
                alt="Photo" class="employee-photo-thumb" style="width: 48px;
 height: 48px; object-fit: cover; border-radius: 50%; margin-right: 1rem;">
            @else
            [image: Photo]
            @endif

            <h5 class="card-title mb-0">
                {{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? 'N/A' }}

           </h5>

        </div>
        {{-- Employee Details (Partial) --}}
        <p class="card-text small mb-1">
            นายจ้าง: {{ $employerName }}
        </p>
        <p class="card-text small mb-3">
            Passport: {{ $employee->employeePassport ??
 '-' }} (หมดอายุ: {{ $employee->passportExpiryDate ? $employee->passportExpiryDate->format('d/m/Y') : '-' }})
            <br>
            Visa ({{ $employee->workPermitMOUGroup ?? '-' }}) | 90-Day: {{ $employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : '-' }}
        </p>

        {{-- Action Buttons --}}
        @include('components.employee-action-buttons', ['employee' => $employee, 'showLocateButton' => ($showLocateButton ??
 false)])
    </div>
</div>