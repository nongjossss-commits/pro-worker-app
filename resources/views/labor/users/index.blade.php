@extends('labor.layout')

@section('title', 'Manage Users - Pro Walker Labour')

@php
    $roleLabels = [
        'labor-accounting' => __('Accounting Staff'),
        'labor-shareholder' => __('Shareholder'),
        'labor-team' => __('Team Lead'),
        'labor-member' => __('Team Member'),
    ];
    $roleBadges = [
        'labor-accounting' => 'bg-warning text-dark',
        'labor-shareholder' => 'bg-info text-dark',
        'labor-team' => 'bg-primary',
        'labor-member' => 'bg-secondary',
    ];
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">{{ __('Manage Users') }}</h4>
    <a href="{{ route('labor.users.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>{{ __('New Login') }}
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Role') }}</th>
                    <th>{{ __('Team') }}</th>
                    <th>{{ __('Matched Member') }}</th>
                    <th class="text-center">{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                @php $roleName = $user->roles->first()->name ?? null; @endphp
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if($roleName)
                        <span class="badge {{ $roleBadges[$roleName] ?? 'bg-secondary' }}">{{ $roleLabels[$roleName] ?? $roleName }}</span>
                        @endif
                    </td>
                    <td>{{ $user->laborTeam->name ?? '-' }}</td>
                    <td>
                        @if($user->laborTeamMember)
                            <span class="badge bg-info text-dark">{{ $user->laborTeamMember->name }}</span>
                        @else
                            <span class="badge bg-light text-muted border">{{ __('Not matched') }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($user->status === 'active')
                            <span class="badge bg-success">{{ __('Active') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('labor.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('labor.users.toggle-status', $user) }}" class="d-inline"
                              onsubmit="return confirm('{{ __('Change this account\'s status?') }}');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-{{ $user->status === 'active' ? 'danger' : 'success' }}">
                                <i class="bi bi-power"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">{{ __('No accounts created yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
