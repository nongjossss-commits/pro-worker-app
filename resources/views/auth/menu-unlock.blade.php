<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This menu is protected. Please enter the password to continue.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('menu.unlock', ['key' => $key]) }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Menu Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autofocus autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-3">
                {{ __('Unlock') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
