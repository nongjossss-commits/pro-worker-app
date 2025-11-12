@php
    $employerName = $employee->employer->employerNameTh ?? 'N/A';
@endphp

<div id="employee-card-{{ $employee->id }}" class="employee-card card mb-3">
    <div class="card-body d-flex align-items-center">
        <div class="me-3">
            <input class="form-check-input employee-checkbox" type="checkbox" value="{{ $employee->id }}" data-employee-id="{{ $employee->id }}">
        </div>

        <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : asset('images/default-profile.png') }}"
            alt="Photo" class="employee-photo-thumb" style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%; margin-right: 1rem;">

        <div class="employee-info flex-grow-1">
            <span class="employee-name-en">
                @if(isset($pagination) && $pagination instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    {{ ($pagination->currentPage() - 1) * $pagination->perPage() + $loop->iteration }}.
                @else
                    {{ $loop->iteration }}.
                @endif
                {{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? 'No English Name' }}
            </span>

            @if($employee->employeeNationality)
                @php
                    $countryCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
                @endphp
                @if($countryCode)
                    <span class="badge bg-light text-dark ms-2 d-inline-flex align-items-center">
                        <img src="{{ asset('images/flags/' . strtolower($countryCode) . '.png') }}" alt="{{ $countryCode }}" style="width: 16px; height: 12px; margin-right: 5px;">
                        <span>{{ $employee->employeeNationality }}</span>
                    </span>
                @endif
            @endif

            <span class="employee-name-th d-block">
                {{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? 'N/A' }} ({{ $employee->employeePosition ?? 'N/A' }})
            </span>

            <span class="employer-name d-block text-muted">นายจ้าง: {{ $employerName }}</span>

            <div class="document-details small mt-2">
                Passport: {{ $employee->employeePassport ?? '-' }} (หมดอายุ: {{ $employee->passportExpiryDate ? $employee->passportExpiryDate->format('d/m/Y') : '-' }})
                <br>
                Visa ({{ $employee->workPermitMOUGroup ?? '-' }}) | 90-Day: {{ $employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : '-' }}
            </div>
        </div>

        <div class="employee-actions">
            @if(isset($isTrashView) && $isTrashView)
                @include('admin.trash._action_buttons', ['modelName' => 'employees', 'item' => $employee])
            @else
                <x-employee-action-buttons :employee="$employee" :show-locate-button="($showLocateButton ?? false)" />
            @endif
        </div>
    </div>
</div>