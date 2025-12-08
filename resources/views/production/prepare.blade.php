@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" x-data="preparationManager()">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="text-uppercase text-muted small fw-bold">Pre-Production Stage</div>
            <h2 class="fw-bold mb-0">{{ $production->project_name ?? 'Untitled Project' }}</h2>
            <div class="text-muted">{{ $production->employer->name_en ?? $production->employer->name_th }}</div>
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
                        <h6 class="fw-bold mt-4 mb-3 text-primary">Financial Data</h6>
                        @php $fin = $production->financial_data ?? []; @endphp
                        <div class="mb-2">
                            <label class="small text-muted">Quotation No.</label>
                            <input type="text" name="financial[quotation_no]" class="form-control form-control-sm" value="{{ $fin['quotation_no'] ?? '' }}">
                        </div>
                        <div class="mb-2">
                            <label class="small text-muted">Invoice No.</label>
                            <input type="text" name="financial[invoice_no]" class="form-control form-control-sm" value="{{ $fin['invoice_no'] ?? '' }}">
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="small text-muted">Total Amount</label>
                                <input type="number" step="0.01" name="financial[total_amount]" class="form-control form-control-sm" value="{{ $fin['total_amount'] ?? '' }}">
                            </div>
                            <div class="col-6">
                                <label class="small text-muted">Paid / Deposit</label>
                                <input type="number" step="0.01" name="financial[paid_amount]" class="form-control form-control-sm" value="{{ $fin['paid_amount'] ?? '' }}">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="small text-muted">Note</label>
                            <textarea name="financial[note]" class="form-control form-control-sm" rows="2">{{ $fin['note'] ?? '' }}</textarea>
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
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Name</th>
                                    <th>Passport / ID</th>
                                    <th>Nationality</th>
                                    <th class="text-end pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($production->items as $item)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold">{{ $item->employee->fullname_th ?? '-' }}</div>
                                            <div class="small text-muted">{{ $item->employee->fullname_en ?? '-' }}</div>
                                        </td>
                                        <td>{{ $item->employee->employeePassport ?? $item->employee->pinkCardNo ?? '-' }}</td>
                                        <td>
                                            @if($item->employee->employeeNationality)
                                                <span class="badge bg-light text-dark border">{{ $item->employee->employeeNationality }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            @if($item->employee && $item->employee->status === 'pending_confirmation')
                                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending Confirmation</span>
                                            @else
                                                <span class="badge bg-secondary">Pending Workflow</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            No employees added to this project yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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
                // Note: x-model updates before @change fires
                let newState = type === 'document_ready' ? this.documentReady : this.financialApproved;

                fetch('{{ route("production.toggle_status", $production->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        type: type,
                        status: newState
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Success toast?
                    } else {
                        // Revert on failure
                        if(type === 'document_ready') this.documentReady = !newState;
                        if(type === 'financial_approved') this.financialApproved = !newState;
                        alert('Error: ' + (data.message || 'Update failed'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert
                    if(type === 'document_ready') this.documentReady = !newState;
                    if(type === 'financial_approved') this.financialApproved = !newState;
                    alert('Network error');
                });
            }
        }
    }
</script>
@endsection
