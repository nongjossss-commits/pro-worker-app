@extends('layouts.app')

@section('title', 'Central Trash')

@section('header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
    <h1 class="mb-3 mb-md-0">Central Trash</h1>
</div>
@endsection

@section('content')
<div class="container-fluid content-section">
    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                {{-- Search Form --}}
                <form action="{{ route('admin.trash.index') }}" method="GET" class="d-flex flex-column flex-md-row gap-2">
                    <input type="hidden" name="view" value="{{ $currentView ?? 'table' }}">
                    {{-- Hidden input to preserve active tab on search --}}
                    @php
                        // Determine active tab for initial hidden input value, logic duplicated from below for availability here
                        $initRequestedTab = request('tab');
                        $initActiveTab = null;
                         if ($initRequestedTab && isset($trashedData[$initRequestedTab]) && $trashedData[$initRequestedTab]->isNotEmpty()) {
                            $initActiveTab = $initRequestedTab;
                        } else {
                            foreach ($trashedData as $key => $items) {
                                if ($items->isNotEmpty()) {
                                    $initActiveTab = $key;
                                    break;
                                }
                            }
                        }
                    @endphp
                    <input type="hidden" name="tab" id="active-tab-input" value="{{ $initActiveTab }}">
                    <input type="text" name="search" class="form-control" placeholder="Search in trash..." value="{{ $search ?? '' }}">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="{{ route('admin.trash.export', request()->query()) }}" class="btn btn-info">Export</a>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#trashSettingsModal">
                        <i class="bi bi-gear"></i>
                    </button>
                </form>

                {{-- View Toggle --}}
                <div class="btn-group">
                    <a href="{{ route('admin.trash.index', array_merge(request()->query(), ['view' => 'table'])) }}" class="btn btn-outline-secondary @if(($currentView ?? 'table') === 'table') active @endif" title="Table View">
                        <i class="bi bi-list-ul"></i>
                    </a>
                    <a href="{{ route('admin.trash.index', array_merge(request()->query(), ['view' => 'card'])) }}" class="btn btn-outline-secondary @if(($currentView ?? 'table') === 'card') active @endif" title="Card View">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                    </a>
                </div>
            </div>

            @if(collect($trashedData)->every(fn($items) => $items->isEmpty()))
                <div class="alert alert-info text-center">
                    <i class="bi bi-trash3 me-2"></i> The trash is currently empty{{ $search ? ' for your search query' : '' }}.
                </div>
            @else
                @php
                    // Determine which tab should be active.
                    // Priority:
                    // 1. Explicit 'tab' query parameter (if that tab exists and has data)
                    // 2. The first non-empty tab found

                    $requestedTab = request('tab');
                    $activeTab = null;

                    // Check if requested tab is valid and has data
                    if ($requestedTab && isset($trashedData[$requestedTab]) && $trashedData[$requestedTab]->isNotEmpty()) {
                        $activeTab = $requestedTab;
                    } else {
                        // Fallback to first non-empty tab
                        foreach ($trashedData as $key => $items) {
                            if ($items->isNotEmpty()) {
                                $activeTab = $key;
                                break;
                            }
                        }
                    }
                @endphp

                <ul class="nav nav-tabs" id="trashTabs" role="tablist">
                    @foreach($trashedData as $modelName => $items)
                        @if($items->isNotEmpty())
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $modelName === $activeTab ? 'active' : '' }}" id="{{ $modelName }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $modelName }}-pane" type="button" role="tab" aria-controls="{{ $modelName }}-pane" aria-selected="{{ $modelName === $activeTab ? 'true' : 'false' }}">
                                    {{ Str::plural(ucfirst(str_replace('_', ' ', $modelName))) }} ({{ $items->count() }})
                                </button>
                            </li>
                        @endif
                    @endforeach
                </ul>

                <div class="tab-content pt-3" id="trashTabsContent">
                    @foreach($trashedData as $modelName => $items)
                        @if($items->isNotEmpty())
                            <div class="tab-pane fade {{ $modelName === $activeTab ? 'show active' : '' }}" id="{{ $modelName }}-pane" role="tabpanel" aria-labelledby="{{ $modelName }}-tab" tabindex="0">

                                {{-- CARD VIEW --}}
                                @if($currentView === 'card')
                                    <div class="row">
                                        @foreach($items as $item)
                                            @if($modelName === 'employees')
                                                <div class="col-12 mb-1">
                                                     @include('partials._employee_card', ['employee' => $item, 'isTrashView' => true, 'showLocateButton' => false])
                                                </div>
                                            @else
                                                {{-- Fallback for other models --}}
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <div class="card h-100">
                                                        <div class="card-body">
                                                            <h5 class="card-title">{{ $item->employerNameTh ?? $item->name ?? 'Item' }}</h5>
                                                            <p class="card-text text-muted">ID: {{ $item->id }}</p>
                                                            <p class="card-text"><small>Deleted: {{ $item->deleted_at->format('d M Y') }}</small></p>
                                                        </div>
                                                        <div class="card-footer bg-transparent border-0 text-end pb-3">
                                                            @if($modelName === 'employers')
                                                                <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employer" data-model-id="{{ $item->id }}" title="พรีวิวข้อมูล"> <i class="bi bi-search"></i> </button>
                                                            @endif
                                                            @include('admin.trash._action_buttons', ['modelName' => $modelName, 'item' => $item])
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                {{-- TABLE VIEW --}}
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th style="width: 40%;">Identifier</th>
                                                @if($modelName === 'employees')
                                                    <th>Nationality</th>
                                                    <th>Employer</th>
                                                @endif
                                                <th>Deleted At</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($items as $item)
                                                <tr id="trash-item-{{ $modelName }}-{{ $item->id }}">
                                                    <td>
                                                        @if($modelName === 'employees')
                                                            <div class="d-flex align-items-center">
                                                                <img src="{{ $item->employeePhoto ? asset('storage/' . $item->employeePhoto) : asset('images/default-profile.png') }}" alt="Photo" class="rounded-circle me-3" style="width: 48px; height: 48px; object-fit: cover;">
                                                                <div>
                                                                    {{ $item->employeeTitleTh }} {{ $item->employeeNameTh }}
                                                                    <small class="d-block text-muted">{{ $item->employeeTitleEn }} {{ $item->employeeNameEn }}</small>
                                                                </div>
                                                            </div>
                                                        @elseif($modelName === 'agents')
                                                            {{ $item->agentNameEn }}
                                                            <small class="d-block text-muted">ID: {{ $item->id }}</small>
                                                        @elseif($modelName === 'importers')
                                                            {{ $item->importerNameTh }}
                                                            <small class="d-block text-muted">{{ $item->importerNameEn }}</small>
                                                        @elseif($modelName === 'delegates')
                                                            {{ $item->delegateNameTh }}
                                                            <small class="d-block text-muted">{{ $item->delegateNameEn }}</small>
                                                        @elseif($modelName === 'tickets')
                                                            {{ $item->subject }}
                                                            <small class="d-block text-muted">Ticket ID: {{ $item->id }}</small>
                                                        @else
                                                            {{ $item->employerNameTh ?? $item->name ?? $item->address_line_1 ?? 'Item ID: ' . $item->id }}
                                                            <small class="d-block text-muted">ID: {{ $item->id }}</small>
                                                        @endif
                                                    </td>
                                                    @if($modelName === 'employees')
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                @php $countryCode = App\Helpers\CountryHelper::getCountryCode($item->employeeNationality); @endphp
                                                                @if($countryCode)
                                                                    <img src="{{ asset('images/flags/' . strtolower($countryCode) . '.png') }}" alt="{{ $item->employeeNationality }}" class="me-2" style="width: 20px;">
                                                                @endif
                                                                {{ $item->employeeNationality }}
                                                            </div>
                                                        </td>
                                                        <td>{{ $item->employer->employerNameTh ?? 'N/A' }}</td>
                                                    @endif
                                                    <td>{{ $item->deleted_at->format('d M Y, H:i') }}</td>
                                                    <td class="text-end">
                                                        <div class="d-flex flex-column flex-md-row justify-content-end gap-2">
                                                            @if($modelName === 'employees')
                                                                <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employee" data-model-id="{{ $item->id }}" title="พรีวิวข้อมูล"> <i class="bi bi-search"></i> </button>
                                                            @elseif($modelName === 'employers')
                                                                 <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employer" data-model-id="{{ $item->id }}" title="พรีวิวข้อมูล"> <i class="bi bi-search"></i> </button>
                                                            @endif
                                                            {{-- RESTORE BUTTON (Permission-Protected) --}}
                                                            @if(($modelName === 'tickets' && auth()->user()->can('manage-tickets')) || auth()->user()->can('restore-' . $modelName))
                                                                {{-- FIX: Added method="POST" to prevent 405 error from Brief 17 --}}
                                                                <form action="{{ route('admin.trash.restore', ['model' => $modelName, 'id' => $item->id]) }}" method="POST" class="d-grid d-md-inline restore-form">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-sm btn-outline-success">Restore</button>
                                                                </form>
                                                            @endif

                                                            {{-- FORCE DELETE BUTTON (Permission-Protected) --}}
                                                            @if(($modelName === 'tickets' && auth()->user()->can('manage-tickets')) || auth()->user()->can('force-delete-' . $modelName))
                                                                <form action="{{ route('admin.trash.forceDelete', ['model' => $modelName, 'id' => $item->id]) }}" method="POST" class="d-grid d-md-inline delete-form">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                                        Force Delete
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

@include('admin.trash.partials._settings_modal')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const exportButton = document.querySelector('a.btn-info[href*="export"]');
    const trashTabs = document.querySelectorAll('#trashTabs .nav-link');
    const searchInput = document.querySelector('input[name="search"]');

    const updateExportLink = () => {
        const activeTab = document.querySelector('#trashTabs .nav-link.active');
        if (!activeTab || !exportButton) {
            return;
        }

        const modelName = activeTab.id.replace('-tab', '');
        const currentSearch = searchInput ? searchInput.value : '';

        // Build a fresh URL to avoid stacking query parameters
        const url = new URL(exportButton.href.split('?')[0]);
        url.searchParams.set('model', modelName);

        // Only add the search parameter if it's not empty
        if (currentSearch) {
            url.searchParams.set('search', currentSearch);
        }

        exportButton.href = url.toString();
    };

    trashTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (event) {
            updateExportLink();

            // Update the URL to include the active tab parameter
            // This ensures that if the page reloads (e.g. after a delete/restore action),
            // the user stays on the same tab.
            const activeTabId = event.target.id.replace('-tab', '');
            const url = new URL(window.location);
            url.searchParams.set('tab', activeTabId);
            window.history.replaceState({}, '', url);

            // Also update the hidden input in the search form if it exists
            const tabInput = document.getElementById('active-tab-input');
            if (tabInput) {
                tabInput.value = activeTabId;
            }
        });
    });

    // Initial setup on page load
    updateExportLink();

    const attachSweetAlert = (selector, options) => {
        document.body.addEventListener('submit', function (event) {
            if (!event.target.matches(selector)) {
                return;
            }

            event.preventDefault();
            const form = event.target;

            Swal.fire({
                title: options.title,
                text: options.text,
                icon: options.icon,
                showCancelButton: true,
                confirmButtonColor: options.confirmButtonColor || '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: options.confirmButtonText
            }).then((result) => {
                if (result.isConfirmed) {
                    submitFormAjax(form);
                }
            });
        });
    };

    const submitFormAjax = async (form) => {
        try {
            const formData = new FormData(form);
            // The HTML form has the correct method (POST), and for DELETE, it includes the @method('DELETE') directive,
            // which adds a hidden _method field. FormData picks this up automatically.
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok) {
                Swal.fire({
                    title: 'Success!',
                    text: data.success || 'Action completed successfully.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire('Error!', data.error || 'An unknown error occurred.', 'error');
            }
        } catch (error) {
            console.error('Submission error:', error);
            Swal.fire('Error!', 'A network or server error occurred.', 'error');
        }
    };

    // Attach to Restore forms
    attachSweetAlert('.restore-form', {
        title: 'Are you sure?',
        text: "This item will be restored from the trash.",
        icon: 'question',
        confirmButtonText: 'Yes, restore it!'
    });

    // Attach to Force Delete forms
    attachSweetAlert('.delete-form', {
        title: 'Are you sure?',
        text: "This action is permanent and cannot be undone!",
        icon: 'warning',
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, permanently delete it!'
    });
});
</script>
@endpush
