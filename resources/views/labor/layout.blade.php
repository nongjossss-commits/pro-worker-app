<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pro Walker Labor')</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Flatpickr — opt-in only (input.js-flatpickr), same as the main app's layouts/app.blade.php -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        body {
            font-family: 'Inter', 'Sarabun', sans-serif;
            background-color: #f8fafc;
        }
        .labor-topbar {
            background: #111827;
        }
        .labor-topbar .nav-link {
            color: rgba(255,255,255,.75);
        }
        .labor-topbar .nav-link.active,
        .labor-topbar .nav-link:hover {
            color: #fff;
        }
        .stat-card {
            border-radius: .75rem;
        }
    </style>
    @stack('styles')

    <!-- Alpine.js (same as the main app — used for conditional form fields) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg labor-topbar navbar-dark mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="{{ route('labor.dashboard') }}">
                <i class="bi bi-briefcase-fill me-2"></i>Pro Walker Labor
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#laborNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="laborNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('labor.dashboard') ? 'active' : '' }}" href="{{ route('labor.dashboard') }}">
                            <i class="bi bi-speedometer2 me-1"></i>{{ __('Dashboard') }}
                        </a>
                    </li>
                    @can('manage-labor-ledger')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs(['labor.teams.*', 'labor.team-members.*', 'labor.users.*']) ? 'active' : '' }}" href="{{ route('labor.teams.index') }}">
                            <i class="bi bi-gear-fill me-1"></i>{{ __('Settings') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs(['labor.charges.*', 'labor.bills.*', 'labor.tax-invoices.*', 'labor.wht-certificates.*']) ? 'active' : '' }}" href="{{ route('labor.charges.index') }}">
                            <i class="bi bi-cash-coin me-1"></i>{{ __('Finance') }}
                        </a>
                    </li>
                    @endcan
                    @unless(auth()->user()->hasRole('labor-team'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('labor.reports.index') ? 'active' : '' }}" href="{{ route('labor.reports.index') }}">
                            <i class="bi bi-bar-chart-line me-1"></i>{{ __('Reports') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('labor.books.*') ? 'active' : '' }}" href="{{ route('labor.books.index') }}">
                            <i class="bi bi-journal-text me-1"></i>{{ __('Company Books') }}
                        </a>
                    </li>
                    @endunless
                    @role('super-admin')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('labor.audit-log.index') ? 'active' : '' }}" href="{{ route('labor.audit-log.index') }}">
                            <i class="bi bi-clock-history me-1"></i>{{ __('Audit Log') }}
                        </a>
                    </li>
                    @endrole
                </ul>
                <ul class="navbar-nav">
                    @can('manage-labor-ledger')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('labor.expenses.*') ? 'active' : '' }}" href="{{ route('labor.expenses.create') }}">
                            <i class="bi bi-receipt me-1"></i>{{ __('Record Expense') }}
                        </a>
                    </li>
                    @endcan
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-translate me-1"></i>
                            @switch(app()->getLocale())
                                @case('th') 🇹🇭 ไทย @break
                                @case('zh') 🇨🇳 中文 @break
                                @default 🇺🇸 English
                            @endswitch
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'th') }}">🇹🇭 ไทย (Thai)</a></li>
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'en') }}">🇺🇸 English</a></li>
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'zh') }}">🇨🇳 中文 (Chinese)</a></li>
                        </ul>
                    </li>
                    @unless(auth()->user()->hasAnyRole(['labor-accounting', 'labor-shareholder', 'labor-team']))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}"><i class="bi bi-box-arrow-left me-1"></i>{{ __('Back to Main System') }}</a>
                    </li>
                    @endunless
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="nav-link btn btn-link">
                                <i class="bi bi-box-arrow-right me-1"></i>{{ __('Logout') }}
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">
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
    @stack('scripts')
</body>
</html>
