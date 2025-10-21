@extends('layouts.app')

@section('title', 'Central Trash')

@section('content')
<div class="container-fluid content-section">
    <h1 class="mb-4">Central Trash</h1>

    <div class="card">
        <div class="card-body">
            @if(empty($trashedData) || collect($trashedData)->every(fn($items) => $items->isEmpty()))
                <div class="alert alert-info text-center">
                    The trash is currently empty.
                </div>
            @else
                {{-- NAV TABS --}}
                <ul class="nav nav-tabs" id="trashTabs" role="tablist">
                    @foreach($trashedData as $modelName => $items)
                        @if($items->isNotEmpty())
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $modelName }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $modelName }}-pane" type="button" role="tab" aria-controls="{{ $modelName }}-pane" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    {{ Str::plural(ucfirst($modelName)) }} ({{ $items->count() }})
                                </button>
                            </li>
                        @endif
                    @endforeach
                </ul>

                {{-- TAB CONTENT --}}
                <div class="tab-content pt-3" id="trashTabsContent">
                    @foreach($trashedData as $modelName => $items)
                        @if($items->isNotEmpty())
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $modelName }}-pane" role="tabpanel" aria-labelledby="{{ $modelName }}-tab" tabindex="0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Identifier</th>
                                                <th>Deleted At</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($items as $item)
                                                <tr id="trash-item-{{ $modelName }}-{{ $item->id }}">
                                                    <td>
                                                        {{ $item->employeeNameTh ?? $item->employerNameTh ?? $item->name ?? $item->importerNameTh ?? $item->delegateNameTh ?? $item->address_line_1 ?? 'N/A' }}
                                                        <small class="d-block text-muted">ID: {{ $item->id }}</small>
                                                    </td>
                                                    <td>{{ $item->deleted_at->format('d M Y, H:i') }}</td>
                                                    <td class="text-end">
                                                        @can('restore-' . strtolower($modelName))
                                                            <button type="button" class="btn btn-sm btn-outline-success btn-restore"
                                                                    data-action="{{ route('admin.trash.restore', ['model' => $modelName, 'id' => $item->id]) }}">
                                                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                                                            </button>
                                                        @endcan
                                                        @can('force-delete-' . strtolower($modelName))
                                                            <button type="button" class="btn btn-sm btn-danger btn-force-delete"
                                                                    data-action="{{ route('admin.trash.forceDelete', ['model' => $modelName, 'id' => $item->id]) }}">
                                                                <i class="bi bi-trash3-fill"></i> Force Delete
                                                            </button>
                                                        @endcan
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const handleAction = (actionUrl, config) => {
        Swal.fire(config.confirm).then((result) => {
            if (result.isConfirmed) {
                fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: new FormData(config.form) // Use FormData from a dummy form
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        const row = document.querySelector(config.rowSelector);
                        if (row) {
                            row.remove();
                        }
                    } else {
                        showToast(data.message || 'An error occurred.', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('A network error occurred.', 'danger');
                });
            }
        });
    };

    document.getElementById('trashTabsContent').addEventListener('click', function(event) {
        const target = event.target.closest('button');
        if (!target) return;

        const actionUrl = target.dataset.action;
        const model = actionUrl.split('/')[3];
        const id = actionUrl.split('/')[4];

        // Create a dummy form for submission
        const form = document.createElement('form');
        const methodInput = document.createElement('input');
        methodInput.name = '_method';
        form.appendChild(methodInput);

        if (target.classList.contains('btn-restore')) {
            methodInput.value = 'POST';
            handleAction(actionUrl, {
                form: form,
                rowSelector: `#trash-item-${model}-${id}`,
                confirm: {
                    title: 'Are you sure?',
                    text: "This item will be restored.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, restore it!'
                }
            });
        } else if (target.classList.contains('btn-force-delete')) {
            methodInput.value = 'DELETE';
            handleAction(actionUrl, {
                form: form,
                rowSelector: `#trash-item-${model}-${id}`,
                confirm: {
                    title: 'Are you absolutely sure?',
                    text: "This action is permanent and cannot be undone.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, force delete it!'
                }
            });
        }
    });
});
</script>
@endpush
