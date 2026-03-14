@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Edit User') }}: {{ $user->name }}
    </h2>
@endsection

@section('title', 'Edit User')

@section('content')
<div class="ccontainer-fluid content-section">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200" x-data="{ selectedRole: '{{ old('role_name', $user->roles->first()->name ?? 'staff') }}', showPassword: false, showConfirmPassword: false }">

                @if ($errors->{{ __('any())') }}<div class="mb-4">
                        <div class="font-medium text-red-600">{{ __('Whoops! Something went wrong.') }}</div>

                        <ul class="mt-3 list-disc list-inside text-sm text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="block font-medium text-sm text-gray-700">{{ __('Name') }}</label>
                        <input id="name" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus />
                    </div>

                    <div class="mt-4">
                        <label for="email" class="block font-medium text-sm text-gray-700">{{ __('Email') }}</label>
                        <input id="email" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" type="email" name="email" value="{{ old('email', $user->email) }}" required />
                    </div>

                    <div class="mt-4">
                        <label for="password" class="block font-medium text-sm text-gray-700">{{ __('New Password (Leave blank to keep current)') }}</label>
                        <div class="relative">
                             <input :type="showPassword ? 'text' : 'password'" id="password" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="password" autocomplete="new-password" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="password_confirmation" class="block font-medium text-sm text-gray-700">{{ __('Confirm New Password') }}</label>
                        <div class="relative">
                            <input :type="showConfirmPassword ? 'text' : 'password'" id="password_confirmation" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="password_confirmation" />
                            <button type="button" @click="showPassword = !showPassword; showConfirmPassword = !showConfirmPassword;" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5">
                                <span x-text="showPassword ? 'Hide' : 'Show'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="role_name" class="block font-medium text-sm text-gray-700">{{ __('Role') }}</label>
                        <select id="role_name" name="role_name" x-model="selectedRole" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" {{ $user->roles->contains($role) ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-4" x-show="selectedRole === 'employer'" style="display: none;" x-transition>
                        <label for="employer_id" class="block font-medium text-sm text-gray-700">{{ __('Link to Employer (Required)') }}</label>
                        <select id="employer_id" name="employer_id" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">-- {{ __('Select Employer') }} --</option>
                            @foreach ($employers as $employer)
                                <option value="{{ $employer->id }}" {{ old('employer_id', $user->employer->id ?? null) == $employer->id ? 'selected' : '' }}>
                                    {{ $employer->employerNameTh }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-6">
                        <h3 class="text-lg font-medium">{{ __('Delegate Permissions') }}</h3>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach ($allPermissions as $permission)
                                <div>
                                    <label for="perm-{{ $permission->id }}" class="inline-flex items-center">
                                        <input id="perm-{{ $permission->id }}" type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                            {{ in_array($permission->name, $userPermissions) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            >
                                        <span class="ml-2 text-sm text-gray-600">
                                            {{ \App\Helpers\PermissionHelper::getLabel($permission->name) }}
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>


                    <div class="flex items-center justify-end mt-4">
                         <a href="{{ route('admin.users.index') }}" class="underline text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                        <button type="submit" class="ml-4 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                            {{ __('Update User') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
