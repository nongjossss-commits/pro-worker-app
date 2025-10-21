<x-app-layout>
    {{-- 1. HEADER SLOT --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Central Trash') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    {{-- 2. NAV TABS --}}
                    <ul class="nav nav-tabs" id="trashTabs" role="tablist">
                        @foreach($trashedData as $modelName => $items)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $modelName }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $modelName }}-pane" type="button" role="tab" aria-controls="{{ $modelName }}-pane" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    {{ ucfirst($modelName) }} ({{ $items->count() }})
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    {{-- 3. TAB CONTENT --}}
                    <div class="tab-content pt-3" id="trashTabsContent">
                        @foreach($trashedData as $modelName => $items)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $modelName }}-pane" role="tabpanel" aria-labelledby="{{ $modelName }}-tab" tabindex="0">

                                {{-- 4. EMPTY STATE --}}
                                @if($items->isEmpty())
                                    <p>No trashed items found for {{ $modelName }}.</p>

                                {{-- 5. TABLE OF ITEMS --}}
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Name / Identifier</th>
                                                    <th>Deleted At</th>
                                                    <th class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($items as $item)
                                                    <tr>
                                                        <td>
                                                            {{-- Display a relevant name field --}}
                                                            {{ $item->employeeNameTh ?? $item->employerNameTh ?? $item->name ?? $item->importerNameTh ?? $item->delegateNameTh ?? $item->address_line_1 ?? 'N/A' }}
                                                            <small class="d-block text-muted">ID: {{ $item->id }}</small>
                                                        </td>
                                                        <td>
                                                            {{-- Handle both 'deleted_at' (standard) and 'terminated_at' (for Employee, if it somehow ends up here) --}}
                                                            {{ $item->deleted_at ? $item->deleted_at->format('Y-m-d H:i') : ($item->terminated_at ? $item->terminated_at->format('Y-m-d H:i') : 'N/A') }}
                                                        </td>
                                                        <td class="text-end">

                                                            {{-- 6. SECURE RESTORE BUTTON --}}
                                                            @can('restore-' . $modelName)
                                                                <form action="{{ route('admin.trash.restore', ['model' => $modelName, 'id' => $item->id]) }}" method="POST" class="d-inline restore-form">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-sm btn-outline-success">Restore</button>
                                                                </form>
                                                            @endcan

                                                            {{-- 7. SECURE FORCE DELETE BUTTON --}}
                                                            @can('force-delete-' . $modelName)
                                                                <form action="{{ route('admin.trash.forceDelete', ['model' => $modelName, 'id' => $item->id]) }}" method="POST" class="d-inline delete-form">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                                        Force Delete
                                                                    </button>
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
                        @endforeach
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- 8. JAVASCRIPT FOR AJAX SUBMISSION --}}
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const handleFormSubmit = async (form, event) => {
                event.preventDefault();

                const isDelete = form.classList.contains('delete-form');
                // Add confirmation only for the dangerous delete action
                if (isDelete && !confirm('Are you sure you want to PERMANENTLY delete this item? This action cannot be undone.')) {
                    return;
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST', // Form method spoofing handles DELETE
                        body: new FormData(form),
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json' // We expect JSON back from our controller
                        }
                    });

                    const data = await response.json();

                    if (response.ok) {
                        // On success, remove the table row from the UI
                        form.closest('tr').remove();
                        alert(data.success || 'Action successful!'); // Show success
                    } else {
                        // On failure (e.g., 403 Forbidden from Controller)
                        alert('Error: ' + (data.error || 'An unknown error occurred.'));
                    }
                } catch (error) {
                    console.error('Submission error:', error);
                    alert('A critical error occurred while submitting the form.');
                }
            };

            // Attach listeners to all forms
            document.querySelectorAll('.restore-form').forEach(form => {
                form.addEventListener('submit', (e) => handleFormSubmit(form, e));
            });

            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', (e) => handleFormSubmit(form, e));
            });
        });
    </script>
    @endpush

</x-app-layout>
