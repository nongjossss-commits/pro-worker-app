@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">{{ __('Super Admin Menu Settings') }}</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

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

@push('scripts')
<script>
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
</script>
@endpush
@endsection
