@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
    <div class="d-flex flex-column justify-content-center align-items-center position-relative"
         style="min-height: 75vh; width: 100%; overflow: hidden;">

        {{-- Watermark Background --}}
        <div class="position-absolute top-50 start-50 translate-middle d-flex align-items-center justify-content-center"
             style="z-index: 0; opacity: 0.15; width: 100%; pointer-events: none;">
            <img src="{{ asset('images/dashboard-watermark.jpg') }}"
                 alt="Watermark"
                 class="img-fluid"
                 style="max-width: 60%; max-height: 60vh; object-fit: contain; mix-blend-mode: multiply;">
        </div>

        {{-- Dashboard Content --}}
        <div class="position-relative text-center" style="z-index: 1;">
            <h2 class="display-4 fw-bold text-secondary mb-3">{{ __('Dashboard') }}</h2>
            <p class="lead text-muted">{{ __("You're logged in!") }}</p>
        </div>

    </div>
@endsection
