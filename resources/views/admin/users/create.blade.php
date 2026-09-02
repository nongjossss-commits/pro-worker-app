@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Create New User') }}
    </h2>
@endsection

@section('title', 'Create New User')

@section('content')
<div class="ccontainer-fluid content-section">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200" x-data="{ selectedRole: '{{ old('role_name', 'staff') }}', showPassword: false, showConfirmPassword: false }">

                @if ($errors->any())
                    <div class="mb-4">
                        <div class="font-medium text-red-600">{{ __('Whoops! Something went wrong.') }}</div>

                        <ul class="mt-3 list-disc list-inside text-sm text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf

                    <div>
                        <label for="name" class="block font-medium text-sm text-gray-700">{{ __('Name') }}</label>
                        <input id="name" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" type="text" name="name" value="{{ old('name') }}" required autofocus />
                    </div>

                    <div class="mt-4">
                        <label for="email" class="block font-medium text-sm text-gray-700">{{ __('Email') }}</label>
                        <input id="email" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" type="email" name="email" value="{{ old('email') }}" required />
                    </div>

                    <div class="mt-4">
                        <label for="password" class="block font-medium text-sm text-gray-700">{{ __('Password') }}</label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" id="password" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="password" required autocomplete="new-password" />
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5">
                                <span x-text="showPassword ? 'Hide' : 'Show'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="password_confirmation" class="block font-medium text-sm text-gray-700">{{ __('Confirm Password') }}</label>
                         <div class="relative">
                            <input :type="showConfirmPassword ? 'text' : 'password'" id="password_confirmation" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="password_confirmation" required />
                             <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5">
                                <span x-text="showConfirmPassword ? 'Hide' : 'Show'"></span>
                            </button>
                        </div>
                    </div>


                    <div class="mt-4">
                        <label for="role_name" class="block font-medium text-sm text-gray-700">{{ __('Role') }}</label>
                        <select id="role_name" name="role_name" x-model="selectedRole" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role_name') == $role->name ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-4" x-show="selectedRole === 'employer'" style="display: none;" x-transition>
                        <label for="employer_id" class="block font-medium text-sm text-gray-700">{{ __('Link to Employer (Required)') }}</label>
                        <select id="employer_id" name="employer_id" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">-- {{ __('Select Employer') }} --</option>
                            @foreach ($employers as $employer)
                                <option value="{{ $employer->id }}" {{ old('employer_id') == $employer->id ? 'selected' : '' }}>{{ $employer->employerNameTh }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-4" x-show="selectedRole === 'labor-team'" style="display: none;" x-transition>
                        <label for="labor_team_id" class="block font-medium text-sm text-gray-700">{{ __('Pro Walker Labour — Team (Required)') }}</label>
                        <select id="labor_team_id" name="labor_team_id" :disabled="selectedRole !== 'labor-team'" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">-- {{ __('Select Team') }} --</option>
                            @foreach ($laborTeams as $team)
                                <option value="{{ $team->id }}" {{ old('labor_team_id') == $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if(auth()->user()->hasRole('super-admin'))
                    <div class="mt-4" x-show="selectedRole === 'admin'" style="display: none;" x-transition>
                        <label for="labor_access_level" class="block font-medium text-sm text-gray-700">{{ __('Pro Walker Labour access') }}</label>
                        <select id="labor_access_level" name="labor_access_level" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            @php($currentLevel = old('labor_access_level', 'none'))
                            <option value="none" {{ $currentLevel === 'none' ? 'selected' : '' }}>{{ __('No access') }} (ไม่ให้เข้า)</option>
                            <option value="view" {{ $currentLevel === 'view' ? 'selected' : '' }}>{{ __('View only') }} (ดูอย่างเดียว)</option>
                            <option value="edit" {{ $currentLevel === 'edit' ? 'selected' : '' }}>{{ __('View + Edit') }} (ดูและแก้ไขได้)</option>
                        </select>
                    </div>

                    <div class="mt-4" x-show="selectedRole === 'admin'" style="display: none;" x-transition>
                        <label for="labor_team_id_admin" class="block font-medium text-sm text-gray-700">{{ __('Pro Walker Labour — Team') }} ({{ __('optional') }})</label>
                        <select id="labor_team_id_admin" name="labor_team_id" :disabled="selectedRole !== 'admin'" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">-- {{ __('Select Team') }} --</option>
                            @foreach ($laborTeams as $team)
                                <option value="{{ $team->id }}" {{ old('labor_team_id') == $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">{{ __('Required before this user can download Pro Worker company documents or issue a contract, even with Labor access granted above.') }}</p>
                    </div>
                    <div class="mt-4">
                        <label for="staff_code" class="block font-medium text-sm text-gray-700">{{ __('Staff Code') }} ({{ __('optional') }})</label>
                        <input type="text" id="staff_code" name="staff_code" value="{{ old('staff_code') }}" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    @endif

                    <div class="flex items-center justify-end mt-4">
                        <a href="{{ route('admin.users.index') }}" class="underline text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>

                        <button type="submit" class="ml-4 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                            {{ __('Create User') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
