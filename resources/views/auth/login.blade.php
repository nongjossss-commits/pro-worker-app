<x-guest-layout>
    <x-slot name="icon">bi-person-circle</x-slot>
    <x-slot name="heading">{{ __('Login') }}</x-slot>
    <x-slot name="subheading">{{ __('Pro Worker Labour Business OS') }}</x-slot>

    @if (session('status'))
        <div class="auth-alert auth-alert--success"><i class="bi bi-check-circle-fill"></i><div>{{ session('status') }}</div></div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="auth-field">
            <label for="email" class="auth-label">{{ __('Email / Username') }}</label>
            <div class="auth-input-wrap">
                <i class="bi bi-person"></i>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="auth-input @error('email') is-invalid @enderror" placeholder="{{ __('Email / Username') }}">
            </div>
            @error('email')
                <ul class="auth-error"><li>{{ $message }}</li></ul>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password" class="auth-label">{{ __('Password') }}</label>
            <div class="auth-input-wrap">
                <i class="bi bi-lock"></i>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="auth-input @error('password') is-invalid @enderror" placeholder="{{ __('Password') }}">
                <button type="button" class="auth-toggle" data-toggle-password="password" tabindex="-1" aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button>
            </div>
            @error('password')
                <ul class="auth-error"><li>{{ $message }}</li></ul>
            @enderror
        </div>

        <div class="auth-row">
            <label for="remember_me" class="auth-check">
                <input id="remember_me" type="checkbox" name="remember">
                {{ __('Remember me') }}
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="auth-link">{{ __('Forgot your password?') }}</a>
            @endif
        </div>

        <button type="submit" class="auth-btn"><i class="bi bi-box-arrow-in-right"></i> {{ __('Log in') }}</button>
    </form>

    @push('scripts')
    <script>
        // Auto-refresh logic to prevent 419 Page Expired errors
        // 1. Back/forward cache: the CSRF token on a restored page may be stale — reload it.
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
        // 2. Reload after 20 minutes on screen so the token always matches the server session.
        setTimeout(function () {
            window.location.reload();
        }, 20 * 60 * 1000);
    </script>
    @endpush
</x-guest-layout>
