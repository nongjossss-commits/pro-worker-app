@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" x-data="preparationManager()">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <div class="text-uppercase text-muted small fw-bold">Pre-Production Stage</div>
                @if($production->type === 'independent')
                    <span class="badge bg-purple text-white small" style="background-color: #6f42c1;">Independent / Mixed</span>
                @else
                    <span class="badge bg-primary small">Employer</span>
                @endif
            </div>
            <h2 class="fw-bold mb-0">{{ $production->project_name ?? 'Untitled Project' }}</h2>
            <div class="text-muted">
                @if($production->type === 'employer' && $production->employer)
                    {{ $production->employer->name_en ?? $production->employer->name_th }}
                @else
                    Various Employers
                @endif
            </div>
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
        <div class="col-lg-3 mb-4">
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
        <div class="col-lg-9">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center gap-3">
                        <h5 class="mb-0 fw-bold">Included Employees ({{ $production->items->count() }})</h5>

                        <!-- Bulk Actions -->
                        <div x-show="selectedCount > 0" x-transition class="d-flex gap-2">
                            <span class="badge bg-primary d-flex align-items-center">
                                <span x-text="selectedCount"></span>&nbsp;Selected
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulkCustomFieldModal">
                                <i class="bi bi-plus-circle me-1"></i> Add Data/Field
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="toggleSelectAll(false)">
                                Clear
                            </button>
                        </div>
                    </div>

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

                <div class="card-body p-3 bg-light">
                    <!-- Dynamic Grid of Cards -->
                    <div class="row g-3">
                        <!-- Select All Checkbox Card -->
                        <div class="col-12 mb-2 d-flex align-items-center gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAll" @change="toggleSelectAll($el.checked)">
                                <label class="form-check-label fw-bold" for="selectAll">Select All</label>
                            </div>
                        </div>

                        @forelse($production->items as $item)
                            <div class="col-md-6 col-xl-4">
                                <div class="card h-100 border-0 shadow-sm position-relative employee-card"
                                     :class="{ 'border-primary border-2': selected.includes({{ $item->id }}) }">

                                    <!-- Selection Checkbox (Absolute) -->
                                    <div class="position-absolute top-0 end-0 p-2">
                                        <input type="checkbox" class="form-check-input" value="{{ $item->id }}" x-model="selected">
                                    </div>

                                    <div class="card-body d-flex gap-3 align-items-start">
                                        <!-- Avatar -->
                                        <div class="flex-shrink-0">
                                            @if(!$item->employee_id)
                                                <div class="position-relative">
                                                    <img src="{{ $item->photo_url }}" class="rounded-circle border" width="50" height="50" style="object-fit: cover; opacity: 0.7;">
                                                    <span class="position-absolute bottom-0 end-0 bg-warning border border-light rounded-circle p-1" style="width: 12px; height: 12px;"></span>
                                                </div>
                                            @else
                                                <img src="{{ $item->photo_url }}" class="rounded-circle border" width="50" height="50" style="object-fit: cover;">
                                            @endif
                                        </div>

                                        <!-- Info -->
                                        <div class="flex-grow-1 overflow-hidden">
                                            <h6 class="mb-0 text-truncate fw-bold">{{ $item->display_name }}</h6>
                                            <div class="small text-muted">{{ $item->passport_number }}</div>

                                            <!-- Ghost Badge -->
                                            @if(!$item->employee_id)
                                                <span class="badge bg-warning text-dark ultra-small mt-1">New / Import</span>
                                            @endif

                                            <!-- Custom Fields Display (Preview) -->
                                            @if(!empty($item->custom_field_values))
                                                <div class="mt-2 border-top pt-1">
                                                    @foreach($production->custom_field_definitions ?? [] as $def)
                                                        @if(isset($item->custom_field_values[$def['key']]))
                                                            <div class="d-flex justify-content-between ultra-small">
                                                                <span class="text-muted">{{ $def['label'] }}:</span>
                                                                @if($def['type'] === 'file')
                                                                     <a href="{{ Storage::url($item->custom_field_values[$def['key']]) }}" target="_blank" class="text-primary text-decoration-none">
                                                                        <i class="bi bi-paperclip"></i> View File
                                                                     </a>
                                                                @else
                                                                    <span class="fw-bold">{{ $item->custom_field_values[$def['key']] }}</span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-5 text-muted">
                                No employees added yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Bulk Add Custom Field / Data -->
    <div class="modal fade" id="bulkCustomFieldModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('production.bulk_update_custom_field', $production->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Data to Selected (<span x-text="selectedCount"></span>)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Hidden Inputs for Selected IDs -->
                        <template x-for="id in selected">
                            <input type="hidden" name="item_ids[]" :value="id">
                        </template>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Field / Step</label>
                            <select name="field_key" class="form-select" id="fieldSelect" x-model="selectedFieldKey" @change="updateFieldType">
                                <option value="">-- Choose Field --</option>
                                @foreach($production->custom_field_definitions ?? [] as $def)
                                    <option value="{{ $def['key'] }}" data-type="{{ $def['type'] }}">{{ $def['label'] }} ({{ ucfirst($def['type']) }})</option>
                                @endforeach
                                <option value="NEW_FIELD">+ Create New Field...</option>
                            </select>
                        </div>

                        <!-- New Field Creation Section -->
                        <div x-show="selectedFieldKey === 'NEW_FIELD'" class="border p-3 rounded bg-light mb-3" x-transition>
                            <h6 class="fw-bold text-primary mb-2">Create New Field Definition</h6>
                            <div class="alert alert-info small">
                                To create a new field type (e.g. "Visa Status"), please use the "Create New Field" button.
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#createFieldDefModal">
                                Create New Field Definition Now
                            </button>
                        </div>

                        <!-- Value Input: Dynamic Type -->
                        <div class="mb-3" x-show="selectedFieldKey && selectedFieldKey !== 'NEW_FIELD'">
                            <label class="form-label">Value to Apply</label>

                            <!-- Text Input -->
                            <input type="text" name="value" class="form-control"
                                   placeholder="Enter value (e.g. Pending)"
                                   x-show="fieldType === 'text'">

                            <!-- Date Input -->
                            <input type="date" name="value_date" class="form-control"
                                   x-show="fieldType === 'date'">

                            <!-- File Input -->
                            <div x-show="fieldType === 'file'">
                                <input type="file" name="value_file" class="form-control">
                                <div class="form-text text-warning small">
                                    <i class="bi bi-exclamation-triangle"></i> Warning: This same file will be attached to ALL selected employees.
                                </div>
                            </div>

                            <div class="form-text">This value will be applied to all selected employees.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="!selectedFieldKey || selectedFieldKey === 'NEW_FIELD'">Apply Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Create Field Definition -->
    <div class="modal fade" id="createFieldDefModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('production.add_custom_field', $production->id) }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Define New Field / Step</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Field Label Name</label>
                            <input type="text" name="field_name" class="form-control" placeholder="e.g. Visa Status, Receipt Date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Data Type</label>
                            <select name="field_type" class="form-select">
                                <option value="text">Text (Status, Notes)</option>
                                <option value="date">Date</option>
                                <option value="file">File Attachment</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Field</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Reuse Existing Modals for Adding Employees -->
    @include('production.partials.modals_add_employee')

</div>

<script>
    function preparationManager() {
        return {
            selected: [],
            selectedFieldKey: '',
            fieldType: 'text',
            get selectedCount() {
                return this.selected.length;
            },
            toggleSelectAll(checked) {
                if (checked) {
                    const checkboxes = document.querySelectorAll('.employee-card input[type="checkbox"]');
                    this.selected = Array.from(checkboxes).map(cb => cb.value);
                } else {
                    this.selected = [];
                }
            },
            updateFieldType(event) {
                const select = event.target;
                const option = select.options[select.selectedIndex];
                const type = option.getAttribute('data-type');
                this.fieldType = type || 'text';
            }
        }
    }
</script>

<style>
    .ultra-small { font-size: 0.75rem; }
    .employee-card { transition: transform 0.2s; }
    .employee-card:hover { transform: translateY(-2px); }
</style>
@endsection
