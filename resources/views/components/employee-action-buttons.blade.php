@props(['employee', 'employer' => null])

@php
    $containerClass = $employer ? "d-grid d-md-flex gap-2 justify-content-md-end" : "d-flex flex-column flex-md-row gap-2 justify-content-end";
@endphp

<div class="{{ $containerClass }} employee-action-buttons">
    {{-- Locate Button --}}
    <a href="{{ route('employees.locate', $employee) }}" class="btn btn-sm btn-outline-info" title="{{ __('Locate Employee') }}">
        <i class="bi bi-geo-alt-fill"></i> <span class="d-none d-md-inline">{{ __('Locate') }}</span>
    </a>

    {{-- View Documents (Updated for Drag & Drop) --}}
    <button type="button"
            class="btn btn-sm btn-outline-primary btn-preview"
            data-model-type="employee"
            data-model-id="{{ $employee->id }}"
            title="{{ __('View Details') }}"
            draggable="true"
            ondragstart="startDragGlobal(event, 'employee', { id: {{ $employee->id }}, name: '{{ $employee->employeeNameTh }}', subtitle: '{{ $employee->employeeCode }}' })">
        <i class="bi bi-eye-fill"></i> <span class="d-none d-md-inline">{{ __('View') }}</span>
    </button>

    {{-- Edit Button --}}
    <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-sm btn-outline-warning" title="{{ __('Edit') }}">
        <i class="bi bi-pencil-fill"></i> <span class="d-none d-md-inline">{{ __('Edit') }}</span>
    </a>

    {{-- Terminate Button (Triggers Modal) --}}
    @if(!$employee->terminated_at)
    <button type="button" class="btn btn-sm btn-outline-danger"
            data-bs-toggle="modal"
            data-bs-target="#terminateModal-{{ $employee->id }}"
            title="{{ __('Terminate') }}">
        <i class="bi bi-person-x-fill"></i> <span class="d-none d-md-inline">{{ __('Terminate') }}</span>
    </button>
    @else
        {{-- Reinstate Button --}}
        <form action="{{ route('employees.reinstate', $employee->id) }}" method="POST" class="d-grid d-md-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-success" title="{{ __('Reinstate') }}">
            <i class="bi bi-person-check-fill"></i> <span class="d-none d-md-inline">{{ __('Reinstate') }}</span>
        </button>
    </form>
    @endif

    {{-- Delete Form (Standard Soft Delete) --}}
    <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" class="d-grid d-md-inline delete-employee-form">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-danger" title="{{ __('Delete') }}">
            <i class="bi bi-trash-fill"></i> <span class="d-none d-md-inline">{{ __('Delete') }}</span>
        </button>
    </form>
</div>

{{-- Terminate Modal --}}
@if(!$employee->terminated_at)
    @include('employees.partials._employee_action_modals', ['employee' => $employee])
@endif
