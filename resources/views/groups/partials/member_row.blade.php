{{-- resources/views/groups/partials/member_row.blade.php --}}
<tr>
    <td>
        <img src="{{ $member->photo_url }}" class="rounded-circle" width="32" height="32" alt="avatar">
    </td>
    <td>
        <div>{{ $member->employeeNameTh }}</div>
        <small class="text-muted">{{ $member->employeeNameEn }}</small>
    </td>
    <td>{{ $member->employeePassport }}</td>
    <td class="text-end">
        <form action="{{ route('groups.teams.members.remove', ['team' => $team->id, 'employee' => $member->id]) }}" method="POST" class="d-inline delete-member-form">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-x-lg"></i>
            </button>
        </form>
    </td>
</tr>
