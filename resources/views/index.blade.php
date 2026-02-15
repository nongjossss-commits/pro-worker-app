@extends('layouts.app')

@section('title', __('Welcome'))

@section('content')
<div class="d-flex flex-column justify-content-center align-items-center min-vh-100 bg-light">
    <div class="text-center p-5 bg-white shadow-sm rounded">
        <h1 class="display-1 fw-bold text-primary mb-4">{{ __('WELCOME') }}</h1>
        <p class="lead text-muted mb-4">{{ __('Please select a menu from the sidebar to continue.') }}</p>

        @auth
            <div class="d-flex justify-content-center gap-3 mt-4">
                @if(\App\Facades\SuperAdmin::isVisible('dashboard'))
                <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-bar-chart-fill me-2"></i>{{ __('Go to Statistics') }}
                </a>
                @endif

                @if(\App\Facades\SuperAdmin::isVisible('employees'))
                <a href="{{ route('employees.index') }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-people-fill me-2"></i>{{ __('Manage Employees') }}
                </a>
                @endif
            </div>
        @endauth
    </div>
</div>
@endsection
