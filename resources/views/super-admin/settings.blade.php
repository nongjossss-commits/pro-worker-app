@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">{{ __('Super Admin Menu Settings') }}</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" id="superAdminSettingsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="menu-settings-tab" data-bs-toggle="tab" data-bs-target="#menu-settings" type="button" role="tab" aria-controls="menu-settings" aria-selected="true">
                <i class="bi bi-menu-button-wide"></i> Menu Visibility & Access
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="download-profiles-tab" data-bs-toggle="tab" data-bs-target="#download-profiles" type="button" role="tab" aria-controls="download-profiles" aria-selected="false">
                <i class="bi bi-download"></i> Download Profiles
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="attachment-settings-tab" data-bs-toggle="tab" data-bs-target="#attachment-settings" type="button" role="tab" aria-controls="attachment-settings" aria-selected="false">
                <i class="bi bi-file-earmark-arrow-up"></i> Attachment Files Settings
            </button>
        </li>
    </ul>

    <div class="tab-content" id="superAdminSettingsTabContent">
        <!-- Tab 1: Menu Visibility & Access -->
        <div class="tab-pane fade show active" id="menu-settings" role="tabpanel" aria-labelledby="menu-settings-tab">
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
        <div class="tab-pane fade" id="download-profiles" role="tabpanel" aria-labelledby="download-profiles-tab">
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
                                            <form action="{{ route('super-admin.download-profiles.destroy', $profile->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this profile?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
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
        <div class="tab-pane fade" id="attachment-settings" role="tabpanel" aria-labelledby="attachment-settings-tab">

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

                            <form action="{{ route('super-admin.attachments.swap') }}" method="POST" onsubmit="return confirm('Are you sure you want to perform this mass file operation? This cannot be easily undone.');">
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
                                    <button type="submit" class="btn btn-warning fw-bold">Execute Mass Swap</button>
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
                            <div class="alert alert-info py-2 small">
                                Set default text for generic "Other Documents". Saving this will apply the new text to <strong>all existing records</strong> and save it as the default for future records. (Users can still edit individual records later).
                            </div>

                            <form action="{{ route('super-admin.attachments.descriptions') }}" method="POST" onsubmit="return confirm('This will overwrite custom descriptions for ALL existing employers and employees with these values. Continue?');">
                                @csrf

                                <h6 class="fw-bold mt-3 text-secondary">Employer - Other Documents</h6>
                                <div class="row">
                                    @for($i = 1; $i <= 3; $i++)
                                        @php $key = "employer_other_{$i}_desc"; @endphp
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label small">Other Doc {{ $i }}</label>
                                            <input type="text" class="form-control form-control-sm" name="{{ $key }}" value="{{ $settings[$key]->value ?? '' }}" placeholder="Default description">
                                        </div>
                                    @endfor
                                </div>

                                <h6 class="fw-bold mt-4 text-secondary">Employee - Other Documents</h6>
                                <div class="row">
                                    @for($i = 1; $i <= 10; $i++)
                                        @php $key = "employee_other_{$i}_desc"; @endphp
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small">Other Doc {{ $i }} (Slot {{ $i + 8 }})</label>
                                            <input type="text" class="form-control form-control-sm" name="{{ $key }}" value="{{ $settings[$key]->value ?? '' }}" placeholder="Default description">
                                        </div>
                                    @endfor
                                </div>

                                <div class="d-grid mt-3">
                                    <button type="submit" class="btn btn-primary fw-bold">Save Defaults & Update All Records</button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

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
</script>
@endpush
@endsection
