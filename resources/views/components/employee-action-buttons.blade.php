@props(['employee', 'showLocateButton' => false])

<div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-start gap-2">
    {{-- Preview Button (Info Blue) --}}
    <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employee" data-model-id="{{ $employee->id }}" title="พรีวิวข้อมูล">
        <i class="bi bi-search"></i>
    </button>

    {{-- Download Button --}}
    <button type="button" class="btn btn-sm btn-outline-dark btn-download-single" data-employee-id="{{ $employee->id }}" title="ดาวน์โหลดเอกสาร">
        <i class="bi bi-download"></i>
    </button>

    {{-- Create Job Button (Green) - Placeholder --}}
    <a href="#" class="btn btn-sm btn-outline-success" title="Create Job (Coming Soon)">
        <i class="bi bi-briefcase-fill"></i>
    </a>
    
    {{-- Edit Button (Yellow) --}}
    @can('edit-employees')
        <a href="{{ route('employees.edit', ['employee' => $employee->id]) }}" class="btn btn-sm btn-warning" title="Edit Employee">
            <i class="bi bi-pencil-fill"></i>
        </a>
    @endcan

    {{-- Locate Button (Blue) - Conditional --}}
    @if($showLocateButton)
        <a href="{{ route('employees.locate', $employee) }}" class="btn btn-sm btn-primary" title="Locate in Employer List">
            <i class="bi bi-geo-alt-fill"></i>
        </a>
    @endif

    {{-- Terminate Button (Dark Grey) --}}
    @can('terminate-employees')
        <button type="button"
                class="btn btn-sm btn-secondary btn-terminate"
                title="Terminate Employment"
                data-bs-toggle="modal"
                data-bs-target="#terminateEmployeeModal"
                data-employee-id="{{ $employee->id }}"
                data-employee-name="{{ $employee->employeeNameTh }}">
            <i class="bi bi-person-x-fill"></i>
        </button>
    @endcan

    {{-- Standard Delete (Soft Delete to Trash) Button --}}
    @can('delete-employees')
        <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" class="d-grid d-md-inline delete-employee-form">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger" title="Delete Employee (to Trash)">
                <i class="bi bi-trash-fill"></i>
            </button>
        </form>
    @endcan
</div>
