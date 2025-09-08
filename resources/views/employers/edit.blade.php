{{-- This is the FINAL CORRECTED and COMPLETE file content. --}}

@extends('layouts.app')

@section('content')
<div class="container">
    {{-- Main Employer Edit Form --}}
    <div class="card">
        <div class="card-header">
            <h1>Edit Employer: {{ $employer->name }}</h1>
        </div>
        <div class="card-body">
            {{-- Display success/error messages from redirects --}}
            @if (session('success'))
                <div class="alert alert-success" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Placeholder for your actual employer form fields --}}
            <p>Employer details form...</p>
        </div>
    </div>

    {{-- Employee Section --}}
    <div class="card mt-4">
        <div class="card-header">
            <h2>Employees</h2>
        </div>
        <div class="card-body">
            <div class="list-group">
                {{--
                    THE CRITICAL FIX IS HERE:
                    Changed from ($employer->employees as $employee) to ($employees as $employee)
                    to match the variable passed from EmployerController@edit.
                --}}
                @forelse($employees as $employee)
                    <div class="list-group-item list-group-item-action" id="employee-card-{{ $employee->id }}">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">{{ $employee->name_th ?? 'N/A' }} / {{ $employee->name_en ?? 'N/A' }}</h5>
                            <small>Passport: {{ $employee->passport_number ?? '-' }}</small>
                        </div>
                        <p class="mb-1">
                            Visa expires on: {{ $employee->visa_expiry_date ? $employee->visa_expiry_date->format('d M Y') : 'N/A' }}
                        </p>
                        <p class="mb-1">
                            Work Permit expires on: {{ $employee->work_permit_expiry_date ? $employee->work_permit_expiry_date->format('d M Y') : 'N/A' }}
                        </p>
                    </div>
                @empty
                    <p class="text-muted">No employees found for this employer.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

{{-- This section pushes the necessary CSS to the main layout's <head> --}}
@push('styles')
<style>
    /* This style creates the visual highlight effect */
    .employee-card-highlight {
        transition: all 0.5s ease-in-out;
        background-color: #fffbeb !important;
        border: 2px solid #f97316 !important;
        box-shadow: 0 4px 15px rgba(249, 115, 22, 0.2);
        transform: scale(1.01);
    }
</style>
@endpush

{{-- This section pushes the necessary JavaScript to the end of the <body> --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Check if the URL has a hash (e.g., #employee-card-123)
        if (window.location.hash) {
            const hash = window.location.hash;

            const targetElement = document.querySelector(hash);

            if (targetElement) {
                // Add the highlight class for the visual effect
                targetElement.classList.add('employee-card-highlight');

                // Scroll the element into the middle of the viewport smoothly
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'nearest'
                });

                // Remove the highlight class after 5 seconds
                setTimeout(() => {
                    targetElement.classList.remove('employee-card-highlight');
                }, 5000); // 5 seconds
            }
        }
    });
</script>
@endpush
