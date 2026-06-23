@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">{{ __('Super Admin Menu Settings') }}</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Resolve active tab from the URL once so every <button> + <pane> agrees.
         Without this, hard-coded `active` on tab 1 fought with `request('tab')`
         on tabs 4/5, leaving two panes shown after upload redirects. --}}
    @php $activeTab = request('tab') ?: 'menu-settings'; @endphp

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" id="superAdminSettingsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'menu-settings' ? 'active' : '' }}" id="menu-settings-tab" data-bs-toggle="tab" data-bs-target="#menu-settings" type="button" role="tab" aria-controls="menu-settings" aria-selected="{{ $activeTab === 'menu-settings' ? 'true' : 'false' }}">
                <i class="bi bi-menu-button-wide"></i> Menu Visibility & Access
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'download-profiles' ? 'active' : '' }}" id="download-profiles-tab" data-bs-toggle="tab" data-bs-target="#download-profiles" type="button" role="tab" aria-controls="download-profiles" aria-selected="{{ $activeTab === 'download-profiles' ? 'true' : 'false' }}">
                <i class="bi bi-download"></i> Download Profiles
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'attachment-settings' ? 'active' : '' }}" id="attachment-settings-tab" data-bs-toggle="tab" data-bs-target="#attachment-settings" type="button" role="tab" aria-controls="attachment-settings" aria-selected="{{ $activeTab === 'attachment-settings' ? 'true' : 'false' }}">
                <i class="bi bi-file-earmark-arrow-up"></i> Attachment Files Settings
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'employee-cap' ? 'active' : '' }}" id="employee-cap-tab" data-bs-toggle="tab" data-bs-target="#employee-cap" type="button" role="tab" aria-controls="employee-cap" aria-selected="{{ $activeTab === 'employee-cap' ? 'true' : 'false' }}">
                <i class="bi bi-people-fill"></i> {{ __('Employee Cap') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'branding' ? 'active' : '' }}" id="branding-tab" data-bs-toggle="tab" data-bs-target="#branding" type="button" role="tab" aria-controls="branding" aria-selected="{{ $activeTab === 'branding' ? 'true' : 'false' }}">
                <i class="bi bi-palette-fill"></i> {{ __('Branding') }}
            </button>
        </li>
    </ul>

    <div class="tab-content" id="superAdminSettingsTabContent">
        <!-- Tab 1: Menu Visibility & Access -->
        <div class="tab-pane fade {{ $activeTab === 'menu-settings' ? 'show active' : '' }}" id="menu-settings" role="tabpanel" aria-labelledby="menu-settings-tab">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Menu Name</th>
                            <th>Key</th>
                            <th class="text-center">Visibility</th>
                            <th class="text-center">Access Password</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($menus as $key => $label)
                            @php
                                $setting = $settings[$key] ?? null;
                                $isVisible = $setting ? $setting->is_visible : true; // Default visible
                                $hasPassword = $setting && !empty($setting->access_password);
                            @endphp
                            <tr>
                                <td class="fw-bold">{{ $label }}</td>
                                <td class="text-muted small"><code>{{ $key }}</code></td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               id="toggle-{{ $key }}"
                                               {{ $isVisible ? 'checked' : '' }}
                                               onchange="toggleVisibility('{{ $key }}', this.checked)">
                                        <label class="form-check-label ms-2" for="toggle-{{ $key }}">
                                            <span class="badge {{ $isVisible ? 'bg-success' : 'bg-danger' }}" id="badge-{{ $key }}">
                                                {{ $isVisible ? 'Visible' : 'Hidden' }}
                                            </span>
                                        </label>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($hasPassword)
                                        <span class="badge bg-warning text-dark"><i class="bi bi-lock-fill me-1"></i> Protected</span>
                                    @else
                                        <span class="badge bg-light text-dark border"><i class="bi bi-unlock me-1"></i> None</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editMenuModal-{{ $key }}">
                                        <i class="bi bi-gear-fill"></i> Configure
                                    </button>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editMenuModal-{{ $key }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <form action="{{ route('super-admin.settings.update') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="key" value="{{ $key }}">

                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Configure: {{ $label }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">

                                                <!-- Password Setting -->
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Access Password (Optional)</label>
                                                    <div class="input-group mb-2">
                                                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                                                        <input type="password" class="form-control" name="password" placeholder="Set new password">
                                                    </div>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                                        <input type="password" class="form-control" name="password_confirmation" placeholder="Confirm password">
                                                    </div>
                                                    <div class="form-text">Leave blank to keep existing password (if any).</div>
                                                </div>

                                                @if($hasPassword)
                                                <div class="mb-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="remove_password" value="1" id="removePassword-{{ $key }}">
                                                        <label class="form-check-label text-danger" for="removePassword-{{ $key }}">
                                                            Remove current password protection
                                                        </label>
                                                    </div>
                                                </div>
                                                @endif

                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Download Profiles -->
        <div class="tab-pane fade {{ $activeTab === 'download-profiles' ? 'show active' : '' }}" id="download-profiles" role="tabpanel" aria-labelledby="download-profiles-tab">
            <div class="d-flex justify-content-end mb-3">
                <a href="{{ route('super-admin.download-profiles.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Create New Profile
                </a>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Logo</th>
                                    <th>Company/Office Name</th>
                                    <th>Phone Number</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($profiles as $profile)
                                    <tr>
                                        <td>
                                            @if($profile->logo_path)
                                                <img src="{{ Storage::url($profile->logo_path) }}" alt="Logo" style="height: 40px; max-width: 100px; object-fit: contain;">
                                            @else
                                                <span class="text-muted small">No Logo</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold">{{ $profile->name }}</td>
                                        <td>{{ $profile->phone_number ?: '-' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('super-admin.download-profiles.edit', $profile->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil-fill"></i> Edit
                                            </a>
                                            <form id="delete-profile-form-{{ $profile->id }}" action="{{ route('super-admin.download-profiles.destroy', $profile->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteProfile({{ $profile->id }})">
                                                    <i class="bi bi-trash-fill"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No download profiles found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Attachment Files Settings -->
        <div class="tab-pane fade {{ $activeTab === 'attachment-settings' ? 'show active' : '' }}" id="attachment-settings" role="tabpanel" aria-labelledby="attachment-settings-tab">

            <div class="row">
                <!-- Swap Files Section -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100 border-warning">
                        <div class="card-header bg-warning text-dark fw-bold">
                            <i class="bi bi-arrow-left-right"></i> Swap Attachment Files (One-Time Operation)
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info py-2 small">
                                This will physically swap the uploaded files for all existing users in the system. <br>
                                <strong>Note:</strong> This is a one-time operation. It does not affect newly created users.
                            </div>

                            <form id="mass-swap-form" action="{{ route('super-admin.attachments.swap') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Entity Type</label>
                                    <select class="form-select" name="entity_type" id="swapEntityType" required onchange="updateSwapFields()">
                                        <option value="employee">Employee</option>
                                        <option value="employer">Employer</option>
                                    </select>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">From Field</label>
                                        <select class="form-select" name="from_field" id="swapFromField" required>
                                            <!-- Populated by JS -->
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">To Field</label>
                                        <select class="form-select" name="to_field" id="swapToField" required>
                                            <!-- Populated by JS -->
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold">Swap Behavior</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mode" id="modeSwap" value="swap" checked>
                                        <label class="form-check-label" for="modeSwap">
                                            <strong>Swap Both:</strong> File A goes to B, File B goes to A. (Keeps both files)
                                        </label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="radio" name="mode" id="modeMoveDelete" value="move_delete">
                                        <label class="form-check-label text-danger" for="modeMoveDelete">
                                            <strong>Move & Delete:</strong> File A moves to B. The old file in B is PERMANENTLY DELETED. Field A becomes empty.
                                        </label>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="button" class="btn btn-warning fw-bold" onclick="confirmMassSwap()">Execute Mass Swap</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Update Descriptions Section -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100 border-primary">
                        <div class="card-header bg-primary text-white fw-bold">
                            <i class="bi bi-fonts"></i> Default Descriptions for "Other Documents"
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info py-2 small mb-3">
                                <strong>{{ __('แก้ทีละช่อง:') }}</strong> {{ __('พิมพ์รายละเอียดของช่องนั้น แล้วกดปุ่ม Update — ระบบจะอัพเดตทุก record ที่มีอยู่ + ตั้งเป็น default ให้ record ใหม่ที่จะเพิ่มเข้ามา') }}
                                <br>
                                <span class="text-muted">{{ __('Record เก่าจะไม่ถูกอัพเดทอัตโนมัติซ้ำ — Super Admin ต้องกดปุ่มที่นี่อีกครั้งเพื่อ force-update') }}</span>
                            </div>

                            <h6 class="fw-bold mt-2 text-secondary"><i class="bi bi-building me-1"></i>Employer - Other Documents</h6>
                            @for($i = 1; $i <= 3; $i++)
                                @php
                                    $key = "employer_other_{$i}_desc";
                                    $currentValue = $settings[$key]->value ?? '';
                                @endphp
                                <div class="mb-2" x-data="otherDocSlotEditor({ entity: 'employer', slot: {{ $i }}, initial: {{ json_encode($currentValue) }} })">
                                    <label class="form-label small mb-1">Other Doc {{ $i }}</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" x-model="value" placeholder="Default description (เช่น 'ใบเปลี่ยนนายจ้าง')" :disabled="saving">
                                        <button type="button" class="btn btn-primary" @click="save()" :disabled="saving || !dirty">
                                            <span x-show="!saving"><i class="bi bi-cloud-arrow-up me-1"></i>{{ __('Update') }}</span>
                                            <span x-show="saving" style="display: none;"><span class="spinner-border spinner-border-sm"></span></span>
                                        </button>
                                    </div>
                                    <div class="form-text small text-success" x-show="lastSaved" style="display: none;" x-text="lastSaved"></div>
                                </div>
                            @endfor

                            <hr>

                            <h6 class="fw-bold mt-3 text-secondary"><i class="bi bi-person me-1"></i>Employee - Other Documents</h6>
                            <div class="row g-2">
                                @for($i = 1; $i <= 10; $i++)
                                    @php
                                        $key = "employee_other_{$i}_desc";
                                        $currentValue = $settings[$key]->value ?? '';
                                    @endphp
                                    <div class="col-md-6" x-data="otherDocSlotEditor({ entity: 'employee', slot: {{ $i }}, initial: {{ json_encode($currentValue) }} })">
                                        <label class="form-label small mb-1">Other Doc {{ $i }} <span class="text-muted">(Slot {{ $i + 8 }})</span></label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" x-model="value" placeholder="Default description" :disabled="saving">
                                            <button type="button" class="btn btn-primary" @click="save()" :disabled="saving || !dirty" :title="dirty ? '{{ __('Save & apply to all') }}' : '{{ __('No changes') }}'">
                                                <span x-show="!saving"><i class="bi bi-cloud-arrow-up"></i></span>
                                                <span x-show="saving" style="display: none;"><span class="spinner-border spinner-border-sm"></span></span>
                                            </button>
                                        </div>
                                        <div class="form-text small text-success" x-show="lastSaved" style="display: none;" x-text="lastSaved"></div>
                                    </div>
                                @endfor
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Tab 4: Employee Cap (system-wide max) --}}
        <div class="tab-pane fade {{ $activeTab === 'employee-cap' ? 'show active' : '' }}" id="employee-cap" role="tabpanel" aria-labelledby="employee-cap-tab">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-people-fill me-2"></i> {{ __('Maximum Active Employees') }}
                    </h5>
                    <p class="text-muted small mb-3">
                        {{ __('Caps how many active employees the whole system can hold. Once reached, new employee saves are blocked until an existing employee is removed or this cap is raised. Leave blank or set 0 for unlimited.') }}
                    </p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 bg-light text-center">
                                <div class="text-muted small">{{ __('Current Active Employees') }}</div>
                                <div class="display-6 fw-bold text-primary">{{ number_format($currentEmployees) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 bg-light text-center">
                                <div class="text-muted small">{{ __('Current Cap') }}</div>
                                <div class="display-6 fw-bold {{ $maxEmployees ? 'text-dark' : 'text-success' }}">
                                    {{ $maxEmployees ? number_format($maxEmployees) : '∞' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center {{ $maxEmployees && $currentEmployees >= $maxEmployees ? 'bg-danger-subtle' : 'bg-light' }}">
                                <div class="text-muted small">{{ __('Remaining Slots') }}</div>
                                <div class="display-6 fw-bold {{ $maxEmployees && $currentEmployees >= $maxEmployees ? 'text-danger' : 'text-success' }}">
                                    {{ $maxEmployees ? number_format(max(0, $maxEmployees - $currentEmployees)) : '∞' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('super-admin.settings.max-employees') }}" method="POST" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('Max Employees') }}</label>
                            <input type="number"
                                   name="max_employees"
                                   class="form-control form-control-lg"
                                   min="0"
                                   max="1000000"
                                   placeholder="{{ __('Blank or 0 = unlimited') }}"
                                   value="{{ $maxEmployees }}">
                        </div>
                        <div class="col-md-6 d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save-fill me-1"></i> {{ __('Save Cap') }}
                            </button>
                        </div>
                    </form>

                    @if($maxEmployees && $currentEmployees >= $maxEmployees)
                        <div class="alert alert-danger mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            {{ __('Cap reached — new employee saves are currently blocked across the whole system.') }}
                        </div>
                    @elseif($maxEmployees && ($maxEmployees - $currentEmployees) <= max(5, intdiv($maxEmployees, 20)))
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="bi bi-exclamation-circle-fill me-1"></i>
                            {{ __('Approaching the cap — only :n slots left.', ['n' => $maxEmployees - $currentEmployees]) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tab 5: Branding (app name + logo + theme colors + manual export) --}}
        <div class="tab-pane fade {{ $activeTab === 'branding' ? 'show active' : '' }}" id="branding" role="tabpanel" aria-labelledby="branding-tab">

            {{-- Download All Manuals — training booklet branded with this installation's logo + name --}}
            <div class="card mb-3 border-primary">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <h5 class="card-title mb-1">
                            <i class="bi bi-book-half me-2 text-primary"></i> {{ __('Download User Manuals (All-in-One PDF)') }}
                        </h5>
                        <p class="text-muted small mb-0">
                            {{ __('Opens every menu manual on one printable page — use the browser print dialog to save as PDF for training new staff.') }}
                        </p>
                    </div>
                    <a href="{{ route('super-admin.manuals.bundle') }}" target="_blank" class="btn btn-primary">
                        <i class="bi bi-download me-1"></i> {{ __('Open Manual Bundle') }}
                    </a>
                </div>
            </div>

            {{-- App Name --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-tag-fill me-2"></i> {{ __('App Name') }}
                    </h5>
                    <p class="text-muted small mb-3">
                        {{ __('Shown next to the logo in the sidebar and on welcome screens. Short name is optional — used in cramped spaces; if blank, the full name is used.') }}
                    </p>
                    <form action="{{ route('super-admin.brand.name.update') }}" method="POST" class="row g-3">
                        @csrf
                        <div class="col-md-7">
                            <label class="form-label small fw-bold mb-1">{{ __('Full name') }} *</label>
                            <input type="text" name="app_name" class="form-control" maxlength="100"
                                   value="{{ $brand['app_name'] }}" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold mb-1">{{ __('Short name (optional)') }}</label>
                            <input type="text" name="short_name" class="form-control" maxlength="60"
                                   value="{{ $brand['short_name'] !== $brand['app_name'] ? $brand['short_name'] : '' }}"
                                   placeholder="{{ __('Defaults to full name') }}">
                        </div>
                        <div class="col-12 d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save-fill me-1"></i> {{ __('Save Name') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3">

                {{-- Logo Manager --}}
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-image-fill me-2"></i> {{ __('App Logo') }}
                            </h5>
                            <p class="text-muted small mb-3">
                                {{ __('Upload one or more logos and pick which one appears in the sidebar. Recommended: square or wide ratio, PNG/JPG/SVG up to 2 MB.') }}
                            </p>

                            {{-- Upload form --}}
                            <form action="{{ route('super-admin.brand.logo.upload') }}" method="POST" enctype="multipart/form-data" class="d-flex gap-2 align-items-end mb-3 border rounded p-2 bg-light">
                                @csrf
                                <div class="flex-grow-1">
                                    <label class="form-label small fw-bold mb-1">{{ __('Upload new logo') }}</label>
                                    <input type="file" name="logo" class="form-control form-control-sm" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-upload"></i> {{ __('Upload') }}
                                </button>
                            </form>

                            {{-- Existing logos grid --}}
                            @if(empty($brand['logos']))
                                <div class="text-muted small text-center p-4 border rounded">
                                    {{ __('No logos uploaded yet. The app is using the default logo.') }}
                                </div>
                            @else
                                <div class="row g-2">
                                    @foreach($brand['logos'] as $logo)
                                        @php $isActive = ($brand['active_logo'] ?? null) === $logo['path']; @endphp
                                        <div class="col-md-4 col-sm-6">
                                            <div class="border rounded p-2 h-100 d-flex flex-column {{ $isActive ? 'border-success border-2' : '' }}">
                                                <div class="text-center bg-light rounded mb-2 d-flex align-items-center justify-content-center" style="height: 110px;">
                                                    <img src="{{ asset('storage/' . $logo['path']) }}" alt="Logo" style="max-height: 100px; max-width: 100%;">
                                                </div>
                                                @if($isActive)
                                                    <span class="badge bg-success mb-2 align-self-start">
                                                        <i class="bi bi-check-circle-fill"></i> {{ __('Active') }}
                                                    </span>
                                                @endif
                                                <div class="d-flex gap-1 mt-auto">
                                                    @unless($isActive)
                                                        <form action="{{ route('super-admin.brand.logo.active') }}" method="POST" class="flex-grow-1">
                                                            @csrf
                                                            <input type="hidden" name="path" value="{{ $logo['path'] }}">
                                                            <button type="submit" class="btn btn-sm btn-outline-success w-100" title="{{ __('Use this logo') }}">
                                                                <i class="bi bi-check-lg"></i> {{ __('Use') }}
                                                            </button>
                                                        </form>
                                                    @endunless
                                                    <form action="{{ route('super-admin.brand.logo.delete') }}" method="POST" onsubmit="return confirm('{{ __('Delete this logo?') }}')">
                                                        @csrf
                                                        <input type="hidden" name="path" value="{{ $logo['path'] }}">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Theme Colors --}}
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-palette-fill me-2"></i> {{ __('Theme Colors') }}
                            </h5>
                            <p class="text-muted small mb-3">
                                {{ __('Pick the three colors that define this installation\'s look. Reload the page after saving to see the change everywhere.') }}
                            </p>

                            <form action="{{ route('super-admin.brand.colors.update') }}" method="POST" x-data="brandColorForm({
                                primary: '{{ $brand['primary_color'] }}',
                                sidebar: '{{ $brand['sidebar_color'] }}',
                                accent:  '{{ $brand['accent_color'] }}'
                            })">
                                @csrf

                                {{-- Primary --}}
                                <div class="mb-3">
                                    <label class="form-label small fw-bold mb-1">{{ __('Primary color') }}</label>
                                    <div class="text-muted small mb-1">{{ __('Buttons, links, badges, active highlights.') }}</div>
                                    <div class="input-group input-group-sm">
                                        <input type="color" class="form-control form-control-color" x-model="primary" style="max-width: 60px;">
                                        <input type="text" class="form-control" name="primary_color" x-model="primary" pattern="^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$" required>
                                    </div>
                                </div>

                                {{-- Sidebar --}}
                                <div class="mb-3">
                                    <label class="form-label small fw-bold mb-1">{{ __('Sidebar background') }}</label>
                                    <div class="text-muted small mb-1">{{ __('Background color of the left menu drawer.') }}</div>
                                    <div class="input-group input-group-sm">
                                        <input type="color" class="form-control form-control-color" x-model="sidebar" style="max-width: 60px;">
                                        <input type="text" class="form-control" name="sidebar_color" x-model="sidebar" pattern="^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$" required>
                                    </div>
                                </div>

                                {{-- Accent --}}
                                <div class="mb-3">
                                    <label class="form-label small fw-bold mb-1">{{ __('Accent color') }}</label>
                                    <div class="text-muted small mb-1">{{ __('Lighter highlight — hovers, focus rings, soft backgrounds.') }}</div>
                                    <div class="input-group input-group-sm">
                                        <input type="color" class="form-control form-control-color" x-model="accent" style="max-width: 60px;">
                                        <input type="text" class="form-control" name="accent_color" x-model="accent" pattern="^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$" required>
                                    </div>
                                </div>

                                {{-- Preview --}}
                                <div class="border rounded p-3 mb-3" :style="`background:${sidebar};`">
                                    <div class="text-muted small mb-2">{{ __('Preview') }}</div>
                                    <button type="button" class="btn btn-sm me-1" :style="`background:${primary}; color:#fff; border-color:${primary};`">{{ __('Primary button') }}</button>
                                    <span class="badge" :style="`background:${accent}; color:#fff;`">{{ __('Accent') }}</span>
                                </div>

                                {{-- Preset palettes --}}
                                <div class="mb-3">
                                    <div class="small fw-bold mb-1">{{ __('Quick presets') }}</div>
                                    <div class="d-flex gap-1 flex-wrap">
                                        @foreach([
                                            ['Orange (default)', '#F97316', '#FFFFFF', '#FB923C'],
                                            ['Blue', '#2563EB', '#FFFFFF', '#60A5FA'],
                                            ['Green', '#16A34A', '#FFFFFF', '#4ADE80'],
                                            ['Purple', '#7C3AED', '#FFFFFF', '#A78BFA'],
                                            ['Slate Dark', '#0F172A', '#1E293B', '#38BDF8'],
                                            ['Crimson', '#DC2626', '#FFFFFF', '#F87171'],
                                        ] as $p)
                                            <button type="button" class="btn btn-sm border" :style="`background:${'{{ $p[1] }}'}; color:#fff;`"
                                                    @click="primary='{{ $p[1] }}'; sidebar='{{ $p[2] }}'; accent='{{ $p[3] }}'">
                                                {{ $p[0] }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-grow-1">
                                        <i class="bi bi-save-fill me-1"></i> {{ __('Save Colors') }}
                                    </button>
                                </div>
                            </form>

                            <form action="{{ route('super-admin.brand.colors.reset') }}" method="POST" class="mt-2"
                                  onsubmit="return confirm('{{ __('Reset all theme colors to factory defaults?') }}')">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('Reset to defaults') }}
                                </button>
                            </form>

                            <div class="alert alert-info small mt-3 mb-0">
                                <i class="bi bi-info-circle"></i>
                                {{ __('Tip: press Ctrl+Shift+R to hard-refresh the browser if colors don\'t update right away.') }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
    function brandColorForm(initial) {
        return {
            primary: initial.primary || '#F97316',
            sidebar: initial.sidebar || '#FFFFFF',
            accent:  initial.accent  || '#FB923C',
        };
    }
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab) {
            const tabButton = document.getElementById(tab + '-tab');
            if (tabButton) {
                const bootstrapTab = new bootstrap.Tab(tabButton);
                bootstrapTab.show();
            }
        }
    });

    function toggleVisibility(key, isVisible) {
        // Optimistic UI Update
        const badge = document.getElementById('badge-' + key);
        if (isVisible) {
            badge.classList.remove('bg-danger');
            badge.classList.add('bg-success');
            badge.textContent = 'Visible';
        } else {
            badge.classList.remove('bg-success');
            badge.classList.add('bg-danger');
            badge.textContent = 'Hidden';
        }

        fetch('{{ route('super-admin.settings.update-visibility') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ key: key, is_visible: isVisible })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Update Sidebar
                reloadSidebar();
                showToast('Visibility updated successfully', 'success');
            } else {
                showToast('Update failed', 'danger');
                // Revert checkbox and badge if failed
                document.getElementById('toggle-' + key).checked = !isVisible;
                if (!isVisible) { // we tried to hide, so revert to visible
                    badge.classList.remove('bg-danger');
                    badge.classList.add('bg-success');
                    badge.textContent = 'Visible';
                } else {
                    badge.classList.remove('bg-success');
                    badge.classList.add('bg-danger');
                    badge.textContent = 'Hidden';
                }
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Network error', 'danger');
             // Revert checkbox
             document.getElementById('toggle-' + key).checked = !isVisible;
        });
    }

    function reloadSidebar() {
        // Append timestamp to prevent browser caching, and 'refresh' to force server cache clear
        const url = '{{ route('super-admin.sidebar') }}' + '?t=' + new Date().getTime() + '&refresh=1';
        fetch(url)
        .then(res => res.text())
        .then(html => {
            document.getElementById('main-nav').innerHTML = html;
        });
    }

    // Attachment Swap logic
    const employeeFields = [
        { value: 'employee_doc_1', text: 'Document 1' },
        { value: 'employee_doc_2', text: 'Document 2' },
        { value: 'employee_doc_3', text: 'Document 3' },
        { value: 'employee_doc_4', text: 'Document 4' },
        { value: 'employee_doc_5', text: 'Document 5' },
        { value: 'employee_doc_6', text: 'Document 6' },
        { value: 'employee_doc_7', text: 'Document 7' },
        { value: 'employee_doc_8', text: 'Document 8' },
        { value: 'employee_doc_9', text: 'Document 9 (Other 1)' },
        { value: 'employee_doc_10', text: 'Document 10 (Other 2)' },
        { value: 'employee_doc_11', text: 'Document 11 (Other 3)' },
        { value: 'employee_doc_12', text: 'Document 12 (Other 4)' },
        { value: 'employee_doc_13', text: 'Document 13 (Other 5)' },
        { value: 'employee_doc_14', text: 'Document 14 (Other 6)' },
        { value: 'employee_doc_15', text: 'Document 15 (Other 7)' },
        { value: 'employee_doc_16', text: 'Document 16 (Other 8)' },
        { value: 'employee_doc_17', text: 'Document 17 (Other 9)' },
        { value: 'employee_doc_18', text: 'Document 18 (Other 10)' },
    ];

    const employerFields = [
        { value: 'employer_doc_company', text: 'Company Document (1)' },
        { value: 'employer_doc_lease', text: 'Lease Agreement (2)' },
        { value: 'employer_doc_construction', text: 'Construction Document (3)' },
        { value: 'employer_doc_other_1', text: 'Other 1 (4)' },
        { value: 'employer_doc_other_2', text: 'Other 2 (5)' },
        { value: 'employer_doc_other_3', text: 'Other 3 (6)' },
    ];

    function updateSwapFields() {
        const type = document.getElementById('swapEntityType').value;
        const fromSelect = document.getElementById('swapFromField');
        const toSelect = document.getElementById('swapToField');

        fromSelect.innerHTML = '';
        toSelect.innerHTML = '';

        const fields = type === 'employee' ? employeeFields : employerFields;

        fields.forEach(f => {
            fromSelect.add(new Option(f.text, f.value));
            toSelect.add(new Option(f.text, f.value));
        });

        // Set default selection to something different for convenience
        if(toSelect.options.length > 1) {
            toSelect.selectedIndex = 1;
        }
    }

    // Initialize fields on load
    document.addEventListener("DOMContentLoaded", function() {
        updateSwapFields();
    });

    function confirmDeleteProfile(profileId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Are you sure you want to delete this profile?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-profile-form-' + profileId).submit();
            }
        });
    }

    function confirmMassSwap() {
        Swal.fire({
            title: 'Are you sure?',
            text: "Are you sure you want to perform this mass file operation? This cannot be easily undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, execute swap!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('mass-swap-form').submit();
            }
        });
    }

    // Per-slot AJAX editor for "Other Documents" defaults
    document.addEventListener('alpine:init', () => {
        Alpine.data('otherDocSlotEditor', ({ entity, slot, initial }) => ({
            entity, slot,
            value: initial || '',
            original: initial || '',
            saving: false,
            lastSaved: '',
            get dirty() { return (this.value || '') !== (this.original || ''); },
            save() {
                if (this.saving || !this.dirty) return;
                const entityLabel = this.entity === 'employer' ? 'นายจ้าง' : 'ลูกจ้าง';
                const newValue = (this.value || '').trim();
                const wasEmpty = !this.original;
                const isEmpty = !newValue;
                let msg = `จะ${isEmpty ? 'ล้าง' : 'ตั้ง'}ค่า default ของ Other Doc ${this.slot} (${entityLabel}) เป็น:\n\n"${newValue || '(ว่าง)'}"\n\nและจะ force-update ทุก ${entityLabel} ที่มีอยู่แล้ว ให้ตรงกันด้วย\n\nดำเนินการ?`;
                Swal.fire({
                    title: 'อัพเดต Slot ' + this.slot,
                    text: msg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    confirmButtonText: 'ยืนยัน, อัพเดต',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (!result.isConfirmed) return;
                    this.saving = true;
                    const fd = new FormData();
                    fd.append('_token', '{{ csrf_token() }}');
                    fd.append('entity', this.entity);
                    fd.append('slot', this.slot);
                    fd.append('value', newValue);
                    fetch('{{ route("super-admin.attachments.descriptions.single") }}', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: fd
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.value = newValue;
                            this.original = newValue;
                            this.lastSaved = '✓ ' + data.message;
                            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 2500 });
                        } else {
                            Swal.fire('Error', data.message || 'Failed', 'error');
                        }
                    })
                    .catch(err => Swal.fire('Error', err.message, 'error'))
                    .finally(() => { this.saving = false; });
                });
            }
        }));
    });
</script>
@endpush
@endsection
