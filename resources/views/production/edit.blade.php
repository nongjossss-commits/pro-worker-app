@extends('layouts.app')

@section('title', 'Edit Job: ' . $production->project_name)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-folder2-open me-2"></i>{{ $production->project_name }}
        </h1>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
        </a>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ request('tab') == 'financial' ? 'active' : '' }}" href="?tab=financial">{{ __('Financial') }}</a>
        </li>
        <!-- Add other tabs if needed, e.g. "Overview" -->
    </ul>

    <div class="tab-content">
        @if(request('tab') == 'financial')
            @include('production.partials.financial-tab', [
                'production' => $production,
                'employeeCount' => $employeeCount,
                'employees' => $employees
            ])
        @else
            <!-- Default or Overview Content -->
            <div class="alert alert-info">{{ __('Select a tab to view details.') }}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    {{-- Include additional scripts if needed for financial tab specifically --}}
@endpush
