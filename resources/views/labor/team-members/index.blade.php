@extends('labor.layout')

@section('title', 'Team Members - Pro Walker Labor')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">{{ __('Team Members') }}</h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#registerMemberModal">
        <i class="bi bi-person-plus me-1"></i>{{ __('Register Member') }}
    </button>
</div>
<p class="text-muted small">{{ __('Every ID is paired with its team right here, at registration — this is the only place a new member can be created, and the team pairing cannot be changed afterwards.') }}</p>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('ID') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Team') }}</th>
                    <th class="text-center">{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Jobs Filed') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                <tr>
                    <td class="text-muted">#{{ $member->id }}</td>
                    <td>{{ $member->name }}</td>
                    <td>{{ $member->team->name ?? '-' }}</td>
                    <td class="text-center">
                        @if($member->is_active)
                            <span class="badge bg-success">{{ __('Active') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                        @endif
                    </td>
                    <td class="text-end">{{ $member->ledger_entries_count }}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#editMemberModal{{ $member->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('labor.team-members.destroy', $member) }}" class="d-inline"
                              onsubmit="return confirm('{{ __('Remove this member? Their recorded entries stay, just unlinked from this name.') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>

                <div class="modal fade" id="editMemberModal{{ $member->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('labor.team-members.update', $member) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Edit Member') }} #{{ $member->id }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Name') }}</label>
                                        <input type="text" name="name" class="form-control" value="{{ $member->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Team') }}</label>
                                        <input type="text" class="form-control" value="{{ $member->team->name ?? '-' }}" disabled>
                                        <div class="form-text">{{ __('Team is locked at registration and cannot be changed.') }}</div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                               id="memberActive{{ $member->id }}" {{ $member->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label" for="memberActive{{ $member->id }}">{{ __('Active') }}</label>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">{{ __('No members registered yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($members->hasPages())
    <div class="card-footer bg-white">
        {{ $members->links() }}
    </div>
    @endif
</div>

<div class="modal fade" id="registerMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('labor.team-members.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Register Member') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Name') }}</label>
                        <input type="text" name="name" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Team') }}</label>
                        <select name="labor_team_id" class="form-select" required>
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('Cannot be changed after registering — pick carefully.') }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Register') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
