@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Edit User') }}: {{ $user->name }}
    </h2>
@endsection

@section('title', 'Edit User')

@section('content')
<div class="container-fluid content-section">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
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
                        <label for="name" class="form-label">Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-control" />
                    </div>

                    <div class="mt-4">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-control" />
                    </div>

                    <div class="mt-4">
                        <label for="role_name" class="form-label">Role</label>
                        <select id="role_name" name="role_name" class="form-select">
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" {{ $user->roles->contains($role) ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-6">
                        <h3 class="text-lg font-medium">Delegate Permissions (Feature D)</h3>
                        <div class="mt-4 row">
                            @foreach ($allPermissions as $permission)
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input id="perm-{{ $permission->id }}" type="checkbox" name="permissions[]" value="{{ $permission->name }}" {{ in_array($permission->name, $userPermissions) ? 'checked' : '' }} class="form-check-input" >
                                        <label for="perm-{{ $permission->id }}" class="form-check-label">{{ $permission->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
