@props(['employee', 'showLocateButton' => false])

<div class="d-flex align-items-center justify-content-start">
    {{-- Create Job Button (Green) - Placeholder --}}
    <a href="#" class="btn btn-sm btn-outline-success me-1" title="Create Job (Coming Soon)">
        <i class="bi bi-briefcase-fill"></i>
    </a>
    
    {{-- Edit Button (Yellow) --}}
    @can('edit-employees')
        <a href="{{ route('employees.edit', ['employee' => $employee->id]) }}" class="btn btn-sm btn-warning me-1" title="Edit Employee">
            <i class="bi bi-pencil-fill"></i>
        </a>
    @endcan

    {{-- Locate Button (Blue) - Conditional --}}
    @if($showLocateButton)
        <a href="{{ route('employees.locate', $employee) }}" class="btn btn-sm btn-primary me-1" title="Locate in Employer List">
            <i class="bi bi-geo-alt-fill"></i>
        </a>
    @endif

    {{-- Standard Delete (Soft Delete) Button --}}
    @can('delete-employees')
        <button type="button"
                class="btn btn-sm btn-danger btn-trigger-delete-modal"
                title="Delete Employee"
                data-action="{{ route('employees.destroy', $employee->id) }}"
                data-message="Are you sure you want to move this employee to the Central Trash?"
                data-is-force-delete="false">
            <i class="bi bi-trash-fill"></i>
        </button>
    @endcan
</div>
