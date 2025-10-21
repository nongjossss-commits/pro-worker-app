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
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Function to handle ALL SweetAlert confirmations
            const attachSweetAlert = (forms, options) => {
                forms.forEach(form => {
                    form.addEventListener('submit', function (event) {
                        // 1. Intercept the form submission
                        event.preventDefault();

                        // 2. Show SweetAlert (with Cancel button)
                        Swal.fire({
                            title: options.title,
                            text: options.text,
                            icon: options.icon,
                            showCancelButton: true, // <-- FIXES BUG A
                            confirmButtonColor: options.confirmButtonColor || '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: options.confirmButtonText
                        }).then((result) => {
                            // 3. If confirmed, submit the form via AJAX
                            if (result.isConfirmed) {
                                submitFormAjax(form);
                            }
                        });
                    });
                });
            };

            // Function to handle the AJAX (Fetch) submission
            const submitFormAjax = async (form) => {
                try {
                    const response = await fetch(form.action, {
                        method: 'POST', // Form method spoofing handles DELETE
                        body: new FormData(form),
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (response.ok) {
                        // 4. On success, show success alert AND reload page
                        Swal.fire(
                            'Success!',
                            data.success || 'Action completed.',
                            'success'
                        ).then(() => {
                            window.location.reload(); // <-- FIXES BUG B/C
                        });
                    } else {
                        Swal.fire('Error!', data.error || 'An unknown error occurred.', 'error');
                    }
                } catch (error) {
                    console.error('Submission error:', error);
                    Swal.fire('Error!', 'A critical error occurred.', 'error');
                }
            };

            // Attach to Restore forms
            attachSweetAlert(document.querySelectorAll('.restore-form'), {
                title: 'Are you sure?',
                text: "This item will be restored.",
                icon: 'warning',
                confirmButtonText: 'Yes, restore it!'
            });

            // Attach to Force Delete forms
            attachSweetAlert(document.querySelectorAll('.delete-form'), {
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            });

        });
    </script>
    @endpush
