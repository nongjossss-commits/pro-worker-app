@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('workflow.show', $item->production_order_id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Board
        </a>
        <h1 class="h3 mb-0">Item Details</h1>
    </div>

    <div class="row">
        {{-- Employee / Item Info --}}
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    @if($item->employee)
                        <img src="{{ $item->employee->avatar_url }}" class="rounded-circle mb-3" width="100" height="100" style="object-fit: cover;">
                        <h5 class="fw-bold">{{ $item->employee->fullname_th }}</h5>
                        <p class="text-muted">{{ $item->employee->employer->name_th ?? 'Unknown Employer' }}</p>
                    @else
                        <img src="{{ asset('/images/default-avatar.png') }}" class="rounded-circle mb-3" width="100" height="100">
                        <h5 class="fw-bold">{{ $item->new_employee_data['name_th'] ?? 'New Employee' }}</h5>
                        <span class="badge bg-info text-dark">New Entry</span>
                    @endif
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Status</span>
                        <span class="badge bg-primary">{{ $item->currentBarrier->name ?? 'Pending' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Order ID</span>
                        <span>#{{ $item->order->id }}</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Steps / Fields --}}
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Progress Tracker</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStepModal">
                        <i class="bi bi-plus"></i> Add Field
                    </button>
                </div>
                <div class="card-body">
                    @if($item->steps->count() > 0)
                        <div class="timeline">
                            @foreach($item->steps as $step)
                                <div class="d-flex mb-3 border-bottom pb-3">
                                    <div class="me-3">
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            @if($step->step_type === 'text') <i class="bi bi-fonts"></i>
                                            @elseif($step->step_type === 'date') <i class="bi bi-calendar"></i>
                                            @else <i class="bi bi-paperclip"></i>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1">{{ $step->label }}</h6>
                                        <div class="text-muted small mb-1">
                                            @if($step->step_type === 'text')
                                                {{ $step->value_text ?? '-' }}
                                            @elseif($step->step_type === 'date')
                                                {{ $step->value_date ? \Carbon\Carbon::parse($step->value_date)->format('d/m/Y') : '-' }}
                                            @else
                                                <a href="#" class="text-decoration-none">View File</a>
                                            @endif
                                        </div>
                                        <small class="text-muted">Added {{ $step->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard-x display-4 mb-3 d-block"></i>
                            No fields or steps added yet.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Add Step Modal -->
    <div class="modal fade" id="addStepModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('workflow.item.step.store', $item->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Field</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="step_type" class="form-select" x-data="{ type: 'text' }" x-model="type" @change="$dispatch('type-change', type)">
                                <option value="text">Text / Note</option>
                                <option value="date">Date</option>
                                <option value="file">File Attachment</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Label</label>
                            <input type="text" name="label" class="form-control" placeholder="e.g. Visa Submission" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Value (Initial)</label>
                            <input type="text" name="value" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Field</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
