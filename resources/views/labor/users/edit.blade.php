@extends('labor.layout')

@section('title', 'Edit User - Pro Walker Labour')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <h4 class="fw-bold mb-3">{{ __('Edit') }}: {{ $user->name }}</h4>

        <div class="card shadow-sm border-0">
            <div class="card-body" x-data="{ selectedRole: '{{ old('role_name', $user->roles->first()->name ?? 'labor-team') }}', showPassword: false }">
                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('labor.users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">{{ __('Name') }}</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Email') }}</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('New Password (leave blank to keep current)') }}</label>
                        <div class="input-group">
                            <input :type="showPassword ? 'text' : 'password'" name="password" class="form-control" autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary" @click="showPassword = !showPassword">
                                <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Confirm New Password') }}</label>
                        <input :type="showPassword ? 'text' : 'password'" name="password_confirmation" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Role') }}</label>
                        <select name="role_name" x-model="selectedRole" class="form-select">
                            <option value="labor-team">{{ __('Team Lead') }} ({{ __('sees only their own team') }})</option>
                            <option value="labor-accounting">{{ __('Accounting Staff') }} ({{ __('view + edit every team') }})</option>
                            <option value="labor-shareholder">{{ __('Shareholder') }} ({{ __('view every team, read-only; can optionally also lead their own team') }})</option>
                        </select>
                    </div>

                    <div class="mb-3" x-show="selectedRole === 'labor-team' || selectedRole === 'labor-shareholder'" style="display: none;" x-transition>
                        <label class="form-label">
                            <span x-show="selectedRole === 'labor-team'">{{ __('Team (Required)') }}</span>
                            <span x-show="selectedRole === 'labor-shareholder'">{{ __('Own Team (Optional)') }}</span>
                        </label>
                        <select name="labor_team_id" class="form-select">
                            <option value="">-- {{ __('Select Team') }} --</option>
                            @foreach ($teams as $team)
                                <option value="{{ $team->id }}" {{ old('labor_team_id', $user->labor_team_id) == $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('labor.users.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
