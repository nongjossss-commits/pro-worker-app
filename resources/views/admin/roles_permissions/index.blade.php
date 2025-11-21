@extends('layouts.app')

@section('title', __('Manage Roles and Permissions'))

@section('content')
<div class="content-section">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <h2 class="mb-3 mb-md-0">
            <i class="bi bi-shield-lock-fill me-2"></i>
            {{ __('Manage Roles and Permissions') }}
        </h2>
    </div>

    <div class="row">
        {{-- Roles Column --}}
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">{{ __('Roles') }}</h5>
                </div>
                <div class="card-body">
                    @foreach ($roles as $role)
                        <div class="mb-4 pb-3 border-bottom last:border-bottom-0">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="fw-bold text-primary mb-0">{{ ucfirst(__($role->name)) }}</h5>
                                <span class="badge bg-light text-dark border">{{ $role->permissions->count() }} {{ __('permissions') }}</span>
                            </div>
                            <div class="d-flex flex-wrap gap-1">
                                @forelse ($role->permissions as $permission)
                                    {{-- Attempt to translate permission name, fallback to formatted string --}}
                                    <span class="badge bg-secondary fw-normal">{{ __($permission->name) }}</span>
                                @empty
                                    <span class="text-muted fst-italic small">{{ __('No permissions assigned.') }}</span>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Permissions Column --}}
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">{{ __('All Available Permissions') }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-1">
                        @foreach ($permissions as $permission)
                             <span class="badge bg-info text-dark fw-normal">{{ __($permission->name) }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
