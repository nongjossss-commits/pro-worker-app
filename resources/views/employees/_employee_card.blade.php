<div class="list-group-item list-group-item-action">
    <div class="d-flex align-items-center">
        {{-- Checkbox --}}
        <div class="me-3">
            <input class="form-check-input bulk-action-checkbox" type="checkbox" value="{{ $employee->id }}" id="employee_checkbox_{{ $employee->id }}">
        </div>

        {{-- FIX: Correct structure for the employee info section --}}
        <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}"
             class="employee-photo-thumb" alt="Photo">

        <div class="flex-grow-1">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">
                    {{ $employee->employeeNameEn ?? 'N/A' }}
                    @if($employee->employeeNationality)
                        <img src="https://flagcdn.com/w20/{{ strtolower(substr($employee->employeeNationality, 0, 2)) }}.png" alt="">
                    @endif
                </h5>
                <small class="text-muted">{{ $employee->employer->employerNameTh ?? 'N/A' }}</small>
            </div>
            <p class="mb-1">{{ $employee->employeeNameTh ?? 'N/A' }} ({{ $employee->employeePosition ?? 'N/A' }})</p>
            <small class="text-muted">Passport: {{ $employee->employeePassport ?? '-' }} | Work Permit: {{ $employee->employeeWorkPermit ?? '-' }}</small>
        </div>
        {{-- END OF FIX --}}

        <div class="ms-auto ps-3">
             <div class="btn-group-vertical btn-group-sm">
                 <a href="{{ route('employees.edit', ['employer' => $employee->employer_id, 'employee' => $employee->id]) }}" class="btn btn-outline-primary"><i class="bi bi-pencil-fill"></i></a>
                 <button type="button" class="btn btn-outline-danger"><i class="bi bi-trash-fill"></i></button>
             </div>
        </div>
    </div>
</div>
