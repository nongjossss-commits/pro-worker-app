{{--
    Shared layout for every "enter a password / verify" screen: login, menu
    unlock, forgot / reset / confirm password, verify email, register.

    Colours, logo and app name come from Super Admin → Branding
    (App\Services\BrandService), the same source the main app layout uses,
    so changing the theme there re-colours these screens too.

    Optional named slots:  icon (bootstrap-icons class), heading, subheading
--}}
@php
    // The login page must render even if the brand settings can't be read
    // (DB hiccup, fresh install) — fall back to the factory brand then.
    try {
        $brand = \App\Services\BrandService::current();
        $logoUrl = \App\Services\BrandService::logoUrl();
    } catch (\Throwable $e) {
        $brand = \App\Services\BrandService::DEFAULTS;
        $logoUrl = asset('images/logo_new.jpg');
    }
    $primary = $brand['primary_color'];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $primary }}">

    <title>{{ isset($heading) && trim(strip_tags($heading)) !== '' ? trim(strip_tags($heading)) . ' - ' : '' }}{{ $brand['app_name'] }}</title>

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
            --ink: #0f172a;
            --ink-soft: #475569;
            --ink-muted: #94a3b8;
            --line: #e2e8f0;
            --surface: #ffffff;
            --danger: #dc2626;
            --success: #16a34a;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; }
        body.auth-body {
            min-height: 100vh;
            font-family: 'Inter', 'Sarabun', system-ui, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(900px 480px at 0% 0%, rgba(var(--brand-rgb), .16), transparent 60%),
                radial-gradient(700px 420px at 100% 100%, rgba(var(--brand-rgb), .12), transparent 60%),
                #f8fafc;
            -webkit-font-smoothing: antialiased;
        }
        .auth-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            gap: 20px;
        }
        .auth-card {
            width: 100%;
            max-width: 440px;
            background: var(--surface);
            border-radius: 20px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 12px 40px -12px rgba(15, 23, 42, .18);
            overflow: hidden;
        }
        .auth-card__bar {
            height: 6px;
            background: linear-gradient(90deg, var(--brand), var(--brand-accent));
        }
        .auth-card__body { padding: 32px 32px 28px; }
        @media (max-width: 480px) { .auth-card__body { padding: 26px 20px 22px; } }

        .auth-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 26px;
            text-decoration: none;
            color: inherit;
        }
        .auth-brand img {
            width: 44px; height: 44px;
            object-fit: contain;
            border-radius: 12px;
            background: #fff;
            border: 1px solid var(--line);
            padding: 4px;
        }
        .auth-brand__name, .auth-brand__sub { display: block; }
        .auth-brand__name { font-weight: 700; font-size: 15px; line-height: 1.25; }
        .auth-brand__sub { font-size: 12px; color: var(--ink-muted); }

        .auth-hero { text-align: center; margin-bottom: 24px; }
        .auth-hero__icon {
            width: 60px; height: 60px;
            margin: 0 auto 14px;
            display: grid; place-items: center;
            border-radius: 18px;
            font-size: 26px;
            color: var(--brand);
            background: rgba(var(--brand-rgb), .12);
        }
        .auth-hero h1 { margin: 0 0 6px; font-size: 22px; font-weight: 700; letter-spacing: -.01em; }
        .auth-hero p { margin: 0; font-size: 14px; line-height: 1.6; color: var(--ink-soft); }

        .auth-field { margin-bottom: 16px; }
        .auth-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--ink); }
        .auth-input-wrap { position: relative; }
        .auth-input-wrap > .bi {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: var(--ink-muted); font-size: 16px; pointer-events: none;
        }
        .auth-input {
            width: 100%;
            height: 46px;
            padding: 0 44px 0 42px;
            font: inherit; font-size: 15px;
            color: var(--ink);
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .auth-input::placeholder { color: var(--ink-muted); }
        .auth-input:focus { border-color: var(--brand); box-shadow: 0 0 0 4px rgba(var(--brand-rgb), .15); }
        .auth-input.is-invalid { border-color: var(--danger); }
        .auth-input.is-invalid:focus { box-shadow: 0 0 0 4px rgba(220, 38, 38, .12); }
        .auth-input[readonly] { background: #f8fafc; color: var(--ink-soft); }
        .auth-toggle {
            position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
            width: 34px; height: 34px;
            display: grid; place-items: center;
            border: 0; border-radius: 8px;
            background: transparent; color: var(--ink-muted);
            cursor: pointer; font-size: 17px;
        }
        .auth-toggle:hover { color: var(--ink-soft); background: #f1f5f9; }
        .auth-hint { margin-top: 6px; font-size: 12px; color: var(--ink-muted); }
        .auth-error { margin: 6px 0 0; padding: 0; list-style: none; font-size: 13px; color: var(--danger); }
        .auth-error li::before { content: "\F33B"; font-family: 'bootstrap-icons'; margin-right: 6px; }

        .auth-alert {
            display: flex; gap: 10px; align-items: flex-start;
            padding: 12px 14px; margin-bottom: 18px;
            border-radius: 12px; font-size: 14px; line-height: 1.5;
        }
        .auth-alert--success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .auth-alert--info { background: rgba(var(--brand-rgb), .08); color: var(--ink); border: 1px solid rgba(var(--brand-rgb), .25); }
        .auth-alert .bi { font-size: 17px; line-height: 1.3; }

        .auth-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 4px 0 20px; font-size: 14px; }
        .auth-check { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; color: var(--ink-soft); }
        .auth-check input { width: 16px; height: 16px; accent-color: var(--brand); }

        .auth-btn {
            width: 100%; height: 48px;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            font: inherit; font-size: 15px; font-weight: 600;
            color: #fff;
            background: var(--brand);
            border: 0; border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 8px 20px -8px rgba(var(--brand-rgb), .7);
            transition: background .15s, transform .05s;
        }
        .auth-btn:hover { background: var(--brand-dark); }
        .auth-btn:active { transform: translateY(1px); }
        .auth-btn:focus-visible { outline: none; box-shadow: 0 0 0 4px rgba(var(--brand-rgb), .3); }
        .auth-btn[disabled] { opacity: .7; cursor: progress; }
        .auth-btn--ghost {
            background: transparent; color: var(--ink-soft);
            border: 1px solid var(--line); box-shadow: none;
        }
        .auth-btn--ghost:hover { background: #f8fafc; color: var(--ink); }
        .auth-btn + .auth-btn, .auth-stack > * + * { margin-top: 10px; }

        .auth-link { color: var(--brand); font-weight: 600; text-decoration: none; }
        .auth-link:hover { color: var(--brand-dark); text-decoration: underline; }
        .auth-footer-links { margin-top: 20px; text-align: center; font-size: 14px; color: var(--ink-soft); }
        .auth-footer-links a { display: inline-flex; align-items: center; gap: 6px; }
        .auth-copy { font-size: 12px; color: var(--ink-muted); text-align: center; }
    </style>
    @stack('head')
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-card">
            <div class="auth-card__bar"></div>
            <div class="auth-card__body">
                <a href="{{ url('/') }}" class="auth-brand">
                    <img src="{{ $logoUrl }}" alt="{{ $brand['app_name'] }}">
                    <span>
                        <span class="auth-brand__name">{{ $brand['app_name'] }}</span>
                        <span class="auth-brand__sub">{{ __('Pro Worker Labour Business OS') }}</span>
                    </span>
                </a>

                @if(isset($heading) || isset($icon))
                    <div class="auth-hero">
                        <div class="auth-hero__icon"><i class="bi {{ isset($icon) ? trim($icon) : 'bi-shield-lock' }}"></i></div>
                        @isset($heading)<h1>{{ $heading }}</h1>@endisset
                        @isset($subheading)<p>{{ $subheading }}</p>@endisset
                    </div>
                @endif

                {{ $slot }}
            </div>
        </section>
        <div class="auth-copy">&copy; {{ date('Y') }} {{ $brand['app_name'] }}</div>
    </main>

    <script>
        // Show/hide for every password field: <button data-toggle-password="inputId">
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-toggle-password]');
            if (!btn) return;
            const input = document.getElementById(btn.dataset.togglePassword);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            const icon = btn.querySelector('.bi');
            if (icon) icon.className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        });
        // Prevent double submit + give feedback on slow connections
        document.addEventListener('submit', function (e) {
            const btn = e.target.querySelector('button[type="submit"].auth-btn');
            if (btn && !btn.disabled) {
                setTimeout(function () { btn.disabled = true; }, 0);
            }
        });
        // Re-enable buttons when the page is restored from the back/forward cache
        window.addEventListener('pageshow', function () {
            document.querySelectorAll('button[type="submit"].auth-btn').forEach(function (b) { b.disabled = false; });
        });
    </script>
    @stack('scripts')
</body>
</html>
