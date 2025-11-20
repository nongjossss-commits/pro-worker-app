{{-- We won't use the main layout for the login page --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Login') }} - Pro Worker Labour</title>

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

                    <div>
                        <label for="password" class="sr-only">{{ __('Password') }}</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                               class="appearance-none rounded-b-md relative block w-full px-3 py-2 border border-slate-300 placeholder-slate-500 text-slate-900 focus:outline-none focus:ring-orange-500 focus:border-orange-500 focus:z-10 sm:text-sm"
                               placeholder="{{ __('Password') }}">
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