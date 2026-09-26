<x-guest-layout>
    <x-slot name="icon">bi-shield-lock-fill</x-slot>
    <x-slot name="heading">{{ __('Protected Menu') }}</x-slot>
    <x-slot name="subheading">{{ __('Enter the password for the ":menu" menu to continue.', ['menu' => $menuLabel ?? $key]) }}</x-slot>

    @if (session('status'))
        <div class="auth-alert auth-alert--success"><i class="bi bi-check-circle-fill"></i><div>{{ session('status') }}</div></div>
    @endif

    <form method="POST" action="{{ route('menu.unlock', ['key' => $key]) }}">
        @csrf

        <div class="auth-field">
            <label for="password" class="auth-label">{{ __('Menu Password') }}</label>
            <div class="auth-input-wrap">
                <i class="bi bi-key"></i>
                <input id="password" type="password" name="password" required autofocus autocomplete="current-password"
                       class="auth-input @error('password') is-invalid @enderror" placeholder="••••••••">
                <button type="button" class="auth-toggle" data-toggle-password="password" aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button>
            </div>
            @error('password')
                <ul class="auth-error"><li>{{ $message }}</li></ul>
            @enderror
            <div class="auth-hint"><i class="bi bi-clock-history"></i> {{ __('Once unlocked, this menu stays open for 30 minutes of activity.') }}</div>
        </div>

        <div class="auth-stack">
            <button type="submit" class="auth-btn"><i class="bi bi-unlock-fill"></i> {{ __('Unlock') }}</button>
            <a href="{{ route('dashboard') }}" class="auth-btn auth-btn--ghost" style="text-decoration:none"><i class="bi bi-arrow-left"></i> {{ __('Back to Dashboard') }}</a>
        </div>
    </form>
</x-guest-layout>
