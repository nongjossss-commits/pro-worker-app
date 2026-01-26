@extends('layouts.app')

@section('content')
@php
    // Prepare data for AlpineJS
    $boardItems = $production->items->map(function($item) {
        return [
            'id' => $item->id,
            'is_new' => $item->employee_id ? false : true,
            'name' => $item->employee ? ($item->employee->fullname_th ?? $item->employee->name_th) : ($item->new_employee_data['name_th'] ?? 'New Employee'),
            'photo_url' => $item->employee ? $item->employee->avatar_url : null,
            'employer_name' => $item->employee && $item->employee->employer ? $item->employee->employer->name_th : 'Unknown',
            'steps' => $item->steps
        ];
    });
@endphp

<div class="container-fluid py-4 h-100 d-flex flex-column" x-data="workflowBoard({{ $production->id }})">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('workflow.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
                <h1 class="h4 fw-bold mb-0">{{ $production->project_name }}</h1>
                <span class="badge {{ $production->type === 'independent' ? 'bg-purple' : 'bg-primary' }}">
                    {{ ucfirst($production->type) }}
                </span>
            </div>
            <p class="text-muted small mb-0 ms-5">{{ $production->description }}</p>
        </div>
        <div class="d-flex gap-2">
            {{-- Bulk Actions Toolbar --}}
            <div class="btn-group" role="group" x-show="selectedItems.length > 0" x-transition>
                <button type="button" class="btn btn-outline-primary" @click="openBulkStepModal('text')">
                    <i class="bi bi-fonts"></i> Text
                </button>
                <button type="button" class="btn btn-outline-primary" @click="openBulkStepModal('date')">
                    <i class="bi bi-calendar"></i> Date
                </button>
                <button type="button" class="btn btn-outline-primary" @click="openBulkStepModal('file')">
                    <i class="bi bi-paperclip"></i> File
                </button>
                <button type="button" class="btn btn-outline-secondary" @click="clearSelection()">
                    Clear (<span x-text="selectedItems.length"></span>)
                </button>
            </div>

            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                <i class="bi bi-person-plus"></i> Add Employee
            </button>
        </div>
    </div>

    {{-- Summary Dashboard --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
             <div class="card bg-white border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-light rounded-circle p-3 me-3">
                        <i class="bi bi-people-fill text-primary fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0">Total Employees</h6>
                        <h3 class="fw-bold mb-0" x-text="items.length">0</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Items List (Replaces Board) --}}
    <div class="flex-grow-1 overflow-auto">
        <div class="row g-3">
            <template x-for="item in items" :key="item.id">
                <div class="col-md-4 col-lg-3">
                    <div class="card shadow-sm h-100"
                         :class="{ 'border-primary': selectedItems.includes(item.id) }"
                         @click.ctrl="toggleSelection(item.id)"
                         @click.shift="toggleSelection(item.id)">
                        <div class="card-body">
                            <div class="d-flex gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" :value="item.id" x-model="selectedItems" @click.stop>
                                </div>
                                <div class="flex-grow-1" @click="openItemDetail(item)" style="cursor: pointer;">
                                    <div class="d-flex align-items-center mb-2">
                                        <img :src="item.photo_url || '/images/default-avatar.png'" class="rounded-circle me-2" width="40" height="40">
                                        <div>
                                            <div class="fw-bold text-truncate" style="max-width: 150px;" x-text="item.name"></div>
                                            <template x-if="item.is_new">
                                                <div class="badge bg-info text-dark" style="font-size: 0.7rem;">{{ __('New Entry') }}</div>
                                            </template>
                                        </div>
                                    </div>

                                    @if($production->type === 'independent')
                                        <div class="small text-muted mb-2">
                                            <i class="bi bi-building"></i> <span x-text="item.employer_name"></span>
                                        </div>
                                    @endif

                                    <div class="d-flex gap-1 mt-2 flex-wrap">
                                        <template x-for="step in item.steps" :key="step.id">
                                            <span class="badge bg-secondary" style="font-size: 0.7rem;" x-text="step.label"></span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Modals --}}
    <!-- Bulk Step Modal -->
    <div class="modal fade" id="bulkStepModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Field') }} (<span x-text="selectedItems.length"></span>)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Field Name (Label)') }}</label>
                        <input type="text" class="form-control" x-model="bulkStepLabel" placeholder="e.g. Submission Date">
                    </div>

                    <template x-if="bulkStepType === 'text'">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Value (Optional)') }}</label>
                            <input type="text" class="form-control" x-model="bulkStepValue">
                        </div>
                    </template>
                    <template x-if="bulkStepType === 'date'">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Date Value') }}</label>
                            <input type="date" class="form-control" x-model="bulkStepValue">
                        </div>
                    </template>
                     <template x-if="bulkStepType === 'file'">
                        <div class="alert alert-info small">
                            {{ __('For files, this will create a placeholder. You can upload files individually later.') }}
                        </div>
                    </template>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" @click="submitBulkStep">{{ __('Add Field') }}</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('workflowBoard', (orderId) => ({
            items: @json($boardItems),
            selectedItems: [],
            bulkStepType: 'text',
            bulkStepLabel: '',
            bulkStepValue: '',
            csrfToken: document.querySelector('meta[name="csrf-token"]').content,

            toggleSelection(id) {
                if (this.selectedItems.includes(id)) {
                    this.selectedItems = this.selectedItems.filter(i => i !== id);
                } else {
                    this.selectedItems.push(id);
                }
            },

            clearSelection() {
                this.selectedItems = [];
            },

            openBulkStepModal(type) {
                this.bulkStepType = type;
                this.bulkStepLabel = '';
                this.bulkStepValue = '';
                new bootstrap.Modal(document.getElementById('bulkStepModal')).show();
            },

            submitBulkStep() {
                if(!this.bulkStepLabel) return;

                const payload = {
                    item_ids: this.selectedItems,
                    step_type: this.bulkStepType,
                    label: this.bulkStepLabel,
                    value: this.bulkStepValue
                };

                fetch('{{ route('workflow.api.bulk_step') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        // Reload page to reflect changes
                        window.location.reload();
                    } else {
                        alert('Failed to create fields');
                    }
                });

                bootstrap.Modal.getInstance(document.getElementById('bulkStepModal')).hide();
            },

            openItemDetail(item) {
                // Navigate to detail view
                window.location.href = `/workflow/item/${item.id}`;
            }
        }));
    });
</script>
@endsection
