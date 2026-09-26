<x-guest-layout>
    <x-slot name="icon">bi-key-fill</x-slot>
    <x-slot name="heading">{{ __('Set a new password') }}</x-slot>
    <x-slot name="subheading">{{ __('Choose a new password for your account.') }}</x-slot>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="auth-field">
            <label for="email" class="auth-label">{{ __('Email') }}</label>
            <div class="auth-input-wrap">
                <i class="bi bi-envelope"></i>
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="username"
                       class="auth-input @error('email') is-invalid @enderror" {{ $request->email ? '' : 'autofocus' }}>
            </div>
            @error('email')
                <ul class="auth-error"><li>{{ $message }}</li></ul>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password" class="auth-label">{{ __('New Password') }}</label>
            <div class="auth-input-wrap">
                <i class="bi bi-lock"></i>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       class="auth-input @error('password') is-invalid @enderror" {{ $request->email ? 'autofocus' : '' }}>
                <button type="button" class="auth-toggle" data-toggle-password="password" aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button>
            </div>
            @error('password')
                <ul class="auth-error">@foreach ($errors->get('password') as $msg)<li>{{ $msg }}</li>@endforeach</ul>
            @enderror
            <div class="auth-hint">{{ __('At least 8 characters.') }}</div>
        </div>

        <div class="auth-field">
            <label for="password_confirmation" class="auth-label">{{ __('Confirm Password') }}</label>
            <div class="auth-input-wrap">
                <i class="bi bi-lock"></i>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                       class="auth-input @error('password_confirmation') is-invalid @enderror">
                <button type="button" class="auth-toggle" data-toggle-password="password_confirmation" aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button>
            </div>
            @error('password_confirmation')
                <ul class="auth-error"><li>{{ $message }}</li></ul>
            @enderror
        </div>

        <button type="submit" class="auth-btn"><i class="bi bi-check2-circle"></i> {{ __('Reset Password') }}</button>
    </form>

    <div class="auth-footer-links">
        <a href="{{ route('login') }}" class="auth-link"><i class="bi bi-arrow-left"></i> {{ __('Back to login') }}</a>
    </div>
</x-guest-layout>
