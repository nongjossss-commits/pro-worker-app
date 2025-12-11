@extends('layouts.app')

@section('title', 'Registration Resolution')

@section('content')
<div class="container-fluid">
    {{-- Top Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <h1 class="display-4 fw-bold">{{ $totalEmployees }}</h1>
                    <p class="card-text fs-5">Total Employees</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark text-white h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <h1 class="display-4 fw-bold">{{ $totalEmployers }}</h1>
                    <p class="card-text fs-5">Total Employers</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Workflow Progress (Global)</h5>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#manageStepsModal">
                            <i class="bi bi-gear"></i> Settings
                        </button>
                    </div>
                    <div class="d-flex gap-2 flex-wrap" id="global-stats-container">
                        @foreach($steps as $step)
                            <div class="border rounded p-2 text-center" style="min-width: 60px;">
                                <div class="fw-bold">{{ $step->order }}</div>
                                <span class="badge bg-success rounded-pill global-stat-badge" data-step-id="{{ $step->id }}">{{ $stepStats[$step->id] ?? 0 }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Actions Bar --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
        <h4 class="mb-0 text-primary fw-bold"><i class="bi bi-people-fill me-2"></i>Registration Resolution</h4>

        <form action="{{ route('production.registration.index') }}" method="GET" class="d-flex flex-grow-1 mx-md-4" style="max-width: 500px;">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search employee or employer..." value="{{ request('search') }}">
                <button class="btn btn-primary" type="submit">Search</button>
                @if(request('search'))
                    <a href="{{ route('production.registration.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>

        <div class="d-flex gap-2">
            <a href="{{ route('production.registration.create') }}" class="btn btn-warning text-white">
                <i class="bi bi-plus-lg"></i> Add New Employee
            </a>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-file-earmark-spreadsheet"></i> Import
            </button>
        </div>
    </div>

    {{-- Employers List --}}
    <div class="accordion" id="employersAccordion">
        @foreach($employers as $employer)
            <div class="card mb-3 border shadow-sm">
                <div class="card-header bg-white py-3" id="heading{{ $employer->id }}">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center w-100 gap-3">
                        <button class="btn btn-link text-decoration-none text-dark fw-bold p-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $employer->id }}" aria-expanded="true" aria-controls="collapse{{ $employer->id }}">
                            <span class="fs-5">{{ $employer->employerNameTh }} ({{ $employer->employerNameEn }})</span>
                        </button>

                        {{-- Employer Scoreboard --}}
                        <div class="d-flex gap-1 flex-wrap align-items-center justify-content-center employer-stats-container" id="employer-stats-{{ $employer->id }}">
                             @foreach($steps as $step)
                                <div class="border rounded px-2 py-1 text-center bg-light" style="min-width: 40px;">
                                    <small class="fw-bold d-block" style="font-size: 0.65rem;">Step {{ $step->order }}</small>
                                    <span class="badge bg-secondary rounded-pill employer-stat-badge" data-step-id="{{ $step->id }}">{{ $employer->stepStats[$step->id] ?? 0 }}</span>
                                </div>
                             @endforeach
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#financeModal-{{ $employer->id }}" onclick="event.stopPropagation()">
                                <i class="bi bi-currency-dollar"></i> Finance
                            </button>
                            <span class="badge bg-secondary">{{ $employer->employees->count() }} Employees</span>
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $employer->id }}">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="collapse{{ $employer->id }}" class="accordion-collapse collapse show" aria-labelledby="heading{{ $employer->id }}">
                    <div class="card-body bg-light">
                         <div class="employee-list">
                            @foreach($employer->employees as $employee)
                                @include('production.registration._employee_card', ['employee' => $employee, 'steps' => $steps])
                            @endforeach
                         </div>
                    </div>
                </div>
            </div>

            {{-- Finance Modal for this Employer --}}
            <div class="modal fade" id="financeModal-{{ $employer->id }}" tabindex="-1" aria-hidden="true" onclick="event.stopPropagation()">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Finance: {{ $employer->employerNameTh }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body bg-light">
                            {{-- Reuse the existing Financial Tab component --}}
                            {{-- We pass the 'Shadow' Production Order we created in the Controller --}}
                            @include('production.partials.financial-tab', ['production' => $employer->financeOrder])
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- Include Drawer --}}
@include('production.registration.partials.offcanvas_drawer')

{{-- Manage Steps Modal --}}
<div class="modal fade" id="manageStepsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Workflow Steps</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStepForm" class="mb-4">
                    <div class="input-group">
                        <input type="text" class="form-control" id="newStepName" placeholder="Enter step name (e.g., Medical Checkup)" required>
                        <button class="btn btn-primary" type="submit">Add Step</button>
                    </div>
                </form>

                <ul class="list-group" id="stepsList">
                    @foreach($steps as $step)
                        <li class="list-group-item d-flex justify-content-between align-items-center" id="step-item-{{ $step->id }}">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary rounded-pill">{{ $step->order }}</span>
                                <span class="step-name">{{ $step->name }}</span>
                                <input type="text" class="form-control form-control-sm d-none step-edit-input" value="{{ $step->name }}">
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary btn-edit-step" onclick="toggleEditStep({{ $step->id }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success d-none btn-save-step" onclick="saveStep({{ $step->id }})">
                                    <i class="bi bi-check"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteStep({{ $step->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Employees from Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('employees.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="redirect_route" value="{{ route('production.registration.index') }}">

                    <div class="mb-3">
                        <label class="form-label">Select Employer</label>
                        <select class="form-select" name="employer_id" required>
                            <option value="">-- Choose Employer --</option>
                            @foreach($employers as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->employerNameTh }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Excel File</label>
                        <input type="file" class="form-control" name="file" required accept=".xlsx, .xls, .csv">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // --- Manage Steps ---
    document.getElementById('addStepForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const name = document.getElementById('newStepName').value;

        fetch('{{ route("production.registration.steps.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ name: name })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
        });
    });

    function deleteStep(id) {
        if(!confirm('Delete this step?')) return;
        fetch(`/production/registration/steps/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
        });
    }

    function toggleEditStep(id) {
        const item = document.getElementById(`step-item-${id}`);
        item.querySelector('.step-name').classList.toggle('d-none');
        item.querySelector('.step-edit-input').classList.toggle('d-none');
        item.querySelector('.btn-edit-step').classList.toggle('d-none');
        item.querySelector('.btn-save-step').classList.toggle('d-none');
    }

    function saveStep(id) {
        const item = document.getElementById(`step-item-${id}`);
        const newName = item.querySelector('.step-edit-input').value;

        fetch(`/production/registration/steps/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ name: newName })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
        });
    }

    // --- Employee Actions ---
    function finalizeEmployee(id) {
        if(!confirm('Save this employee to the main database?')) return;
        fetch(`/production/registration/${id}/finalize`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
        });
    }

    function restoreEmployeeState(id) {
         if(!confirm('Undo save status?')) return;
        fetch(`/production/registration/${id}/restore-state`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
        });
    }

    // --- New AJAX Toggle Step with Real-time Updates ---
    function toggleStep(employeeId, stepId, completed) {
        // Optimistic UI Update (Toggle Button Style)
        const btn = document.querySelector(`button[onclick="toggleStep(${employeeId}, ${stepId}, ${!completed})"]`);
        // Note: The onclick will be updated by the server response logic if we wanted to be super precise,
        // but for now we just want to toggle visual state.
        // Actually, let's wait for server response to be safe, or just toggle class.

        fetch(`/production/registration/${employeeId}/progress`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ step_id: stepId, completed: completed })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // 1. Update Button State
                if (btn) {
                    if (completed) {
                        btn.classList.remove('btn-outline-secondary');
                        btn.classList.add('btn-success');
                        btn.innerHTML = btn.innerHTML + ' <i class="bi bi-check"></i>';
                        btn.setAttribute('onclick', `toggleStep(${employeeId}, ${stepId}, false)`);
                    } else {
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-outline-secondary');
                        const icon = btn.querySelector('i');
                        if(icon) icon.remove();
                        btn.setAttribute('onclick', `toggleStep(${employeeId}, ${stepId}, true)`);
                    }
                }

                // 2. Update Global Stats
                if (data.globalStats) {
                    const globalContainer = document.getElementById('global-stats-container');
                    for (const [sId, count] of Object.entries(data.globalStats)) {
                        const badge = globalContainer.querySelector(`.global-stat-badge[data-step-id="${sId}"]`);
                        if (badge) badge.textContent = count;
                    }
                }

                // 3. Update Employer Stats
                if (data.employerStats && data.employerId) {
                    const employerContainer = document.getElementById(`employer-stats-${data.employerId}`);
                    if (employerContainer) {
                         for (const [sId, count] of Object.entries(data.employerStats)) {
                            const badge = employerContainer.querySelector(`.employer-stat-badge[data-step-id="${sId}"]`);
                            if (badge) badge.textContent = count;
                        }
                    }
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }
</script>
@endpush
