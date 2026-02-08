<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted small">
        <i class="bi bi-info-circle me-1"></i> {{ __('Items are automatically deleted after') }}
        <span class="fw-bold text-dark">{{ $retentionDays > 0 ? $retentionDays . ' ' . __('days') : __('Never (Forever)') }}</span>.
    </div>
    <button class="btn btn-sm btn-outline-secondary" onclick="openTrashSettings()">
        <i class="bi bi-gear-fill me-1"></i> {{ __('Settings') }}
    </button>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>{{ __('Project / Employer') }}</th>
                <th>{{ __('Employee') }}</th>
                <th>{{ __('Deleted At') }}</th>
                <th class="text-end">{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr id="trash-row-{{ $item->id }}">
                    <td>
                        <div class="fw-bold">{{ $item->order->project_name ?? '-' }}</div>
                        <div class="small text-muted">{{ $item->order->employer->employerNameTh ?? '-' }}</div>
                    </td>
                    <td>
                        @if($item->employee)
                            <div>{{ $item->employee->employeeNameTh ?? $item->employee->employeeNameEn }}</div>
                            <div class="small text-muted">{{ $item->employee->employeePassport ?? '-' }}</div>
                        @else
                            <span class="text-muted">{{ __('No Employee') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="small">{{ $item->deleted_at->format('d/m/Y H:i') }}</div>
                        <div class="small text-muted">{{ $item->deleted_at->diffForHumans() }}</div>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-success me-1" onclick="restoreTrashItem({{ $item->id }})" title="{{ __('Restore') }}">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="forceDeleteTrashItem({{ $item->id }})" title="{{ __('Delete Forever') }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center py-4 text-muted">
                        <i class="bi bi-trash fs-4 d-block mb-2"></i>
                        {{ __('No deleted items found.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="d-flex justify-content-center mt-3">
        {{ $items->links() }}
    </div>
</div>

<script>
    window.openTrashSettings = function() {
        const currentDays = '{{ $retentionDays }}';

        Swal.fire({
            title: '{{ __("Trash Retention Settings") }}',
            text: '{{ __("How long should deleted items be kept before permanent deletion?") }}',
            input: 'select',
            inputOptions: {
                '7': '7 Days',
                '15': '15 Days',
                '30': '30 Days',
                '60': '60 Days',
                '90': '90 Days',
                '365': '1 Year',
                'forever': 'Forever (Never Auto-delete)'
            },
            inputValue: currentDays === '0' ? 'forever' : currentDays,
            showCancelButton: true,
            confirmButtonText: '{{ __("Save") }}',
            showLoaderOnConfirm: true,
            preConfirm: (value) => {
                return fetch('{{ route("workflow.trash.settings.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ retention_days: value === 'forever' ? 0 : value })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(response.statusText)
                    }
                    return response.json()
                })
                .catch(error => {
                    Swal.showValidationMessage(
                        `Request failed: ${error}`
                    )
                })
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: '{{ __("Saved!") }}',
                    icon: 'success'
                }).then(() => {
                    // Reload trash content
                    const modalBody = document.getElementById('trashModalBody');
                    if(modalBody) {
                        // Re-fetch current url or default
                        // We need to keep current page/search if possible, but simple reload is fine
                        const url = '{{ route("workflow.trash") }}' + window.location.search;
                        // Using window.location.search might append dashboard filters to trash fetch which is good or bad?
                        // Controller handles 'is_pre_production' param.
                        // Let's just call the open function if available or fetch
                        if(window.openTrashModal) {
                            window.openTrashModal();
                        }
                    }
                });
            }
        });
    }

    window.forceDeleteTrashItem = function(id) {
        Swal.fire({
            title: '{{ __("Delete Forever?") }}',
            text: '{{ __("You will not be able to recover this item!") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '{{ __("Yes, delete it!") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/workflow/trash/${id}/force-delete`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        document.getElementById('trash-row-' + id).remove();
                        Swal.fire(
                            '{{ __("Deleted!") }}',
                            '{{ __("Item has been permanently deleted.") }}',
                            'success'
                        )
                    }
                });
            }
        })
    }
</script>
