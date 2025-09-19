<div id="employee-card-{{ $employee->id }}" class="list-group-item list-group-item-action">
    <div class="d-flex align-items-center">
        {{-- Checkbox for Bulk Actions --}}
        <div class="me-3">
            <input class="form-check-input bulk-action-checkbox" type="checkbox" value="{{ $employee->id }}" id="employee_checkbox_{{ $employee->id }}">
        </div>

        {{-- FINAL FIX: Apply inline styles to guarantee the size --}}
        <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}"
             alt="Photo"
             style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%; margin-right: 1rem;">

        <div class="flex-grow-1">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">
                    {{ $employee->employeeNameEn ?? 'N/A' }}
                    @if($employee->employeeNationality)
                        @php $flagCode = \App\Helpers\CountryHelper::getFlagCode($employee->employeeNationality); @endphp
                        @if($flagCode)
                            <img src="https://flagcdn.com/w20/{{ $flagCode }}.png" alt="{{ $employee->employeeNationality }}">
                        @endif
                    @endif
                </h5>
                <small class="text-muted">{{ $employee->employer->employerNameTh ?? 'N/A' }}</small>
            </div>
            <p class="mb-1">{{ $employee->employeeNameTh ?? 'N/A' }} ({{ $employee->employeePosition ?? 'N/A' }})</p>
            <small class="text-muted">Passport: {{ $employee->employeePassport ?? '-' }} | Work Permit: {{ $employee->employeeWorkPermit ?? '-' }}</small>
        </div>

        <div class="ms-auto ps-3">
             <div class="btn-group-vertical btn-group-sm">
                 <a href="{{ route('employees.edit', ['employer' => $employee->employer_id, 'employee' => $employee->id]) }}" class="btn btn-outline-primary"><i class="bi bi-pencil-fill"></i></a>
                 <button type="button" class="btn btn-outline-danger"><i class="bi bi-trash-fill"></i></button>
             </div>
        </div>
    </div>
</div>
