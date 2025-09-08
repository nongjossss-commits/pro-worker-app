{{-- This is the CORRECTED and COMPLETE file content. --}}

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
                {{-- Use @forelse to handle cases with no employees --}}
                @forelse($employer->employees as $employee)
                    {{--
                        THIS IS THE CRITICAL PART:
                        The id="employee-card-{{ $employee->id }}" is the hook for our JavaScript.
                        The rest of the employee display logic is the user's original, correct code.
                    --}}
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
    /*
      This style creates the visual highlight effect as per the design.
      The transition properties make the effect appear and disappear smoothly.
    */
    .employee-card-highlight {
        transition: all 0.5s ease-in-out;
        background-color: #fffbeb !important; /* A light yellow background */
        border: 2px solid #f97316 !important; /* An orange border */
        box-shadow: 0 4px 15px rgba(249, 115, 22, 0.2);
        transform: scale(1.01);
    }
</style>
@endpush

{{-- This section pushes the necessary JavaScript to the end of the <body> --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // This script runs after the page's HTML has been fully loaded.

        // Check if the URL has a hash (e.g., #employee-card-123)
        if (window.location.hash) {
            const hash = window.location.hash;

            // Use the hash as a selector to find the target employee card
            const targetElement = document.querySelector(hash);

            if (targetElement) {
                // 1. Add the highlight class to apply the visual effect immediately.
                targetElement.classList.add('employee-card-highlight');

                // 2. Scroll the element into the middle of the viewport smoothly.
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'nearest'
                });

                // 3. Set a timer to remove the highlight class after 5 seconds.
                setTimeout(() => {
                    targetElement.classList.remove('employee-card-highlight');
                }, 5000); // 5000 milliseconds = 5 seconds
            }
        }
    });
</script>
@endpush
