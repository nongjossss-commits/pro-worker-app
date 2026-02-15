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
                                    @if($isVisible)
                                        <span class="badge bg-success"><i class="bi bi-eye-fill me-1"></i> Visible</span>
                                    @else
                                        <span class="badge bg-danger"><i class="bi bi-eye-slash-fill me-1"></i> Hidden</span>
                                    @endif
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

                                                <!-- Visibility Toggle -->
                                                <div class="mb-4">
                                                    <label class="form-label fw-bold">Visibility Status</label>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="isVisible-{{ $key }}" name="is_visible" value="1" {{ $isVisible ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="isVisible-{{ $key }}">Show this menu to users</label>
                                                    </div>
                                                    <div class="form-text">
                                                        If unchecked, this menu will be hidden for EVERYONE (including Admins).
                                                    </div>
                                                </div>

                                                <hr>

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
@endsection
