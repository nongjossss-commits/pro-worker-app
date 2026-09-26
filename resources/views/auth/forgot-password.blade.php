<x-guest-layout>
    <x-slot name="icon">bi-envelope-paper</x-slot>
    <x-slot name="heading">{{ __('Forgot your password?') }}</x-slot>
    <x-slot name="subheading">{{ __('Enter the email address of your account and we will send you a link to set a new password.') }}</x-slot>

    @if (session('status'))
        <div class="auth-alert auth-alert--success"><i class="bi bi-check-circle-fill"></i><div>{{ session('status') }}</div></div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="auth-field">
            <label for="email" class="auth-label">{{ __('Email') }}</label>
            <div class="auth-input-wrap">
                <i class="bi bi-envelope"></i>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                       class="auth-input @error('email') is-invalid @enderror" placeholder="name@example.com">
            </div>
            @error('email')
                <ul class="auth-error"><li>{{ $message }}</li></ul>
            @enderror
            <div class="auth-hint">{{ __('The link is valid for 60 minutes. If you request again, only the newest email works.') }}</div>
        </div>

        <button type="submit" class="auth-btn"><i class="bi bi-send-fill"></i> {{ __('Email Password Reset Link') }}</button>
    </form>

    <div class="auth-footer-links">
        <a href="{{ route('login') }}" class="auth-link"><i class="bi bi-arrow-left"></i> {{ __('Back to login') }}</a>
    </div>
</x-guest-layout>
