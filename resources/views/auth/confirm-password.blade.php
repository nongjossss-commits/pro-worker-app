<x-guest-layout>
    <x-slot name="icon">bi-shield-check</x-slot>
    <x-slot name="heading">{{ __('Confirm your password') }}</x-slot>
    <x-slot name="subheading">{{ __('This is a secure area of the application. Please confirm your password before continuing.') }}</x-slot>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="auth-field">
            <label for="password" class="auth-label">{{ __('Password') }}</label>
            <div class="auth-input-wrap">
                <i class="bi bi-lock"></i>
                <input id="password" type="password" name="password" required autofocus autocomplete="current-password"
                       class="auth-input @error('password') is-invalid @enderror">
                <button type="button" class="auth-toggle" data-toggle-password="password" aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button>
            </div>
            @error('password')
                <ul class="auth-error"><li>{{ $message }}</li></ul>
            @enderror
        </div>

        <button type="submit" class="auth-btn"><i class="bi bi-check2-circle"></i> {{ __('Confirm') }}</button>
    </form>
</x-guest-layout>
