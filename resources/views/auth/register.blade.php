<x-guest-layout>
    <x-slot name="icon">bi-person-plus-fill</x-slot>
    <x-slot name="heading">{{ __('Register') }}</x-slot>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="auth-field">
            <label for="name" class="auth-label">{{ __('Name') }}</label>
            <div class="auth-input-wrap">
                <i class="bi bi-person"></i>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                       class="auth-input @error('name') is-invalid @enderror">
            </div>
            @error('name')
                <ul class="auth-error"><li>{{ $message }}</li></ul>
            @enderror
        </div>

        <div class="auth-field">
            <label for="email" class="auth-label">{{ __('Email') }}</label>
            <div class="auth-input-wrap">
                <i class="bi bi-envelope"></i>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                       class="auth-input @error('email') is-invalid @enderror">
            </div>
            @error('email')
                <ul class="auth-error"><li>{{ $message }}</li></ul>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password" class="auth-label">{{ __('Password') }}</label>
            <div class="auth-input-wrap">
                <i class="bi bi-lock"></i>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       class="auth-input @error('password') is-invalid @enderror">
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

        <button type="submit" class="auth-btn">{{ __('Register') }}</button>
    </form>

    <div class="auth-footer-links">
        <a href="{{ route('login') }}" class="auth-link">{{ __('Already registered?') }}</a>
    </div>
</x-guest-layout>
