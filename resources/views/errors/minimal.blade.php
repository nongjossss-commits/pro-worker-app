{{--
    Overrides Laravel's default errors::minimal layout, so every stock error
    page (401, 402, 403, 404, 429, 500, 503 — framework views that only set
    title / code / message) renders in the app's own design instead of the
    plain grey "500 | SERVER ERROR" page. Messages passed to abort(403, '...')
    still show as-is.

    Must render even when the app is broken (500) or down (503): brand
    settings are read inside try/catch, and nothing here needs the session,
    CSRF token or an authenticated user.
--}}
@php
    try {
        $brand = \App\Services\BrandService::current();
        $logoUrl = \App\Services\BrandService::logoUrl();
    } catch (\Throwable $e) {
        $brand = \App\Services\BrandService::DEFAULTS;
        $logoUrl = asset('images/logo_new.jpg');
    }
    $primary = $brand['primary_color'];
    $code = trim($__env->yieldContent('code'));

    $icons = [
        '401' => 'bi-person-lock', '402' => 'bi-credit-card', '403' => 'bi-shield-lock',
        '404' => 'bi-signpost-split', '410' => 'bi-calendar-x', '419' => 'bi-hourglass-bottom', '429' => 'bi-speedometer2',
        '500' => 'bi-exclamation-octagon', '503' => 'bi-tools',
    ];
    $hints = [
        '401' => __('Please log in to continue.'),
        '403' => __('You do not have permission to open this page. If you think this is a mistake, please contact your administrator.'),
        '404' => __('The page you are looking for could not be found. The link may be wrong or the item may have been removed.'),
        '419' => __('Your session has expired. Please refresh the page and try again.'),
        '429' => __('Too many requests in a short time. Please wait a moment and try again.'),
        '500' => __('Something went wrong on our side. Please try again. If the problem continues, tell your administrator the time it happened.'),
        '503' => __('The system is being updated. Please come back in a few minutes.'),
    ];
    $danger = in_array($code, ['500', '503'], true);
    // A page may override the hint / buttons / add a script (see errors/419).
    $hint = trim($__env->yieldContent('hint')) ?: ($hints[$code] ?? '');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials._theme_init')
    <meta name="theme-color" content="{{ $primary }}">
    <title>@yield('title') - {{ $brand['app_name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --brand: {{ $primary }};
            --brand-rgb: {{ \App\Services\BrandService::hexToRgb($primary) }};
            --brand-dark: {{ \App\Services\BrandService::darken($primary, 0.12) }};
            --brand-accent: {{ $brand['accent_color'] }};
            --tone: {{ $danger ? '220, 38, 38' : 'var(--brand-rgb)' }};
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh;
            font-family: 'Inter', 'Sarabun', system-ui, sans-serif;
            color: #0f172a;
            background:
                radial-gradient(900px 480px at 0% 0%, rgba(var(--brand-rgb), .14), transparent 60%),
                radial-gradient(700px 420px at 100% 100%, rgba(var(--brand-rgb), .10), transparent 60%),
                #f8fafc;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 32px 16px; gap: 20px;
            -webkit-font-smoothing: antialiased;
        }
        .card {
            width: 100%; max-width: 480px;
            background: #fff; border-radius: 20px; overflow: hidden;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 12px 40px -12px rgba(15, 23, 42, .18);
            text-align: center;
        }
        .bar { height: 6px; background: linear-gradient(90deg, var(--brand), var(--brand-accent)); }
        .body { padding: 32px 32px 28px; }
        @media (max-width: 480px) { .body { padding: 26px 20px 22px; } }
        .brand { display: inline-flex; align-items: center; gap: 10px; color: inherit; text-decoration: none; margin-bottom: 24px; }
        .brand img { width: 36px; height: 36px; object-fit: contain; border-radius: 10px; border: 1px solid #e2e8f0; padding: 3px; background: #fff; }
        .brand span { font-weight: 700; font-size: 14px; }
        .icon {
            width: 68px; height: 68px; margin: 0 auto 14px;
            display: grid; place-items: center; border-radius: 20px; font-size: 30px;
            color: rgb(var(--tone)); background: rgba(var(--tone), .1);
        }
        .code { font-size: 13px; font-weight: 700; letter-spacing: .12em; color: rgb(var(--tone)); margin-bottom: 6px; }
        h1 { margin: 0 0 10px; font-size: 22px; font-weight: 700; }
        p { margin: 0 auto; max-width: 380px; font-size: 14px; line-height: 1.65; color: #475569; }
        .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-top: 24px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            height: 44px; padding: 0 18px; border-radius: 12px;
            font: inherit; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer;
        }
        .btn-primary { color: #fff; background: var(--brand); border: 0; box-shadow: 0 8px 20px -8px rgba(var(--brand-rgb), .7); }
        .btn-primary:hover { background: var(--brand-dark); }
        .btn-ghost { color: #475569; background: #fff; border: 1px solid #e2e8f0; }
        .btn-ghost:hover { background: #f8fafc; color: #0f172a; }
        .meta { margin-top: 18px; font-size: 12px; color: #94a3b8; }
        .copy { font-size: 12px; color: #94a3b8; }

        /* Dark display mode (the choice made in the app's top bar, or the device setting) */
        html[data-bs-theme="dark"] { color-scheme: dark; }
        html[data-bs-theme="dark"] body {
            color: #f1f5f9;
            background:
                radial-gradient(900px 480px at 0% 0%, rgba(var(--brand-rgb), .20), transparent 60%),
                radial-gradient(700px 420px at 100% 100%, rgba(var(--brand-rgb), .14), transparent 60%),
                #0f172a;
        }
        html[data-bs-theme="dark"] .card { background: #1e293b; box-shadow: 0 16px 48px -12px rgba(0, 0, 0, .6); }
        html[data-bs-theme="dark"] p { color: #cbd5e1; }
        html[data-bs-theme="dark"] .brand img { border-color: #334155; }
        html[data-bs-theme="dark"] .btn-ghost { color: #cbd5e1; background: #1e293b; border-color: #334155; }
        html[data-bs-theme="dark"] .btn-ghost:hover { background: #273449; color: #f8fafc; }
        html[data-bs-theme="dark"] .meta, html[data-bs-theme="dark"] .copy { color: #64748b; }
    </style>
</head>
<body>
    <main class="card">
        <div class="bar"></div>
        <div class="body">
            <a href="{{ url('/') }}" class="brand">
                <img src="{{ $logoUrl }}" alt="{{ $brand['app_name'] }}">
                <span>{{ $brand['app_name'] }}</span>
            </a>

            <div class="icon"><i class="bi {{ $icons[$code] ?? 'bi-exclamation-circle' }}"></i></div>
            <div class="code">{{ __('ERROR') }} {{ $code }}</div>
            <h1>@yield('message')</h1>
            @if($hint !== '' && trim($__env->yieldContent('message')) !== $hint)
                <p>{{ $hint }}</p>
            @endif

            <div class="actions">
                @hasSection('actions')
                    @yield('actions')
                @else
                <button type="button" class="btn btn-ghost" onclick="history.length > 1 ? history.back() : (location.href='{{ url('/') }}')">
                    <i class="bi bi-arrow-left"></i> {{ __('Go back') }}
                </button>
                @if($code === '500' || $code === '503' || $code === '429')
                    <button type="button" class="btn btn-primary" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i> {{ __('Try again') }}
                    </button>
                @else
                    <a href="{{ url('/') }}" class="btn btn-primary"><i class="bi bi-house-door"></i> {{ __('Go to home page') }}</a>
                @endif
                @endif
            </div>

            <div class="meta">{{ now()->format('d/m/Y H:i:s') }}</div>
        </div>
    </main>
    <div class="copy">&copy; {{ date('Y') }} {{ $brand['app_name'] }}</div>
    @yield('scripts')
</body>
</html>
