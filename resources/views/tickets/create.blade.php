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

                    {{-- Display Validation Errors --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong>Whoops!</strong> There were some problems with your input.<br><br>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif


                    <form action="{{ route('tickets.store') }}" method="POST" id="createTicketForm" enctype="multipart/form-data">
                        @csrf

                        {{-- Subject Field --}}
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}" required>
                            @error('subject')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Message Body Field --}}
                        <div class="form-group mt-3">
                            <label for="body">Message</label>
                            <textarea class="form-control @error('body') is-invalid @enderror" id="body" name="body" rows="8" required>{{ old('body') }}</textarea>
                             @error('body')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Attachment Controls --}}
                        <div class="form-group mt-4">
                            <label class="form-label">Attachments</label>
                            <div class="btn-group" role="group" aria-label="Attachment options">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#attachEmployeeModal">
                                    <i class="bi bi-paperclip"></i> Attach Existing Employee (<span id="selected-count">0</span>)
                                </button>
                                <a href="{{ route('employees.create', ['source_ticket' => 'new']) }}" class="btn btn-info">
                                    <i class="bi bi-plus-circle"></i> Attach New Employee
                                </a>
                                <label class="btn btn-warning">
                                    <i class="bi bi-upload"></i> Attach File <input type="file" name="attachments[]" multiple hidden>
                                </label>
                            </div>
                        </div>


                        <!-- Hidden input for selected employee IDs -->
                        <input type="hidden" name="employee_ids[]" id="employee_ids_input">


                        {{-- Action Buttons --}}
                        <div class="form-group mt-4">
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
    const employeeSearchInput = document.getElementById('employeeSearchInput');
    const employeeItems = document.querySelectorAll('.employee-item');
    const createTicketForm = document.getElementById('createTicketForm');

    let selectedEmployeeIds = [];

    // Function to update the hidden input and count
    function updateSelection() {
        selectedEmployeeIds = Array.from(document.querySelectorAll('.employee-checkbox:checked')).map(cb => cb.value);
        selectedCountSpan.textContent = selectedEmployeeIds.length;

        // Clear existing hidden inputs for employees
        const existingInputs = createTicketForm.querySelectorAll('input[name="employee_ids[]"]');
        existingInputs.forEach(input => input.remove());

        // Add new hidden inputs for each selected ID
        selectedEmployeeIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'employee_ids[]';
            input.value = id;
            createTicketForm.appendChild(input);
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

    // Handle the "Attach Selected" button click
    document.getElementById('attach-selected-btn').addEventListener('click', function() {
        updateSelection();
    });


    // Initial update in case of old input
    updateSelection();
});
</script>
@endpush
