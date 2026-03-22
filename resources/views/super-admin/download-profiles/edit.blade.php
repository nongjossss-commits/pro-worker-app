@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <a href="{{ route('super-admin.download-profiles.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to Profiles
        </a>
    </div>

    <div class="card shadow-sm max-w-lg mx-auto" style="max-width: 600px;">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Edit Download Profile</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('super-admin.download-profiles.update', $downloadProfile->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label fw-bold">Company / Office Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $downloadProfile->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="phone_number" class="form-label fw-bold">Phone Number</label>
                    <input type="text" class="form-control @error('phone_number') is-invalid @enderror" id="phone_number" name="phone_number" value="{{ old('phone_number', $downloadProfile->phone_number) }}">
                    @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <label for="logo" class="form-label fw-bold">Company Logo</label>
                    @if($downloadProfile->logo_path)
                        <div class="mb-2">
                            <img src="{{ Storage::url($downloadProfile->logo_path) }}" alt="Current Logo" class="img-thumbnail" style="max-height: 100px;">
                        </div>
                        <div class="form-text text-muted mb-2">Upload a new image below to replace the current logo.</div>
                    @endif
                    <input class="form-control @error('logo') is-invalid @enderror" type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/jpg,image/webp">
                    <div class="form-text">Recommended height is about 1-1.5 cm for best PDF layout results. Maximum file size: 2MB.</div>
                    @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
