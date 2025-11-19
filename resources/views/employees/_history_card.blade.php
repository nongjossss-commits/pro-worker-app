@php
    $employerName = $employee->employer->employerNameTh ?? 'N/A';
    $employeeFullName = $employee->employeeFullName;
@endphp
<div id="history-row-{{ $employee->id }}" class="list-group-item list-group-item-action">
    <div class="d-flex align-items-center">
        <div class="me-3">
            <input class="form-check-input history-employee-checkbox" type="checkbox" data-employee-id="{{ $employee->id }}">
        </div>

        <img src="{{ $employee->photo_url }}" alt="Photo" class="employee-photo-thumb">

        <div class="flex-grow-1">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">
                    {{ $employee->employeeNameEn ?? 'N/A' }}
                     @php
                        $countryCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
                    @endphp
                    @if($countryCode)
                        <img src="{{ asset('images/flags/' . strtolower($countryCode) . '.png') }}" alt="{{ $countryCode }}" title="{{ $employee->employeeNationality }}" class="ms-2" style="width: 20px;">
                    @endif
                </h5>
                <small class="text-muted" title="นายจ้าง">{{ $employerName }}</small>
            </div>
            <p class="mb-1">{{ $employeeFullName }}</p>
            <small class="text-muted d-block">
                Terminated: <strong>{{ $employee->terminated_at ? $employee->terminated_at->format('d/m/Y') : '-' }}</strong>
                <span class="badge bg-secondary">{{ $employee->days_since_termination }} วัน</span>
            </small>
            <small class="text-muted d-block">Reason: {{ $employee->termination_reason ?: '-' }}</small>
        </div>

        <div class="ms-auto ps-3">
            <div class="btn-group-vertical btn-group-sm">
                <button class="btn btn-outline-success btn-reinstate" title="Restore" data-employee-id="{{ $employee->id }}"><i class="bi bi-arrow-counterclockwise"></i></button>
                <button class="btn btn-outline-danger btn-move-to-trash" title="Move to Trash" data-employee-id="{{ $employee->id }}"><i class="bi bi-trash3-fill"></i></button>
                <button class="btn btn-outline-info btn-transfer-employee" title="Transfer Employer" data-employee-id="{{ $employee->id }}" data-employee-name="{{ $employeeFullName }}"><i class="bi bi-person-up"></i></button>
            </div>
        </div>
    </div>
</div>
