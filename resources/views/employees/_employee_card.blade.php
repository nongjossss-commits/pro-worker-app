<div class="list-group-item list-group-item-action">
    <div class="d-flex align-items-center">
        {{-- NEW: Checkbox --}}
        <div class="me-3">
            <input class="form-check-input bulk-action-checkbox" type="checkbox" value="{{ $employee->id }}" id="employee_checkbox_{{ $employee->id }}">
        </div>
        <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}" class="employee-photo-thumb" alt="Photo">
        <div class="flex-grow-1">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">{{ $employee->name }}</h5>
                <small>3 days ago</small>
            </div>
            <p class="mb-1">Passport No: {{ $employee->passport_number }}</p>
            <small>Nationality: {{ $employee->nationality }}</small>
        </div>
        <div class="ms-auto">
            <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-warning btn-sm">
                <i class="fa-solid fa-pen-to-square"></i>
            </a>
            <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </form>
        </div>
    </div>
</div>
