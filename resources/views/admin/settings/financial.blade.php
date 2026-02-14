@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Bill Header Settings</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProfileModal">
            <i class="bi bi-plus-lg"></i> Add New Profile
        </button>
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-4">
        @foreach($profiles as $profile)
        <div class="col">
            <div class="card h-100 shadow-sm {{ $profile->is_default ? 'border-primary' : '' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        @if($profile->logo_path)
                            <img src="{{ asset('storage/' . $profile->logo_path) }}" style="height: 40px;">
                        @else
                            <div class="bg-light p-2 rounded text-muted">No Logo</div>
                        @endif
                        @if($profile->is_default)
                            <span class="badge bg-primary">Default</span>
                        @endif
                    </div>
                    <h5 class="card-title fw-bold">{{ $profile->name }}</h5>
                    <p class="card-text small text-muted">{{ $profile->address }}</p>
                    <p class="card-text small">Tax ID: {{ $profile->tax_id }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.settings.financial.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Company Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tax ID</label>
                        <input type="text" name="tax_id" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="isDefault">
                        <label class="form-check-label" for="isDefault">Set as Default</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
