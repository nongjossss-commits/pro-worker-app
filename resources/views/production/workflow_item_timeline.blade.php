@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header with Breadcrumb-like Nav -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="text-muted small">
                <a href="{{ route('production.show', $item->production_order_id) }}" class="text-decoration-none text-muted">
                    <i class="bi bi-arrow-left me-1"></i>Back to Project
                </a>
            </div>
            <h3 class="fw-bold mt-2">
                {{ $item->employee->fullname_th ?? 'Unknown Name' }}
                <span class="fs-5 text-muted fw-normal">({{ $item->employee->employeePassport }})</span>
            </h3>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary">{{ $item->employee->employer->name_th ?? 'Unknown Employer' }}</span>
                @if($item->currentBarrier)
                    <span class="badge bg-{{ $item->currentBarrier->color }}">
                        Current Status: {{ $item->currentBarrier->name }}
                    </span>
                @else
                    <span class="badge bg-light text-dark border">No Status Set</span>
                @endif
            </div>
        </div>
        <div>
            <!-- Header Actions if needed -->
        </div>
    </div>

    <div class="row">
        <!-- Left: Timeline Feed -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Timeline & Documents</h5>
                </div>
                <div class="card-body bg-light">
                    <div class="timeline">
                        @forelse($item->steps as $step)
                            <div class="card mb-3 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <div>
                                            @if($step->step_type === 'barrier')
                                                <span class="badge bg-{{ $step->barrier->color ?? 'dark' }} mb-1">Status Update</span>
                                                <h5 class="fw-bold text-{{ $step->barrier->color ?? 'dark' }} mb-0">
                                                    <i class="bi bi-flag-fill me-2"></i>{{ $step->barrier->name }}
                                                </h5>
                                            @elseif($step->step_type === 'text')
                                                <span class="badge bg-info mb-1">Note</span>
                                                <h6 class="fw-bold mb-0">{{ $step->label ?? 'Note' }}</h6>
                                            @elseif($step->step_type === 'date')
                                                <span class="badge bg-warning text-dark mb-1">Date</span>
                                                <h6 class="fw-bold mb-0">{{ $step->label ?? 'Important Date' }}</h6>
                                            @elseif($step->step_type === 'file')
                                                <span class="badge bg-primary mb-1">File Attachment</span>
                                                <h6 class="fw-bold mb-0">{{ $step->label ?? 'Document' }}</h6>
                                            @endif
                                        </div>
                                        <div class="text-end text-muted small">
                                            <div>{{ $step->created_at->format('d/m/Y H:i') }}</div>
                                            <div>by {{ $step->creator->name ?? 'System' }}</div>
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div class="mt-2">
                                        @if($step->step_type === 'text')
                                            <p class="mb-0">{{ $step->value_text }}</p>
                                        @elseif($step->step_type === 'date')
                                            <div class="fs-5 fw-bold text-primary">
                                                <i class="bi bi-calendar-event me-2"></i>{{ $step->value_date->format('d/m/Y') }}
                                            </div>
                                            @if($step->value_text)
                                                <p class="text-muted small mt-1">{{ $step->value_text }}</p>
                                            @endif
                                        @elseif($step->step_type === 'file')
                                            <div class="d-flex align-items-center p-2 border rounded bg-white">
                                                <i class="bi bi-file-earmark-text fs-3 text-danger me-3"></i>
                                                <div class="flex-grow-1 text-truncate">
                                                    {{ $step->value_text ?? 'Attached File' }}
                                                </div>
                                                <a href="{{ Storage::url($step->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-hourglass-split fs-1 d-block mb-3"></i>
                                No activity recorded yet. Start by adding a step on the right.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Action Tools -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 20px; z-index: 100;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Update Tracking</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary text-start" data-bs-toggle="modal" data-bs-target="#addTextModal">
                            <i class="bi bi-chat-left-text me-2"></i>Add Note / Text
                        </button>
                        <button class="btn btn-outline-warning text-dark text-start" data-bs-toggle="modal" data-bs-target="#addDateModal">
                            <i class="bi bi-calendar-check me-2"></i>Add Key Date
                        </button>
                        <button class="btn btn-outline-secondary text-start" data-bs-toggle="modal" data-bs-target="#addFileModal">
                            <i class="bi bi-paperclip me-2"></i>Attach Document
                        </button>
                        <hr>
                        <button class="btn btn-success fw-bold text-start" data-bs-toggle="modal" data-bs-target="#addBarrierModal">
                            <i class="bi bi-shield-lock me-2"></i>Update Status (Barrier)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals for Actions -->

<!-- 1. Add Text Modal -->
<div class="modal fade" id="addTextModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('workflow.item.step.store', $item->id) }}" method="POST">
            @csrf
            <input type="hidden" name="step_type" value="text">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Label (Optional)</label>
                        <input type="text" name="label" class="form-control" placeholder="e.g. Note from Officer">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="value_text" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Note</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 2. Add Date Modal -->
<div class="modal fade" id="addDateModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('workflow.item.step.store', $item->id) }}" method="POST">
            @csrf
            <input type="hidden" name="step_type" value="date">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Date</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Label</label>
                        <input type="text" name="label" class="form-control" placeholder="e.g. Appointment Date, Expiry Date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Date</label>
                        <input type="date" name="value_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note (Optional)</label>
                        <input type="text" name="value_text" class="form-control" placeholder="e.g. at Ministry of Labor">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Save Date</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 3. Add File Modal -->
<div class="modal fade" id="addFileModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('workflow.item.step.store', $item->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="step_type" value="file">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Attach File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Document Type / Label</label>
                        <input type="text" name="label" class="form-control" placeholder="e.g. Receipt, Signed Form" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File</label>
                        <input type="file" name="value_file" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-secondary">Upload</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 4. Add Barrier Modal -->
<div class="modal fade" id="addBarrierModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('workflow.item.step.store', $item->id) }}" method="POST">
            @csrf
            <input type="hidden" name="step_type" value="barrier">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-success">Update Status (Place Barrier)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Setting a barrier updates the official status of this employee in the workflow.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Status</label>
                        <select name="barrier_id" class="form-select" required>
                            @php
                                $barriers = \App\Models\WorkflowBarrier::orderBy('sequence')->get();
                            @endphp
                            @foreach($barriers as $barrier)
                                <option value="{{ $barrier->id }}">{{ $barrier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note (Optional)</label>
                        <textarea name="value_text" class="form-control" rows="2" placeholder="Any remarks for this status update..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Update Status</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
