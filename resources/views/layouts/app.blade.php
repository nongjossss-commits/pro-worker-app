<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Company Records')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- PWA Manifest & Meta Tags -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PWL System">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.png') }}">

    <!-- Tailwind CSS (Scoped/Utility) for Components using it -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            prefix: '', // No prefix to match component code
            corePlugins: {
               preflight: false, // Disable preflight to avoid conflict with Bootstrap
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --bs-primary: #F97316;
            --bs-primary-rgb: 249, 115, 22;
            --bs-primary-dark: #EA580C;
            --bs-primary-light: #FB923C;
            --bs-body-font-family: 'Inter', 'Sarabun', sans-serif;
            --bs-body-bg: #f8fafc; /* Fallback color */
            --bs-border-color: #e2e8f0;
        }

        body {
            font-size: 1rem;
            line-height: 1.6;
            background-color: var(--bs-body-bg);
            background-image: radial-gradient(var(--bs-border-color) 1px, transparent 1px);
            background-size: 1.5rem 1.5rem;
            background-attachment: fixed;
        }

        .main-layout {
            display: flex;
            min-height: 100vh;
        }

        #sidebar {
            width: 260px;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
            /* padding: 1.5rem; <-- REMOVED */
            --bs-offcanvas-width: 260px; /* <-- ADDED */
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        #sidebar .navbar-brand {
            font-weight: 700;
            color: var(--bs-primary);
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.5rem;
            text-decoration: none;
        }

        #sidebar .list-group-item {
            border: none;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            color: #475569;
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
            font-size: 0.95rem;
        }

        #sidebar .list-group-item.active {
            background-color: var(--bs-primary-light);
            color: #ffffff;
            background-image: linear-gradient(to right, var(--bs-primary-light), var(--bs-primary));
            box-shadow: 0 4px 6px -1px rgba(249, 115, 22, 0.2), 0 2px 4px -2px rgba(249, 115, 22, 0.2);
        }

        #sidebar .list-group-item:hover:not(.active) {
            background-color: #f1f5f9;
            color: #1e293b;
        }

        #main-content {
            flex-grow: 1;
            padding: 2.5rem;
            overflow-y: auto;
        }

        .content-section {
            background-color: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid var(--bs-border-color);
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.07), 0 1px 2px -1px rgb(0 0 0 / 0.07);
            padding: 2rem;
        }
        .table thead {
            --bs-table-bg: #f8fafc;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        /* Highlight Animation for Employee Card */
        @keyframes highlightPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.7);
                border-color: #f97316;
                background-color: #fff7ed;
                transform: scale(1.02);
            }
            50% {
                box-shadow: 0 0 0 10px rgba(249, 115, 22, 0);
                border-color: #f97316;
                background-color: #fff7ed;
                transform: scale(1.02);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(249, 115, 22, 0);
                border-color: #f97316;
                background-color: #fff7ed;
                transform: scale(1.02);
            }
        }

        .employee-card.highlight {
            animation: highlightPulse 2s ease-out infinite;
            border: 2px solid #f97316 !important;
            background-color: #fff7ed !important;
            z-index: 10;
        }
        .modal-backdrop.fade.show{
            display: none;
        }
        .modal.fade.show{
            background: rgba(0, 0, 0, 0.6);
        }
        /* Restore padding to the new offcanvas body */
        #sidebar .offcanvas-body {
            padding: 1.5rem;
        }
        /* Reduce main content padding on mobile view */
        @media (max-width: 991.98px) {
            #main-content {
                padding: 1.5rem;
            }
        }

        .scroll-to-top {
            position: fixed;
            bottom: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--bs-primary);
            color: #ffffff;
            text-align: center;
            line-height: 50px;
            font-size: 24px;
            cursor: pointer;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s, transform 0.3s;
            transform: translateY(20px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .scroll-to-top.left {
            left: 20px;
        }
        .scroll-to-top.right {
            right: 20px;
        }
        .scroll-to-top.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .scroll-to-top:hover {
            background-color: var(--bs-primary-dark);
        }

        /* Drawer Handle */
        .drawer-handle {
            position: fixed;
            left: 0;
            top: 50%;
            transform: translateY(-50%) translateX(-100%); /* Initially hidden off-screen */
            width: 12px;
            height: 50vh;
            background-color: var(--bs-primary);
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            cursor: pointer;
            z-index: 999;
            transition: transform 0.3s ease, width 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            opacity: 0.8;
        }
        .drawer-handle.show {
            transform: translateY(-50%) translateX(0); /* Slide in */
        }
        .drawer-handle:hover {
            width: 16px;
            opacity: 1;
        }
        .drawer-handle i {
            color: white;
            font-size: 10px;
        }

        .form-check-input {
            border: 2px solid #0d6efd !important;
        }
    </style>
</head>
<body>

    <div class="main-layout">
        <aside id="sidebar" class="offcanvas offcanvas-start" tabindex="-1" aria-labelledby="sidebarLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="sidebarLabel">
                    <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none text-reset">
                        <img src="{{ asset('images/logo_new.jpg') }}" alt="Logo" style="height: 40px; width: auto;"> Proworker labour
                    </a>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column p-0">
            <a class="navbar-brand d-flex flex-column align-items-center mb-4 mt-3" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo_new.jpg') }}" alt="Proworker Logo" class="mb-2" style="height: 130px; width: auto; max-width: 100%; border: none;">
                <span style="line-height: 1.2;">Proworker labour</span>
            </a>
            <div class="list-group" id="main-nav">
                @include('partials.sidebar-menu')
            </div>
            </div>
        </aside>

        <main id="main-content" style="position: relative; z-index: 1;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                        <i class="bi bi-list"></i>
                    </button>

                    {{-- Download Center Button (Fixed for better visibility) --}}
                    <button class="btn btn-outline-secondary d-none d-md-block" onclick="openDownloadCenter()">
                        <i class="bi bi-cloud-download-fill me-1"></i> Download Center
                    </button>

                    {{-- Drag to Split Button --}}
                    <a href="{{ request()->fullUrl() }}"
                       class="btn btn-outline-secondary d-none d-md-block ms-2"
                       draggable="true"
                       ondragstart="event.dataTransfer.setData('text/plain', this.href); event.dataTransfer.setData('text/uri-list', this.href);"
                       title="{{ __('ลากเพื่อแยกหน้าจอ (Drag to Split)') }}">
                        <i class="bi bi-grid-3x2-gap-fill me-1"></i> {{ __('ลากเพื่อแยก') }}
                    </a>
                </div>

                <div class="d-flex align-items-center ms-auto gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-translate me-1"></i>
                            @switch(app()->getLocale())
                                @case('th') 🇹🇭 ไทย @break
                                @case('zh') 🇨🇳 中文 @break
                                @default 🇺🇸 English
                            @endswitch
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'th') }}">{{ __('🇹🇭 ไทย (Thai)') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'en') }}">🇺🇸 English</a></li>
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'zh') }}">🇨🇳 中文 (Chinese)</a></li>
                        </ul>
                    </div>

                    <!-- User Dropdown (Top Right) -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                            <span class="d-none d-md-inline">{{ Auth::user()->name ?? __('User') }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="d-inline" id="logout-form">
                                    @csrf
                                    <a href="{{ route('logout') }}" class="dropdown-item text-danger" id="btn-logout">
                                        <i class="bi bi-box-arrow-right me-2"></i>{{ __('Logout') }}
                                    </a>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            @yield('debug-tracker')
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('danger'))
                <div class="alert alert-danger">
                    {{ session('danger') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning">
                    {{ session('warning') }}
                </div>
            @endif

            @if (session('duplicate_error'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: '{{ __("Duplicate Employee") }}',
                            text: "{!! session('duplicate_error') !!}",
                            icon: 'warning',
                            confirmButtonText: '{{ __("OK") }}',
                            confirmButtonColor: '#F97316'
                        });
                    });
                </script>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

        {{-- Notification Modals --}}
        <div class="modal fade" id="renewNotificationModal" tabindex="-1" aria-labelledby="renewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="renew-form" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="renewModalLabel">{{ __('Renew Notification') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            {{-- Dynamic Content Area --}}

                            {{-- Field for Pink Card Number (Only for pink_card_missing) --}}
                            <div class="mb-3 d-none" id="pink_card_number_group">
                                <label for="pink_card_number" class="form-label">{{ __('เลขบัตรชมพู') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="pink_card_number" name="pink_card_number">
                            </div>

                            {{-- Field for Expiry Date (Hidden for missing data types) --}}
                            <div class="mb-3" id="new_due_date_group">
                                <label for="new_due_date" class="form-label">{{ __('Select new expiry date') }}: <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="new_due_date" name="new_due_date">
                            </div>

                            {{-- File Attachment --}}
                            <div class="mb-3">
                                <label for="attachment" class="form-label" id="attachment_label">{{ __('Attach document (if any)') }}:</label>
                                <input type="file" class="form-control" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                                <div class="form-text text-muted" id="attachment_help"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="cancelNotificationModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="cancel-form" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="cancelModalLabel">{{ __('Confirm Cancellation') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>{{ __('Are you sure you want to cancel this notification?') }}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                            <button type="submit" class="btn btn-danger">{{ __('Confirm Cancel') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Job Owner Management Modal --}}
    <div class="modal fade" id="jobOwnerModal" tabindex="-1" aria-labelledby="jobOwnerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jobOwnerModalLabel">{{ __('Manage Job Owners') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- List of current owners --}}
                    <h5>{{ __('All Job Owners') }}</h5>
                    <ul class="list-group mb-3" id="jobOwnerList">
                        {{-- Owners will be loaded here via JS --}}
                        <li class="list-group-item text-muted">{{ __('Loading...') }}</li>
                    </ul>

                    <hr>

                    {{-- Add new owner form --}}
                    <h6>{{ __('Add New Job Owner') }}</h6>
                    <div class="input-group">
                        <input type="text" id="newJobOwnerName" class="form-control" placeholder="{{ __('Job Owner Name') }}">
                        <button class="btn btn-primary" type="button" id="saveNewJobOwnerBtn">{{ __('Save') }}</button>
                    </div>
                    <div id="jobOwnerError" class="text-danger small mt-1" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- START: Delete Address Confirmation Modal --}}
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">{{ __('Confirm Deletion') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{ __('Are you sure you want to delete this address?') }}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">{{ __('Delete') }}</button>
            </div>
        </div>
    </div>
</div>
{{-- END: Delete Address Confirmation Modal --}}

    {{-- Toast Notification Container --}}
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="bi bi-check-circle-fill rounded me-2 text-success"></i>
                <strong class="me-auto">{{ __('Notification') }}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toast-body-content">
                </div>
        </div>
    </div>

    {{-- Chat Widget Component --}}
    @include('components.chat-widget')

    {{-- Document Scanner Component (Global) --}}
    @include('components.document-scanner')

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/central-delete-handler.js'])
    <script src="{{ asset('js/financial-security.js') }}"></script>

    <script>
    // Global Drag Helper
    window.startDragGlobal = function(e, type, data) {
        const payload = {
            type: type,
            title: data.name || data.title || 'Item',
            subtitle: data.subtitle || data.code || '',
            url: data.url || window.location.href,
            source_menu: data.source_menu || document.title, // Default to page title if not provided
            ...data
        };
        e.dataTransfer.effectAllowed = 'copy';
        const jsonPayload = JSON.stringify(payload);
        e.dataTransfer.setData('application/json', jsonPayload);
        e.dataTransfer.setData('text/plain', jsonPayload); // Fallback for broader compatibility
    }

    // Register PWA Service Worker & Push Subscription
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    initializePushSubscription(registration);
                })
                .catch(err => {
                    console.log('ServiceWorker registration failed: ', err);
                });
        });
    }

    // Helper to convert VAPID key
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function initializePushSubscription(registration) {
        // Check if push is supported
        if (!('PushManager' in window)) {
            console.log('Push messaging isn\'t supported.');
            return;
        }

        // Check permission state
        if (Notification.permission === 'denied') {
            console.log('The user has blocked notifications.');
            return;
        }

        // We can request permission here, or let the user click a button.
        // For PWA experience, we often ask on load or interaction.
        // Let's ask on load for now as per requirement "like a modern app".

        // Use a dummy VAPID public key if we don't have a real one yet from .env
        // In a real scenario, this comes from config.
        // I will use a placeholder or check if one is injected.
        const vapidPublicKey = '{{ config('services.webpush.public_key', 'BCmti7ScwxxVAlB7WAzMMSiwV4-D1_5z509i546e4k7e4k7e4k7e4k7e4k7e4k7e4k7e4k7e4k7e4k7e4k4') }}';

        // If key is invalid placeholder, we can't really subscribe securely, but we'll try standard subscribe
        // For local testing without VAPID, push might not work fully in all browsers.

        // Ideally:
        const convertedVapidKey = urlBase64ToUint8Array(vapidPublicKey);

        // Check if already subscribed
        registration.pushManager.getSubscription()
            .then(function(subscription) {
                if (subscription) {
                    console.log('User is already subscribed:', subscription);
                    sendSubscriptionToBackEnd(subscription);
                    return subscription;
                }

                return registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedVapidKey
                });
            })
            .then(function(subscription) {
                if (subscription) {
                    console.log('User is subscribed:', subscription);
                    sendSubscriptionToBackEnd(subscription);
                }
            })
            .catch(function(err) {
                console.log('Failed to subscribe the user: ', err);
            });
    }

    function sendSubscriptionToBackEnd(subscription) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('{{ route('push.subscribe') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(subscription)
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Bad status code from server.');
            }
            return response.json();
        })
        .then(function(responseData) {
            if (!responseData.success) {
                throw new Error('Bad response from server.');
            }
        });
    }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const renewModal = document.getElementById('renewNotificationModal');
            if (renewModal) {
                renewModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const notificationId = button.getAttribute('data-notification-id');
                    const notificationType = button.getAttribute('data-notification-type');

                    const form = document.getElementById('renew-form');
                    const modalTitle = document.getElementById('renewModalLabel');
                    const pinkCardGroup = document.getElementById('pink_card_number_group');
                    const dueDateGroup = document.getElementById('new_due_date_group');
                    const dueDateInput = document.getElementById('new_due_date');
                    const attachmentLabel = document.getElementById('attachment_label');
                    const attachmentInput = document.getElementById('attachment');
                    const pinkCardInput = document.getElementById('pink_card_number');

                    // Reset Fields
                    pinkCardGroup.classList.add('d-none');
                    dueDateGroup.classList.remove('d-none');
                    dueDateInput.required = true;
                    pinkCardInput.required = false;
                    attachmentInput.required = false; // Default
                    attachmentLabel.textContent = '{{ __('Attach document (if any)') }}:';

                    if(notificationId) {
                        form.action = `/notifications/${notificationId}/renew`;
                    }

                    // Adjust modal based on type
                    if (notificationType === 'pink_card_missing') {
                        modalTitle.textContent = 'อัพเดตข้อมูลบัตรชมพู';
                        pinkCardGroup.classList.remove('d-none');
                        pinkCardInput.required = true;
                        dueDateGroup.classList.add('d-none');
                        dueDateInput.required = false;
                        attachmentLabel.innerHTML = 'แนบไฟล์บัตรชมพู <span class="text-danger">*</span>';
                        attachmentInput.required = true;
                    } else if (notificationType === 'residence_permit_missing') {
                        modalTitle.textContent = 'อัพเดตใบแจ้งที่พักอาศัย';
                        dueDateGroup.classList.add('d-none');
                        dueDateInput.required = false;
                        attachmentLabel.innerHTML = 'แนบไฟล์ใบแจ้งที่พักอาศัย <span class="text-danger">*</span>';
                        attachmentInput.required = true;
                    } else {
                        modalTitle.textContent = '{{ __('Renew Notification') }}';
                    }
                });
            }

            const cancelModal = document.getElementById('cancelNotificationModal');
            if (cancelModal) {
                cancelModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const notificationId = button.getAttribute('data-notification-id');
                    const form = document.getElementById('cancel-form');
                    if(notificationId) {
                        form.action = `/notifications/${notificationId}/cancel`;
                    }
                });
            }
        });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const jobOwnerModalEl = document.getElementById('jobOwnerModal');
        if (!jobOwnerModalEl) return;

        const jobOwnerModal = new bootstrap.Modal(jobOwnerModalEl);
        const jobOwnerList = document.getElementById('jobOwnerList');
        const newJobOwnerNameInput = document.getElementById('newJobOwnerName');
        const saveNewJobOwnerBtn = document.getElementById('saveNewJobOwnerBtn');
        const jobOwnerError = document.getElementById('jobOwnerError');
        const mainJobOwnerSelect = document.getElementById('job_owner_id');
        const deleteJobOwnerBtn = document.getElementById('deleteJobOwnerBtn');

        // --- Main Logic ---

        // 1. Open Modal: Fetch and display all current job owners
        jobOwnerModalEl.addEventListener('show.bs.modal', function () {
            fetchJobOwners();
        });

        // 2. Add Owner: Handle click on "Save New Owner" button
        saveNewJobOwnerBtn.addEventListener('click', function () {
            const name = newJobOwnerNameInput.value.trim();
            if (!name) {
                showError('{{ __('Please enter job owner name') }}');
                return;
            }

            fetch('{{ route('job-owners.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name: name })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Add to modal list
                    appendOwnerToList(data.jobOwner);
                    // Add to main dropdown and select it
                    if (mainJobOwnerSelect) {
                        const newOption = new Option(data.jobOwner.name, data.jobOwner.id, true, true);
                        mainJobOwnerSelect.add(newOption, null);
                    }
                    newJobOwnerNameInput.value = '';
                    hideError();
                }
            })
            .catch(error => {
                if (error.errors && error.errors.name) {
                    showError(error.errors.name[0]);
                } else {
                    showError('{{ __('Error saving') }}');
                }
            });
        });

        // 3. Delete Owner: Handle clicks on delete icons in the modal
        jobOwnerList.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-job-owner-icon')) {
                const ownerId = e.target.dataset.id;
                if (confirm('{{ __('Are you sure you want to delete this job owner?') }}')) {
                    deleteOwner(ownerId);
                }
            }
        });

        // 4. Delete selected owner from the main form
        if (deleteJobOwnerBtn && mainJobOwnerSelect) {
            deleteJobOwnerBtn.addEventListener('click', function() {
                const selectedOwnerId = mainJobOwnerSelect.value;
                if (selectedOwnerId && selectedOwnerId !== '--- เลือกเจ้าของงาน ---') { // Note: This check might be fragile if translation changes, using ID is better but logic is based on value
                     if (confirm('{{ __('Are you sure you want to delete this job owner?') }}')) {
                        deleteOwner(selectedOwnerId);
                    }
                } else {
                    alert('{{ __('Please select a job owner to delete') }}');
                }
            });
        }


        // --- Helper Functions ---

        function fetchJobOwners() {
            jobOwnerList.innerHTML = '<li class="list-group-item text-muted">{{ __('Loading...') }}</li>';
            fetch('{{ route('job-owners.index') }}')
                .then(response => response.json())
                .then(data => {
                    jobOwnerList.innerHTML = '';
                    if (data.length === 0) {
                        jobOwnerList.innerHTML = '<li class="list-group-item text-muted">{{ __('No job owner data') }}</li>';
                    } else {
                        data.forEach(owner => appendOwnerToList(owner));
                    }
                });
        }

        function appendOwnerToList(owner) {
             // Check if "no data" placeholder exists and remove it
            const placeholder = jobOwnerList.querySelector('.text-muted');
            if (placeholder) {
                placeholder.parentElement.innerHTML = '';
            }
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.id = `job-owner-${owner.id}`;
            li.textContent = owner.name;
            const deleteIcon = document.createElement('i');
            deleteIcon.className = 'bi bi-trash-fill text-danger delete-job-owner-icon';
            deleteIcon.style.cursor = 'pointer';
            deleteIcon.dataset.id = owner.id;
            li.appendChild(deleteIcon);
            jobOwnerList.appendChild(li);
        }

        function deleteOwner(ownerId) {
            fetch(`/job-owners/${ownerId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove from modal list
                    const listItem = document.getElementById(`job-owner-${ownerId}`);
                    if (listItem) listItem.remove();

                    // Remove from main dropdown
                    if (mainJobOwnerSelect) {
                        const optionToRemove = mainJobOwnerSelect.querySelector(`option[value='${ownerId}']`);
                        if (optionToRemove) optionToRemove.remove();
                    }
                } else {
                    alert(data.message || '{{ __('Cannot delete') }}');
                }
            });
        }

        function showError(message) {
            jobOwnerError.textContent = message;
            jobOwnerError.style.display = 'block';
            newJobOwnerNameInput.classList.add('is-invalid');
        }

        function hideError() {
            jobOwnerError.textContent = '';
            jobOwnerError.style.display = 'none';
            newJobOwnerNameInput.classList.remove('is-invalid');
        }
    });
    </script>
    <script>
    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('liveToast');
        const toastBody = document.getElementById('toast-body-content');
        const toastIcon = toastEl.querySelector('.toast-header i');

        // Reset classes
        toastIcon.className = 'rounded me-2';

        if (type === 'success') {
            toastIcon.classList.add('bi', 'bi-check-circle-fill', 'text-success');
        } else if (type === 'danger') {
            toastIcon.classList.add('bi', 'bi-exclamation-triangle-fill', 'text-danger');
        }

        toastBody.textContent = message;
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Function to initialize Flatpickr on a given element
        function initFlatpickr(element) {
            if (element._flatpickr) return; // Already initialized

            // Check if element is readonly or disabled, if so, we might want to respect that
            // Flatpickr handles this automatically usually.

            flatpickr(element, {
                locale: 'th',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                allowInput: true,
                disableMobile: true, // Force Flatpickr UI on mobile for consistency
                onChange: function(selectedDates, dateStr, instance) {
                    // Dispatch native input event for frameworks like Alpine.js or Vue
                    // and 'change' event for standard listeners
                    instance.element.dispatchEvent(new Event('input', {bubbles: true}));
                    instance.element.dispatchEvent(new Event('change', {bubbles: true}));
                }
            });
        }

        // 1. Initialize on existing elements
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => initFlatpickr(input));

        // 2. Observe for new elements (e.g. via Alpine or AJAX)
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.tagName === 'INPUT' && node.type === 'date') {
                            initFlatpickr(node);
                        } else if (node.querySelectorAll) {
                             // Check children
                            const inputs = node.querySelectorAll('input[type="date"]');
                            inputs.forEach(input => initFlatpickr(input));
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
    </script>

    @include('components.download-modals')
    @include('partials.background-removal-scripts')
    @include('partials.view_selected_modal')
    @stack('scripts')

<!-- Universal Preview Modal -->
<div class="modal fade" id="universalPreviewModal" tabindex="-1" aria-labelledby="universalPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="universalPreviewModalLabel">{{ __('Preview Data') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('Loading...') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PDF Preview Modal -->
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-labelledby="pdfPreviewModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="height: 90vh;">
        <div class="modal-content h-100">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfPreviewModalLabel">{{ __('PDF Preview') }}</h5>
                <div class="d-flex align-items-center gap-2">
                    <!-- Image Zoom Controls (Initially Hidden) -->
                    <div id="imageZoomControls" class="d-none btn-group btn-group-sm me-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="zoomImage(-0.1)" title="Zoom Out"><i class="bi bi-dash-lg"></i></button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetZoom()" title="Reset Zoom"><i class="bi bi-arrows-fullscreen"></i></button>
                        <button type="button" class="btn btn-outline-secondary" onclick="zoomImage(0.1)" title="Zoom In"><i class="bi bi-plus-lg"></i></button>
                    </div>

                    <a href="" id="pdfDownloadBtn" class="btn btn-sm btn-primary" download>
                        <i class="bi bi-download"></i> {{ __('Download') }}
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0 h-100 overflow-hidden bg-dark position-relative">
                <iframe id="pdfPreviewFrame" src="" class="w-100 h-100" style="border: none;"></iframe>

                <!-- Image Preview Container -->
                <div id="imagePreviewContainer" class="d-none w-100 h-100 overflow-auto d-flex bg-dark">
                    <div id="imageLoadingSpinner" class="spinner-border text-light position-absolute top-50 start-50 translate-middle" role="status" style="z-index: 5;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <img id="imagePreview" src="" alt="Preview" class="m-auto" style="max-width: 100%; max-height: 100%; object-fit: contain; box-shadow: 0 0 20px rgba(0,0,0,0.5);">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let imageZoomLevel = 1.0;
let isImageFitToScreen = true;

window.viewPDF = function(url, title = 'PDF Preview') {
    // Check if URL is valid
    if (!url) {
        console.error('viewPDF called with empty URL');
        return;
    }

    // Fallback if bootstrap is not loaded yet
    if (typeof bootstrap === 'undefined') {
        console.warn('Bootstrap not loaded, opening in new tab');
        window.open(url, '_blank');
        return;
    }

    const modalEl = document.getElementById('pdfPreviewModal');
    if (!modalEl) {
        console.error('PDF Preview Modal element not found');
        window.open(url, '_blank');
        return;
    }

    const iframe = document.getElementById('pdfPreviewFrame');
    const imageContainer = document.getElementById('imagePreviewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const imageSpinner = document.getElementById('imageLoadingSpinner');
    const zoomControls = document.getElementById('imageZoomControls');
    const downloadBtn = document.getElementById('pdfDownloadBtn');
    const modalTitle = document.getElementById('pdfPreviewModalLabel');

    try {
        // Construct URL object to parse parts
        // Use window.location.origin as base for relative URLs
        const pdfUrl = new URL(url, window.location.origin);

        // Add disposition=inline for local URLs or routes
        // We check if hostname matches to allow protocol mismatches (http vs https behind proxy)
        if (pdfUrl.hostname === window.location.hostname) {
             pdfUrl.searchParams.set('disposition', 'inline');
        }

        if(modalTitle) modalTitle.textContent = title;
        if(downloadBtn) downloadBtn.href = url; // Keep original URL for download button

        // Detect if file is an image based on extension
        const pathname = pdfUrl.pathname.toLowerCase();
        const isImage = /\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(pathname);

        if (isImage) {
            // Image Mode
            if(iframe) iframe.style.display = 'none';
            if(imageContainer) imageContainer.classList.remove('d-none');
            if (zoomControls) zoomControls.classList.remove('d-none');

            // Reset Zoom
            isImageFitToScreen = true;
            imageZoomLevel = 1.0;
            if (typeof updateImageStyle === 'function') updateImageStyle();

            // Load Image
            if(imageSpinner) imageSpinner.classList.remove('d-none');
            if(imagePreview) {
                imagePreview.style.opacity = '0.5';
                imagePreview.src = pdfUrl.toString();

                imagePreview.onload = function() {
                    if(imageSpinner) imageSpinner.classList.add('d-none');
                    imagePreview.style.opacity = '1';
                };
                imagePreview.onerror = function() {
                    if(imageSpinner) imageSpinner.classList.add('d-none');
                    console.error('Image failed to load');
                };
            }

        } else {
            // PDF / Other Mode
            if(iframe) {
                iframe.style.display = 'block';
                iframe.src = pdfUrl.toString();
            }
            if(imageContainer) imageContainer.classList.add('d-none');
            if (zoomControls) zoomControls.classList.add('d-none');
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

    } catch (e) {
        console.error('Error opening preview:', e);
        // Fallback to new tab
        window.open(url, '_blank');
    }
};

window.updateImageStyle = function() {
    const img = document.getElementById('imagePreview');
    if (!img) return;

    if (isImageFitToScreen) {
        img.style.maxWidth = '100%';
        img.style.maxHeight = '100%';
        img.style.width = 'auto';
        img.style.height = 'auto';
        img.style.cursor = 'zoom-in';
    } else {
        img.style.maxWidth = 'none';
        img.style.maxHeight = 'none';
        if (img.naturalWidth) {
             img.style.width = (img.naturalWidth * imageZoomLevel) + 'px';
             img.style.height = 'auto';
        }
        img.style.cursor = 'grab';
    }
};

window.zoomImage = function(delta) {
    const img = document.getElementById('imagePreview');
    if (!img) return;

    if (isImageFitToScreen) {
        isImageFitToScreen = false;
        // Calculate starting zoom level based on current visual size vs natural size
        const currentVisualWidth = img.width;
        const naturalWidth = img.naturalWidth || currentVisualWidth;
        // Start zoom from current visual scale + delta
        if (naturalWidth > 0) {
            imageZoomLevel = (currentVisualWidth / naturalWidth) + delta;
        } else {
            imageZoomLevel = 1.0 + delta;
        }
    } else {
        imageZoomLevel += delta;
    }

    if (imageZoomLevel < 0.1) imageZoomLevel = 0.1;
    updateImageStyle();
};

window.resetZoom = function() {
    isImageFitToScreen = true;
    imageZoomLevel = 1.0;
    updateImageStyle();
};

// Full-Featured Address Management Script
document.addEventListener('DOMContentLoaded', function () {
    (async () => {
    // --- Configuration & Global State ---
    const thaiDataUrl = "/thai-addresses"; // Hardcoded URL
    let thaiAddressData = [];
    let dataLoaded = false;
    const addressModalEl = document.getElementById('addressModal');
    if (!addressModalEl) return; // Exit if the modal isn't on the page

    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const saveBtn = document.getElementById('saveAddressBtn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // --- Form Fields ---
    const fields = {
        id: document.getElementById('address_id'),
        addressable_id: document.getElementById('addressable_id'),
        addressable_type: document.getElementById('addressable_type'),
        type: document.getElementById('address_type'),
        addrNo: document.getElementById('addrNo'),
        addrNoEn: document.getElementById('addrNoEn'),
        addrMoo: document.getElementById('addrMoo'),
        addrMooEn: document.getElementById('addrMooEn'),
        addrSoi: document.getElementById('addrSoi'),
        addrSoiEn: document.getElementById('addrSoiEn'),
        addrRoad: document.getElementById('addrRoad'),
        addrRoadEn: document.getElementById('addrRoadEn'),
        addrProvince: document.getElementById('addrProvince'),
        addrProvinceEn: document.getElementById('addrProvinceEn'),
        addrDistrict: document.getElementById('addrDistrict'),
        addrDistrictEn: document.getElementById('addrDistrictEn'),
        addrSubDistrict: document.getElementById('addrSubDistrict'),
        addrSubDistrictEn: document.getElementById('addrSubDistrictEn'),
        addrZipCode: document.getElementById('addrZipCode')
    };

    await fetchThaiAddressData();

    // --- Data Loading ---
    async function fetchThaiAddressData() {
        if (dataLoaded) return;
        try {
            const response = await fetch(thaiDataUrl);
            if (!response.ok) throw new Error('Network response was not ok.');
            thaiAddressData = await response.json();
            dataLoaded = true;
            populateProvinces();
        } catch (error) {
            console.error('Failed to fetch Thai address data:', error);
            showToast('{{ __('Failed to load address data') }}', 'danger');
        }
    }

    // --- Dropdown Population ---
    function populateProvinces() {
        fields.addrProvince.innerHTML = '<option value="">{{ __('Select Province') }}</option>';
        const uniqueProvinces = [...new Map(thaiAddressData.map(item => [item['province_th'].trim(), item])).values()];
        uniqueProvinces.sort((a, b) => a.province_th.trim().localeCompare(b.province_th.trim(), 'th'));
        uniqueProvinces.forEach(item => {
            const option = new Option(item.province_th.trim(), item.province_th.trim());
            fields.addrProvince.add(option);
        });

        // Notify Alpine.js about options update
        fields.addrProvince.dispatchEvent(new Event('options-updated'));
    }

    function populateDistricts(province) {
        fields.addrDistrict.innerHTML = '<option value="">{{ __('Select District') }}</option>';
        fields.addrSubDistrict.innerHTML = '<option value="">{{ __('Select Sub-district') }}</option>'; // Reset sub-districts
        fields.addrDistrict.disabled = true;
        fields.addrSubDistrict.disabled = true;

        if (!province) {
            // Trigger Alpine.js observer explicitly if returning early
            fields.addrDistrict.dispatchEvent(new Event('options-updated'));
            fields.addrSubDistrict.dispatchEvent(new Event('options-updated'));
            fields.addrDistrict.dispatchEvent(new Event('change'));
            fields.addrSubDistrict.dispatchEvent(new Event('change'));
            return;
        }

        const districts = [...new Set(thaiAddressData.filter(d => d.province_th.trim() === province.trim()).map(d => d.district_th.trim()))];
        districts.sort((a, b) => a.localeCompare(b, 'th'));
        districts.forEach(district => {
            const option = new Option(district, district);
            fields.addrDistrict.add(option);
        });

        if (districts.length > 0) {
            fields.addrDistrict.disabled = false;
        }

        // Notify Alpine.js about the new options and state change
        fields.addrDistrict.dispatchEvent(new Event('options-updated'));
        fields.addrDistrict.dispatchEvent(new Event('change'));
        fields.addrSubDistrict.dispatchEvent(new Event('options-updated'));
        fields.addrSubDistrict.dispatchEvent(new Event('change'));
    }

    function populateSubDistricts(province, district) {
        fields.addrSubDistrict.innerHTML = '<option value="">{{ __('Select Sub-district') }}</option>';
        fields.addrSubDistrict.disabled = true;

        if (!province || !district) {
            fields.addrSubDistrict.dispatchEvent(new Event('options-updated'));
            fields.addrSubDistrict.dispatchEvent(new Event('change'));
            return;
        }

        const subDistricts = thaiAddressData.filter(d => d.province_th.trim() === province.trim() && d.district_th.trim() === district.trim());
        const uniqueSubDistricts = [...new Map(subDistricts.map(item => [item['subdistrict_th'].trim(), item])).values()];
        uniqueSubDistricts.sort((a, b) => a.subdistrict_th.trim().localeCompare(b.subdistrict_th.trim(), 'th'));
        uniqueSubDistricts.forEach(sub => {
            const option = new Option(sub.subdistrict_th.trim(), sub.subdistrict_th.trim());
            fields.addrSubDistrict.add(option);
        });

        if (uniqueSubDistricts.length > 0) {
            fields.addrSubDistrict.disabled = false;
        }

        // Notify Alpine.js
        fields.addrSubDistrict.dispatchEvent(new Event('options-updated'));
        fields.addrSubDistrict.dispatchEvent(new Event('change'));
    }

    // --- Event Listeners for Dropdowns ---
    fields.addrProvince.addEventListener('change', function() {
        populateDistricts(this.value);
        const selectedData = thaiAddressData.find(d => d.province_th.trim() === this.value.trim());
        fields.addrProvinceEn.value = selectedData ? selectedData.province_en.trim() : '';
        fields.addrDistrictEn.value = '';
        fields.addrSubDistrictEn.value = '';
        fields.addrZipCode.value = '';
    });

    fields.addrDistrict.addEventListener('change', function() {
        populateSubDistricts(fields.addrProvince.value, this.value);
        const selectedData = thaiAddressData.find(d => d.province_th.trim() === fields.addrProvince.value.trim() && d.district_th.trim() === this.value.trim());
        fields.addrDistrictEn.value = selectedData ? selectedData.district_en.trim() : '';
        fields.addrSubDistrictEn.value = '';
        fields.addrZipCode.value = '';
    });

    fields.addrSubDistrict.addEventListener('change', function() {
        const selectedData = thaiAddressData.find(d =>
            d.province_th.trim() === fields.addrProvince.value.trim() &&
            d.district_th.trim() === fields.addrDistrict.value.trim() &&
            d.subdistrict_th.trim() === this.value.trim()
        );
        if (selectedData) {
            fields.addrSubDistrictEn.value = selectedData.subdistrict_en.trim();
            fields.addrZipCode.value = selectedData.zip_code ? String(selectedData.zip_code).trim() : '';
        }
    });

    // --- Modal Opening Logic ---
    addressModalEl.addEventListener('show.bs.modal', function(e) {
        const button = e.relatedTarget;
        if (!button) return;

        const isAddButton = button.matches('.add-address-btn') || button.matches('.temp-edit-address-btn') === false && button.closest('.add-address-btn');
        const isEditButton = button.matches('.edit-address-btn') || button.closest('.edit-address-btn');

        // Identify the actual button element in case a child element (like an icon) was clicked
        const targetBtn = isAddButton ? (button.matches('.add-address-btn') ? button : button.closest('.add-address-btn')) :
                          isEditButton ? (button.matches('.edit-address-btn') ? button : button.closest('.edit-address-btn')) : null;

        if (isAddButton && targetBtn) {
            addressForm.reset();
            fields.id.value = '';
            fields.addressable_id.value = targetBtn.dataset.addressableId;
            if (targetBtn.dataset.addressableType) {
                fields.addressable_type.value = targetBtn.dataset.addressableType;
            } else {
                fields.addressable_type.value = 'App\\Models\\Employer';
            }
            fields.type.value = targetBtn.dataset.type;

            // Re-trigger Alpine's reactivity by firing a change event
            fields.addrProvince.dispatchEvent(new Event('change'));
            fields.addrDistrict.dispatchEvent(new Event('change'));
            fields.addrSubDistrict.dispatchEvent(new Event('change'));

            document.getElementById('addressModalLabel').textContent = '{{ __('Add New Address') }}';
        } else if (isEditButton && targetBtn) {
            const addressId = targetBtn.dataset.addressId;
            document.getElementById('addressModalLabel').textContent = '{{ __('Loading...') }}';

            // Disable save button while loading
            saveBtn.disabled = true;

            // Perform fetch without blocking the modal rendering (no await in the event listener)
            fetch(`/addresses/${addressId}/edit`)
                .then(response => {
                    if (!response.ok) throw new Error('Failed to fetch address data.');
                    return response.json();
                })
                .then(data => {
                    addressForm.reset();
                    for (const key in data) {
                        if (fields[key]) {
                           fields[key].value = data[key];
                        }
                    }

                    populateDistricts(data.addrProvince);
                    fields.addrDistrict.value = data.addrDistrict;

                    populateSubDistricts(data.addrProvince, data.addrDistrict);
                    fields.addrSubDistrict.value = data.addrSubDistrict;

                    // Trigger Alpine.js to re-read values
                    fields.addrProvince.dispatchEvent(new Event('change'));
                    fields.addrDistrict.dispatchEvent(new Event('change'));
                    fields.addrSubDistrict.dispatchEvent(new Event('change'));

                    document.getElementById('addressModalLabel').textContent = '{{ __('Edit Address') }}';
                })
                .catch(error => {
                    console.error('Error fetching address for edit:', error);
                    showToast('{{ __('Failed to fetch address data') }}', 'danger');
                    addressModal.hide();
                })
                .finally(() => {
                    saveBtn.disabled = false;
                });
        }
    });

    // --- Save Logic ---
    saveBtn.addEventListener('click', async function() {
        const formData = new FormData(addressForm);
        const addressId = fields.id.value;
        const url = addressId ? `/addresses/${addressId}` : '/addresses';
        const method = 'POST';

        if (addressId) {
            formData.append('_method', 'PUT');
        }

        try {
            saveBtn.disabled = true;
            saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ __('Saving...') }}`;

            const response = await fetch(url, {
                method: method,
                body: formData,
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });

            const result = await response.json();

            if (!response.ok) {
                if (response.status === 422 && result.errors) {
                     let errorMsg = Object.values(result.errors).flat().join('\\n');
                     throw new Error(errorMsg);
                }
                throw new Error(result.message || '{{ __('An unknown error occurred') }}');
            }

            showToast(result.message || '{{ __('Address saved successfully') }}', 'success');
            addressModal.hide();

            setTimeout(() => location.reload(), 1500);

        } catch (error) {
            console.error('Save address error:', error);
            showToast(error.message || '{{ __('Error saving address') }}', 'danger');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '{{ __('Save') }}';
        }
    });

    addressModalEl.addEventListener('hidden.bs.modal', function () {
        addressForm.reset();
        populateDistricts('');
        document.getElementById('addressModalLabel').textContent = '{{ __('Add New Address') }}';
    });
    })();
});
</script>

<script>
// --- Global Selection Manager (Persists across pages/searches) ---
const STORAGE_KEY = 'selectedEmployeeData';

// Helper: Get all stored data
window.getGlobalSelectedData = function() {
    const stored = sessionStorage.getItem(STORAGE_KEY);
    try {
        return stored ? JSON.parse(stored) : [];
    } catch (e) {
        console.error('Error parsing selectedEmployeeData', e);
        return [];
    }
};

// Helper: Get just IDs (compatibility)
window.getGlobalSelectedIds = function() {
    return window.getGlobalSelectedData().map(item => item.id);
};

document.addEventListener('DOMContentLoaded', function () {
    // Stores array of objects: { id: "1", employer_id: "5" }
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
    const bulkActionBar = document.querySelector('.bulk-action-bar');
    const selectedCountSpan = document.getElementById('selected-count');
    const bulkActionButton = bulkActionBar ? bulkActionBar.querySelector('button') : null;

    // Helper: Save data
    window.setGlobalSelectedData = function(data) {
        // Ensure uniqueness by ID
        const unique = [];
        const map = new Map();
        for (const item of data) {
            if(!map.has(String(item.id))) {
                map.set(String(item.id), true);
                unique.push(item);
            }
        }
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(unique));
        updateUI();
    };

    // Helper: Add items (accepts array of {id, employer_id})
    function addItems(newItems) {
        const current = window.getGlobalSelectedData();
        // Filter out existing items that are being re-added (to update them with potentially newer data)
        const newIds = newItems.map(i => String(i.id));
        const currentFiltered = current.filter(i => !newIds.includes(String(i.id)));
        const combined = [...currentFiltered, ...newItems];
        window.setGlobalSelectedData(combined);
    }

    // Helper: Remove items by ID
    function removeItemsByIds(idsToRemove) {
        const current = window.getGlobalSelectedData();
        const filtered = current.filter(item => !idsToRemove.includes(String(item.id)));
        window.setGlobalSelectedData(filtered);
    }
    window.removeItemsByIds = removeItemsByIds;

    // Clear all selections
    window.clearGlobalSelection = function() {
        sessionStorage.removeItem(STORAGE_KEY);
        // Uncheck all visible
        document.querySelectorAll('.employee-checkbox').forEach(cb => cb.checked = false);
        if(selectAllCheckbox) selectAllCheckbox.checked = false;
        updateUI();
    };

    // UI Updater
    function updateUI() {
        const allData = window.getGlobalSelectedData();
        const count = allData.length;
        const allIds = allData.map(item => String(item.id));

        if (bulkActionBar) {
            if (count > 0) {
                bulkActionBar.style.display = 'flex';
                if (selectedCountSpan) selectedCountSpan.textContent = count;
                if (bulkActionButton) bulkActionButton.disabled = false;
            } else {
                bulkActionBar.style.display = 'none';
                if (bulkActionButton) bulkActionButton.disabled = true;
            }
        }

        // Update View Selected Button Count (Global Badge)
        const viewSelectedBadge = document.getElementById('view-selected-count');
        if (viewSelectedBadge) viewSelectedBadge.textContent = count;

        // Sync individual checkboxes dynamically across the page
        const currentCheckboxes = document.querySelectorAll('.employee-checkbox');
        let visibleCount = 0;
        let visibleCheckedCount = 0;

        currentCheckboxes.forEach(cb => {
            cb.checked = allIds.includes(String(cb.value));

            // Check if it's visible to determine "Select All" state
            const cardWrapper = cb.closest('.item-card-wrapper') || cb.closest('.employee-card-wrapper') || cb.closest('tr');
            const isHidden = cardWrapper && (cardWrapper.classList.contains('d-none') || cardWrapper.classList.contains('hide-cancelled') || cardWrapper.style.display === 'none');

            if (!isHidden) {
                visibleCount++;
                if (cb.checked) {
                    visibleCheckedCount++;
                }
            }
        });

        // Sync "Select All" checkbox state based on VISIBLE items
        if (selectAllCheckbox) {
            if (visibleCount > 0 && visibleCheckedCount === visibleCount) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else if (visibleCheckedCount > 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
        }

        // Also fire a custom event so other components (like employer-select-all) can update
        document.dispatchEvent(new Event('global-selection-updated'));
    }

    // --- Initialization ---

    // Note: We use Event Delegation for the individual checkboxes to support dynamic content (AJAX)
    // The 'employeeCheckboxes' variable defined above is static. We will re-query it when needed inside updateUI.

    // Helper to extract rich data
    function getEmployeeData(cb) {
        return {
            id: cb.value,
            employer_id: cb.dataset.employerId || '',
            name_th: cb.dataset.nameTh || '',
            name_en: cb.dataset.nameEn || '',
            title_th: cb.dataset.titleTh || '',
            title_en: cb.dataset.titleEn || '',
            nationality: cb.dataset.nationality || '',
            photo: cb.dataset.photo || '',
            employer_name: cb.dataset.employerName || '',
            title_th: cb.dataset.titleTh || '',
            title_en: cb.dataset.titleEn || '',
            nationality: cb.dataset.nationality || '',
            country_code: cb.dataset.countryCode || '',
            gender: cb.dataset.gender || '',
            insurance_type: cb.dataset.insuranceType || '',
            passport: cb.dataset.passport || '',
            production_item_id: cb.dataset.productionItemId || ''
        };
    }

    // Expose UI refresh globally (for AJAX loaded content)
    window.refreshGlobalSelectionUI = function() {
        const savedIds = window.getGlobalSelectedIds();
        // Re-query checkboxes to include newly added ones
        const currentCheckboxes = document.querySelectorAll('.employee-checkbox');
        currentCheckboxes.forEach(cb => {
            if (savedIds.includes(String(cb.value))) {
                cb.checked = true;
            } else {
                cb.checked = false; // Ensure unchecked if not in state
            }
        });
        updateUI();
    };

    // 1. Restore state from storage on load
    window.refreshGlobalSelectionUI();

    // 2. Handle Individual Checkbox Changes (Event Delegation)
    document.body.addEventListener('change', function (e) {
        if (e.target.matches('.employee-checkbox')) {
            const checkbox = e.target;
            const data = getEmployeeData(checkbox);
            if (checkbox.checked) {
                addItems([data]);
            } else {
                removeItemsByIds([data.id]);
            }
        }
    });

    // 3. Handle "Select All" Checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            // Re-query currently visible checkboxes
            const currentCheckboxes = document.querySelectorAll('.employee-checkbox');

            const visibleCheckboxes = Array.from(currentCheckboxes).filter(cb => {
                const cardWrapper = cb.closest('.item-card-wrapper') || cb.closest('.employee-card-wrapper') || cb.closest('tr');
                const isHidden = cardWrapper && (cardWrapper.classList.contains('d-none') || cardWrapper.classList.contains('hide-cancelled') || cardWrapper.style.display === 'none');
                return !isHidden;
            });

            const visibleItems = visibleCheckboxes.map(cb => getEmployeeData(cb));
            const visibleIds = visibleItems.map(item => item.id);

            if (this.checked) {
                // Check all visible and add to storage
                visibleCheckboxes.forEach(cb => cb.checked = true);
                addItems(visibleItems);
            } else {
                // Uncheck all visible and remove from storage
                visibleCheckboxes.forEach(cb => cb.checked = false);
                removeItemsByIds(visibleIds);
            }

            // Trigger custom event for specific employers
            document.dispatchEvent(new Event('global-selection-updated'));
        });
    }

    // --- New Feature: View Selected Modal Logic ---
    window.openViewSelectedModal = function(customData = null, customTitle = null, customStorageKey = null) {
        const data = customData || window.getGlobalSelectedData();
        const container = document.getElementById('selected-items-container');
        const countBadge = document.getElementById('modal-selected-count');
        const modalEl = document.getElementById('viewSelectedModal');
        const titleEl = document.getElementById('viewSelectedModalLabel');

        if (!container || !modalEl) {
            console.error('Modal elements not found');
            return;
        }

        if (customTitle && titleEl) {
            titleEl.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i>${customTitle}`;
        } else if (titleEl) {
            titleEl.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i>{{ __('Selected Employees') }}`;
        }

        if (countBadge) countBadge.textContent = data.length;

        container.innerHTML = ''; // Clear

        if (data.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-5 text-muted w-100">
                    <i class="bi bi-check2-circle display-1 opacity-25"></i>
                    <p class="mt-3">{{ __('No employees selected') }}</p>
                </div>
            `;
        } else {
            data.forEach(item => {
                const titleTh = item.title_th || '';
                const nameTh = item.name_th || '-';
                const fullNameTh = titleTh + ' ' + nameTh;

                const titleEn = item.title_en || '';
                const nameEn = item.name_en || '-';
                const fullNameEn = titleEn + ' ' + nameEn;

                const nationality = item.nationality || '-';
                const employer = item.employer_name || '-';
                const photo = item.photo || 'https://placehold.co/50x50/e2e8f0/6c757d?text=PIC';

                const cardHtml = `
                    <div class="col" id="modal-item-${item.id}">
                        <div class="card h-100 shadow-sm border position-relative hover-shadow transition-all">
                            <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 rounded-circle shadow-sm bg-white"
                                    onclick="window.removeSelectedItemFromModal('${item.id}', '${customStorageKey || ''}')"
                                    title="{{ __('Remove') }}" style="width: 28px; height: 28px; padding: 0; z-index: 5;">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            <div class="card-body d-flex align-items-center gap-3 p-3">
                                <div class="flex-shrink-0">
                                    <img src="${photo}" class="rounded-circle shadow-sm border" width="60" height="60" style="object-fit: cover;">
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-bold text-dark text-truncate" title="${fullNameEn}">${fullNameEn}</div>
                                    <div class="text-muted small text-truncate" title="${fullNameTh}">${fullNameTh}</div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="badge bg-light text-dark border"><i class="bi bi-flag me-1"></i>${nationality}</span>
                                    </div>
                                    <div class="small text-primary mt-1 text-truncate" title="${employer}">
                                        <i class="bi bi-building me-1"></i>${employer}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.innerHTML += cardHtml;
            });
        }

        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    };

    window.removeSelectedItemFromModal = function(id, customStorageKey = null) {
        // removeItemsByIds is defined inside DOMContentLoaded scope previously, so it's not global.
        // We need to access the logic. But wait, `removeItemsByIds` was defined inside the closure.
        // We need to expose it or reimplement it.
        // Let's reimplement a simple version here relying on `getGlobalSelectedData` and `setGlobalSelectedData` which ARE global.

        let current = [];
        let key = STORAGE_KEY;

        if (customStorageKey) {
            key = customStorageKey;
            try {
                const stored = sessionStorage.getItem(key);
                current = stored ? JSON.parse(stored) : [];
            } catch (e) {
                console.error('Error parsing custom storage', e);
                current = [];
            }
        } else {
            current = window.getGlobalSelectedData();
        }

        const filtered = current.filter(item => String(item.id) !== String(id));

        if (customStorageKey) {
            sessionStorage.setItem(key, JSON.stringify(filtered));
            // Trigger a custom event for local UI updates
            window.dispatchEvent(new CustomEvent('custom-selection-changed', { detail: { key: key } }));
        } else {
            window.setGlobalSelectedData(filtered);
            // Sync main UI (Checkboxes)
            if(window.refreshGlobalSelectionUI) {
                window.refreshGlobalSelectionUI();
            }
        }

        // Remove from DOM immediately
        const el = document.getElementById(`modal-item-${id}`);
        if(el) {
            el.remove();
        }

        // Update Modal Count
        const countBadge = document.getElementById('modal-selected-count');
        if (countBadge) countBadge.textContent = filtered.length;

        // If empty, show empty state
        if (filtered.length === 0) {
            const container = document.getElementById('selected-items-container');
            if (container) {
                container.innerHTML = `
                    <div class="col-12 text-center py-5 text-muted w-100">
                        <i class="bi bi-check2-circle display-1 opacity-25"></i>
                        <p class="mt-3">{{ __('No employees selected') }}</p>
                    </div>
                `;
            }
            // Optional: Close modal if empty? No, user might want to see it cleared.
        }
    };
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.body.addEventListener('submit', function(e) {
        if (e.target.matches('.delete-employee-form')) {
            e.preventDefault();
            const form = e.target;

            Swal.fire({
                title: '{{ __('Are you sure?') }}',
                text: "{{ __('This will move the employee to the Central Trash. You can recover them later.') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ __('Yes, move to Trash!') }}',
                cancelButtonText: '{{ __('Cancel') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    const action = form.getAttribute('action');
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    fetch(action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: new FormData(form)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                             window.location.reload();
                        } else {
                            showToast(data.message || '{{ __('An error occurred while trying to delete the employee.') }}', 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Delete Error:', error);
                        showToast('{{ __('A network error occurred. Please try again.') }}', 'danger');
                    });
                }
            });
        }
    });
});
</script>

<!-- Scroll to Top Buttons -->
<div class="scroll-to-top left" id="scrollToTopLeft"><i class="bi bi-chevron-up"></i></div>
<div class="scroll-to-top right" id="scrollToTopRight"><i class="bi bi-chevron-up"></i></div>

<!-- Side Drawer Handle -->
<div id="drawer-handle" class="drawer-handle" title="Open Menu">
    <i class="bi bi-chevron-right"></i>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Scroll to Top Logic ---
    const scrollToTopLeft = document.getElementById('scrollToTopLeft');
    const scrollToTopRight = document.getElementById('scrollToTopRight');

    function checkScroll() {
        if (window.scrollY > 200) {
            scrollToTopLeft.classList.add('show');
            scrollToTopRight.classList.add('show');
        } else {
            scrollToTopLeft.classList.remove('show');
            scrollToTopRight.classList.remove('show');
        }
    }

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    window.addEventListener('scroll', checkScroll);
    scrollToTopLeft.addEventListener('click', scrollToTop);
    scrollToTopRight.addEventListener('click', scrollToTop);

    // --- Drawer Handle Logic ---
    const drawerHandle = document.getElementById('drawer-handle');
    const sidebarElement = document.getElementById('sidebar');

    if (drawerHandle && sidebarElement) {
        // Show handle when scrolled down
        window.addEventListener('scroll', function() {
            // Check if sidebar is currently open to avoid showing handle over it (though CSS z-index/transform handles visibility mostly)
            const isSidebarOpen = sidebarElement.classList.contains('show');

            if (window.scrollY > 100 && !isSidebarOpen) {
                drawerHandle.classList.add('show');
            } else {
                drawerHandle.classList.remove('show');
            }
        });

        // Open sidebar on click
        drawerHandle.addEventListener('click', function() {
            const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(sidebarElement);
            bsOffcanvas.show();
        });

        // Hide handle when sidebar is open
        sidebarElement.addEventListener('show.bs.offcanvas', function () {
            drawerHandle.classList.remove('show');
        });

        // Re-check scroll position when sidebar closes
        sidebarElement.addEventListener('hidden.bs.offcanvas', function () {
            if (window.scrollY > 100) {
                drawerHandle.classList.add('show');
            }
        });
    }
});
</script>

<script>
    // Global Logout Handler for SweetAlert
    document.addEventListener('DOMContentLoaded', function() {
        const logoutBtn = document.getElementById('btn-logout');
        if(logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: '{{ __('Ready to Leave?') }}',
                    text: "{{ __('Select "Logout" below if you are ready to end your current session.') }}",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#F97316',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ __('Logout') }}',
                    cancelButtonText: '{{ __('Cancel') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('logout-form').submit();
                    }
                });
            });
        }
    });

    // Global Highlight & Scroll Handler
    document.addEventListener('DOMContentLoaded', function() {
        if (window.location.hash) {
            // Decouple from execution flow to ensure DOM is fully ready
            setTimeout(() => {
                const id = window.location.hash.substring(1);
                const el = document.getElementById(id);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('highlight');
                    setTimeout(() => {
                        el.classList.remove('highlight');
                    }, 5000);
                }
            }, 300);
        }
    });
</script>

</body>
</html>
