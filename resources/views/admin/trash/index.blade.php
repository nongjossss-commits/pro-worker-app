@extends('layouts.app')

@section('title', 'Central Trash')

@section('header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
    <h1 class="mb-3 mb-md-0">Central Trash</h1>
    <div class="d-flex gap-2">
        {{-- Search Form --}}
        <form action="{{ route('admin.trash.index') }}" method="GET" class="d-flex gap-2">
            <input type="hidden" name="view" value="{{ $currentView }}">
            <input type="text" name="search" class="form-control" placeholder="Search in trash..." value="{{ $search ?? '' }}">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        {{-- View Toggle --}}
        <div class="btn-group">
            <a href="{{ route('admin.trash.index', array_merge(request()->query(), ['view' => 'table'])) }}" class="btn btn-outline-secondary @if($currentView === 'table') active @endif" title="Table View">
                <i class="bi bi-list-ul"></i>
            </a>
            <a href="{{ route('admin.trash.index', array_merge(request()->query(), ['view' => 'card'])) }}" class="btn btn-outline-secondary @if($currentView === 'card') active @endif" title="Card View">
                <i class="bi bi-grid-3x3-gap-fill"></i>
            </a>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid content-section">
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
                                    {{ Str::plural(ucfirst(str_replace('_', ' ', $modelName))) }} ({{ $items->count() }})
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
                                                        @else
                                                            {{ $item->employerNameTh ?? $item->name ?? $item->address_line_1 ?? 'N/A' }}
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
                                                        {{-- RESTORE BUTTON (Permission-Protected) --}}
                                                        @can('restore-' . $modelName)
                                                            {{-- FIX: Added method="POST" to prevent 405 error from Brief 17 --}}
                                                            <form action="{{ route('admin.trash.restore', ['model' => $modelName, 'id' => $item->id]) }}" method="POST" class="d-inline restore-form">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-success">Restore</button>
                                                            </form>
                                                        @endcan

                                                        {{-- FORCE DELETE BUTTON (Permission-Protected) --}}
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
