<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Company Records')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --bs-primary: #F97316;
            --bs-primary-rgb: 249, 115, 22;
            --bs-primary-dark: #EA580C;
            --bs-primary-light: #FB923C;
            --bs-body-font-family: 'Inter', 'Sarabun', sans-serif;
            --bs-body-bg: #f8fafc;
            --bs-border-color: #e2e8f0;
        }
        body {
            font-size: 1rem;
            line-height: 1.6;
            background-color: var(--bs-body-bg);
        }
        .btn-primary {
            --bs-btn-bg: var(--bs-primary);
            --bs-btn-border-color: var(--bs-primary);
            --bs-btn-hover-bg: var(--bs-primary-dark);
            --bs-btn-hover-border-color: var(--bs-primary-dark);
            --bs-btn-active-bg: var(--bs-primary-dark);
            --bs-btn-active-border-color: var(--bs-primary-dark);
            --bs-btn-focus-shadow-rgb: var(--bs-primary-rgb);
        }
        .content-section {
            background-color: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid var(--bs-border-color);
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.07), 0 1px 2px -1px rgb(0 0 0 / 0.07);
        }
        .main-layout {
            display: flex;
            min-height: 100vh;
        }
        #sidebar {
            width: 260px;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
            padding: 1.5rem;
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
        .table thead {
            --bs-table-bg: #f8fafc;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        .table tbody td {
            font-size: 0.9rem;
        }
        h2 {
            font-weight: 700;
            color: #1e293b;
            font-size: 1.75rem;
        }
    </style>
</head>
<body>

    <div class="main-layout">
        <aside id="sidebar">
            <a class="navbar-brand fs-4" href="#"><i class="bi bi-building-fill-gear"></i> Company Records</a>
            <div class="list-group" id="main-nav">
                <a href="#" class="list-group-item list-group-item-action"><i class="bi bi-pie-chart-fill me-2"></i>ภาพรวม</a>
                <a href="#" class="list-group-item list-group-item-action"><i class="bi bi-bell-fill me-2"></i>แจ้งเตือน</a>
                <hr>
                <a href="{{ route('employers.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('employers.*') ? 'active' : '' }}"><i class="bi bi-person-vcard-fill me-2"></i>ข้อมูลนายจ้าง</a>
                <a href="#" class="list-group-item list-group-item-action"><i class="bi bi-box-arrow-in-down-left me-2"></i>ข้อมูลบริษัทนำเข้า</a>
                <a href="#" class="list-group-item list-group-item-action"><i class="bi bi-person-square me-2"></i>ข้อมูลเอเจนซี่</a>
                <a href="#" class="list-group-item list-group-item-action"><i class="bi bi-people-fill me-2"></i>ข้อมูลพนักงาน</a>
            </div>
            <div class="mt-auto">
                <hr>
                <div class="d-flex align-items-center">
                    <i class="bi bi-person-circle fs-3 me-2"></i>
                    <div>
                        <strong>{{ Auth::user()->name }}</strong>
                        <br>
                        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-danger small">Logout</a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <main id="main-content">
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
