{{-- We won't use the main layout for the login page --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Login') }} - {{ __('Pro Worker Labour Business OS') }}</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', 'Sarabun', sans-serif;
            background-color: #f8fafc; /* Tailwind's slate-50 */
        }
    </style>

    <script>
        // Auto-refresh logic to prevent 419 Page Expired errors
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Handle Back/Forward Cache (bfcache)
            // If the user navigates back to this page, the CSRF token might be stale.
            // We force a reload if the page is persisted in the bfcache.
            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });

            // 2. Auto-refresh after inactivity
            // If the tab is left open longer than the session lifetime (or a safe margin),
            // reload the page to get a fresh CSRF token before the user tries to submit.
            // Setting this to 20 minutes (1200000 ms) as a safe check.
            const REFRESH_TIME = 20 * 60 * 1000;

            let refreshTimer = setTimeout(function() {
                window.location.reload();
            }, REFRESH_TIME);

            // Optional: Reset timer on user interaction if we wanted to keep the session alive via AJAX,
            // but for a login page, we just want to ensure the token on screen matches the server.
            // Since the server token expires independently of client activity on a static login page,
            // a fixed reload is safer than an activity-based one for the 'login form' specifically.

            // 3. Show/hide password toggle
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeShow = document.getElementById('eyeIconShow');
            const eyeHide = document.getElementById('eyeIconHide');
            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', function () {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    eyeShow.classList.toggle('hidden', isHidden);
                    eyeHide.classList.toggle('hidden', !isHidden);
                });
            }
        });
    </script>
</head>
<body class="antialiased text-slate-800">

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-slate-900">
                    {{ __('Login') }}
                </h2>
                <p class="mt-2 text-center text-sm text-slate-600">
                    {{ __('Pro Worker Labour Business OS') }}
                </p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-6">
                @csrf
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">{{ __('Email / Username') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                               class="appearance-none rounded-t-md relative block w-full px-3 py-2 border border-slate-300 placeholder-slate-500 text-slate-900 focus:outline-none focus:ring-orange-500 focus:border-orange-500 focus:z-10 sm:text-sm"
                               placeholder="{{ __('Email / Username') }}">
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="relative">
                        <label for="password" class="sr-only">{{ __('Password') }}</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                               class="appearance-none rounded-b-md relative block w-full px-3 py-2 pr-10 border border-slate-300 placeholder-slate-500 text-slate-900 focus:outline-none focus:ring-orange-500 focus:border-orange-500 focus:z-10 sm:text-sm"
                               placeholder="{{ __('Password') }}">
                        <button type="button" id="togglePassword" tabindex="-1"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
                                aria-label="{{ __('Show password') }}">
                            <svg id="eyeIconShow" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 3.5c-4.14 0-7.68 2.6-9.09 6.25a.75.75 0 0 0 0 .5C2.32 13.9 5.86 16.5 10 16.5s7.68-2.6 9.09-6.25a.75.75 0 0 0 0-.5C17.68 6.1 14.14 3.5 10 3.5Zm0 10.75a4.25 4.25 0 1 1 0-8.5 4.25 4.25 0 0 1 0 8.5Z" />
                                <path d="M10 8.25a1.75 1.75 0 1 0 0 3.5 1.75 1.75 0 0 0 0-3.5Z" />
                            </svg>
                            <svg id="eyeIconHide" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 0 0-1.06 1.06l14.5 14.5a.75.75 0 1 0 1.06-1.06l-1.86-1.86c1.66-1.16 2.98-2.73 3.77-4.6a.75.75 0 0 0 0-.52C17.68 6.1 14.14 3.5 10 3.5c-1.5 0-2.9.35-4.14.98L3.28 2.22Zm4.6 4.6 1.36 1.36a2.25 2.25 0 0 1 3.02 3.02l1.36 1.36a4.25 4.25 0 0 0-5.74-5.74Z" clip-rule="evenodd" />
                                <path d="M2.36 6.24a.75.75 0 0 1 1.02.28c.31.53.67 1.02 1.08 1.47l1.1 1.1A4.25 4.25 0 0 0 10 14.25c.4 0 .78-.05 1.15-.15l1.14 1.14A9.94 9.94 0 0 1 10 16.5c-4.14 0-7.68-2.6-9.09-6.25a.75.75 0 0 1 0-.5c.35-.92.83-1.77 1.41-2.51a.75.75 0 0 1 .04-1Z" />
                            </svg>
                        </button>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember_me" type="checkbox" class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded" name="remember">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-900">
                            {{ __('Remember me') }}
                        </label>
                    </div>

                    <div class="text-sm">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="font-medium text-orange-600 hover:text-orange-500">
                                {{ __('Forgot your password?') }}
                            </a>
                        @endif
                    </div>
                </div>

                <div>
                    <button type="submit"
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        {{ __('Log in') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>