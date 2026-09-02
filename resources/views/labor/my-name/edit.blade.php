@extends('labor.layout')

@section('title', 'My Name - Pro Walker Labour')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">{{ __('Edit My Name') }}</h4>
    <a href="{{ route('labor.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Dashboard') }}
    </a>
</div>

<div class="card shadow-sm border-0" style="max-width: 480px;">
    <div class="card-body">
        <p class="text-muted small">{{ __('This is the name that appears on reports and documents — separate from your login name, which stays exactly as it is.') }}</p>

        <form method="POST" action="{{ route('labor.my-name.update') }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">{{ __('Name') }}</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $member->name) }}" required autofocus>
                @error('name')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        </form>
    </div>
</div>
@endsection
