@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('User Management') }}
    </h2>
@endsection

@section('title', __('User Management'))

@section('content')
<div class="container-fluid content-section">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">

                {{-- Actions & Search Bar --}}
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
                    <div class="d-flex gap-2 w-100 w-md-auto">
                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                            <i class="bi bi-person-plus-fill me-1"></i> {{ __('Create New User') }}
                        </a>
                        {{-- Finance Settings Button --}}
                        <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#financeSettingsModal">
                            <i class="bi bi-gear-fill me-1"></i> {{ __('Finance System Settings') }}
                        </button>
                    </div>

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
</div>

{{-- Finance Settings Modal --}}
<div class="modal fade" id="financeSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="financeSettingsForm" onsubmit="updateFinanceSettings(event)">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Finance System Access') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="finance-status-alert" class="alert d-none"></div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Set Global Finance Password') }}</label>
                        <input type="password" name="finance_password" class="form-control" placeholder="{{ __('Enter new password') }}" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Confirm Password') }}</label>
                        <input type="password" name="finance_password_confirmation" class="form-control" placeholder="{{ __('Confirm new password') }}" required minlength="6">
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Recovery Email') }}</label>
                        <input type="email" name="recovery_email" id="recovery_email_input" class="form-control" placeholder="admin@example.com" required>
                        <div class="form-text">{{ __('Used to reset the finance password if forgotten.') }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Settings') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('financeSettingsModal');
        modal.addEventListener('show.bs.modal', function() {
            fetch('{{ route('finance.settings.status') }}')
                .then(res => res.json())
                .then(data => {
                    const emailInput = document.getElementById('recovery_email_input');
                    if(emailInput) emailInput.value = data.recovery_email || '';

                    const alertBox = document.getElementById('finance-status-alert');
                    if(data.has_password) {
                        alertBox.className = 'alert alert-success';
                        alertBox.textContent = '{{ __('Password is currently set.') }}';
                        alertBox.classList.remove('d-none');
                    } else {
                        alertBox.className = 'alert alert-warning';
                        alertBox.textContent = '{{ __('No password set. Please configure access.') }}';
                        alertBox.classList.remove('d-none');
                    }
                });
        });
    });

    function updateFinanceSettings(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '{{ __('Saving...') }}';

        fetch('{{ route('finance.settings.update') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json().then(data => ({status: res.status, body: data})))
        .then(response => {
            if(response.status === 200) {
                Swal.fire({
                    icon: 'success',
                    title: '{{ __('Success') }}',
                    text: response.body.message
                }).then(() => {
                    bootstrap.Modal.getInstance(document.getElementById('financeSettingsModal')).hide();
                    form.reset();
                });
            } else {
                throw new Error(response.body.message || JSON.stringify(response.body.errors));
            }
        })
        .catch(err => {
            Swal.fire({
                icon: 'error',
                title: '{{ __('Error') }}',
                text: err.message
            });
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
</script>
@endpush
@endsection
