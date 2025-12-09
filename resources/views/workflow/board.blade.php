@extends('layouts.app')

@section('content')
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

    {{-- Board Area --}}
    <div class="flex-grow-1 overflow-auto">
        <div class="d-flex gap-3 h-100 pb-3" style="min-width: 100%;">

            {{-- Loop through Barriers (Columns) --}}
            @foreach($barriers as $barrier)
                <div class="card bg-light border-0 shadow-sm" style="min-width: 300px; max-width: 300px;">
                    <div class="card-header bg-transparent fw-bold border-0 d-flex justify-content-between">
                        <span>{{ $barrier->name }}</span>
                        <span class="badge bg-secondary rounded-pill" x-text="getItemsCount({{ $barrier->id }})">0</span>
                    </div>
                    <div class="card-body p-2 overflow-auto custom-scrollbar"
                         @dragover.prevent
                         @drop="dropItem($event, {{ $barrier->id }})">

                        {{-- New / Imported Section --}}
                        <template x-if="getItems({{ $barrier->id }}, true).length > 0">
                            <div>
                                <div class="small fw-bold text-primary mb-2 mt-2 border-bottom pb-1">New / Imported</div>
                                <template x-for="item in getItems({{ $barrier->id }}, true)" :key="item.id">
                                    <div class="card mb-2 shadow-sm employee-card-draggable"
                                         :class="{ 'border-primary': selectedItems.includes(item.id) }"
                                         draggable="true"
                                         @dragstart="dragStart($event, item)"
                                         @click.ctrl="toggleSelection(item.id)"
                                         @click.shift="toggleSelection(item.id)">

                                        <div class="card-body p-2">
                                            <div class="d-flex gap-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" :value="item.id" x-model="selectedItems" @click.stop>
                                                </div>
                                                <div class="flex-grow-1" @click="openItemDetail(item)">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <img :src="item.photo_url || '/images/default-avatar.png'" class="rounded-circle me-2" width="24" height="24">
                                                        <div class="fw-bold small text-truncate" style="max-width: 120px;" x-text="item.name"></div>
                                                    </div>
                                                    <div class="badge bg-info text-dark mb-1" style="font-size: 0.6rem;">New Entry</div>
                                                    <div class="d-flex gap-1 mt-1 flex-wrap">
                                                        <template x-for="step in item.steps" :key="step.id">
                                                            <span class="badge bg-secondary" style="font-size: 0.6rem;" x-text="step.label"></span>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Existing Section --}}
                        <template x-if="getItems({{ $barrier->id }}, false).length > 0">
                            <div>
                                <div class="small fw-bold text-secondary mb-2 mt-2 border-bottom pb-1">Existing Database</div>
                                <template x-for="item in getItems({{ $barrier->id }}, false)" :key="item.id">
                                    <div class="card mb-2 shadow-sm employee-card-draggable"
                                         :class="{ 'border-primary': selectedItems.includes(item.id) }"
                                         draggable="true"
                                         @dragstart="dragStart($event, item)"
                                         @click.ctrl="toggleSelection(item.id)"
                                         @click.shift="toggleSelection(item.id)">

                                        <div class="card-body p-2">
                                            <div class="d-flex gap-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" :value="item.id" x-model="selectedItems" @click.stop>
                                                </div>
                                                <div class="flex-grow-1" @click="openItemDetail(item)">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <img :src="item.photo_url || '/images/default-avatar.png'" class="rounded-circle me-2" width="24" height="24">
                                                        <div class="fw-bold small text-truncate" style="max-width: 120px;" x-text="item.name"></div>
                                                    </div>

                                                    @if($production->type === 'independent')
                                                        <div class="small text-muted mb-1" style="font-size: 0.75rem;">
                                                            <i class="bi bi-building"></i> <span x-text="item.employer_name"></span>
                                                        </div>
                                                    @endif

                                                    <div class="d-flex gap-1 mt-1 flex-wrap">
                                                        <template x-for="step in item.steps" :key="step.id">
                                                            <span class="badge bg-secondary" style="font-size: 0.6rem;" x-text="step.label"></span>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                    </div>
                </div>
            @endforeach

        </div>
    </div>

    {{-- Modals --}}
    <!-- Bulk Step Modal -->
    <div class="modal fade" id="bulkStepModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Field to Selected (<span x-text="selectedItems.length"></span>)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Field Name (Label)</label>
                        <input type="text" class="form-control" x-model="bulkStepLabel" placeholder="e.g. Submission Date">
                    </div>

                    <template x-if="bulkStepType === 'text'">
                        <div class="mb-3">
                            <label class="form-label">Value (Optional)</label>
                            <input type="text" class="form-control" x-model="bulkStepValue">
                        </div>
                    </template>
                    <template x-if="bulkStepType === 'date'">
                        <div class="mb-3">
                            <label class="form-label">Date Value</label>
                            <input type="date" class="form-control" x-model="bulkStepValue">
                        </div>
                    </template>
                     <template x-if="bulkStepType === 'file'">
                        <div class="alert alert-info small">
                            For files, this will create a placeholder. You can upload files individually later.
                        </div>
                    </template>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" @click="submitBulkStep">Add Field</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('workflowBoard', (orderId) => ({
            items: @json($production->items->map(function($item) {
                return [
                    'id' => $item->id,
                    'barrier_id' => $item->current_barrier_id ?? $barriers->first()->id,
                    'is_new' => $item->employee_id ? false : true,
                    'name' => $item->employee ? ($item->employee->fullname_th ?? $item->employee->name_th) : ($item->new_employee_data['name_th'] ?? 'New Employee'),
                    'photo_url' => $item->employee ? $item->employee->avatar_url : null,
                    'employer_name' => $item->employee && $item->employee->employer ? $item->employee->employer->name_th : 'Unknown',
                    'steps' => $item->steps
                ];
            })),
            selectedItems: [],
            bulkStepType: 'text',
            bulkStepLabel: '',
            bulkStepValue: '',
            csrfToken: document.querySelector('meta[name="csrf-token"]').content,

            getItems(barrierId, isNew = null) {
                let filtered = this.items.filter(i => i.barrier_id == barrierId);
                if (isNew !== null) {
                    filtered = filtered.filter(i => i.is_new === isNew);
                }
                return filtered;
            },

            getItemsCount(barrierId) {
                return this.getItems(barrierId).length;
            },

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

            dragStart(e, item) {
                e.dataTransfer.setData('item_id', item.id);
                e.dataTransfer.effectAllowed = 'move';
            },

            dropItem(e, barrierId) {
                const itemId = e.dataTransfer.getData('item_id');
                const item = this.items.find(i => i.id == itemId);
                if (item && item.barrier_id != barrierId) {
                    const originalBarrierId = item.barrier_id;
                    item.barrier_id = barrierId; // Optimistic update

                    fetch('{{ route('workflow.api.update_barrier') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify({ item_id: itemId, barrier_id: barrierId })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(!data.success) {
                            item.barrier_id = originalBarrierId; // Revert
                            alert('Failed to update status');
                        }
                    });
                }
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
                        // Reload page to reflect changes (simplest way to update nested steps)
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
