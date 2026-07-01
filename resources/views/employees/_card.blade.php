@php
    $flagCodes = ['เมียนมา' => 'mm', 'ลาว' => 'la', 'กัมพูชา' => 'kh', 'เวียดนาม' => 'vn'];
    $nationality = $employee->employeeNationality ?? null;
    $flagCode = $nationality ? ($flagCodes[$nationality] ?? null) : null;
@endphp

<div class="card mb-3" id="employee-card-{{ $employee->id }}">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="d-flex align-items-center flex-grow-1">
                <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}"
                     class="employee-photo-thumb"
                     style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; margin-right: 1rem;"
                     alt="Photo">
                <div class="flex-grow-1">
                    <p class="mb-0">
                        <strong>{{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? 'No English Name' }}</strong>
                        @if($nationality)
                            <span class="text-muted small"> - {{ $nationality }}</span>
                            @if($flagCode)
                                <img src="https://flagcdn.com/w20/{{ $flagCode }}.png" alt="{{ $nationality }}" class="ms-1" style="width: 20px; vertical-align: middle;">
                            @endif
                        @endif
                    </p>
                    <p class="mb-1 text-muted small">{{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? __('No Thai Name') }}</p>
                    <p class="mb-1 text-muted small"><strong>{{ __('Employer') }}:</strong> {{ $employee->employer->employerNameTh ?? 'N/A' }}</p>
                    {{-- START: Full Details --}}
                    <p class="mb-1 text-muted small">{{ __('Passport') }}: {{ $employee->employeePassport ?? '-' }} ({{ __('Expires') }}: {{ $employee->passportExpiryDate ? \Carbon\Carbon::parse($employee->passportExpiryDate)->format('d/m/Y') : '-' }})</p>
                    <p class="mb-1 text-muted small">{{ __('Work Permit') }}: {{ $employee->employeeWorkPermit ?? '-' }} ({{ __('Expires') }}: {{ $employee->workPermitExpiryDate ? \Carbon\Carbon::parse($employee->workPermitExpiryDate)->format('d/m/Y') : '-' }})</p>
                    <p class="mb-1 text-muted small">{{ __('Visa Endorsement') }}: {{ $employee->visaEndorsementNo ?? '-' }} ({{ __('Date') }}: {{ $employee->visaEndorsementDate ? \Carbon\Carbon::parse($employee->visaEndorsementDate)->format('d/m/Y') : '-' }})</p>
                    <p class="mb-0 text-muted small">{{ __('Visa') }} ({{ $employee->workPermitMOUGroup ?? '-' }}) {{ __('Expires') }}: {{ $employee->visaExpiryDate ? \Carbon\Carbon::parse($employee->visaExpiryDate)->format('d/m/Y') : '-' }} | {{ __('90-Day Report') }}: {{ $employee->ninetyDayReportDate ? \Carbon\Carbon::parse($employee->ninetyDayReportDate)->format('d/m/Y') : '-' }}</p>
                    {{-- END: Full Details --}}
                </div>
            </div>
            <div class="btn-group btn-group-sm">
                <a href="{{ route('employees.locate', $employee->id) }}" class="btn btn-outline-info" title="{{ __('Locate Employer') }}"><i class="bi bi-geo-alt-fill"></i></a>
                <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-outline-primary" title="{{ __('Edit') }}"><i class="bi bi-pencil-fill"></i></a>
                <button type="button" class="btn btn-outline-danger delete-employee-btn" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-delete-url="{{ route('employees.destroy', $employee->id) }}" title="{{ __('Delete') }}"><i class="bi bi-trash-fill"></i></button>
            </div>
        </div>
    </div>
</div>
