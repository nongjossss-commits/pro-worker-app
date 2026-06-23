@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('User Management') }}
    </h2>
@endsection

@section('title', __('User Management'))

@section('content')
<x-help-button manual="user_management" title="{{ __('User Management') }}" />
<div class="container-fluid content-section">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">

                {{-- Actions & Search Bar --}}
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary w-100 w-md-auto">
                        <i class="bi bi-person-plus-fill me-1"></i> {{ __('Create New User') }}
                    </a>

                    <form action="{{ route('admin.users.index') }}" method="GET" class="d-flex gap-2 w-100 w-md-auto" style="max-width: 400px;">
                        <input type="text" name="search" class="form-control" placeholder="{{ __('Search name or email...') }}" value="{{ request('search') }}">
                        <button type="submit" class="btn btn-secondary">{{ __('Search') }}</button>
                        @if(request('search'))
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">{{ __('Clear') }}</a>
                        @endif
                    </form>
                </div>

                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                {{-- Role Tabs --}}
                <ul class="nav nav-tabs mb-3" id="userRoleTabs" role="tablist">
                    {{-- Super Admin tab — แสดงเฉพาะ user ที่เป็น super-admin (กัน admin/staff เห็นบัญชี super-admin) --}}
                    @if(auth()->user()->hasRole('super-admin'))
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ ($activeTab ?? 'admin') == 'super-admin' ? 'active' : '' }}" id="super-admin-tab" data-bs-toggle="tab" data-bs-target="#super-admin-pane" type="button" role="tab" aria-controls="super-admin-pane" aria-selected="{{ ($activeTab ?? 'admin') == 'super-admin' ? 'true' : 'false' }}">
                            <i class="bi bi-shield-fill-check me-1 text-danger"></i> {{ __('Super Admin') }}
                        </button>
                    </li>
                    @endif
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ ($activeTab ?? 'admin') == 'admin' ? 'active' : '' }}" id="admin-tab" data-bs-toggle="tab" data-bs-target="#admin-pane" type="button" role="tab" aria-controls="admin-pane" aria-selected="true">
                            <i class="bi bi-shield-lock me-1"></i> {{ __('Admin') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ ($activeTab ?? 'admin') == 'staff' ? 'active' : '' }}" id="staff-tab" data-bs-toggle="tab" data-bs-target="#staff-pane" type="button" role="tab" aria-controls="staff-pane" aria-selected="false">
                            <i class="bi bi-person-badge me-1"></i> {{ __('Staff') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ ($activeTab ?? 'admin') == 'caretaker' ? 'active' : '' }}" id="caretaker-tab" data-bs-toggle="tab" data-bs-target="#caretaker-pane" type="button" role="tab" aria-controls="caretaker-pane" aria-selected="false">
                            <i class="bi bi-person-gear me-1"></i> {{ __('Customer Care') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ ($activeTab ?? 'admin') == 'employer' ? 'active' : '' }}" id="employer-tab" data-bs-toggle="tab" data-bs-target="#employer-pane" type="button" role="tab" aria-controls="employer-pane" aria-selected="false">
                            <i class="bi bi-briefcase me-1"></i> {{ __('Employers') }}
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="userRoleTabsContent">

                    {{-- Super Admin Tab Pane — เฉพาะ super-admin --}}
                    @if(auth()->user()->hasRole('super-admin'))
                    <div class="tab-pane fade {{ ($activeTab ?? 'admin') == 'super-admin' ? 'show active' : '' }}" id="super-admin-pane" role="tabpanel" aria-labelledby="super-admin-tab">
                        @include('admin.users.partials.table', ['users' => $users->filter(fn($u) => $u->roles->contains('name', 'super-admin'))])
                    </div>
                    @endif

                    {{-- Admin Tab Pane --}}
                    <div class="tab-pane fade {{ ($activeTab ?? 'admin') == 'admin' ? 'show active' : '' }}" id="admin-pane" role="tabpanel" aria-labelledby="admin-tab">
                        @include('admin.users.partials.table', ['users' => $users->filter(fn($u) => $u->roles->contains('name', 'admin'))])
                    </div>

                    {{-- Staff Tab Pane --}}
                    <div class="tab-pane fade {{ ($activeTab ?? 'admin') == 'staff' ? 'show active' : '' }}" id="staff-pane" role="tabpanel" aria-labelledby="staff-tab">
                        @include('admin.users.partials.table', ['users' => $users->filter(fn($u) => $u->roles->contains('name', 'staff'))])
                    </div>

                    {{-- Caretaker Tab Pane (Renamed to Customer Care in UI) --}}
                    <div class="tab-pane fade {{ ($activeTab ?? 'admin') == 'caretaker' ? 'show active' : '' }}" id="caretaker-pane" role="tabpanel" aria-labelledby="caretaker-tab">
                        @include('admin.users.partials.table', ['users' => $users->filter(fn($u) => $u->roles->contains('name', 'caretaker'))])
                    </div>

                    {{-- Employer Tab Pane --}}
                    <div class="tab-pane fade {{ ($activeTab ?? 'admin') == 'employer' ? 'show active' : '' }}" id="employer-pane" role="tabpanel" aria-labelledby="employer-tab">
                        @include('admin.users.partials.table', ['users' => $users->filter(fn($u) => $u->roles->contains('name', 'employer'))])
                    </div>

                </div>

            </div>
        </div>
    </div>

    {{-- Roles & Permissions (merged from /admin/roles-permissions to keep the
         sidebar lean — this is read-only reference data, doesn't deserve its
         own menu entry). Only shown to users who can manage roles/settings. --}}
    @canany(['manage-roles', 'manage-settings'])
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-light d-flex align-items-center">
            <i class="bi bi-shield-lock-fill me-2 text-primary"></i>
            <h5 class="mb-0">{{ __('Roles and Permissions') }}</h5>
            <span class="text-muted small ms-2">— {{ __('read-only reference') }}</span>
        </div>
        <div class="card-body">
            <div class="row">
                {{-- Roles + their permissions --}}
                <div class="col-lg-8 mb-3 mb-lg-0">
                    <h6 class="text-muted small fw-bold mb-3">{{ __('Roles') }}</h6>
                    @foreach ($roles as $role)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-primary mb-0">{{ ucfirst(__($role->name)) }}</h6>
                                <span class="badge bg-light text-dark border">{{ $role->permissions->count() }} {{ __('permissions') }}</span>
                            </div>
                            <div class="d-flex flex-wrap gap-1">
                                @forelse ($role->permissions as $permission)
                                    <span class="badge bg-secondary fw-normal">{{ __($permission->name) }}</span>
                                @empty
                                    <span class="text-muted fst-italic small">{{ __('No permissions assigned.') }}</span>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- All permissions --}}
                <div class="col-lg-4">
                    <h6 class="text-muted small fw-bold mb-3">
                        {{ __('All Available Permissions') }}
                        <span class="badge bg-light text-dark border ms-1">{{ $permissions->count() }}</span>
                    </h6>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach ($permissions as $permission)
                            <span class="badge bg-info text-dark fw-normal">{{ __($permission->name) }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcanany
</div>
@endsection
