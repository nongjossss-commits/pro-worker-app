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

    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const initMobileSidebar = () => {
            // Check if we are on mobile/tablet (Bootstrap lg breakpoint is 992px)
            if (window.innerWidth < 992) {
                const sidebarEl = document.getElementById('sidebar');

                if (sidebarEl && typeof bootstrap !== 'undefined') {
                    // Get or create the Offcanvas instance
                    const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(sidebarEl);

                    // Show the sidebar (waiting state)
                    bsOffcanvas.show();

                    // Select all links inside the sidebar
                    const links = sidebarEl.querySelectorAll('a');
                    links.forEach(link => {
                        link.addEventListener('click', () => {
                            // Hide the sidebar when a user selects a menu item
                            bsOffcanvas.hide();
                        });
                    });
                } else if (typeof bootstrap === 'undefined') {
                    // If bootstrap is not yet loaded, retry shortly
                    setTimeout(initMobileSidebar, 50);
                }
            }
        };

        // Initialize the logic
        initMobileSidebar();
    });
</script>
@endpush
