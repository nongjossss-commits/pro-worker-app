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
            <form action="{{ route('production.update', $production->id) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="start_workflow" value="1">
                <button type="submit" class="btn btn-success btn-lg shadow-sm"
                    onclick="return confirm('Confirm sending this project to Workflow? This will activate tracking.');">
                    <i class="bi bi-send-check me-2"></i>Send to Workflow
                </button>
            </form>
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
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addExistingModal">Select Existing</a></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addNewModal">Create New (Import/Hiring)</a></li>
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
                                            <span class="badge bg-secondary">Pending Workflow</span>
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
            // Placeholder for any alpine logic
        }
    }
</script>
@endsection
