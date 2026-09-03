<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pro Walker Labour')</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Flatpickr — opt-in only (input.js-flatpickr), same as the main app's layouts/app.blade.php -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    @php $brand = \App\Services\BrandService::current(); @endphp
    <style>
        :root {
            /* Primary orange — pulled from BrandService so this module follows
               the same per-installation branding as the main app. */
            --bs-primary: {{ $brand['primary_color'] }};
            --bs-primary-rgb: {{ \App\Services\BrandService::hexToRgb($brand['primary_color']) }};
            --bs-primary-dark: {{ \App\Services\BrandService::darken($brand['primary_color'], 0.12) }};
            --bs-primary-light: {{ $brand['accent_color'] }};
            /* Secondary green — Pro Walker Labor's own accent (BrandService has
               no green slot), used for success states/secondary actions. */
            --bs-success: #16A34A;
            --bs-success-rgb: 22, 163, 74;
            --labor-success-light: #22C55E;
            --brand-sidebar-bg: #FFFFFF;
            --bs-body-font-family: 'Inter', 'Sarabun', sans-serif;
            --bs-body-bg: #f8fafc;
            --bs-border-color: #e2e8f0;
        }
        #sidebar.offcanvas { --bs-offcanvas-bg: var(--brand-sidebar-bg); }

        body {
            font-family: 'Inter', 'Sarabun', sans-serif;
            background-color: var(--bs-body-bg);
        }

        .main-layout { display: flex; min-height: 100vh; }

        #sidebar {
            width: 260px;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
            --bs-offcanvas-width: 260px;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        #sidebar .offcanvas-body { padding: 1.5rem; }
        #sidebar .navbar-brand {
            font-weight: 700;
            color: var(--bs-primary);
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.35rem;
            text-decoration: none;
        }
        #sidebar .list-group-item {
            border: none;
            padding: 0.75rem 1rem;
            margin-bottom: 0.35rem;
            border-radius: 0.5rem;
            font-weight: 600;
            color: #475569;
            transition: background-color .15s ease-in-out, color .15s ease-in-out;
            font-size: 0.95rem;
        }
        #sidebar .list-group-item.active {
            color: #ffffff;
            background-image: linear-gradient(to right, var(--bs-primary-light), var(--bs-primary));
            box-shadow: 0 4px 6px -1px rgba(var(--bs-primary-rgb), 0.25), 0 2px 4px -2px rgba(var(--bs-primary-rgb), 0.25);
        }
        #sidebar .list-group-item:hover:not(.active) {
            background-color: #f1f5f9;
            color: #1e293b;
        }
        #sidebar .nav-section-label {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
            padding: 0 1rem;
            margin: 1rem 0 .4rem;
        }

        #main-content { flex-grow: 1; padding: 2rem; overflow-y: auto; }
        @media (max-width: 991.98px) { #main-content { padding: 1.25rem; } }

        .labor-topbar-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .content-section, .card, .stat-card {
            background-color: #ffffff;
            border-radius: .75rem;
            border: 1px solid var(--bs-border-color);
        }
        .stat-card { padding: 1rem 1.25rem; }

        .btn { border-radius: .5rem; font-weight: 600; }
        .btn-primary, .btn-success, .btn-danger, .btn-warning { box-shadow: 0 2px 4px rgba(0,0,0,.08); }
        .btn-success { --bs-btn-bg: var(--bs-success); --bs-btn-border-color: var(--bs-success); --bs-btn-hover-bg: #15803d; --bs-btn-hover-border-color: #15803d; }
        .badge.bg-success { background-color: var(--bs-success) !important; }

        .nav-tabs .nav-link { font-weight: 600; color: #64748b; border: none; border-bottom: 3px solid transparent; }
        .nav-tabs .nav-link.active { color: var(--bs-primary); border-bottom: 3px solid var(--bs-primary); background: none; }

        .table thead {
            --bs-table-bg: #f8fafc;
            color: #64748b;
            text-transform: uppercase;
            font-size: .75rem;
            letter-spacing: .05em;
            font-weight: 600;
        }
    </style>
    @stack('styles')

    <!-- Alpine.js (same as the main app — used for conditional form fields) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <div class="main-layout">
        <aside id="sidebar" class="offcanvas offcanvas-start" tabindex="-1" aria-labelledby="sidebarLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="sidebarLabel">
                    <a href="{{ route('labor.dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none text-reset">
                        <i class="bi bi-briefcase-fill" style="color: var(--bs-primary); font-size: 1.3rem;"></i> Pro Walker Labour
                    </a>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column p-0">
                <div class="list-group" id="labor-main-nav">
                    <a href="{{ route('labor.dashboard') }}" class="list-group-item list-group-item-action {{ request()->routeIs('labor.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i>{{ __('Dashboard') }}
                    </a>

                    <a href="{{ route('labor.company-documents.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('labor.company-documents.*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-arrow-down me-2"></i>{{ __('Company Documents') }}
                    </a>

                    @php
                        $isContractsActive = request()->routeIs('labor.contracts.create');
                        $isContractsSubActive = request()->routeIs(['labor.contracts.*', 'labor.contract-templates.*', 'labor.contract-reports.*']);
                    @endphp
                    <div class="list-group-item list-group-item-action p-0" style="border:none;">
                        <div class="d-flex align-items-stretch w-100">
                            <a href="{{ route('labor.contracts.create') }}" class="flex-grow-1 d-flex align-items-center {{ $isContractsActive ? 'active text-white' : 'text-dark' }}" style="padding:.75rem 1rem; text-decoration:none; border-radius:.5rem 0 0 .5rem; {{ $isContractsActive ? 'background-image: linear-gradient(to right, var(--bs-primary-light), var(--bs-primary));' : '' }}">
                                <i class="bi bi-file-earmark-text me-2"></i>{{ __('Contracts') }}
                            </a>
                            <button class="btn btn-link shadow-none {{ $isContractsActive ? 'text-white' : 'text-dark' }} d-flex align-items-center justify-content-center" type="button" data-bs-toggle="collapse" data-bs-target="#laborContractsSubMenu" aria-expanded="{{ $isContractsSubActive ? 'true' : 'false' }}" style="width:40px; padding:0; border-radius:0 .5rem .5rem 0; {{ $isContractsActive ? 'background-image: linear-gradient(to right, var(--bs-primary-light), var(--bs-primary));' : '' }}" onclick="this.querySelector('i').classList.toggle('bi-chevron-down'); this.querySelector('i').classList.toggle('bi-chevron-up');">
                                <i class="bi bi-chevron-{{ $isContractsSubActive ? 'up' : 'down' }}"></i>
                            </button>
                        </div>
                    </div>
                    <div class="collapse {{ $isContractsSubActive ? 'show' : '' }}" id="laborContractsSubMenu">
                        <a href="{{ route('labor.contracts.create') }}" class="list-group-item list-group-item-action {{ request()->routeIs('labor.contracts.create') ? 'active' : '' }}" style="padding-left:2.5rem;">
                            <i class="bi bi-file-earmark-plus me-2"></i>{{ __('Issue Contract') }}
                        </a>
                        <a href="{{ route('labor.contracts.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('labor.contracts.index') || request()->routeIs('labor.contracts.show') ? 'active' : '' }}" style="padding-left:2.5rem;">
                            <i class="bi bi-clock-history me-2"></i>{{ __('Contract History') }}
                        </a>
                        <a href="{{ route('labor.contracts.verify.form') }}" class="list-group-item list-group-item-action {{ request()->routeIs('labor.contracts.verify*') ? 'active' : '' }}" style="padding-left:2.5rem;">
                            <i class="bi bi-patch-check me-2"></i>{{ __('Verify Contract') }}
                        </a>
                        @role('super-admin')
                        <a href="{{ route('labor.contract-templates.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('labor.contract-templates.*') ? 'active' : '' }}" style="padding-left:2.5rem;">
                            <i class="bi bi-tools me-2"></i>{{ __('Manage Contract Templates') }}
                        </a>
                        <a href="{{ route('labor.contract-reports.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('labor.contract-reports.*') ? 'active' : '' }}" style="padding-left:2.5rem;">
                            <i class="bi bi-bar-chart-fill me-2"></i>{{ __('Contract Statistics') }}
                        </a>
                        @endrole
                        @hasanyrole('admin|super-admin')
                        <a href="{{ route('labor.contract-templates.master-template') }}" class="list-group-item list-group-item-action" style="padding-left:2.5rem;" target="_blank">
                            <i class="bi bi-file-earmark-text me-2"></i>{{ __('View Master Contract') }}
                        </a>
                        @endhasanyrole
                    </div>

                    @unless(auth()->user()->hasAnyRole(['labor-team', 'labor-member']))
                    <a href="{{ route('labor.reports.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('labor.reports.index') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-line me-2"></i>{{ __('Reports') }}
                    </a>
                    <a href="{{ route('labor.books.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('labor.books.*') ? 'active' : '' }}">
                        <i class="bi bi-journal-text me-2"></i>{{ __('Company Books') }}
                    </a>
                    @endunless

                    @can('manage-labor-ledger')
                        @php
                            $isFinanceActive = request()->routeIs('labor.charges.index');
                            $isFinanceSubActive = request()->routeIs(['labor.charges.*', 'labor.bills.*', 'labor.tax-invoices.*', 'labor.wht-certificates.*']);
                        @endphp
                        <div class="nav-section-label">{{ __('Finance') }}</div>
                        <div class="list-group-item list-group-item-action p-0" style="border:none;">
                            <div class="d-flex align-items-stretch w-100">
                                <a href="{{ route('labor.charges.index') }}" class="flex-grow-1 d-flex align-items-center {{ $isFinanceActive ? 'active text-white' : 'text-dark' }}" style="padding:.75rem 1rem; text-decoration:none; border-radius:.5rem 0 0 .5rem; {{ $isFinanceActive ? 'background-image: linear-gradient(to right, var(--bs-primary-light), var(--bs-primary));' : '' }}">
                                    <i class="bi bi-cash-coin me-2"></i>{{ __('Central Billing') }}
                                </a>
                                <button class="btn btn-link shadow-none {{ $isFinanceActive ? 'text-white' : 'text-dark' }} d-flex align-items-center justify-content-center" type="button" data-bs-toggle="collapse" data-bs-target="#laborFinanceSubMenu" aria-expanded="{{ $isFinanceSubActive ? 'true' : 'false' }}" style="width:40px; padding:0; border-radius:0 .5rem .5rem 0; {{ $isFinanceActive ? 'background-image: linear-gradient(to right, var(--bs-primary-light), var(--bs-primary));' : '' }}" onclick="this.querySelector('i').classList.toggle('bi-chevron-down'); this.querySelector('i').classList.toggle('bi-chevron-up');">
                                    <i class="bi bi-chevron-{{ $isFinanceSubActive ? 'up' : 'down' }}"></i>
                                </button>
                            </div>
                        </div>
                        <div class="collapse {{ $isFinanceSubActive ? 'show' : '' }}" id="laborFinanceSubMenu">
                            <a href="{{ route('labor.bills.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('labor.bills.*') ? 'active' : '' }}" style="padding-left:2.5rem;">
                                <i class="bi bi-receipt-cutoff me-2"></i>{{ __('Bills') }}
                            </a>
                            <a href="{{ route('labor.tax-invoices.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('labor.tax-invoices.*') ? 'active' : '' }}" style="padding-left:2.5rem;">
                                <i class="bi bi-file-earmark-text me-2"></i>{{ __('Tax Invoices') }}
                            </a>
                            <a href="{{ route('labor.wht-certificates.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('labor.wht-certificates.*') ? 'active' : '' }}" style="padding-left:2.5rem;">
                                <i class="bi bi-file-earmark-check me-2"></i>{{ __('WHT Certificates') }}
                            </a>
                        </div>

                        @php
                            $isSettingsActive = request()->routeIs('labor.teams.index');
                            $isSettingsSubActive = request()->routeIs(['labor.teams.*', 'labor.team-members.*', 'labor.users.*']);
                        @endphp
                        <div class="nav-section-label">{{ __('Settings') }}</div>
                        <div class="list-group-item list-group-item-action p-0" style="border:none;">
                            <div class="d-flex align-items-stretch w-100">
                                <a href="{{ route('labor.teams.index') }}" class="flex-grow-1 d-flex align-items-center {{ $isSettingsActive ? 'active text-white' : 'text-dark' }}" style="padding:.75rem 1rem; text-decoration:none; border-radius:.5rem 0 0 .5rem; {{ $isSettingsActive ? 'background-image: linear-gradient(to right, var(--bs-primary-light), var(--bs-primary));' : '' }}">
                                    <i class="bi bi-people me-2"></i>{{ __('Manage Teams') }}
                                </a>
                                <button class="btn btn-link shadow-none {{ $isSettingsActive ? 'text-white' : 'text-dark' }} d-flex align-items-center justify-content-center" type="button" data-bs-toggle="collapse" data-bs-target="#laborSettingsSubMenu" aria-expanded="{{ $isSettingsSubActive ? 'true' : 'false' }}" style="width:40px; padding:0; border-radius:0 .5rem .5rem 0; {{ $isSettingsActive ? 'background-image: linear-gradient(to right, var(--bs-primary-light), var(--bs-primary));' : '' }}" onclick="this.querySelector('i').classList.toggle('bi-chevron-down'); this.querySelector('i').classList.toggle('bi-chevron-up');">
                                    <i class="bi bi-chevron-{{ $isSettingsSubActive ? 'up' : 'down' }}"></i>
                                </button>
                            </div>
                        </div>
                        <div class="collapse {{ $isSettingsSubActive ? 'show' : '' }}" id="laborSettingsSubMenu">
                            <a href="{{ route('labor.team-members.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('labor.team-members.*') ? 'active' : '' }}" style="padding-left:2.5rem;">
                                <i class="bi bi-person-lines-fill me-2"></i>{{ __('Manage Members') }}
                            </a>
                            @role('super-admin')
                            <a href="{{ route('labor.users.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('labor.users.*') ? 'active' : '' }}" style="padding-left:2.5rem;">
                                <i class="bi bi-person-badge me-2"></i>{{ __('Manage Users') }}
                            </a>
                            @endrole
                        </div>
                    @endcan

                    @role('super-admin')
                    <div class="nav-section-label">{{ __('System') }}</div>
                    <a href="{{ route('labor.audit-log.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('labor.audit-log.index') ? 'active' : '' }}">
                        <i class="bi bi-clock-history me-2"></i>{{ __('Audit Log') }}
                    </a>
                    @endrole
                </div>

                <div class="mt-auto pt-3">
                    <hr>
                    @unless(auth()->user()->hasAnyRole(['labor-accounting', 'labor-shareholder', 'labor-team', 'labor-member']))
                    <a href="{{ route('home') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-box-arrow-left me-2"></i>{{ __('Back to Main System') }}
                    </a>
                    @endunless
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="list-group-item list-group-item-action w-100 text-start border-0 bg-transparent">
                            <i class="bi bi-box-arrow-right me-2"></i>{{ __('Logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <main id="main-content">
            <div class="labor-topbar-strip">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                        <i class="bi bi-list"></i>
                    </button>
                    <h5 class="mb-0 fw-bold text-dark">@yield('title', __('Pro Walker Labour'))</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @can('manage-labor-ledger')
                    <a href="{{ route('labor.expenses.create') }}" class="btn btn-success">
                        <i class="bi bi-receipt me-1"></i>{{ __('Record Expense') }}
                    </a>
                    @endcan
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-translate me-1"></i>
                            @switch(app()->getLocale())
                                @case('th') 🇹🇭 ไทย @break
                                @case('zh') 🇨🇳 中文 @break
                                @case('my') 🇲🇲 မြန်မာ @break
                                @default 🇺🇸 English
                            @endswitch
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'th') }}">🇹🇭 ไทย (Thai)</a></li>
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'en') }}">🇺🇸 English</a></li>
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'zh') }}">🇨🇳 中文 (Chinese)</a></li>
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'my') }}">🇲🇲 မြန်မာ (Myanmar)</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Flatpickr — only initialized on inputs opted in via .js-flatpickr, so
         every other plain <input type="date"> in the Labor module keeps its
         native browser behavior unchanged. -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-flatpickr').forEach(function (el) {
                flatpickr(el, { locale: 'th', dateFormat: 'Y-m-d' });
            });
        });
    </script>

    {{-- Camera scan / upload / crop / rotate — the exact same component the
         main app uses (resources/views/components/document-scanner.blade.php),
         self-contained (loads its own JS + CDN deps). This module's layout
         is entirely separate from layouts/app.blade.php, so it was never
         pulled in here before — added for the contract signed-copy
         attachment (LaborContractController::uploadSignedCopy()). --}}
    @include('components.document-scanner')

    {{-- <x-file-input-group>'s "View" button on an already-attached file
         calls window.viewPDF(), defined in the main app's
         layouts/_app_scripts.blade.php (not loaded here — see above). That
         version opens a full preview modal this module doesn't have; this
         is a minimal stand-in that just opens the file in a new tab, which
         is all it needs to do here. --}}
    <script>
        window.viewPDF = function (url) {
            if (!url) return;
            window.open(url, '_blank');
        };
    </script>

    @stack('scripts')
</body>
</html>
