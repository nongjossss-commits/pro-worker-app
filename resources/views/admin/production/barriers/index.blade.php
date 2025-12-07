@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0 fw-bold text-gray-800">
            <i class="bi bi-shield-lock me-2"></i>Workflow Barriers (ไม้กั้น)
        </h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBarrierModal">
            <i class="bi bi-plus-lg me-1"></i>Create New Barrier
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>Sequence</th>
                            <th>Name</th>
                            <th>Color Label</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($barriers as $barrier)
                            <tr>
                                <td>{{ $barrier->sequence }}</td>
                                <td>
                                    <span class="badge bg-{{ $barrier->color }} text-wrap p-2 fs-6">
                                        {{ $barrier->name }}
                                    </span>
                                </td>
                                <td>{{ $barrier->color }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary me-1"
                                            onclick="editBarrier({{ $barrier->id }}, '{{ $barrier->name }}', '{{ $barrier->color }}', {{ $barrier->sequence }})">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <form action="{{ route('admin.production.barriers.destroy', $barrier->id) }}" method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-submit-swal"
                                            data-swal-title="Delete Barrier?"
                                            data-swal-text="This cannot be undone."
                                            data-swal-confirm-btn="Yes, delete it">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No barriers defined yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createBarrierModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.production.barriers.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Barrier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Pending Verification">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Color (Bootstrap Class)</label>
                        <select name="color" class="form-select">
                            <option value="secondary">Secondary (Grey)</option>
                            <option value="primary">Primary (Blue)</option>
                            <option value="success">Success (Green)</option>
                            <option value="warning">Warning (Yellow)</option>
                            <option value="danger">Danger (Red)</option>
                            <option value="info">Info (Cyan)</option>
                            <option value="dark">Dark (Black)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sequence Order</label>
                        <input type="number" name="sequence" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Barrier</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal (Dynamic) -->
<div class="modal fade" id="editBarrierModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editBarrierForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Barrier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        <select name="color" id="edit_color" class="form-select">
                            <option value="secondary">Secondary (Grey)</option>
                            <option value="primary">Primary (Blue)</option>
                            <option value="success">Success (Green)</option>
                            <option value="warning">Warning (Yellow)</option>
                            <option value="danger">Danger (Red)</option>
                            <option value="info">Info (Cyan)</option>
                            <option value="dark">Dark (Black)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sequence Order</label>
                        <input type="number" name="sequence" id="edit_sequence" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Barrier</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function editBarrier(id, name, color, sequence) {
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_color').value = color;
        document.getElementById('edit_sequence').value = sequence;

        // Construct action URL
        let form = document.getElementById('editBarrierForm');
        form.action = "{{ route('admin.production.barriers.index') }}/" + id;

        let modal = new bootstrap.Modal(document.getElementById('editBarrierModal'));
        modal.show();
    }
</script>
@endpush
@endsection
