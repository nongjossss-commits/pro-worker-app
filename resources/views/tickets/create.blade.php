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

                    {{-- V2.4-S4-Patch3: Flash Message Display --}}
                    {{-- This block now correctly displays the 'danger' message from the controller's catch block. --}}
                    @if (session('danger'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> {{ session('danger') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    {{-- This block handles standard validation errors --}}
                    @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h4 class="alert-heading">Whoops! There were some problems with your input.</h4>
                        <p>Please review the form and correct the following errors:</p>
                        <hr>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    <form action="{{ route('tickets.store') }}" method="POST" id="createTicketForm">
                        @csrf

                        {{-- V2.4-S4-Patch3: NAME ATTRIBUTE FIXES --}}
                        {{-- All 'name' attributes are now aligned with the TicketController@store validation rules. --}}

                        {{-- Subject Field --}}
                        <div class="form-group">
                            <label for="ticket_subject">Subject</label>
                            <input type="text" class="form-control @error('ticket_subject') is-invalid @enderror" id="ticket_subject" name="ticket_subject" value="{{ old('ticket_subject') }}" required>
                            @error('ticket_subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Message Body Field --}}
                        <div class="form-group mt-3">
                            <label for="message_body">Message</label>
                            <textarea class="form-control @error('message_body') is-invalid @enderror" id="message_body" name="message_body" rows="8" required>{{ old('message_body') }}</textarea>
                            @error('message_body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Attachment Controls --}}
                        <div class="form-group mt-4">
                            <label class="form-label">Attach Employees</label>
                            <div class="btn-group" role="group" aria-label="Attachment options">
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#attachEmployeeModal">
                                    <i class="bi bi-paperclip"></i> Attach Existing Employee (<span id="selected-employee-count">0</span>)
                                </button>
                                <a href="{{ route('employees.create', ['source_ticket' => 'new']) }}" class="btn btn-success">
                                    <i class="bi bi-plus-circle"></i> Create & Attach New Employee
                                </a>
                            </div>
                        </div>

                        {{-- Hidden inputs for employee IDs will be added here by JavaScript --}}
                        <div id="attached-employees-container"></div>


                        {{-- Action Buttons --}}
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary waves-effect waves-light">Submit Ticket</button>
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
                <div class="list-group" id="employee-list-modal" style="max-height: 400px; overflow-y: auto;">
                    @forelse($employees as $employee)
                        <label class="list-group-item employee-item">
                            <input class="form-check-input me-2 employee-checkbox" type="checkbox" value="{{ $employee->id }}">
                            {{ $employee->employeeNameTh }} (ID: {{ $employee->id }})
                        </label>
                    @empty
                        <p class="text-muted text-center">No active employees found.</p>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="attach-selected-employees-btn" data-bs-dismiss="modal">Confirm Selection</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const attachBtn = document.getElementById('attach-selected-employees-btn');
    const employeeSearchInput = document.getElementById('employeeSearchInput');
    const employeeItems = document.querySelectorAll('#employee-list-modal .employee-item');
    const selectedCountSpan = document.getElementById('selected-employee-count');
    const attachedEmployeesContainer = document.getElementById('attached-employees-container');

    // Function to update the hidden inputs based on checkbox state
    function updateAttachedEmployees() {
        // Clear previous hidden inputs
        attachedEmployeesContainer.innerHTML = '';

        // Get all checked checkboxes from the modal
        const checkedCheckboxes = document.querySelectorAll('#employee-list-modal .employee-checkbox:checked');

        // Update the count
        selectedCountSpan.textContent = checkedCheckboxes.length;

        // Create a new hidden input for each checked checkbox
        checkedCheckboxes.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            // V2.4-S4-Patch3: Bug C Fix - Name attribute changed to match controller validation
            input.name = 'attached_employees[]';
            input.value = checkbox.value;
            attachedEmployeesContainer.appendChild(input);
        });
    }

    // Attach event listener to the "Confirm Selection" button in the modal
    if(attachBtn) {
        attachBtn.addEventListener('click', updateAttachedEmployees);
    }

    // Live search functionality for the modal
    if(employeeSearchInput) {
        employeeSearchInput.addEventListener('keyup', function () {
            const searchTerm = this.value.toLowerCase().trim();
            employeeItems.forEach(item => {
                const employeeName = item.textContent.toLowerCase().trim();
                if (employeeName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});
</script>
@endpush
