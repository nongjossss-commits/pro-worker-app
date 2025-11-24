@extends('layouts.app')

@section('title', 'Select Employer - Group & Team')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('groups.index') }}" class="text-decoration-none text-muted">
            <i class="bi bi-arrow-left"></i> {{ __('Back to Selection') }}
        </a>
        <h1 class="h3 mt-2 text-gray-800">{{ __('Select Employer for Group Management') }}</h1>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('groups.affiliated.index') }}" method="GET" class="row g-3">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control form-control-lg"
                           placeholder="{{ __('Search by Employer Name or Company Name...') }}"
                           value="{{ $search }}">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-search"></i> {{ __('Search') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($employers->count() > 0)
    <div class="row g-3">
        @foreach($employers as $employer)
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 bg-white">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-primary">{{ $employer->employerNameTh }}</h5>
                    <h6 class="card-subtitle mb-2 text-muted">{{ $employer->employerNameEn }}</h6>
                    <p class="card-text small text-muted">
                        {{ __('Employees') }}: {{ $employer->employees()->count() }}
                    </p>
                    <a href="{{ route('groups.affiliated.manage', $employer->id) }}" class="btn btn-outline-primary w-100 stretched-link">
                        {{ __('Manage Groups') }}
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @elseif($search)
        <div class="alert alert-info text-center">
            {{ __('No employers found matching your search.') }}
        </div>
    @endif
</div>
@endsection
