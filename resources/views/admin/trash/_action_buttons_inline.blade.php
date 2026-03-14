{{--
This partial is used within the Central Trash UI to provide inline action buttons.
It uses forms to ensure correct HTTP methods (POST for restore, DELETE for force-delete)
and is designed to be intercepted by SweetAlert for confirmation.
--}}

@php
    // To prevent errors in the template rendering, default to null if item is not passed.
    $itemId = $item->id ?? null;
    $modelPlural = Str::plural(strtolower($modelName));
@endphp

@if(isset($is_template) && $is_template)
    {{-- This block is just to ensure the view can be composed without data, do not render anything --}}
@elseif($itemId)
<div class="d-inline-flex gap-2">
    {{-- Restore Form --}}
    @can('restore-' . $modelPlural)
        <form action="{{ route('admin.trash.restore', ['model' => $modelName, 'id' => $itemId]) }}" method="POST" class="d-inline restore-form">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-success" title="Restore this item">
                <i class="bi bi-arrow-counterclockwise"></i>{{ __('Restore') }}</button>
        </form>
    @endcan

    {{-- Force Delete Form --}}
    @can('force-delete-' . $modelPlural)
        <form action="{{ route('admin.trash.forceDelete', ['model' => $modelName, 'id' => $itemId]) }}" method="POST" class="d-inline delete-form">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger" title="Permanently delete this item">
                <i class="bi bi-trash3-fill"></i>{{ __('Delete') }}</button>
        </form>
    @endcan
</div>
@endif
