<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Central Trash') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    <!-- Tab Navigation -->
                    <ul class="nav nav-tabs" id="trashTabs" role="tablist">
                        @foreach ($trashedData as $modelName => $items)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $modelName }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $modelName }}-tab-pane" type="button" role="tab" aria-controls="{{ $modelName }}-tab-pane" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    {{ ucfirst($modelName) }} ({{ $items->count() }})
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="trashTabsContent">
                        @foreach ($trashedData as $modelName => $items)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $modelName }}-tab-pane" role="tabpanel" aria-labelledby="{{ $modelName }}-tab" tabindex="0">
                                <div class="p-4">
                                    @if ($items->isEmpty())
                                        <p class="text-center text-gray-500">No trashed items found for {{ ucfirst($modelName) }}.</p>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Identifier</th>
                                                        <th>Deleted At</th>
                                                        <th class="text-end">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($items as $item)
                                                        <tr id="trash-row-{{ $modelName }}-{{ $item->id }}">
                                                            <td>
                                                                {{ $item->employeeNameTh ?? $item->employerNameTh ?? $item->name ?? $item->id }}
                                                            </td>
                                                            <td>{{ $item->deleted_at->format('d/m/Y H:i:s') }}</td>
                                                            <td class="text-end">
                                                                @can('restore-' . Str::plural($modelName))
                                                                    <form action="{{ route('admin.trash.restore', ['model' => $modelName, 'id' => $item->id]) }}" method="POST" class="d-inline trash-action-form">
                                                                        @csrf
                                                                        @method('POST')
                                                                        <button type="submit" class="btn btn-success btn-sm">Restore</button>
                                                                    </form>
                                                                @endcan
                                                                @can('force-delete-' . Str::plural($modelName))
                                                                    <form action="{{ route('admin.trash.forceDelete', ['model' => $modelName, 'id' => $item->id]) }}" method="POST" class="d-inline trash-action-form">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="btn btn-danger btn-sm">Force Delete</button>
                                                                    </form>
                                                                @endcan
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.body.addEventListener('submit', function(e) {
                if (e.target.matches('.trash-action-form')) {
                    e.preventDefault();
                    const form = e.target;
                    const url = form.action;
                    const methodInput = form.querySelector('input[name="_method"]');
                    const method = methodInput ? methodInput.value : form.method;
                    const token = form.querySelector('input[name="_token"]').value;
                    const row = form.closest('tr');

                    fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                    })
                    .then(response => response.json().then(data => ({ status: response.status, body: data })))
                    .then(({ status, body }) => {
                        if (status === 200) {
                            showToast(body.success, 'success');
                            if(row) {
                                row.remove();
                                // Note: Tab count is not updated for simplicity.
                            }
                        } else {
                            const errorMessage = body.error || 'An unexpected error occurred.';
                            showToast(errorMessage, 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('A network error occurred. Please try again.', 'danger');
                    });
                }
            });
        });
    </script>
    @endpush

</x-app-layout>
