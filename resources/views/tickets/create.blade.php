@extends('layouts.app')

@section('title', 'Submit New Ticket')

@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <h4 class="page-title">Submit New Support Ticket</h4>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">My Tickets</a></li>
                    <li class="breadcrumb-item active">Submit New Ticket</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 header-title">New Ticket Details</h4>
                    <p class="text-muted m-b-30">Please provide as much detail as possible.</p>

                    <form action="{{ route('tickets.store') }}" method="POST" id="createTicketForm">
                        @csrf
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" value="{{ old('subject') }}" required>
                            @error('subject')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mt-3">
                            <label for="message">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="8" required>{{ old('message') }}</textarea>
                            @error('message')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Hidden input for selected employee IDs -->
                        <input type="hidden" name="employee_ids_input_v2" id="employee_ids_input_v2">
                        <input type="hidden" name="employee_ids[]" id="employee_ids_input">


                        <div class="form-group mt-4">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#attachEmployeeModal">
                                <i class="bi bi-paperclip"></i> Attach Existing Employees (<span id="selected-count">0</span>)
                            </button>
                            <button type="submit" class="btn btn-success waves-effect waves-light">Submit Ticket</button>
                            <a href="{{ route('tickets.index') }}" class="btn btn-secondary waves-effect">Cancel</a>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attach Employee Modal -->
<div class="modal fade" id="attachEmployeeModal" tabindex="-1" aria-labelledby="attachEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attachEmployeeModalLabel">Attach Employees</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="employeeSearchInput" class="form-control" placeholder="Search employees by name or ID...">
                </div>
                <div class="list-group" id="employee-list" style="max-height: 400px; overflow-y: auto;">
                    @forelse($employees as $employee)
                        <div class="list-group-item employee-item">
                            <input class="form-check-input me-1 employee-checkbox" type="checkbox" value="{{ $employee->id }}" data-employee-name="{{ $employee->employeeNameTh }}">
                            {{ $employee->employeeNameTh }} ({{ $employee->id }})
                        </div>
                    @empty
                        <p class="text-muted">No employees found.</p>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="attach-selected-btn" data-bs-dismiss="modal">Attach Selected</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
    const selectedCountSpan = document.getElementById('selected-count');
    const employeeIdsInput = document.getElementById('employee_ids_input');
    const employeeSearchInput = document.getElementById('employeeSearchInput');
    const employeeItems = document.querySelectorAll('.employee-item');

    let selectedEmployeeIds = [];

    // Function to update the hidden input and count
    function updateSelection() {
        selectedEmployeeIds = Array.from(document.querySelectorAll('.employee-checkbox:checked')).map(cb => cb.value);
        selectedCountSpan.textContent = selectedEmployeeIds.length;

        // Clear existing hidden inputs
        const form = document.getElementById('createTicketForm');
        const existingInputs = form.querySelectorAll('input[name="employee_ids[]"]');
        existingInputs.forEach(input => input.remove());

        // Add new hidden inputs for each selected ID
        selectedEmployeeIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'employee_ids[]';
            input.value = id;
            form.appendChild(input);
        });
    }

    // Event listener for checkboxes
    employeeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelection);
    });

    // Event listener for search input
    employeeSearchInput.addEventListener('keyup', function () {
        const searchTerm = this.value.toLowerCase();
        employeeItems.forEach(item => {
            const employeeName = item.textContent.toLowerCase();
            if (employeeName.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Initial update in case of old input
    updateSelection();
});
</script>
@endpush
