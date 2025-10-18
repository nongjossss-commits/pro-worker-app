@props(['employee', 'showLocateButton' => false])

{{-- Create Job Button (Green) - Placeholder --}}
<a href="{{ route('jobs.create_from_employee', $employee) }}" class="btn btn-sm btn-success">
    สร้างงาน
</a>

{{-- Edit Button (Yellow) --}}
@can('edit-employees')
<a href="{{ route('employees.edit', ['employee' => $employee->id]) }}" class="btn btn-sm btn-warning">
    แก้ไข
</a>
@endcan

{{-- Locate Button (Blue) - Conditional --}}
@if($showLocateButton)
<a href="{{ route('employees.locate', $employee) }}" class="btn btn-sm btn-info">
    ดูนายจ้าง
</a>
@endif

{{-- Terminate Button (Orange) - Soft Delete (requires reason/date) --}}
@can('terminate-employees')
<button type="button" class="btn btn-sm btn-secondary
 terminate-employee-btn"
    data-employee-id="{{ $employee->id }}"
    data-bs-toggle="modal"
    data-bs-target="#terminateEmployeeModal">
    แจ้งออก/เลิกจ้าง
</button>
@endcan

{{-- Force Delete Button (Red) - Admin Only - UPDATED to use Central Modal --}}
@can('force-delete-employees')
<button type="button" class="btn btn-sm btn-danger delete-resource"
    data-bs-toggle="modal"
    data-bs-target="#centralDeleteConfirmationModal"
    data-action="{{ route('employees.forceDelete', $employee) }}"
    data-type="ลูกจ้าง"
    data-name="{{ $employee->employeeNameTh ?? $employee->employeeNameEn }}"
    data-force-delete="true">
    Force Delete
</button>
@endcan