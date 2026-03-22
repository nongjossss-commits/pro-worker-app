@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Download Profiles</h1>
        <a href="{{ route('super-admin.download-profiles.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Create New Profile</a>
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
@endsection
