@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" x-data="preparationManager()">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="text-uppercase text-muted small fw-bold">Pre-Production Stage</div>
            <h2 class="fw-bold mb-0">{{ $production->project_name ?? 'Untitled Project' }}</h2>
            <div class="text-muted">{{ $production->employer->name_en ?? $production->employer->name_th ?? 'Independent Project' }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('production.index') }}" class="btn btn-outline-secondary">Exit</a>
            <form action="{{ route('production.update', $production->id) }}" method="POST" id="startWorkflowForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="start_workflow" value="1">
                <button type="submit" class="btn btn-success btn-lg shadow-sm"
                    :disabled="!isReadyToStart"
                    onclick="return confirm('Confirm sending this project to Workflow? This will activate tracking and confirm pending employees.');">
                    <i class="bi bi-send-check me-2"></i>Send to Workflow
                </button>
            </form>
        </div>
    </div>

    <!-- Status Dashboard -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100" :class="{'bg-success text-white': documentReady, 'bg-light': !documentReady}">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="bi bi-file-earmark-check me-2"></i>Documents Ready</h5>
                        <div class="small" :class="{'text-white-50': documentReady, 'text-muted': !documentReady}">
                            Checked by Staff / Assignee
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input fs-4" type="checkbox" role="switch"
                            id="documentReadySwitch"
                            x-model="documentReady"
                            @change="toggleStatus('document_ready')">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100" :class="{'bg-success text-white': financialApproved, 'bg-light': !financialApproved}">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="bi bi-cash-coin me-2"></i>Ready to Proceed</h5>
                        <div class="small" :class="{'text-white-50': financialApproved, 'text-muted': !financialApproved}">
                            Financial / Admin Approval
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        @can('approve-production')
                        <input class="form-check-input fs-4" type="checkbox" role="switch"
                            id="financialApprovedSwitch"
                            x-model="financialApproved"
                            @change="toggleStatus('financial_approved')">
                        @else
                        <input class="form-check-input fs-4" type="checkbox" disabled x-model="financialApproved">
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Tabs -->
    <ul class="nav nav-tabs mb-4" id="productionTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-selected="true">Project Details & Employees</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button" role="tab" aria-selected="false">Financial Management</button>
        </li>
    </ul>

    <div class="tab-content" id="productionTabsContent">
        <!-- Tab 1: Project Details & Employees -->
        <div class="tab-pane fade show active" id="details" role="tabpanel" tabindex="0">
            <div class="row">
                <!-- Left Column: Settings -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white fw-bold">Project Details</div>
                        <div class="card-body">
                            <form action="{{ route('production.update', $production->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="mb-3">
                                    <label class="form-label">Project Name</label>
                                    <input type="text" name="project_name" class="form-control" value="{{ $production->project_name }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3">{{ $production->description }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary w-100 mt-2">Save Details</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Employee Management -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0 fw-bold">Included Employees ({{ $production->items->count() }})</h5>
                            <div class="dropdown">
                                <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-plus me-1"></i> Add Employee
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('employees.import', ['production_id' => $production->id]) }}"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Import from Excel</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addExistingModal">Select Existing (DB)</a></li>
                                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addNewModal">Create New (Manual)</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body bg-light">
                            @if($production->items->isEmpty())
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-people display-4"></i>
                                    <p class="mt-2">No employees added to this project yet.</p>
                                </div>
                            @else
                                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-2 g-3">
                                    @foreach($production->items as $item)
                                        <div class="col">
                                            @php
                                                $emp = $item->employee;
                                                // Handle potential null employee if it's a "new temp" employee (stored in new_employee_data)
                                                // If $item->employee_id is null, use $item->new_employee_data
                                                if (!$emp && $item->new_employee_data) {
                                                    $data = $item->new_employee_data; // JSON cast in model? need to check
                                                    // Quick Mock for view consistency
                                                    $emp = new \App\Models\Employee(); // Dummy
                                                    $emp->id = null;
                                                    $emp->employeeNameTh = $data['name_th'] ?? '-';
                                                    $emp->employeeNameEn = '-';
                                                    $emp->employeePassport = $data['passport_no'] ?? '-';
                                                    $emp->employeeNationality = $data['nationality'] ?? '-';
                                                    $emp->status = 'pending_create';
                                                }
                                                $employerName = $emp->employer->employerNameTh ?? 'N/A';
                                            @endphp
                                            <div class="card h-100 border shadow-sm employee-card-production">
                                                <div class="card-body d-flex align-items-start">
                                                    <img src="{{ $emp->employeePhoto ? asset('storage/' . $emp->employeePhoto) : asset('images/default-profile.png') }}"
                                                         alt="Photo" class="rounded-circle me-3 border"
                                                         style="width: 50px; height: 50px; object-fit: cover;">
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <h6 class="fw-bold mb-0 text-truncate" title="{{ $emp->employeeNameTh }}">
                                                                {{ $emp->employeeNameTh ?? 'Unknown' }}
                                                            </h6>
                                                            @if($emp->id)
                                                            <button type="button" class="btn btn-link p-0 text-primary btn-preview"
                                                                data-model-type="employee"
                                                                data-model-id="{{ $emp->id }}"
                                                                title="Preview">
                                                                <i class="bi bi-search"></i>
                                                            </button>
                                                            @endif
                                                        </div>
                                                        <div class="small text-muted mb-1 text-truncate">{{ $emp->employeeNameEn ?? '' }}</div>

                                                        <div class="d-flex align-items-center small mb-1">
                                                            <i class="bi bi-pass me-1 text-secondary"></i>
                                                            <span class="me-2">{{ $emp->employeePassport ?? $emp->pinkCardNo ?? '-' }}</span>
                                                            @if($emp->employeeNationality)
                                                                @php
                                                                    $countryCode = \App\Helpers\CountryHelper::getCountryCode($emp->employeeNationality);
                                                                @endphp
                                                                @if($countryCode)
                                                                    <img src="{{ asset('images/flags/' . strtolower($countryCode) . '.png') }}" alt="{{ $countryCode }}" style="width: 16px; height: 12px;" class="me-1">
                                                                @endif
                                                                <span class="text-secondary">{{ $emp->employeeNationality }}</span>
                                                            @endif
                                                        </div>

                                                        <div class="small text-muted mb-2">
                                                            <i class="bi bi-building me-1"></i> {{ $employerName }}
                                                        </div>

                                                        <div>
                                                            @if($emp->status === 'pending_confirmation' || $emp->status === 'pending_create')
                                                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending Workflow</span>
                                                            @else
                                                                <span class="badge bg-secondary">Pending Workflow</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Financial Management -->
        <div class="tab-pane fade" id="financial" role="tabpanel" tabindex="0">
            @include('production.partials.financial-tab')
        </div>
    </div>
</div>

<!-- Modal: Add Existing -->
<div class="modal fade" id="addExistingModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('production.add_employee', $production->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Existing Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Employee</label>
                        <!-- Should be AJAX search, using simple select for draft -->
                        <select name="employee_id" class="form-select" required>
                            <option value="">-- Choose --</option>
                            @php
                                $employerEmps = \App\Models\Employee::where('employer_id', $production->employer_id)->limit(200)->get();
                            @endphp
                            @foreach($employerEmps as $emp)
                                <option value="{{ $emp->id }}">
                                    {{ $emp->fullname_th }} ({{ $emp->employeePassport }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Create New -->
<div class="modal fade" id="addNewModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('production.add_new_employee', $production->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Employee (External/Import)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <select name="title" class="form-select">
                            <option value="นาย">Mr. (นาย)</option>
                            <option value="นาง">Mrs. (นาง)</option>
                            <option value="นางสาว">Miss (นางสาว)</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Name (TH)</label>
                            <input type="text" name="name_th" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Surname (TH)</label>
                            <input type="text" name="surname_th" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Passport No.</label>
                        <input type="text" name="passport_no" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nationality</label>
                        <select name="nationality" class="form-select">
                            <option value="Myanmar">Myanmar</option>
                            <option value="Cambodia">Cambodia</option>
                            <option value="Laos">Laos</option>
                            <option value="Vietnam">Vietnam</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create & Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function preparationManager() {
        return {
            documentReady: {{ $production->document_ready_at ? 'true' : 'false' }},
            financialApproved: {{ $production->financial_approved_at ? 'true' : 'false' }},

            get isReadyToStart() {
                return this.documentReady && this.financialApproved;
            },

            toggleStatus(type) {
                // Determine new state based on current Alpine model
                let newState = type === 'document_ready' ? this.documentReady : this.financialApproved;
                const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

                if (!csrfToken) {
                    console.error("CSRF token not found");
                    alert('Security Error: CSRF Token missing. Please reload the page.');
                    return;
                }

                fetch('{{ route("production.toggle_status", $production->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        type: type,
                        status: newState
                    })
                })
                .then(async response => {
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json().then(data => {
                            if (!response.ok) {
                                throw new Error(data.message || 'Server returned error');
                            }
                            return data;
                        });
                    } else {
                        // Response is not JSON (likely HTML 500/404 page)
                        const text = await response.text();
                        console.error('Non-JSON response:', text);
                        throw new Error('Server returned invalid response (possibly 500 Error)');
                    }
                })
                .then(data => {
                    if (data.success) {
                        // Success toast (optional)
                    } else {
                        this.revertState(type, newState);
                        alert('Error: ' + (data.message || 'Update failed'));
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    this.revertState(type, newState);
                    alert('Network error: ' + error.message);
                });
            },

            revertState(type, currentState) {
                 if(type === 'document_ready') this.documentReady = !currentState;
                 if(type === 'financial_approved') this.financialApproved = !currentState;
            }
        }
    }
</script>
@endsection
