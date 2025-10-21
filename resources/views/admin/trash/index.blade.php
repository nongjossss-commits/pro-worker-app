@extends('layouts.app')

@section('title', 'Central Trash')

@section('content')
<div class="container-fluid content-section">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <h1 class="mb-3 mb-md-0">Central Trash</h1>
        <div class="d-flex gap-2">
            {{-- Search Form --}}
            <form action="{{ route('admin.trash.index') }}" method="GET" class="d-flex gap-2">
                <input type="hidden" name="view" value="{{ $currentView }}">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search in trash..." value="{{ $search ?? '' }}">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
            </form>

            {{-- View Toggle --}}
            <div class="btn-group">
                <a href="{{ route('admin.trash.index', array_merge(request()->query(), ['view' => 'table'])) }}" class="btn btn-sm btn-outline-secondary @if($currentView === 'table') active @endif" title="Table View">
                    <i class="bi bi-list-ul"></i>
                </a>
                <a href="{{ route('admin.trash.index', array_merge(request()->query(), ['view' => 'card'])) }}" class="btn btn-sm btn-outline-secondary @if($currentView === 'card') active @endif" title="Card View">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if(collect($trashedData)->every(fn($items) => $items->isEmpty()))
                <div class="alert alert-info text-center">
                    <i class="bi bi-trash3 me-2"></i> The trash is currently empty{{ $search ? ' for your search query' : '' }}.
                </div>
            @else
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

                <div class="tab-content pt-3" id="trashTabsContent">
                    @foreach($trashedData as $modelName => $items)
                        @if($items->isNotEmpty())
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $modelName }}-pane" role="tabpanel" aria-labelledby="{{ $modelName }}-tab" tabindex="0">

                                {{-- CARD VIEW --}}
                                @if($currentView === 'card')
                                    <div class="row">
                                        @foreach($items as $item)
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                @if($modelName === 'employees')
                                                    {{-- Reuse the employee card, but adapt it for trash --}}
                                                    @include('partials._employee_card', ['employee' => $item, 'isTrashView' => true])
                                                @else
                                                    {{-- A generic card for other models --}}
                                                    <div class="card h-100">
                                                        <div class="card-body">
                                                            <h5 class="card-title">{{ $item->employerNameTh ?? $item->name ?? 'Item' }}</h5>
                                                            <p class="card-text text-muted">ID: {{ $item->id }}</p>
                                                            <p class="card-text"><small>Deleted: {{ $item->deleted_at->format('d M Y') }}</small></p>
                                                        </div>
                                                        <div class="card-footer bg-transparent border-0 text-end pb-3">
                                                            @include('admin.trash._action_buttons', ['modelName' => $modelName, 'item' => $item])
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                {{-- TABLE VIEW --}}
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle">
                                        <thead>
                                            <tr>
                                                @if($modelName === 'employees')
                                                    <th style="width: 40%;">Employee</th>
                                                    <th>Employer</th>
                                                @else
                                                    <th>Identifier</th>
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
                                                                    {{ $item->employeeNameTh ?? 'N/A' }}
                                                                    <small class="d-block text-muted">{{ $item->employeePassport ?? 'No Passport' }}</small>
                                                                </div>
                                                            </div>
                                                        @else
                                                            {{ $item->employerNameTh ?? $item->name ?? $item->address_line_1 ?? 'N/A' }}
                                                            <small class="d-block text-muted">ID: {{ $item->id }}</small>
                                                        @endif
                                                    </td>
                                                    @if($modelName === 'employees')
                                                        <td>{{ $item->employer->employerNameTh ?? 'N/A' }}</td>
                                                    @endif
                                                    <td>{{ $item->deleted_at->format('d M Y, H:i') }}</td>
                                                    <td class="text-end">
                                                       @include('admin.trash._action_buttons', ['modelName' => $modelName, 'item' => $item])
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

@push('scripts')
{{-- The action handler script remains the same as it's already robust --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const handleAction = (actionUrl, config) => {
        Swal.fire(config.confirm).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = actionUrl;

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);

                if (config.method) {
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = config.method;
                    form.appendChild(methodInput);
                }

                document.body.appendChild(form);
                form.submit();
            }
        });
    };

    document.getElementById('trashTabsContent').addEventListener('click', function(event) {
        const target = event.target.closest('button, a.btn');
        if (!target || (!target.classList.contains('btn-restore') && !target.classList.contains('btn-force-delete'))) {
            return;
        }
        event.preventDefault();

        const actionUrl = target.dataset.action || target.href;

        if (target.classList.contains('btn-restore')) {
            handleAction(actionUrl, {
                method: 'POST', // Restore is a POST
                confirm: {
                    title: 'Are you sure?',
                    text: "This item will be restored.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#secondary',
                    confirmButtonText: 'Yes, restore it!'
                }
            });
        } else if (target.classList.contains('btn-force-delete')) {
            handleAction(actionUrl, {
                method: 'DELETE', // Force delete is a DELETE
                confirm: {
                    title: 'Are you absolutely sure?',
                    text: "This action is permanent and cannot be undone. All related data and files will be deleted forever.",
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
