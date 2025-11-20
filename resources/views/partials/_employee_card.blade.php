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
                {{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? __('No English Name') }}
            </span>

            <button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-2" data-model-type="employee" data-model-id="{{ $employee->id }}" title="{{ __('Preview Data') }}">
                <i class="bi bi-search"></i>
            </button>

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
                {{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? __('No Thai Name') }} ({{ $employee->job_title ?? __('Unspecified Position') }})
            </span>

            <span class="employer-name d-block text-muted">
                {{ __('Employer') }}: {{ $employerName }}
                @if($employee->employer)
                <button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-2" data-model-type="employer" data-model-id="{{ $employee->employer->id }}" title="{{ __('Preview Data') }}">
                    <i class="bi bi-search"></i>
                </button>
                @endif
            </span>

            <div class="document-details small mt-2">
                {{ __('Passport') }}: {{ $employee->employeePassport ?? '-' }} ({{ __('Expiry Date') }}: {{ $employee->passportExpiryDate ? $employee->passportExpiryDate->format('d/m/Y') : '-' }})
                <br>
                {{ __('Visa') }} ({{ $employee->workPermitMOUGroup ?? '-' }}) | {{ __('90-Day Report') }}: {{ $employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : '-' }}
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
