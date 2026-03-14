@can('restore-' . Str::plural(strtolower($modelName)))
    <a href="{{ route('admin.trash.restore', ['model' => $modelName, 'id' => $item->id]) }}"
       class="btn btn-sm btn-outline-success btn-restore"
       data-action="{{ route('admin.trash.restore', ['model' => $modelName, 'id' => $item->id]) }}">
        <i class="bi bi-arrow-counterclockwise"></i>{{ __('Restore') }}</a>
@endcan
@can('force-delete-' . Str::plural(strtolower($modelName)))
    <a href="{{ route('admin.trash.forceDelete', ['model' => $modelName, 'id' => $item->id]) }}"
       class="btn btn-sm btn-danger btn-force-delete"
       data-action="{{ route('admin.trash.forceDelete', ['model' => $modelName, 'id' => $item->id]) }}">
        <i class="bi bi-trash3-fill"></i>{{ __('Force Delete') }}</a>
@endcan
