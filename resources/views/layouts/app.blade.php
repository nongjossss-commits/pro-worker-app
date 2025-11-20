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
            margin-bottom: 2rem;
            text-align: center;
            font-size: 1.75rem !important;
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
        .employee-card.highlight {
            border: 2px solid var(--bs-primary-dark);
            box-shadow: 0 0 12px rgba(var(--bs-primary-rgb), 0.4);
            background-color: #fffbeb;
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
    </style>
</head>
<body>

    <div class="main-layout">
        <aside id="sidebar" class="offcanvas offcanvas-start" tabindex="-1" aria-labelledby="sidebarLabel">
            {{-- START: Offcanvas Header (Mobile Only) --}}
            <div class="offcanvas-header d-lg-none">
                <h5 class="offcanvas-title" id="sidebarLabel"><i class="bi bi-building-fill-gear"></i> Company Records</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
            </div>
            {{-- END: Offcanvas Header --}}
            <div class="offcanvas-body d-flex flex-column p-0">
            <a class="navbar-brand fs-4" href="#"><i class="bi bi-building-fill-gear"></i> Company Records</a>
            <div class="list-group" id="main-nav">
                @can('view-dashboard')
                <a href="#" class="list-group-item list-group-item-action"><i class="bi bi-pie-chart-fill me-2"></i>{{ __('Dashboard') }}</a>
                @endcan
                @can('view-notifications')
                    <a href="{{ route('notifications.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-bell-fill me-2"></i>แจ้งเตือน</span>
                            @if(isset($totalNotificationCount) && $totalNotificationCount > 0)
                                <span class="badge bg-danger rounded-pill">{{ $totalNotificationCount }}</span>
                            @endif
                        </div>
                    </a>
                    @can('manage-tickets')
                    <a href="{{ route('admin.incomplete_employees.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.incomplete_employees.*') ? 'active' : '' }}" style="padding-left: 2.5rem;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-exclamation-octagon-fill me-2"></i>ข้อมูลไม่ครบถ้วน</span>
                            @if(isset($incompleteCount) && $incompleteCount > 0)
                                <span class="badge bg-warning text-dark rounded-pill">{{ $incompleteCount }}</span>
                            @endif
                        </div>
                    </a>
                    @endcan
                @endcan
                {{-- START V2.4: Smart Ticket Links --}}
                {{-- V2.4: Admin/Staff Ticket Inbox --}}
                {{-- Visible if the user has 'manage-tickets' permission. This takes precedence. --}}
                @can('manage-tickets')
                <a href="{{ route('admin.tickets.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.tickets.index') ? 'active' : '' }}">
                <i class="bi bi-inbox-fill me-2"></i>{{ __('Ticket Inbox') }}
                </a>
                <a href="{{ route('admin.tickets.create') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.tickets.create') ? 'active' : '' }}" style="padding-left: 2.5rem;">
                    <i class="bi bi-plus-circle me-2"></i>{{ __('Create New Ticket (Admin)') }}
                </a>
                @else
                {{-- V2.4: Employer Ticket Menu --}}
                {{-- Visible ONLY if the user CANNOT 'manage-tickets' AND is an 'employer'. --}}
                @role('employer')
                <a href="{{ route('tickets.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                <i class="bi bi-ticket-detailed-fill me-2"></i>{{ __('Submit Request/Track Work') }}
                </a>
                @endrole
                @endcan
                {{-- END V2.4: Smart Ticket Links --}}
                <hr>
                @can('view-employers')
                <a href="{{ route('employers.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('employers.*') ? 'active' : '' }}"><i class="bi bi-person-vcard-fill me-2"></i>{{ __('Employers') }}</a>
                @endcan
                @can('view-employees')
                <a href="{{ route('employees.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill me-2"></i>{{ __('Employees') }}
                </a>
                <a href="{{ route('employees.history') }}" class="list-group-item list-group-item-action {{ request()->routeIs('employees.history') ? 'active' : '' }}" style="padding-left: 2.5rem;">
                    <i class="bi bi-person-badge me-2"></i>{{ __('Employment History') }}
                </a>
                @endcan
                @can('view-importers')
                <a href="{{ route('importers.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('importers.*') ? 'active' : '' }}"><i class="bi bi-box-arrow-in-down-left me-2"></i>{{ __('Importers') }}</a>
                @endcan
                @can('view-agents')
                <a href="{{ route('agents.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('agents.*') ? 'active' : '' }}"><i class="bi bi-person-square me-2"></i>{{ __('Agents') }}</a>
                @endcan
                @can('view-delegates')
                <a href="{{ route('delegates.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('delegates.*') ? 'active' : '' }}"><i class="bi bi-people-fill me-2"></i>{{ __('Delegates') }}</a>
                @endcan

                @can('manage-users')
                <a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="bi bi-person-fill-gear me-2"></i>{{ __('User Management') }}</a>
                @endcan
                @canany(['manage-roles', 'manage-settings'])
                <hr>
                <a href="{{ route('admin.roles_permissions.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.roles_permissions.*') ? 'active' : '' }}"><i class="bi bi-shield-lock-fill me-2"></i>{{ __('Roles & Permissions') }}</a>
                @endcanany
                @can('view-trash')
                    <a class="list-group-item list-group-item-action {{ request()->routeIs('admin.trash.index') ? 'active' : '' }}" href="{{ route('admin.trash.index') }}">
                        <i class="bi bi-trash-fill me-2"></i>
                        {{ __('Central Trash') }}
                    </a>
                @endcan
            </div>
            <div class="mt-auto">
                <hr>
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="bi bi-person-circle fs-2"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">{{ Auth::user()->name ?? __('User Name') }}</h6>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <a href="{{ route('logout') }}"
                               onclick="event.preventDefault(); this.closest('form').submit();"
                               class="text-muted small">
                                {{ __('Logout') }}
                            </a>
                        </form>
                    </div>
                </div>
            </div>
            </div>
        </aside>

        <main id="main-content" style="position: relative; z-index: 1;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                {{-- START: V.2 PC Toggle Button (Visible LG and up) --}}
                <button class="btn btn-primary d-none d-lg-block" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                    <i class="bi bi-list me-2"></i> {{ __('Open Menu') }}
                </button>
                {{-- END: V.2 PC Toggle Button --}}

                <div class="d-flex align-items-center ms-auto">
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
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'th') }}">🇹🇭 ไทย (Thai)</a></li>
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'en') }}">🇺🇸 English</a></li>
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'zh') }}">🇨🇳 中文 (Chinese)</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            {{-- START: Mobile Top-Bar (d-lg-none) --}}
            <nav class="navbar bg-white rounded shadow-sm mb-4 d-lg-none">
                <div class="container-fluid">
                    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <a class="navbar-brand fs-4" href="#"><i class="bi bi-building-fill-gear"></i> Company Records</a>
                </div>
            </nav>
            {{-- END: Mobile Top-Bar --}}
            @yield('debug-tracker')
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
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
                            <div class="mb-3">
                                <label for="new_due_date" class="form-label">{{ __('Select new expiry date') }}:</label>
                                <input type="date" class="form-control" id="new_due_date" name="new_due_date" required>
                            </div>
                            <div class="mb-3">
                                <label for="attachment" class="form-label">{{ __('Attach document (if any)') }}:</label>
                                <input type="file" class="form-control" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png">
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

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/central-delete-handler.js'])

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const renewModal = document.getElementById('renewNotificationModal');
            if (renewModal) {
                renewModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const notificationId = button.getAttribute('data-notification-id');
                    const form = document.getElementById('renew-form');
                    if(notificationId) {
                        form.action = `/notifications/${notificationId}/renew`;
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

<script>
// Full-Featured Address Management Script
document.addEventListener('DOMContentLoaded', function () {
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
        const uniqueProvinces = [...new Map(thaiAddressData.map(item => [item['province_th'], item])).values()];
        uniqueProvinces.sort((a, b) => a.province_th.localeCompare(b.province_th, 'th'));
        uniqueProvinces.forEach(item => {
            const option = new Option(item.province_th, item.province_th);
            fields.addrProvince.add(option);
        });
    }

    function populateDistricts(province) {
        fields.addrDistrict.innerHTML = '<option value="">{{ __('Select District') }}</option>';
        fields.addrSubDistrict.innerHTML = '<option value="">{{ __('Select Sub-district') }}</option>'; // Reset sub-districts
        if (!province) return;

        const districts = [...new Set(thaiAddressData.filter(d => d.province_th === province).map(d => d.district_th))];
        districts.sort((a, b) => a.localeCompare(b, 'th'));
        districts.forEach(district => {
            const option = new Option(district, district);
            fields.addrDistrict.add(option);
        });
    }

    function populateSubDistricts(province, district) {
        fields.addrSubDistrict.innerHTML = '<option value="">{{ __('Select Sub-district') }}</option>';
        if (!province || !district) return;

        const subDistricts = thaiAddressData.filter(d => d.province_th === province && d.district_th === district);
        subDistricts.sort((a, b) => a.subdistrict_th.localeCompare(b.subdistrict_th, 'th'));
        subDistricts.forEach(sub => {
            const option = new Option(sub.subdistrict_th, sub.subdistrict_th);
            fields.addrSubDistrict.add(option);
        });
    }

    // --- Event Listeners for Dropdowns ---
    fields.addrProvince.addEventListener('change', function() {
        populateDistricts(this.value);
        const selectedData = thaiAddressData.find(d => d.province_th === this.value);
        fields.addrProvinceEn.value = selectedData ? selectedData.province_en : '';
        fields.addrDistrictEn.value = '';
        fields.addrSubDistrictEn.value = '';
        fields.addrZipCode.value = '';
    });

    fields.addrDistrict.addEventListener('change', function() {
        populateSubDistricts(fields.addrProvince.value, this.value);
        const selectedData = thaiAddressData.find(d => d.province_th === fields.addrProvince.value && d.district_th === this.value);
        fields.addrDistrictEn.value = selectedData ? selectedData.district_en : '';
        fields.addrSubDistrictEn.value = '';
        fields.addrZipCode.value = '';
    });

    fields.addrSubDistrict.addEventListener('change', function() {
        const selectedData = thaiAddressData.find(d =>
            d.province_th === fields.addrProvince.value &&
            d.district_th === fields.addrDistrict.value &&
            d.subdistrict_th === this.value
        );
        if (selectedData) {
            fields.addrSubDistrictEn.value = selectedData.subdistrict_en;
            fields.addrZipCode.value = selectedData.zip_code;
        }
    });

    // --- Modal Opening Logic ---
    addressModalEl.addEventListener('show.bs.modal', async function(e) {
        await fetchThaiAddressData();
        const button = e.relatedTarget;
        if (!button) return;

        const isAddButton = button.matches('.add-address-btn');
        const isEditButton = button.matches('.edit-address-btn');

        if (isAddButton) {
            addressForm.reset();
            fields.id.value = '';
            fields.addressable_id.value = button.dataset.addressableId;
            fields.addressable_type.value = 'App\\Models\\Employer';
            fields.type.value = button.dataset.type;
            document.getElementById('addressModalLabel').textContent = '{{ __('Add New Address') }}';
        } else if (isEditButton) {
            const addressId = button.dataset.addressId;
            document.getElementById('addressModalLabel').textContent = '{{ __('Loading...') }}';
            try {
                const response = await fetch(`/addresses/${addressId}`);
                if (!response.ok) throw new Error('Failed to fetch address data.');
                const data = await response.json();

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

                document.getElementById('addressModalLabel').textContent = '{{ __('Edit Address') }}';
            } catch (error) {
                console.error('Error fetching address for edit:', error);
                showToast('{{ __('Failed to fetch address data') }}', 'danger');
                addressModal.hide();
            }
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
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
    const bulkActionBar = document.querySelector('.bulk-action-bar');
    const selectedCountSpan = document.getElementById('selected-count');
    const bulkActionButton = bulkActionBar ? bulkActionBar.querySelector('button') : null;

    if (!selectAllCheckbox) return; // Exit if controls are not on this page

    function updateBulkActionBar() {
        const selectedCheckboxes = document.querySelectorAll('.employee-checkbox:checked');
        const count = selectedCheckboxes.length;

        if (count > 0) {
            bulkActionBar.style.display = 'flex';
            selectedCountSpan.textContent = count;
            bulkActionButton.disabled = false;
            // Sync select-all checkbox
            selectAllCheckbox.checked = (count === employeeCheckboxes.length);
        } else {
            bulkActionBar.style.display = 'none';
            bulkActionButton.disabled = true;
            selectAllCheckbox.checked = false;
        }
    }

    selectAllCheckbox.addEventListener('change', function () {
        employeeCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActionBar();
    });

    employeeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionBar);
    });
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

<script>
document.addEventListener('DOMContentLoaded', function () {
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
});
</script>

</body>
</html>
