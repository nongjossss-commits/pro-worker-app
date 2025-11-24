@extends('layouts.app')

@section('title', 'Group & Team')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Group & Team Management') }}</h1>
    </div>

    <div class="row g-4">
        <!-- Affiliated with Employer -->
        <div class="col-md-6">
            <div class="card h-100 shadow-sm hover-shadow transition-all">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="bi bi-building-fill text-primary" style="font-size: 4rem;"></i>
                    </div>
                    <h3 class="card-title fw-bold mb-3">{{ __('สังกัดภายใต้นายจ้าง') }}</h3>
                    <p class="card-text text-muted mb-4">
                        {{ __('Manage groups and teams specific to an employer. Employees must belong to the selected employer.') }}
                    </p>
                    <a href="{{ route('groups.affiliated.index') }}" class="btn btn-primary btn-lg w-100">
                        {{ __('Select Employer') }} <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Independent / No Employer -->
        <div class="col-md-6">
            <div class="card h-100 shadow-sm hover-shadow transition-all">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="bi bi-diagram-3-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h3 class="card-title fw-bold mb-3">{{ __('ไม่สังกัดนายจ้าง') }}</h3>
                    <p class="card-text text-muted mb-4">
                        {{ __('Manage independent groups. Add any employee from the system regardless of their employer.') }}
                    </p>
                    <a href="{{ route('groups.independent.manage') }}" class="btn btn-success btn-lg w-100">
                        {{ __('Manage Independent Groups') }} <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
    .transition-all {
        transition: all 0.3s ease;
    }
</style>
@endsection
