@props(['employee', 'showLocateButton' => false])

<div class="d-flex align-items-center justify-content-end">
    {{-- Create Job Button (Green) --}}
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

    {{-- Terminate Button (Orange) --}}
    @can('terminate-employees')
        <button type="button" class="btn btn-sm btn-outline-warning me-1" title="Terminate"
                data-bs-toggle="modal"
                data-bs-target="#terminateEmployeeModal"
                data-employee-id="{{ $employee->id }}">
            <i class="bi bi-person-x-fill"></i>
        </button>
    @endcan

    {{-- Force Delete Button (Red) - Admin Only --}}
    @can('force-delete-employees')
         <button type="button" class="btn btn-sm btn-danger btn-force-delete" title="Force Delete"
                data-employee-id="{{ $employee->id }}">
            <i class="bi bi-trash3-fill"></i>
        </button>
    @endcan
</div>