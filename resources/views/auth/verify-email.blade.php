<x-guest-layout>
    <x-slot name="icon">bi-envelope-check</x-slot>
    <x-slot name="heading">{{ __('Verify your email') }}</x-slot>
    <x-slot name="subheading">{{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}</x-slot>

    @if (session('status') == 'verification-link-sent')
        <div class="auth-alert auth-alert--success">
            <i class="bi bi-check-circle-fill"></i>
            <div>{{ __('A new verification link has been sent to the email address you provided during registration.') }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="auth-btn"><i class="bi bi-send-fill"></i> {{ __('Resend Verification Email') }}</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:10px">
        @csrf
        <button type="submit" class="auth-btn auth-btn--ghost"><i class="bi bi-box-arrow-right"></i> {{ __('Log Out') }}</button>
    </form>
</x-guest-layout>
