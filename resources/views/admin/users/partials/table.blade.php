<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th scope="col">{{ __('Name') }}</th>
                <th scope="col">{{ __('Email') }}</th>
                <th scope="col">{{ __('Role') }}</th>
                <th scope="col">{{ __('Status') }}</th>
                <th scope="col" class="text-end">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <span class="badge bg-secondary">{{ ucfirst(__($user->roles->first()->name ?? 'N/A')) }}</span>
                </td>
                <td>
                    @if($user->status === 'active')
                        <span class="badge bg-success">{{ __('Active') }}</span>
                    @else
                        <span class="badge bg-danger">{{ __('Inactive') }}</span>
                    @endif
                </td>
                <td class="text-end">
                    <div class="d-flex flex-column flex-md-row justify-content-end gap-2">
                        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="d-grid d-md-inline">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-sm {{ $user->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                {{ $user->status === 'active' ? __('Deactivate') : __('Activate') }}
                            </button>
                        </form>
                        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-warning">{{ __('Edit') }}</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted py-4">
                    {{ __('No users found.') }}
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
